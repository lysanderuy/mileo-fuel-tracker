<?php
// Renders the shared vehicles list sections.
// Vehicles are loaded client-side by public/js/vehicles.js.
?>

<div class="veh-section" id="veh-active-section">
  <div class="veh-section-head veh-tabs">
    <div class="veh-tabbar" role="tablist" aria-label="Vehicle groups">
      <button class="veh-tab is-active" id="veh-tab-active" type="button" role="tab" aria-selected="true" aria-controls="veh-active-panel">
        <span>Active</span>
        <span class="veh-tab-count" id="veh-active-count"></span>
      </button>
      <button class="veh-tab" id="veh-tab-archived" type="button" role="tab" aria-selected="false" aria-controls="veh-archived-panel">
        <span>Archived</span>
        <span class="veh-tab-count" id="veh-archived-count"></span>
      </button>
    </div>
  </div>

  <div class="veh-panel" id="veh-active-panel" role="tabpanel" aria-labelledby="veh-tab-active">
    <div id="veh-active-list" class="veh-list">
      <div class="veh-skeleton-row"></div>
      <div class="veh-skeleton-row"></div>
    </div>
  </div>

  <div class="veh-panel" id="veh-archived-panel" role="tabpanel" aria-labelledby="veh-tab-archived" style="display:none">
    <div id="veh-archived-list" class="veh-list"></div>
  </div>
</div>
