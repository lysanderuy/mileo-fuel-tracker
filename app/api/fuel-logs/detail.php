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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

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
           CASE WHEN fl.efficiency_l100km > 0 THEN ROUND(100.0 / fl.efficiency_l100km, 2) END AS efficiency_kml,
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

$stmt = $conn->prepare("
    SELECT id, log_date,
           CASE WHEN efficiency_l100km > 0 THEN ROUND(100.0 / efficiency_l100km, 2) END AS efficiency_kml,
           cost_per_km
    FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ?
      AND (log_date < ? OR (log_date = ? AND id < ?))
    ORDER BY log_date DESC, id DESC
    LIMIT 1
");
$stmt->bind_param('iissi', $user_id, $vehicle_id, $log_date, $log_date, $log_id);
$stmt->execute();
$prior = $stmt->get_result()->fetch_assoc();
$stmt->close();

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
        'efficiency_kml'  => $log['efficiency_kml'] !== null ? (float)$log['efficiency_kml'] : null,
        'cost_per_liter'  => $log['cost_per_liter'] !== null ? (float)$log['cost_per_liter'] : null,
        'cost_per_km'     => $log['cost_per_km'] !== null ? (float)$log['cost_per_km'] : null,
    ],
    'prior' => $prior ? [
        'id'             => (int)$prior['id'],
        'log_date'       => $prior['log_date'],
        'efficiency_kml' => $prior['efficiency_kml'] !== null ? (float)$prior['efficiency_kml'] : null,
        'cost_per_km'    => $prior['cost_per_km'] !== null ? (float)$prior['cost_per_km'] : null,
    ] : null,
    'fillup_number' => $fillup_number,
]);
