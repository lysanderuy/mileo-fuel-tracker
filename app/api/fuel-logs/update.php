<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/includes/api_helpers.php';

api_require_auth();
header('Content-Type: application/json');
api_require_method('POST');

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

$today = (new DateTime())->format('Y-m-d');
if ($log_date > $today) {
    http_response_code(422);
    echo json_encode(['error' => 'Log date cannot be in the future.']);
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

$trip_distance = null;
if ($new_prev_odometer !== null && !$manual_trip_override) {
    $trip_distance = (float)($odometer - $new_prev_odometer);
} elseif ($new_prev_odometer !== null) {
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
} else {
    if ($trip_distance_input !== null && $trip_distance_input !== '') {
        $parsed = (float)$trip_distance_input;
        if ($parsed > 0) {
            $trip_distance = $parsed;
        }
    }
}

$is_full_tank_int = $is_full_tank ? 1 : 0;

// Fixed type string: s(log_date) i(odometer) d(trip_distance) d(liters_filled) d(fuel_price) i(is_full_tank_int) s(notes) i(id) i(user_id)
$stmt = $conn->prepare("
    UPDATE fuel_logs
    SET log_date = ?, odometer = ?, trip_distance = ?,
        liters_filled = ?, fuel_price = ?, is_full_tank = ?,
        notes = NULLIF(?, '')
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param(
    'sidddisii',
    $log_date, $odometer, $trip_distance,
    $liters_filled, $fuel_price, $is_full_tank_int,
    $notes, $id, $user_id
);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("
    SELECT odometer FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ?
    ORDER BY log_date DESC, id DESC
    LIMIT 1
");
$stmt->bind_param('ii', $user_id, $vehicle_id);
$stmt->execute();
$latest_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($latest_row) {
    $latest_odo = (int)$latest_row['odometer'];
    $stmt = $conn->prepare("UPDATE vehicles SET odometer = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('iii', $latest_odo, $vehicle_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

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
