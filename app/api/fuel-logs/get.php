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
    SELECT odometer FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ?
      AND (log_date < ? OR (log_date = ? AND id < ?))
    ORDER BY log_date DESC, id DESC
    LIMIT 1
");
$stmt->bind_param('iissi', $user_id, $vehicle_id, $log_date, $log_date, $log_id);
$stmt->execute();
$prev = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("
    SELECT odometer FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ?
      AND (log_date > ? OR (log_date = ? AND id > ?))
    ORDER BY log_date ASC, id ASC
    LIMIT 1
");
$stmt->bind_param('iissi', $user_id, $vehicle_id, $log_date, $log_date, $log_id);
$stmt->execute();
$next = $stmt->get_result()->fetch_assoc();
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
    ],
    'prev_odometer' => $prev ? (int)$prev['odometer'] : null,
    'next_odometer' => $next ? (int)$next['odometer'] : null,
]);
