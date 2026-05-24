<?php
$dashboard_title = 'Dashboard';
$dashboard_subtitle = 'Your fuel tracking overview';

include_once __DIR__ . '/../includes/header.php';
?>

<div class="db-wrap">

  <div class="db-topbar">
    <div>
      <div class="db-title"><?= htmlspecialchars($dashboard_title) ?></div>
      <div class="db-subtitle"><?= htmlspecialchars($dashboard_subtitle) ?></div>
    </div>
    <div class="db-topbar-actions" id="db-topbar-actions" style="display:none;">
      <div id="db-vehicle-switcher-container"></div>
      <div id="db-time-switcher-container"></div>
      <button class="mm-btn mm-btn-primary mm-btn-sm" id="db-log-fillup-btn" aria-label="Open Fuel Log and start logging a fill-up">+ Log Fill-Up</button>
    </div>
  </div>

  <section class="db-overview-stack" id="db-overview-stack">
    <!-- Skeleton loader for stats -->
    <div class="db-stats" id="db-stats-container">
      <div class="db-card"><div class="hist-skeleton-row"></div></div>
      <div class="db-card"><div class="hist-skeleton-row"></div></div>
      <div class="db-card"><div class="hist-skeleton-row"></div></div>
    </div>

    <div id="db-fleet-section"></div>

    <!-- Skeleton loader for fillups -->
    <div class="db-section" id="db-fillups-section">
      <div class="db-section-head">
        <div class="db-section-title">Recent Fill-ups</div>
      </div>
      <div class="hist-skeleton-row"></div>
      <div class="hist-skeleton-row"></div>
      <div class="hist-skeleton-row"></div>
    </div>
  </section>

</div>

<!-- Fuel Log Modal -->
<?php include __DIR__ . '/../includes/components/fillup_modal.php'; ?>
<script src="js/fillup-modal.js"></script>
<script src="js/dashboard.js"></script>

<link rel="stylesheet" href="css/popover.css">
<script src="js/popover.js"></script>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
