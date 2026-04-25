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

$id           = isset($body['id']) ? (int)$body['id'] : 0;
$name         = trim($body['name']         ?? '');
$make         = trim($body['make']         ?? '');
$model        = trim($body['model']        ?? '');
$year         = isset($body['year']) && $body['year'] !== '' ? (int)$body['year'] : null;
$fuel_type    = trim($body['fuel_type']    ?? '');
$color        = trim($body['color']        ?? '');
$plate_number = trim($body['plate_number'] ?? '');

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

$user_id = (int)$_SESSION['user_id'];

$check = $conn->prepare("SELECT id FROM vehicles WHERE id = ? AND user_id = ?");
$check->bind_param('ii', $id, $user_id);
$check->execute();
$found = $check->get_result()->fetch_assoc();
$check->close();

if (!$found) {
    http_response_code(404);
    echo json_encode(['error' => 'Vehicle not found.']);
    exit;
}

$stmt = $conn->prepare("
    UPDATE vehicles
    SET name = ?, make = ?, model = ?, year = ?, fuel_type = ?, color = ?, plate_number = ?
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param('sssssssii',
    $name,
    $make,
    $model,
    $year,
    $fuel_type,
    $color,
    $plate_number,
    $id,
    $user_id
);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
