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

$user_id = (int)$_SESSION['user_id'];
$vehicle_id = isset($body['vehicle_id']) ? (int)$body['vehicle_id'] : 0;
$log_date = trim($body['log_date'] ?? '');
$odometer = isset($body['odometer']) ? (int)$body['odometer'] : -1;
$liters_filled = isset($body['liters_filled']) ? (float)$body['liters_filled'] : -1;
$fuel_price = isset($body['fuel_price']) ? (float)$body['fuel_price'] : -1;
$manual_trip_override = !empty($body['manual_trip_override']);
$trip_distance_input = $body['trip_distance'] ?? null;
$is_full_tank = array_key_exists('is_full_tank', $body) ? (bool)$body['is_full_tank'] : true;
$notes = trim($body['notes'] ?? '');

if (mb_strlen($notes) > 200) {
    http_response_code(422);
    echo json_encode(['error' => 'Notes must be 200 characters or fewer.']);
    exit;
}

if ($vehicle_id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Vehicle is required.']);
    exit;
}

$date_obj = DateTime::createFromFormat('Y-m-d', $log_date);
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

$stmt = $conn->prepare("
    SELECT id
    FROM vehicles
    WHERE id = ? AND user_id = ? AND is_archived = 0
    LIMIT 1
");
$stmt->bind_param('ii', $vehicle_id, $user_id);
$stmt->execute();
$vehicle_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$vehicle_row) {
    http_response_code(422);
    echo json_encode(['error' => 'Selected vehicle is invalid or archived.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT odometer
    FROM fuel_logs
    WHERE user_id = ? AND vehicle_id = ?
    ORDER BY log_date DESC, id DESC
    LIMIT 1
");
$stmt->bind_param('ii', $user_id, $vehicle_id);
$stmt->execute();
$last_row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$last_odometer = $last_row ? (int)$last_row['odometer'] : null;
if ($last_odometer !== null && $odometer < $last_odometer) {
    http_response_code(422);
    echo json_encode(['error' => 'Odometer must be greater than or equal to last logged value.']);
    exit;
}

$trip_distance = null;
if ($last_odometer !== null && !$manual_trip_override) {
    $trip_distance = (float)($odometer - $last_odometer);
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
    INSERT INTO fuel_logs (user_id, vehicle_id, log_date, odometer, trip_distance, liters_filled, fuel_price, is_full_tank, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''))
");
$stmt->bind_param(
    'iisidddis',
    $user_id,
    $vehicle_id,
    $log_date,
    $odometer,
    $trip_distance,
    $liters_filled,
    $fuel_price,
    $is_full_tank_int,
    $notes
);
$stmt->execute();
$new_id = (int)$stmt->insert_id;
$stmt->close();

http_response_code(201);
echo json_encode([
    'fuel_log' => [
        'id' => $new_id,
        'vehicle_id' => $vehicle_id,
        'log_date' => $log_date,
        'odometer' => $odometer,
        'trip_distance' => $trip_distance,
        'liters_filled' => $liters_filled,
        'fuel_price' => $fuel_price,
        'is_full_tank' => (bool)$is_full_tank_int,
        'efficiency_note' => $is_full_tank ? null : 'Partial fill - efficiency estimate may vary.',
        'notes' => $notes === '' ? null : $notes,
    ],
]);
