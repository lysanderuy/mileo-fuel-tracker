<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/includes/api_helpers.php';

api_require_auth();
header('Content-Type: application/json');
api_require_method('POST');

$body = json_decode(file_get_contents('php://input'), true);

$id            = isset($body['id']) ? (int)$body['id'] : 0;
$name          = trim($body['name']          ?? '');
$make          = trim($body['make']          ?? '');
$model         = trim($body['model']         ?? '');
$year          = isset($body['year']) && $body['year'] !== '' ? (int)$body['year'] : null;
$fuel_type     = trim($body['fuel_type']     ?? '');
$color         = trim($body['color']         ?? '');
$plate_number  = trim($body['plate_number']  ?? '');
$tank_capacity = isset($body['tank_capacity']) && $body['tank_capacity'] !== '' ? (float)$body['tank_capacity'] : null;
$odometer      = isset($body['odometer'])      && $body['odometer']      !== '' ? (int)$body['odometer']      : null;

if ($id <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid vehicle ID.']);
    exit;
}

if ($name === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Vehicle name is required.']);
    exit;
}

$allowed_fuel_types = ['Gasoline', 'Diesel', 'Electric', 'Hybrid', ''];
if (!in_array($fuel_type, $allowed_fuel_types, true)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid fuel type.']);
    exit;
}

if ($year !== null && ($year < 1900 || $year > 2100)) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid year.']);
    exit;
}

if ($tank_capacity === null || $tank_capacity <= 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Tank capacity is required and must be a positive number.']);
    exit;
}

if ($odometer === null || $odometer < 0) {
    http_response_code(422);
    echo json_encode(['error' => 'Odometer reading is required and cannot be negative.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $id, $user_id);
$stmt->execute();
$found = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$found) {
    http_response_code(404);
    echo json_encode(['error' => 'Vehicle not found.']);
    exit;
}

$stmt = $conn->prepare("
    UPDATE vehicles
    SET name = ?, make = ?, model = ?, year = ?, fuel_type = ?, color = ?, plate_number = ?, tank_capacity = ?, odometer = ?
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param('sss' . 'i' . 'sss' . 'diii',
    $name,
    $make,
    $model,
    $year,
    $fuel_type,
    $color,
    $plate_number,
    $tank_capacity,
    $odometer,
    $id,
    $user_id
);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
