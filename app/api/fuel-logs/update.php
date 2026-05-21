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

$body = json_decode(file_get_contents('php://input'), true);

$user_id              = (int)$_SESSION['user_id'];
$id                   = isset($body['id']) ? (int)$body['id'] : 0;
$log_date             = trim($body['log_date'] ?? '');
$odometer             = isset($body['odometer']) ? (int)$body['odometer'] : -1;
$liters_filled        = isset($body['liters_filled']) ? (float)$body['liters_filled'] : -1;
$fuel_price           = isset($body['fuel_price']) ? (float)$body['fuel_price'] : -1;
$manual_trip_override = !empty($body['manual_trip_override']);
$trip_distance_input  = $body['trip_distance'] ?? null;
$is_full_tank         = array_key_exists('is_full_tank', $body) ? (bool)$body['is_full_tank'] : true;
$notes                = trim($body['notes'] ?? '');

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Log ID is required.']);
    exit;
}

if (mb_strlen($notes) > 200) {
    http_response_code(422);
    echo json_encode(['error' => 'Notes must be 200 characters or fewer.']);
    exit;
}

$date_obj   = DateTime::createFromFormat('Y-m-d', $log_date);
$date_valid = $date_obj && $date_obj->format('Y-m-d') === $log_date;
if (!$date_valid) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid log date.']);
    exit;
}

if ($odometer < 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Odometer must be zero or greater.']);
    exit;
}

if ($liters_filled <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Liters filled must be greater than zero.']);
    exit;
}

if ($fuel_price <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Fuel price must be greater than zero.']);
    exit;
}

// Fetch current log, verify ownership
$stmt = $conn->prepare("
    SELECT id, vehicle_id, log_date, odometer, trip_distance
    FROM fuel_logs
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$current = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current) {
    http_response_code(404);
    echo json_encode(['error' => 'Log not found.']);
    exit;
}

$vehicle_id   = (int)$current['vehicle_id'];
$old_date     = $current['log_date'];

// Validate vehicle is not archived
$stmt = $conn->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ? AND is_archived = 0 LIMIT 1");
$stmt->bind_param('ii', $vehicle_id, $user_id);
$stmt->execute();
$vehicle_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vehicle_row) {
    http_response_code(422);
    echo json_encode(['error' => 'Vehicle is archived and cannot be updated.']);
    exit;
}

