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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user_id    = (int)$_SESSION['user_id'];
$vehicle_id = isset($_GET['vehicle_id']) && $_GET['vehicle_id'] !== '' ? (int)$_GET['vehicle_id'] : 0;
$page       = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page   = 20;
$offset     = ($page - 1) * $per_page;

$count_query = "SELECT COUNT(*) AS total FROM fuel_logs WHERE user_id = ?";
if ($vehicle_id > 0) {
    $count_query .= " AND vehicle_id = ?";
}

$stmt = $conn->prepare($count_query);
if ($vehicle_id > 0) {
    $stmt->bind_param('ii', $user_id, $vehicle_id);
} else {
    $stmt->bind_param('i', $user_id);
}
$stmt->execute();
$count_row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$total = (int)($count_row['total'] ?? 0);

$vehicle_filter = $vehicle_id > 0 ? " AND fl.vehicle_id = {$vehicle_id}" : "";
$logs_query = "
    WITH ranked AS (
        SELECT
            fl.id,
            fl.log_date,
            fl.vehicle_id,
            fl.fuel_price,
            fl.cost_per_liter,
            fl.liters_filled,
            fl.trip_distance,
            fl.odometer,
            fl.is_full_tank,
            fl.notes,
            fl.cost_per_km,
            CASE WHEN fl.is_full_tank = 1 THEN (
                SELECT SUM(fl2.liters_filled) / NULLIF(
                    fl.odometer - (
                        SELECT pf.odometer FROM fuel_logs pf
                        WHERE pf.vehicle_id = fl.vehicle_id AND pf.user_id = {$user_id}
                          AND pf.is_full_tank = 1
                          AND (pf.log_date < fl.log_date OR (pf.log_date = fl.log_date AND pf.id < fl.id))
                        ORDER BY pf.log_date DESC, pf.id DESC LIMIT 1
                    ), 0
                ) * 100
                FROM fuel_logs fl2
                WHERE fl2.vehicle_id = fl.vehicle_id AND fl2.user_id = {$user_id}
                  AND (fl2.log_date > (
                          SELECT pf.log_date FROM fuel_logs pf
                          WHERE pf.vehicle_id = fl.vehicle_id AND pf.user_id = {$user_id}
                            AND pf.is_full_tank = 1
                            AND (pf.log_date < fl.log_date OR (pf.log_date = fl.log_date AND pf.id < fl.id))
                          ORDER BY pf.log_date DESC, pf.id DESC LIMIT 1
                      )
                      OR (fl2.log_date = (
                              SELECT pf.log_date FROM fuel_logs pf
                              WHERE pf.vehicle_id = fl.vehicle_id AND pf.user_id = {$user_id}
                                AND pf.is_full_tank = 1
                                AND (pf.log_date < fl.log_date OR (pf.log_date = fl.log_date AND pf.id < fl.id))
                              ORDER BY pf.log_date DESC, pf.id DESC LIMIT 1
                          )
                          AND fl2.id > (
                              SELECT pf.id FROM fuel_logs pf
                              WHERE pf.vehicle_id = fl.vehicle_id AND pf.user_id = {$user_id}
                                AND pf.is_full_tank = 1
                                AND (pf.log_date < fl.log_date OR (pf.log_date = fl.log_date AND pf.id < fl.id))
                              ORDER BY pf.log_date DESC, pf.id DESC LIMIT 1
                          )))
                  AND (fl2.log_date < fl.log_date OR (fl2.log_date = fl.log_date AND fl2.id <= fl.id))
            ) END AS efficiency_l100km
        FROM fuel_logs fl
        WHERE fl.user_id = {$user_id}{$vehicle_filter}
    )
    SELECT
        r.id,
        r.log_date,
        r.vehicle_id,
        r.fuel_price,
        r.cost_per_liter,
        r.liters_filled,
        r.trip_distance,
        r.odometer,
        r.is_full_tank,
        r.notes,
        r.cost_per_km,
        CASE WHEN r.efficiency_l100km > 0 THEN 100.0 / r.efficiency_l100km END AS efficiency_kml,
        LAG(CASE WHEN r.efficiency_l100km > 0 THEN 100.0 / r.efficiency_l100km END)
            OVER (PARTITION BY r.vehicle_id ORDER BY r.log_date ASC, r.id ASC)  AS prior_efficiency_kml,
        LAG(r.cost_per_km)
            OVER (PARTITION BY r.vehicle_id ORDER BY r.log_date ASC, r.id ASC)  AS prior_cost_per_km,
        v.name AS vehicle_name
    FROM ranked r
    JOIN vehicles v ON v.id = r.vehicle_id
    ORDER BY r.log_date DESC, r.id DESC LIMIT {$per_page} OFFSET {$offset}
";

$result = $conn->query($logs_query);

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = [
        'id'             => (int)$row['id'],
        'log_date'       => $row['log_date'],
        'fuel_price'     => (float)$row['fuel_price'],
        'cost_per_liter' => $row['cost_per_liter'] !== null ? (float)$row['cost_per_liter'] : null,
        'liters_filled'  => (float)$row['liters_filled'],
        'trip_distance'  => (float)$row['trip_distance'],
        'odometer'       => (int)$row['odometer'],
        'is_full_tank'   => (bool)$row['is_full_tank'],
        'efficiency_kml' => $row['efficiency_kml'] !== null ? (float)$row['efficiency_kml'] : null,
        'cost_per_km'    => $row['cost_per_km'] !== null ? (float)$row['cost_per_km'] : null,
        'prior_efficiency_kml' => $row['prior_efficiency_kml'] !== null ? (float)$row['prior_efficiency_kml'] : null,
        'prior_cost_per_km'    => $row['prior_cost_per_km'] !== null ? (float)$row['prior_cost_per_km'] : null,
        'notes'          => $row['notes'],
        'vehicle_name'   => $row['vehicle_name']
    ];
}

echo json_encode([
    'logs'     => $logs,
    'total'    => $total,
    'page'     => $page,
    'per_page' => $per_page,
    'has_more' => ($offset + count($logs)) < $total,
]);

