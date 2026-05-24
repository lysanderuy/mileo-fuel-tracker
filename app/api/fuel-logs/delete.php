<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/includes/api_helpers.php';

api_require_auth();
header('Content-Type: application/json');
api_require_method('POST');

$body   = json_decode(file_get_contents('php://input'), true);
$user_id = (int)$_SESSION['user_id'];
$id      = isset($body['id']) ? (int)$body['id'] : 0;

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Log ID is required.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT id, vehicle_id, log_date, odometer
    FROM fuel_logs
    WHERE id = ? AND user_id = ?
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

$stmt = $conn->prepare("
    SELECT odometer FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ?
      AND (log_date < ? OR (log_date = ? AND id < ?))
    ORDER BY log_date DESC, id DESC
    LIMIT 1
");
$stmt->bind_param('iissi', $user_id, $vehicle_id, $log_date, $log_date, $id);
$stmt->execute();
$prev = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("
    SELECT id, odometer FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ?
      AND (log_date > ? OR (log_date = ? AND id > ?))
    ORDER BY log_date ASC, id ASC
    LIMIT 1
");
$stmt->bind_param('iissi', $user_id, $vehicle_id, $log_date, $log_date, $id);
$stmt->execute();
$next = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare("DELETE FROM fuel_logs WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$stmt->close();

if ($next) {
    $next_id = (int)$next['id'];
    if ($prev) {
        $new_trip = (float)((int)$next['odometer'] - (int)$prev['odometer']);
        $stmt = $conn->prepare("UPDATE fuel_logs SET trip_distance = ? WHERE id = ?");
        $stmt->bind_param('di', $new_trip, $next_id);
    } else {
        $stmt = $conn->prepare("UPDATE fuel_logs SET trip_distance = NULL WHERE id = ?");
        $stmt->bind_param('i', $next_id);
    }
    $stmt->execute();
    $stmt->close();
}

$stmt = $conn->prepare("
    SELECT odometer FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ?
    ORDER BY log_date DESC, id DESC
    LIMIT 1
");
$stmt->bind_param('ii', $user_id, $vehicle_id);
$stmt->execute();
$latest_remaining = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($latest_remaining) {
    $stmt = $conn->prepare("UPDATE vehicles SET odometer = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('iii', (int)$latest_remaining['odometer'], $vehicle_id, $user_id);
} else {
    $stmt = $conn->prepare("UPDATE vehicles SET odometer = NULL WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $vehicle_id, $user_id);
}
$stmt->execute();
$stmt->close();

http_response_code(200);
echo json_encode(['success' => true]);
