<?php
$page = 'vehicles';
include_once __DIR__ . '/../includes/header.php';
?>

<div class="veh-wrap">

  <div class="veh-topbar">
    <div>
      <div class="veh-title">My Vehicles</div>
      <div class="veh-subtitle">Manage your fleet from a dedicated workspace</div>
    </div>
    <div class="veh-topbar-actions">
      <button class="mm-btn mm-btn-primary mm-btn-sm" id="veh-add-btn">+ Add Vehicle</button>
    </div>
  </div>

  <?php include __DIR__ . '/../includes/components/vehicles_section.php'; ?>

</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
