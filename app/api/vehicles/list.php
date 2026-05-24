<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/includes/api_helpers.php';

api_require_auth();
header('Content-Type: application/json');
api_require_method('GET');

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT id, name, make, model, year, fuel_type, color, plate_number, tank_capacity, is_archived, is_default,
           COALESCE(
             (SELECT odometer FROM fuel_logs
              WHERE user_id = ? AND vehicle_id = v.id
              ORDER BY log_date DESC, id DESC LIMIT 1),
             v.odometer
           ) AS current_odometer
    FROM vehicles v
    WHERE user_id = ?
    ORDER BY is_archived ASC, is_default DESC, name ASC
");
$stmt->bind_param('ii', $user_id, $user_id);
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
        'odometer'      => $row['current_odometer'] !== null ? (int)$row['current_odometer'] : null,
        'is_archived'   => (bool)$row['is_archived'],
        'is_default'    => (bool)$row['is_default'],
    ];
}
$stmt->close();

echo json_encode(['vehicles' => $vehicles]);
