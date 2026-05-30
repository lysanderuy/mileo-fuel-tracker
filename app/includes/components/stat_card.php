<?php
// Caller sets:
// $stat = [
//   'label'    => string,
//   'value'    => string (raw HTML — may contain HTML entities),
//   'sub_html' => string (raw HTML — may contain <span class="up/down">),
// ]
?>
<div class="db-card">
  <div class="db-card-label"><?= htmlspecialchars($stat['label']) ?></div>
  <div class="db-card-value"><?= $stat['value'] ?></div>
  <div class="db-card-sub"><?= $stat['sub_html'] ?></div>
</div>
