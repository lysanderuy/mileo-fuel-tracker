<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/includes/api_helpers.php';

api_require_auth();
header('Content-Type: application/json');
api_require_method('GET');

$user_id = (int)$_SESSION['user_id'];
$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Log ID is required.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT fl.id, fl.vehicle_id, fl.log_date, fl.odometer, fl.trip_distance,
           fl.liters_filled, fl.fuel_price, fl.is_full_tank, fl.notes,
           fl.cost_per_liter,
           fl.cost_per_km,
           v.name AS vehicle_name
    FROM fuel_logs fl
    JOIN vehicles v ON v.id = fl.vehicle_id
    WHERE fl.id = ? AND fl.user_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$log = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$log) {
    http_response_code(404);
    echo json_encode(['error' => 'Log not found.']);
    exit;
}

$vehicle_id = (int)$log['vehicle_id'];
$log_date   = $log['log_date'];
$log_id     = (int)$log['id'];

$stmt = $conn->prepare("
    SELECT COUNT(*) AS cnt
    FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ?
      AND (log_date < ? OR (log_date = ? AND id <= ?))
");
$stmt->bind_param('iissi', $user_id, $vehicle_id, $log_date, $log_date, $log_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$fillup_number = (int)$row['cnt'];

$efficiency_kml = null;
if ((bool)$log['is_full_tank']) {
    $eff_row = $conn->query("
        SELECT SUM(fl2.liters_filled) / NULLIF({$log['odometer']} - pf.odometer, 0) * 100 AS efficiency_l100km
        FROM fuel_logs fl2
        JOIN (
            SELECT id, odometer, log_date
            FROM fuel_logs
            WHERE vehicle_id = {$vehicle_id} AND user_id = {$user_id}
              AND is_full_tank = 1
              AND (log_date < '{$log_date}' OR (log_date = '{$log_date}' AND id < {$log_id}))
            ORDER BY log_date DESC, id DESC LIMIT 1
        ) pf ON (fl2.log_date > pf.log_date OR (fl2.log_date = pf.log_date AND fl2.id > pf.id))
        WHERE fl2.vehicle_id = {$vehicle_id} AND fl2.user_id = {$user_id}
          AND (fl2.log_date < '{$log_date}' OR (fl2.log_date = '{$log_date}' AND fl2.id <= {$log_id}))
    ")->fetch_assoc();
    $l100 = $eff_row['efficiency_l100km'] ?? null;
    if ($l100 !== null && $l100 > 0) {
        $efficiency_kml = 100.0 / (float)$l100;
    }
}

$stmt = $conn->prepare("
    SELECT id, log_date, odometer, cost_per_km, is_full_tank
    FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ?
      AND is_full_tank = 1
      AND (log_date < ? OR (log_date = ? AND id < ?))
    ORDER BY log_date DESC, id DESC
    LIMIT 1
");
$stmt->bind_param('iissi', $user_id, $vehicle_id, $log_date, $log_date, $log_id);
$stmt->execute();
$prior_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$prior = null;
if ($prior_row) {
    $p_id       = (int)$prior_row['id'];
    $p_date     = $prior_row['log_date'];
    $p_odometer = (int)$prior_row['odometer'];

    // Compute efficiency FOR the prior log (not before it)
    // This uses the tank before the prior log to get the delta
    $prior_eff = null;
    if ((bool)$prior_row['is_full_tank']) {
        $prev_before_prior = $conn->query("
            SELECT odometer
            FROM fuel_logs
            WHERE vehicle_id = {$vehicle_id} AND user_id = {$user_id}
              AND is_full_tank = 1
              AND (log_date < '{$p_date}' OR (log_date = '{$p_date}' AND id < {$p_id}))
            ORDER BY log_date DESC, id DESC
            LIMIT 1
        ")->fetch_assoc();

        if ($prev_before_prior && $prev_before_prior['odometer'] !== null) {
            $trip = $p_odometer - (int)$prev_before_prior['odometer'];
            $liters = $conn->query("
                SELECT SUM(liters_filled) AS total_liters
                FROM fuel_logs
                WHERE vehicle_id = {$vehicle_id} AND user_id = {$user_id}
                  AND is_full_tank = 1
                  AND (log_date > '{$prev_before_prior['log_date']}' OR (log_date = '{$prev_before_prior['log_date']}' AND id > {$prev_before_prior['id']}))
                  AND (log_date < '{$p_date}' OR (log_date = '{$p_date}' AND id <= {$p_id}))
            ")->fetch_assoc()['total_liters'] ?? null;

            if ($trip > 0 && $liters > 0) {
                $prior_eff = $trip / (float)$liters;
            }
        }
    }

    $prior = [
        'id'             => $p_id,
        'log_date'       => $p_date,
        'efficiency_kml' => $prior_eff,
        'cost_per_km'    => $prior_row['cost_per_km'] !== null ? (float)$prior_row['cost_per_km'] : null,
    ];
}

echo json_encode([
    'log' => [
        'id'            => $log_id,
        'vehicle_id'    => $vehicle_id,
        'vehicle_name'  => $log['vehicle_name'],
        'log_date'      => $log['log_date'],
        'odometer'      => (int)$log['odometer'],
        'trip_distance' => $log['trip_distance'] !== null ? (float)$log['trip_distance'] : null,
        'liters_filled' => (float)$log['liters_filled'],
        'fuel_price'    => (float)$log['fuel_price'],
        'is_full_tank'  => (bool)$log['is_full_tank'],
        'notes'         => $log['notes'],
        'efficiency_kml'  => $efficiency_kml,
        'cost_per_liter'  => $log['cost_per_liter'] !== null ? (float)$log['cost_per_liter'] : null,
        'cost_per_km'     => $log['cost_per_km'] !== null ? (float)$log['cost_per_km'] : null,
    ],
    'prior'         => $prior,
    'fillup_number' => $fillup_number,
]);
