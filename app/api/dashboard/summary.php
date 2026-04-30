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
$vehicle_id = isset($_GET['vehicle_id']) && $_GET['vehicle_id'] !== '' ? (int)$_GET['vehicle_id'] : null;

// Get all active vehicles for the switcher
$vehicles = [];
$has_is_default = false;
$check = $conn->query("SHOW COLUMNS FROM vehicles LIKE 'is_default'");
if ($check && $check->num_rows > 0) {
    $has_is_default = true;
}

$orderBy = $has_is_default ? "is_default DESC, name ASC" : "name ASC";
$selectFields = $has_is_default ? "id, name, is_default" : "id, name";

$stmt = $conn->prepare("SELECT $selectFields FROM vehicles WHERE user_id = ? AND is_archived = 0 ORDER BY $orderBy");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $vehicles[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'is_default' => $has_is_default ? (bool)$row['is_default'] : false
    ];
}
$stmt->close();

$active_vehicle_name = 'All Vehicles';
if ($vehicle_id) {
    foreach ($vehicles as $v) {
        if ($v['id'] === $vehicle_id) {
            $active_vehicle_name = $v['name'];
            break;
        }
    }
}

// Aggregate stats
$stats_query = "
    SELECT
        COUNT(*)                                                                        AS total_fillups,
        SUM(fuel_price)                                                                 AS total_spent,
        AVG(CASE WHEN efficiency_l100km > 0 THEN 100.0 / efficiency_l100km END)        AS avg_kml,
        AVG(cost_per_km)                                                                AS avg_cost_km,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN 1          ELSE 0 END)   AS month_fillups,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN fuel_price ELSE 0 END)   AS month_spent,
        AVG(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE())
                  AND efficiency_l100km > 0 THEN 100.0 / efficiency_l100km END)        AS month_avg_kml,
        AVG(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN cost_per_km END)         AS month_avg_cost_km
    FROM fuel_logs
    WHERE user_id = ?
";

if ($vehicle_id) {
    $stats_query .= " AND vehicle_id = ?";
}

$stmt = $conn->prepare($stats_query);
if ($vehicle_id) {
    $stmt->bind_param('ii', $user_id, $vehicle_id);
} else {
    $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$agg = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stats = [
    'total_fillups'     => (int)($agg['total_fillups'] ?? 0),
    'month_fillups'     => (int)($agg['month_fillups'] ?? 0),
    'total_spent'       => (float)($agg['total_spent'] ?? 0),
    'month_spent'       => (float)($agg['month_spent'] ?? 0),
    'avg_kml'           => ($agg['avg_kml'] !== null) ? (float)$agg['avg_kml'] : null,
    'month_avg_kml'     => ($agg['month_avg_kml'] !== null) ? (float)$agg['month_avg_kml'] : null,
    'avg_cost_km'       => ($agg['avg_cost_km'] !== null) ? (float)$agg['avg_cost_km'] : null,
    'month_avg_cost_km' => ($agg['month_avg_cost_km'] !== null) ? (float)$agg['month_avg_cost_km'] : null,
];

// Recent fill-ups
$fillups = [];
$logs_query = "
    SELECT
        fl.log_date,
        v.name                                                                          AS vehicle_name,
        fl.liters_filled,
        fl.cost_per_liter,
        fl.fuel_price,
        fl.notes,
        CASE WHEN fl.efficiency_l100km > 0 THEN 100.0 / fl.efficiency_l100km END       AS efficiency_kml
    FROM fuel_logs fl
    JOIN vehicles v ON v.id = fl.vehicle_id
    WHERE fl.user_id = ?
";

if ($vehicle_id) {
    $logs_query .= " AND fl.vehicle_id = ?";
}

$logs_query .= " ORDER BY fl.log_date DESC, fl.id DESC LIMIT 5";

$stmt = $conn->prepare($logs_query);
if ($vehicle_id) {
    $stmt->bind_param('ii', $user_id, $vehicle_id);
} else {
    $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $fillups[] = [
        'date'           => $row['log_date'],
        'station'        => $row['vehicle_name'],
        'liters_filled'  => (float)$row['liters_filled'],
        'cost_per_liter' => (float)$row['cost_per_liter'],
        'fuel_price'     => (float)$row['fuel_price'],
        'efficiency_kml' => $row['efficiency_kml'] !== null ? (float)$row['efficiency_kml'] : null,
        'notes'          => $row['notes'],
    ];
}
$stmt->close();

echo json_encode([
    'stats'               => $stats,
    'vehicles'            => $vehicles,
    'active_vehicle_name' => $active_vehicle_name,
    'has_vehicles'        => count($vehicles) > 0,
    'fillups'             => $fillups,
]);

