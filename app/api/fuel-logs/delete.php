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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$body   = json_decode(file_get_contents('php://input'), true);
$user_id = (int)$_SESSION['user_id'];
$id      = isset($body['id']) ? (int)$body['id'] : 0;

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Log ID is required.']);
    exit;
}

// Fetch log, verify ownership
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

// Find the log immediately before this one (same vehicle, chronological)
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

// Find the log immediately after this one (same vehicle, chronological)
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

// Delete the log
$stmt = $conn->prepare("DELETE FROM fuel_logs WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$stmt->close();

// ── Downstream Recomputation ─────────────────────────────────────────────────
// The successor's trip_distance referenced this log as its predecessor.
// - If there is no successor, nothing to recompute.
// - If successor exists but no predecessor now exists, trip_distance becomes NULL
//   (successor is now the first log — we cannot compute its trip).
// - If successor exists and a predecessor also exists, recompute the trip.

if ($next) {
    $next_id = (int)$next['id'];
    if ($prev) {
        $new_trip = (float)((int)$next['odometer'] - (int)$prev['odometer']);
        $stmt = $conn->prepare("UPDATE fuel_logs SET trip_distance = ? WHERE id = ?");
        $stmt->bind_param('di', $new_trip, $next_id);
    } else {
        $null_trip = null;
        $stmt = $conn->prepare("UPDATE fuel_logs SET trip_distance = NULL WHERE id = ?");
        $stmt->bind_param('i', $next_id);
    }
    $stmt->execute();
    $stmt->close();
}
// ─────────────────────────────────────────────────────────────────────────────

// Update vehicle's odometer to the previous log's odometer, or NULL if no prior logs
$new_vehicle_odometer = $prev ? (int)$prev['odometer'] : null;
if ($new_vehicle_odometer !== null) {
    $stmt = $conn->prepare("UPDATE vehicles SET odometer = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('iii', $new_vehicle_odometer, $vehicle_id, $user_id);
} else {
    $stmt = $conn->prepare("UPDATE vehicles SET odometer = NULL WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $vehicle_id, $user_id);
}
$stmt->execute();
$stmt->close();

http_response_code(200);
echo json_encode(['success' => true]);
