<?php
require_once __DIR__ . '/../../config/db.php';

$user_id = (int)$_SESSION['user_id'];
$dashboard_title = 'Dashboard';
$dashboard_subtitle = 'Your fuel tracking overview';

// Aggregate stats
$stmt = $conn->prepare("
    SELECT
        COUNT(*)                                                                        AS total_fillups,
        SUM(fuel_price)                                                                 AS total_spent,
        AVG(CASE WHEN efficiency_l100km > 0 THEN 100.0 / efficiency_l100km END)        AS avg_kml,
        AVG(cost_per_km)                                                                AS avg_cost_km,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN 1          ELSE 0 END)   AS month_fillups,
        SUM(CASE WHEN MONTH(log_date) = MONTH(CURDATE())
                  AND  YEAR(log_date) =  YEAR(CURDATE()) THEN fuel_price ELSE 0 END)   AS month_spent
    FROM fuel_logs
    WHERE user_id = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$agg = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_fillups = (int)($agg['total_fillups'] ?? 0);
$month_fillups = (int)($agg['month_fillups'] ?? 0);
$total_spent   = (float)($agg['total_spent']  ?? 0);
$month_spent   = (float)($agg['month_spent']  ?? 0);
$avg_kml       = ($agg['avg_kml']     !== null) ? (float)$agg['avg_kml']     : null;
$avg_cost_km   = ($agg['avg_cost_km'] !== null) ? (float)$agg['avg_cost_km'] : null;

// Vehicle check
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_vehicles
    FROM vehicles
    WHERE user_id = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$vehicle_totals = $stmt->get_result()->fetch_assoc();
$stmt->close();
$total_vehicles = (int)($vehicle_totals['total_vehicles'] ?? 0);
$has_vehicles = $total_vehicles > 0;

// Recent fill-ups (last 5)
$fillups = [];
$stmt = $conn->prepare("
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
    ORDER BY fl.log_date DESC, fl.id DESC
    LIMIT 5
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $kml = ($row['efficiency_kml'] !== null) ? (float)$row['efficiency_kml'] : null;
    if    ($kml === null)  { $badge = 'yellow'; $status = 'N/A'; }
    elseif ($kml >= 12.5)  { $badge = 'green';  $status = 'Good'; }
    elseif ($kml >= 10)    { $badge = 'yellow'; $status = 'Average'; }
    else                   { $badge = 'red';    $status = 'Poor'; }

    $fillups[] = [
        'date'        => date('M j, Y', strtotime($row['log_date'])),
        'station'     => $row['vehicle_name'],
        'liters'      => number_format((float)$row['liters_filled'], 1) . ' L',
        'price_per_l' => '&#8369;' . number_format((float)$row['cost_per_liter'], 2),
        'total'       => '&#8369;' . number_format((float)$row['fuel_price'], 0),
        'efficiency'  => $kml !== null ? number_format($kml, 1) . ' km/L' : '&mdash;',
        'badge'       => $badge,
        'status'      => $status,
        'notes'       => $row['notes'] !== null ? trim($row['notes']) : '',
    ];
}
$stmt->close();

// Build stat cards
if ($total_fillups === 0) {
    $stats = [
        ['label' => 'Total Fill-ups', 'value' => '&mdash;', 'sub_html' => 'Start logging fill-ups'],
        ['label' => 'Avg Efficiency', 'value' => '&mdash;', 'sub_html' => '&mdash;'],
        ['label' => 'Total Spent',    'value' => '&mdash;', 'sub_html' => '&mdash;'],
        ['label' => 'Avg Cost / km',  'value' => '&mdash;', 'sub_html' => '&mdash;'],
    ];
} else {
    $stats = [
        [
            'label'    => 'Total Fill-ups',
            'value'    => $total_fillups,
            'sub_html' => 'This month: ' . $month_fillups,
        ],
        [
            'label'    => 'Avg Efficiency',
            'value'    => $avg_kml !== null ? number_format($avg_kml, 1) : '&mdash;',
            'sub_html' => 'km/L all time',
        ],
        [
            'label'    => 'Total Spent',
            'value'    => '&#8369;' . number_format($total_spent, 0),
            'sub_html' => 'This month: &#8369;' . number_format($month_spent, 0),
        ],
        [
            'label'    => 'Avg Cost / km',
            'value'    => $avg_cost_km !== null ? '&#8369;' . number_format($avg_cost_km, 2) : '&mdash;',
            'sub_html' => 'all time',
        ],
    ];
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="db-wrap">

  <div class="db-topbar">
    <div>
      <div class="db-title"><?= htmlspecialchars($dashboard_title) ?></div>
      <div class="db-subtitle"><?= htmlspecialchars($dashboard_subtitle) ?></div>
    </div>
    <div class="db-topbar-actions">
      <?php if ($has_vehicles): ?>
        <a class="mm-btn mm-btn-primary mm-btn-sm" href="?page=quick-log" aria-label="Open Quick Log and start logging a fill-up">Log Fill-Up</a>
      <?php endif; ?>
    </div>
  </div>

  <section class="db-overview-stack">
    <div class="db-stats">
      <?php foreach ($stats as $stat): ?>
        <?php include __DIR__ . '/../includes/components/stat_card.php'; ?>
      <?php endforeach; ?>
    </div>

    <?php include __DIR__ . '/../includes/components/fillups_section.php'; ?>
  </section>

</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
