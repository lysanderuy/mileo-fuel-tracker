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

$user_id    = (int)$_SESSION['user_id'];
$vehicle_id = isset($_GET['vehicle_id']) && $_GET['vehicle_id'] !== '' ? (int)$_GET['vehicle_id'] : 0;
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page   = 20;
$offset     = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) AS total FROM fuel_logs WHERE user_id = ?";
if ($vehicle_id > 0) {
    $count_query .= " AND vehicle_id = ?";
}

$stmt = $conn->prepare($count_query);
if ($vehicle_id > 0) {
    $stmt->bind_param('ii', $user_id, $vehicle_id);
} else {
    $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$count_row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$total = (int)($count_row['total'] ?? 0);

$logs_query = "
    SELECT
        fl.id,
        fl.log_date,
        fl.fuel_price,
        fl.liters_filled,
        fl.trip_distance,
        fl.odometer,
        fl.is_full_tank,
        CASE WHEN fl.efficiency_l100km > 0 THEN ROUND(100.0 / fl.efficiency_l100km, 2) END AS efficiency_kml,
        fl.notes,
        v.name AS vehicle_name
    FROM fuel_logs fl
    JOIN vehicles v ON v.id = fl.vehicle_id
    WHERE fl.user_id = ?
";
if ($vehicle_id > 0) {
    $logs_query .= " AND fl.vehicle_id = ?";
}
$logs_query .= " ORDER BY fl.log_date DESC, fl.id DESC LIMIT {$per_page} OFFSET {$offset}";

$stmt = $conn->prepare($logs_query);
if ($vehicle_id > 0) {
    $stmt->bind_param('ii', $user_id, $vehicle_id);
} else {
    $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = [
        'id'             => (int)$row['id'],
        'log_date'       => $row['log_date'],
        'fuel_price'     => (float)$row['fuel_price'],
        'liters_filled'  => (float)$row['liters_filled'],
        'trip_distance'  => (float)$row['trip_distance'],
        'odometer'       => (int)$row['odometer'],
        'is_full_tank'   => (bool)$row['is_full_tank'],
        'efficiency_kml' => $row['efficiency_kml'] !== null ? (float)$row['efficiency_kml'] : null,
        'notes'          => $row['notes'],
        'vehicle_name'   => $row['vehicle_name']
    ];
}
$stmt->close();

echo json_encode([
    'logs'     => $logs,
    'total'    => $total,
    'page'     => $page,
    'per_page' => $per_page,
    'has_more' => ($offset + count($logs)) < $total,
]);

