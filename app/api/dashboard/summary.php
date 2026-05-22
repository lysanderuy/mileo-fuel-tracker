<?php
require_once __DIR__ . '/../../../config/db.php';

set_exception_handler(function (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    echo json_encode(['error' => $e->getMessage()]);
    exit;
});

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$user_id = (int)$_SESSION['user_id'];
$vehicle_id = isset($_GET['vehicle_id']) && $_GET['vehicle_id'] !== '' ? (int)$_GET['vehicle_id'] : null;

// Get all active vehicles for the switcher
$vehicles = [];
$has_is_default = false;
$check = $conn->query("SHOW COLUMNS FROM vehicles LIKE 'is_default'");
if ($check && $check->num_rows > 0) {
    $has_is_default = true;
}

$orderBy = $has_is_default ? "is_default DESC, name ASC" : "name ASC";
$selectFields = $has_is_default ? "id, name, is_default" : "id, name";

$stmt = $conn->prepare("SELECT $selectFields FROM vehicles WHERE user_id = ? AND is_archived = 0 ORDER BY $orderBy");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $vehicles[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'is_default' => $has_is_default ? (bool)$row['is_default'] : false
    ];
}
$stmt->close();

$active_vehicle_name = 'All Vehicles';
if ($vehicle_id) {
    foreach ($vehicles as $v) {
        if ($v['id'] === $vehicle_id) {
            $active_vehicle_name = $v['name'];
            break;
        }
    }
}

// Aggregate stats
$stats_query = "
    SELECT
        COUNT(*)                                                                                AS total_fillups,
        SUM(fuel_price)                                                                         AS total_spent,
        SUM(trip_distance) / NULLIF(SUM(liters_filled), 0)                                     AS avg_kml,
        SUM(fuel_price)    / NULLIF(SUM(trip_distance), 0)                                     AS avg_cost_km,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN 1          ELSE 0 END)           AS month_fillups,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN fuel_price ELSE 0 END)           AS month_spent,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN trip_distance ELSE NULL END)
            / NULLIF(SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN liters_filled ELSE NULL END), 0) AS month_avg_kml,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN fuel_price   ELSE NULL END)
            / NULLIF(SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN trip_distance ELSE NULL END), 0) AS month_avg_cost_km,
        SUM(fuel_price) / NULLIF(SUM(liters_filled), 0)                                        AS avg_cost_per_liter,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN fuel_price   ELSE NULL END)
            / NULLIF(SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN liters_filled ELSE NULL END), 0) AS month_avg_cost_per_liter,
        SUM(trip_distance)                                                                       AS total_distance,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN trip_distance ELSE 0 END)         AS month_total_distance
    FROM fuel_logs
    WHERE user_id = ?
";

if ($vehicle_id) {
    $stats_query .= " AND vehicle_id = ?";
}

