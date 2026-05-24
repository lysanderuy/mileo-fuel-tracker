<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/includes/api_helpers.php';

api_require_auth();
header('Content-Type: application/json');
api_require_method('POST');

$body = json_decode(file_get_contents('php://input'), true);

$name          = trim($body['name']          ?? '');
$make          = trim($body['make']          ?? '');
$model         = trim($body['model']         ?? '');
$year          = isset($body['year']) && $body['year'] !== '' ? (int)$body['year'] : null;
$fuel_type     = trim($body['fuel_type']     ?? '');
$color         = trim($body['color']         ?? '');
$plate_number  = trim($body['plate_number']  ?? '');
$tank_capacity = isset($body['tank_capacity']) && $body['tank_capacity'] !== '' ? (float)$body['tank_capacity'] : null;
$odometer      = isset($body['odometer'])      && $body['odometer']      !== '' ? (int)$body['odometer']      : null;

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

$stmt = $conn->prepare("
    INSERT INTO vehicles (user_id, name, make, model, year, fuel_type, color, plate_number, tank_capacity, odometer)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('isssssssdi',
    $user_id,
    $name,
    $make,
    $model,
    $year,
    $fuel_type,
    $color,
    $plate_number,
    $tank_capacity,
    $odometer
);
$stmt->execute();
$new_id = $stmt->insert_id;
$stmt->close();

$count_stmt = $conn->prepare("SELECT COUNT(*) FROM vehicles WHERE user_id = ? AND is_archived = 0");
$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$count_stmt->bind_result($vehicle_count);
$count_stmt->fetch();
$count_stmt->close();

$is_default = false;
if ($vehicle_count === 1) {
    $def_stmt = $conn->prepare("UPDATE vehicles SET is_default = 1 WHERE id = ?");
    $def_stmt->bind_param('i', $new_id);
    $def_stmt->execute();
    $def_stmt->close();
    $is_default = true;
}

http_response_code(201);
echo json_encode([
    'vehicle' => [
        'id'            => $new_id,
        'name'          => $name,
        'make'          => $make ?: null,
        'model'         => $model ?: null,
        'year'          => $year,
        'fuel_type'     => $fuel_type ?: null,
        'color'         => $color ?: null,
        'plate_number'  => $plate_number ?: null,
        'tank_capacity' => $tank_capacity,
        'odometer'      => $odometer,
        'is_archived'   => false,
        'is_default'    => $is_default,
    ],
]);
