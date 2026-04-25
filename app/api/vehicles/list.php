<?php
require_once __DIR__ . '/../../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, name, make, model, year, fuel_type, color, plate_number, is_archived
    FROM vehicles
    WHERE user_id = ?
    ORDER BY is_archived ASC, name ASC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicles[] = [
        'id'           => (int)$row['id'],
        'name'         => $row['name'],
        'make'         => $row['make'],
        'model'        => $row['model'],
        'year'         => $row['year'] !== null ? (int)$row['year'] : null,
        'fuel_type'    => $row['fuel_type'],
        'color'        => $row['color'],
        'plate_number' => $row['plate_number'],
        'is_archived'  => (bool)$row['is_archived'],
    ];
}
$stmt->close();

echo json_encode(['vehicles' => $vehicles]);
