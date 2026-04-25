<?php
require_once __DIR__ . '/../../../config/db.php';

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

$name         = trim($body['name']         ?? '');
$make         = trim($body['make']         ?? '');
$model        = trim($body['model']        ?? '');
$year         = isset($body['year']) && $body['year'] !== '' ? (int)$body['year'] : null;
$fuel_type    = trim($body['fuel_type']    ?? '');
$color        = trim($body['color']        ?? '');
$plate_number = trim($body['plate_number'] ?? '');

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

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    INSERT INTO vehicles (user_id, name, make, model, year, fuel_type, color, plate_number)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('isssssss',
    $user_id,
    $name,
    $make,
    $model,
    $year,
    $fuel_type,
    $color,
    $plate_number
);
$stmt->execute();
$new_id = $stmt->insert_id;
$stmt->close();

http_response_code(201);
echo json_encode([
    'vehicle' => [
        'id'           => $new_id,
        'name'         => $name,
        'make'         => $make ?: null,
        'model'        => $model ?: null,
        'year'         => $year,
        'fuel_type'    => $fuel_type ?: null,
        'color'        => $color ?: null,
        'plate_number' => $plate_number ?: null,
        'is_archived'  => false,
    ],
]);