// Find old_next (the log immediately after this one in OLD ordering, before we change dates).
// This is needed for downstream recomputation when the date changes.
$stmt = $conn->prepare("
    SELECT id, odometer, log_date FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ? AND id != ?
      AND (log_date > ? OR (log_date = ? AND id > ?))
    ORDER BY log_date ASC, id ASC
    LIMIT 1
");
$stmt->bind_param('iiissi', $user_id, $vehicle_id, $id, $old_date, $old_date, $id);
$stmt->execute();
$old_next = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Find new_prev: log just before new position (excludes self)
$stmt = $conn->prepare("
    SELECT odometer FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ? AND id != ?
      AND (log_date < ? OR (log_date = ? AND id < ?))
    ORDER BY log_date DESC, id DESC
    LIMIT 1
");
$stmt->bind_param('iiissi', $user_id, $vehicle_id, $id, $log_date, $log_date, $id);
$stmt->execute();
$new_prev = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Find new_next: log just after new position (excludes self)
$stmt = $conn->prepare("
    SELECT id, odometer FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ? AND id != ?
      AND (log_date > ? OR (log_date = ? AND id > ?))
    ORDER BY log_date ASC, id ASC
    LIMIT 1
");
$stmt->bind_param('iiissi', $user_id, $vehicle_id, $id, $log_date, $log_date, $id);
$stmt->execute();
$new_next = $stmt->get_result()->fetch_assoc();
$stmt->close();

$new_prev_odometer = $new_prev ? (int)$new_prev['odometer'] : null;
$new_next_odometer = $new_next ? (int)$new_next['odometer'] : null;

// Validate odometer against neighbors in new ordering
if ($new_prev_odometer !== null && $odometer < $new_prev_odometer) {
    http_response_code(422);
    echo json_encode(['error' => 'Odometer must be ≥ the previous fill-up (' . $new_prev_odometer . ' km).']);
    exit;
}

if ($new_next_odometer !== null && $odometer > $new_next_odometer) {
    http_response_code(422);
    echo json_encode(['error' => 'Odometer must be ≤ the next fill-up (' . $new_next_odometer . ' km).']);
    exit;
}

// Compute trip_distance for this log
$trip_distance = null;
if ($new_prev_odometer !== null && !$manual_trip_override) {
    $trip_distance = (float)($odometer - $new_prev_odometer);
} else {
    if ($trip_distance_input === null || $trip_distance_input === '') {
        http_response_code(422);
        echo json_encode(['error' => 'Trip distance is required.']);
        exit;
    }
    $trip_distance = (float)$trip_distance_input;
    if ($trip_distance < 0) {
        http_response_code(422);
        echo json_encode(['error' => 'Trip distance must be zero or greater.']);
        exit;
    }
}

$is_full_tank_int = $is_full_tank ? 1 : 0;

$stmt = $conn->prepare("
    UPDATE fuel_logs
    SET log_date = ?, odometer = ?, trip_distance = ?,
        liters_filled = ?, fuel_price = ?, is_full_tank = ?,
        notes = NULLIF(?, '')
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param(
    'siiddiisi',
    $log_date, $odometer, $trip_distance,
    $liters_filled, $fuel_price, $is_full_tank_int,
    $notes, $id, $user_id
);
$stmt->execute();
$stmt->close();

// ── Downstream Recomputation ─────────────────────────────────────────────────
// After the update, the log is now at its new position.
// 1. new_next's trip_distance = new_next.odometer - new_odometer
// 2. If the date changed and old_next != new_next:
//    old_next now has a different predecessor; recompute its trip_distance.

if ($new_next) {
    $new_next_id       = (int)$new_next['id'];
    $trip_for_new_next = (float)((int)$new_next['odometer'] - $odometer);
    $stmt = $conn->prepare("UPDATE fuel_logs SET trip_distance = ? WHERE id = ?");
    $stmt->bind_param('di', $trip_for_new_next, $new_next_id);
    $stmt->execute();
    $stmt->close();
}

$date_changed = ($old_date !== $log_date);
if ($date_changed && $old_next && (!$new_next || (int)$old_next['id'] !== (int)$new_next['id'])) {
    $old_next_id   = (int)$old_next['id'];
    $old_next_date = $old_next['log_date'];

    // Find old_next's new predecessor (the log that now comes just before it)
    $stmt = $conn->prepare("
        SELECT odometer FROM fuel_logs
        WHERE user_id = ? AND vehicle_id = ? AND id != ?
          AND (log_date < ? OR (log_date = ? AND id < ?))
        ORDER BY log_date DESC, id DESC
        LIMIT 1
    ");
    $stmt->bind_param('iiissi', $user_id, $vehicle_id, $old_next_id, $old_next_date, $old_next_date, $old_next_id);
    $stmt->execute();
    $old_next_new_prev = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($old_next_new_prev) {
        $trip_for_old_next = (float)((int)$old_next['odometer'] - (int)$old_next_new_prev['odometer']);
        $stmt = $conn->prepare("UPDATE fuel_logs SET trip_distance = ? WHERE id = ?");
        $stmt->bind_param('di', $trip_for_old_next, $old_next_id);
        $stmt->execute();
        $stmt->close();
    }
}
// ─────────────────────────────────────────────────────────────────────────────

http_response_code(200);
echo json_encode([
    'fuel_log' => [
        'id'              => $id,
        'vehicle_id'      => $vehicle_id,
        'log_date'        => $log_date,
        'odometer'        => $odometer,
        'trip_distance'   => $trip_distance,
        'liters_filled'   => $liters_filled,
        'fuel_price'      => $fuel_price,
        'is_full_tank'    => (bool)$is_full_tank_int,
        'efficiency_note' => $is_full_tank ? null : 'Partial fill - efficiency estimate may vary.',
        'notes'           => $notes === '' ? null : $notes,
    ],
]);
