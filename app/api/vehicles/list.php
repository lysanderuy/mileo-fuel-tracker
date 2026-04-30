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

$has_is_default = false;
$check = $conn->query("SHOW COLUMNS FROM vehicles LIKE 'is_default'");
if ($check && $check->num_rows > 0) {
    $has_is_default = true;
}

$orderBy = "is_archived ASC, " . ($has_is_default ? "is_default DESC, " : "") . "name ASC";
$selectFields = "id, name, make, model, year, fuel_type, color, plate_number, tank_capacity, odometer, is_archived" . ($has_is_default ? ", is_default" : "");

$stmt = $conn->prepare("
    SELECT $selectFields
    FROM vehicles
    WHERE user_id = ?
    ORDER BY $orderBy
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$vehicles = [];
while ($row = $result->fetch_assoc()) {
    $vehicles[] = [
        'id'            => (int)$row['id'],
        'name'          => $row['name'],
        'make'          => $row['make'],
        'model'         => $row['model'],
        'year'          => $row['year'] !== null ? (int)$row['year'] : null,
        'fuel_type'     => $row['fuel_type'],
        'color'         => $row['color'],
        'plate_number'  => $row['plate_number'],
        'tank_capacity' => $row['tank_capacity'] !== null ? (float)$row['tank_capacity'] : null,
        'odometer'      => $row['odometer']      !== null ? (int)$row['odometer']         : null,
        'is_archived'   => (bool)$row['is_archived'],
        'is_default'    => $has_is_default ? (bool)$row['is_default'] : false,
    ];
}
$stmt->close();

echo json_encode(['vehicles' => $vehicles]);
