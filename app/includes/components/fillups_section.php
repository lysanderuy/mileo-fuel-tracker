<?php
// Caller sets:
// $fillups = [] for empty state, or an array of rows where each row has:
//   'date', 'station', 'liters', 'price_per_l' (raw HTML), 'total' (raw HTML),
//   'efficiency', 'badge' ('green'|'yellow'), 'status'
?>
<div class="db-section">

  <div class="db-section-head">
    <div class="db-section-title">Recent Fill-ups</div>
  </div>

  <?php if (empty($fillups) && empty($has_vehicles)): ?>

    <div class="db-empty">
      <div class="db-empty-icon">&#128663;</div>
      <div class="db-empty-title">Start by adding your vehicle</div>
      <div class="db-empty-sub">Before you can track fuel, you'll need to <a href="#" class="db-empty-link">add a vehicle</a> first.</div>
    </div>

  <?php elseif (empty($fillups)): ?>

    <div class="db-empty">
      <div class="db-empty-icon">&#9981;</div>
      <div class="db-empty-title">No fill-ups logged yet</div>
      <div class="db-empty-sub"><a href="#" class="db-empty-link">Log a fill-up</a> to start seeing your efficiency, cost, and spending trends here.</div>
    </div>

  <?php else: ?>

    <table class="db-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Vehicle</th>
          <th>Liters</th>
          <th>Price / L</th>
          <th>Total</th>
          <th>Efficiency</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($fillups as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['date']) ?></td>
            <td><?= htmlspecialchars($row['station']) ?></td>
            <td><?= htmlspecialchars($row['liters']) ?></td>
            <td><?= $row['price_per_l'] ?></td>
            <td><?= $row['total'] ?></td>
            <td><?= htmlspecialchars($row['efficiency']) ?></td>
            <td><span class="db-badge db-badge-<?= htmlspecialchars($row['badge']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

  <?php endif; ?>

</div>