$stmt = $conn->prepare($stats_query);
if ($vehicle_id) {
    $stmt->bind_param('ii', $user_id, $vehicle_id);
} else {
    $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$agg = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stats = [
    'total_fillups'     => (int)($agg['total_fillups'] ?? 0),
    'month_fillups'     => (int)($agg['month_fillups'] ?? 0),
    'total_spent'       => (float)($agg['total_spent'] ?? 0),
    'month_spent'       => (float)($agg['month_spent'] ?? 0),
    'avg_kml'           => ($agg['avg_kml'] !== null) ? (float)$agg['avg_kml'] : null,
    'month_avg_kml'     => ($agg['month_avg_kml'] !== null) ? (float)$agg['month_avg_kml'] : null,
    'avg_cost_km'              => ($agg['avg_cost_km'] !== null) ? (float)$agg['avg_cost_km'] : null,
    'month_avg_cost_km'        => ($agg['month_avg_cost_km'] !== null) ? (float)$agg['month_avg_cost_km'] : null,
    'avg_cost_per_liter'       => ($agg['avg_cost_per_liter'] !== null) ? (float)$agg['avg_cost_per_liter'] : null,
    'month_avg_cost_per_liter' => ($agg['month_avg_cost_per_liter'] !== null) ? (float)$agg['month_avg_cost_per_liter'] : null,
    'total_distance'           => ($agg['total_distance'] !== null) ? (float)$agg['total_distance'] : null,
    'month_total_distance'     => ($agg['month_total_distance'] !== null) ? (float)$agg['month_total_distance'] : null,
];

$fleet = [];
if (count($vehicles) > 1) {
    $fleet_stmt = $conn->prepare("
        SELECT
            v.id,
            v.name,
            SUM(fl.trip_distance) / NULLIF(SUM(fl.liters_filled), 0) AS avg_kml,
            SUM(fl.fuel_price) / NULLIF(SUM(fl.trip_distance), 0)    AS avg_cost_km,
            SUM(fl.fuel_price)                                        AS total_spent,
            COUNT(*)                                                  AS total_fillups
        FROM fuel_logs fl
        JOIN vehicles v ON v.id = fl.vehicle_id
        WHERE fl.user_id = ? AND v.is_archived = 0
        GROUP BY v.id, v.name
        ORDER BY avg_kml DESC
    ");
    $fleet_stmt->bind_param('i', $user_id);
    $fleet_stmt->execute();
    $fleet_res = $fleet_stmt->get_result();
    while ($fr = $fleet_res->fetch_assoc()) {
        $fleet[] = [
            'id'            => (int)$fr['id'],
            'name'          => $fr['name'],
            'avg_kml'       => $fr['avg_kml'] !== null ? (float)$fr['avg_kml'] : null,
            'avg_cost_km'   => $fr['avg_cost_km'] !== null ? (float)$fr['avg_cost_km'] : null,
            'total_spent'   => (float)$fr['total_spent'],
            'total_fillups' => (int)$fr['total_fillups'],
        ];
    }
    $fleet_stmt->close();
}

// Recent fill-ups
$fillups = [];
$vehicle_filter = $vehicle_id ? " AND fl.vehicle_id = {$vehicle_id}" : "";
$logs_query = "
    WITH ranked AS (
        SELECT
            fl.id,
            fl.log_date,
            fl.vehicle_id,
            fl.is_full_tank,
            fl.liters_filled,
            fl.cost_per_liter,
            fl.fuel_price,
            fl.trip_distance,
            fl.notes,
            fl.cost_per_km,
            CASE WHEN fl.is_full_tank = 1 THEN (
                SELECT SUM(fl2.liters_filled) / NULLIF(
                    fl.odometer - (
                        SELECT pf.odometer FROM fuel_logs pf
                        WHERE pf.vehicle_id = fl.vehicle_id AND pf.user_id = {$user_id}
                          AND pf.is_full_tank = 1
                          AND (pf.log_date < fl.log_date OR (pf.log_date = fl.log_date AND pf.id < fl.id))
                        ORDER BY pf.log_date DESC, pf.id DESC LIMIT 1
                    ), 0
                ) * 100
                FROM fuel_logs fl2
                WHERE fl2.vehicle_id = fl.vehicle_id AND fl2.user_id = {$user_id}
                  AND (fl2.log_date > (
                          SELECT pf.log_date FROM fuel_logs pf
                          WHERE pf.vehicle_id = fl.vehicle_id AND pf.user_id = {$user_id}
                            AND pf.is_full_tank = 1
                            AND (pf.log_date < fl.log_date OR (pf.log_date = fl.log_date AND pf.id < fl.id))
                          ORDER BY pf.log_date DESC, pf.id DESC LIMIT 1
                      )
                      OR (fl2.log_date = (
                              SELECT pf.log_date FROM fuel_logs pf
                              WHERE pf.vehicle_id = fl.vehicle_id AND pf.user_id = {$user_id}
                                AND pf.is_full_tank = 1
                                AND (pf.log_date < fl.log_date OR (pf.log_date = fl.log_date AND pf.id < fl.id))
                              ORDER BY pf.log_date DESC, pf.id DESC LIMIT 1
                          )
                          AND fl2.id > (
                              SELECT pf.id FROM fuel_logs pf
                              WHERE pf.vehicle_id = fl.vehicle_id AND pf.user_id = {$user_id}
                                AND pf.is_full_tank = 1
                                AND (pf.log_date < fl.log_date OR (pf.log_date = fl.log_date AND pf.id < fl.id))
                              ORDER BY pf.log_date DESC, pf.id DESC LIMIT 1
                          )))
                  AND (fl2.log_date < fl.log_date OR (fl2.log_date = fl.log_date AND fl2.id <= fl.id))
            ) END AS efficiency_l100km
        FROM fuel_logs fl
        WHERE fl.user_id = {$user_id}{$vehicle_filter}
    )
    SELECT
        r.id,
        r.log_date,
        r.vehicle_id,
        r.is_full_tank,
        r.liters_filled,
        r.cost_per_liter,
        r.fuel_price,
        r.trip_distance,
        r.notes,
        r.cost_per_km,
        CASE WHEN r.efficiency_l100km > 0 THEN 100.0 / r.efficiency_l100km END AS efficiency_kml,
        LAG(CASE WHEN r.efficiency_l100km > 0 THEN 100.0 / r.efficiency_l100km END)
            OVER (PARTITION BY r.vehicle_id ORDER BY r.log_date ASC, r.id ASC)  AS prior_efficiency_kml,
        LAG(r.cost_per_km)
            OVER (PARTITION BY r.vehicle_id ORDER BY r.log_date ASC, r.id ASC)  AS prior_cost_per_km,
        v.name AS vehicle_name
    FROM ranked r
    JOIN vehicles v ON v.id = r.vehicle_id
    ORDER BY r.log_date DESC, r.id DESC LIMIT 5
";

$result = $conn->query($logs_query);
if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Logs query failed: ' . $conn->error]);
    exit;
}
while ($row = $result->fetch_assoc()) {
    $fillups[] = [
        'date'           => $row['log_date'],
        'station'        => $row['vehicle_name'],
        'is_full_tank'   => (bool)$row['is_full_tank'],
        'liters_filled'  => (float)$row['liters_filled'],
        'cost_per_liter' => (float)$row['cost_per_liter'],
        'fuel_price'     => (float)$row['fuel_price'],
        'efficiency_kml' => $row['efficiency_kml'] !== null ? (float)$row['efficiency_kml'] : null,
        'cost_per_km'    => $row['cost_per_km'] !== null ? (float)$row['cost_per_km'] : null,
        'prior_efficiency_kml' => $row['prior_efficiency_kml'] !== null ? (float)$row['prior_efficiency_kml'] : null,
        'prior_cost_per_km'    => $row['prior_cost_per_km'] !== null ? (float)$row['prior_cost_per_km'] : null,
        'trip_distance'  => $row['trip_distance'] !== null ? (float)$row['trip_distance'] : null,
        'notes'          => $row['notes'],
    ];
}

echo json_encode([
    'stats'               => $stats,
    'vehicles'            => $vehicles,
    'active_vehicle_name' => $active_vehicle_name,
    'has_vehicles'        => count($vehicles) > 0,
    'fillups'             => $fillups,
    'fleet'               => $fleet,
]);

