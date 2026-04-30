<?php
$page = 'history';
include_once __DIR__ . '/../includes/header.php';
?>

<div class="hist-wrap">

  <div class="hist-topbar">
    <div>
      <div class="hist-title">History</div>
      <div class="hist-subtitle">All fill-ups for your selected vehicle, newest first</div>
    </div>
    <div class="hist-topbar-actions" id="hist-topbar-actions" style="display:none">
      <div id="hist-vehicle-switcher-container"></div>
      <button class="mm-btn mm-btn-primary mm-btn-sm" id="hist-log-fillup-btn" aria-label="Open fuel log modal and start logging a fill-up">+ Log Fill-Up</button>
    </div>
  </div>

  <div id="hist-content-area">
    <div class="hist-section">
      <div class="hist-table-wrap" id="hist-table-wrap">
        <div class="hist-skeleton-row"></div>
        <div class="hist-skeleton-row"></div>
        <div class="hist-skeleton-row"></div>
        <div class="hist-skeleton-row"></div>
      </div>
      <div class="hist-load-more-wrap" id="hist-load-more-wrap" style="display:none">
        <button class="hist-load-more-btn" id="hist-load-more-btn">Load more</button>
      </div>
    </div>
  </div>

</div>

<!-- Fuel Log Modal -->
<?php include __DIR__ . '/../includes/components/fillup_modal.php'; ?>

<script src="js/fillup-modal.js"></script>
<script src="js/history.js"></script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
