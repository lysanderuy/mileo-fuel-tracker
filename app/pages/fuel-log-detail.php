<?php
$page = 'fuel-log-detail';
include_once __DIR__ . '/../includes/header.php';
?>
<div class="fld-wrap">
  <div id="fld-skeleton">
    <div class="fld-skeleton-header"></div>
    <div class="fld-skeleton-card"></div>
    <div class="fld-skeleton-grid"></div>
  </div>
  <div id="fld-content" hidden></div>
  <div id="fld-error" hidden></div>
</div>
<?php include __DIR__ . '/../includes/components/fillup_modal.php'; ?>
<div class="hist-confirm-overlay" id="fld-confirm-overlay" hidden>
  <div class="hist-confirm-modal" role="alertdialog" aria-modal="true" aria-labelledby="fld-confirm-title">
    <h2 class="hist-confirm-title" id="fld-confirm-title">Delete fill-up?</h2>
    <p class="hist-confirm-body">This fill-up will be permanently removed. If a later fill-up exists, its trip distance will be recomputed.</p>
    <div class="hist-confirm-actions">
      <button type="button" class="mm-btn mm-btn-secondary mm-btn-sm" id="fld-confirm-cancel-btn">Cancel</button>
      <button type="button" class="mm-btn mm-btn-danger mm-btn-sm" id="fld-confirm-delete-btn">Delete</button>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../includes/footer.php'; ?>
