<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../app/includes/api_helpers.php';

api_require_auth();
header('Content-Type: application/json');
api_require_method('GET');

$user_id    = (int)$_SESSION['user_id'];
$vehicle_id = isset($_GET['vehicle_id']) && $_GET['vehicle_id'] !== '' ? (int)$_GET['vehicle_id'] : null;
$time_range = isset($_GET['time_range']) && in_array($_GET['time_range'], ['all_time', 'this_month'], true) ? $_GET['time_range'] : 'all_time';

$vehicles = [];
$stmt = $conn->prepare("SELECT id, name, is_default FROM vehicles WHERE user_id = ? AND is_archived = 0 ORDER BY is_default DESC, name ASC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $vehicles[] = [
        'id'         => (int)$row['id'],
        'name'       => $row['name'],
        'is_default' => (bool)$row['is_default'],
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

$stats_query = "
    SELECT
        COUNT(*)                                                                                AS total_fillups,
        SUM(fuel_price)                                                                         AS total_spent,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN 1          ELSE 0 END)           AS month_fillups,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN fuel_price ELSE 0 END)           AS month_spent,
        SUM(trip_distance)                                                                       AS total_distance,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN trip_distance ELSE NULL END)      AS month_total_distance
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
    'total_fillups'        => (int)($agg['total_fillups'] ?? 0),
    'month_fillups'        => (int)($agg['month_fillups'] ?? 0),
    'total_spent'          => (float)($agg['total_spent'] ?? 0),
    'month_spent'          => (float)($agg['month_spent'] ?? 0),
    'total_distance'       => ($agg['total_distance'] !== null) ? (float)$agg['total_distance'] : null,
    'month_total_distance' => ($agg['month_total_distance'] !== null) ? (float)$agg['month_total_distance'] : null,
];

$fleet = [];
if (count($vehicles) > 0) {
    $fleet_base_query = "
        SELECT
            v.id,
            v.name,
            SUM(fl.trip_distance) / NULLIF(SUM(fl.liters_filled), 0) AS avg_kml,
            SUM(fl.fuel_price) / NULLIF(SUM(fl.trip_distance), 0)    AS avg_cost_km,
            SUM(fl.fuel_price)                                        AS total_spent,
            COUNT(*)                                                  AS total_fillups
        FROM fuel_logs fl
        JOIN vehicles v ON v.id = fl.vehicle_id
        WHERE fl.user_id = ? AND v.is_archived = 0
    ";
    if ($time_range === 'this_month') {
        $fleet_base_query .= " AND MONTH(fl.log_date) = MONTH(CURDATE()) AND YEAR(fl.log_date) = YEAR(CURDATE())";
    }
    $fleet_base_query .= " GROUP BY v.id, v.name ORDER BY avg_kml DESC";

    $fleet_stmt = $conn->prepare($fleet_base_query);
    $fleet_stmt->bind_param('i', $user_id);
    $fleet_stmt->execute();
    $fleet_res = $fleet_stmt->get_result();
    while ($fr = $fleet_res->fetch_assoc()) {
        $fleet[] = [
            'id'            => (int)$fr['id'],
            'name'          => $fr['name'],
            'avg_kml'       => $fr['avg_kml'] !== null ? (float)$fr['avg_kml'] : null,
            'avg_cost_km'   => $fr['avg_cost_km'] !== null ? (float)$fr['avg_cost_km'] : null,
            'total_spent'   => (float)$fr['total_spent'],
            'total_fillups' => (int)$fr['total_fillups'],
        ];
    }
    $fleet_stmt->close();
}

$fillups = [];
$vehicle_filter = $vehicle_id ? " AND fl.vehicle_id = {$vehicle_id}" : "";
$time_filter = ($time_range === 'this_month')
    ? " AND MONTH(fl.log_date) = MONTH(CURDATE()) AND YEAR(fl.log_date) = YEAR(CURDATE())"
    : "";
$logs_query = "
    WITH ranked AS (
        SELECT
            fl.id,
            fl.log_date,
            fl.vehicle_id,
            fl.is_full_tank,
            fl.liters_filled,
            fl.cost_per_liter,
            fl.fuel_price,
            fl.trip_distance,
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
        WHERE fl.user_id = {$user_id}{$vehicle_filter}{$time_filter}
    )
    SELECT
        r.id,
        r.log_date,
        r.vehicle_id,
        r.is_full_tank,
        r.liters_filled,
        r.cost_per_liter,
        r.fuel_price,
        r.trip_distance,
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
    ORDER BY r.log_date DESC, r.id DESC LIMIT 5
";

$result = $conn->query($logs_query);
if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Logs query failed: ' . $conn->error]);
    exit;
}
while ($row = $result->fetch_assoc()) {
    $fillups[] = [
        'date'           => $row['log_date'],
        'vehicle_name'   => $row['vehicle_name'],
        'is_full_tank'   => (bool)$row['is_full_tank'],
        'liters_filled'  => (float)$row['liters_filled'],
        'cost_per_liter' => (float)$row['cost_per_liter'],
        'fuel_price'     => (float)$row['fuel_price'],
        'efficiency_kml' => $row['efficiency_kml'] !== null ? (float)$row['efficiency_kml'] : null,
        'cost_per_km'    => $row['cost_per_km'] !== null ? (float)$row['cost_per_km'] : null,
        'prior_efficiency_kml' => $row['prior_efficiency_kml'] !== null ? (float)$row['prior_efficiency_kml'] : null,
        'prior_cost_per_km'    => $row['prior_cost_per_km'] !== null ? (float)$row['prior_cost_per_km'] : null,
        'trip_distance'  => $row['trip_distance'] !== null ? (float)$row['trip_distance'] : null,
        'notes'          => $row['notes'],
    ];
}

echo json_encode([
    'stats'               => $stats,
    'vehicles'            => $vehicles,
    'active_vehicle_name' => $active_vehicle_name,
    'has_vehicles'        => count($vehicles) > 0,
    'fillups'             => $fillups,
    'fleet'               => $fleet,
]);
