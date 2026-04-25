<?php
require_once __DIR__ . '/../../config/db.php';

$page = 'quick-log';
$user_id = (int)$_SESSION['user_id'];

$vehicles = [];
$stmt = $conn->prepare("
    SELECT id, name, make, model, year, fuel_type, plate_number
    FROM vehicles
    WHERE user_id = ? AND is_archived = 0
    ORDER BY created_at ASC, id ASC
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['id'] = (int)$row['id'];
    $row['last_odometer'] = null;
    $vehicles[] = $row;
}
$stmt->close();

if (!empty($vehicles)) {
    $stmt = $conn->prepare("
        SELECT vehicle_id, odometer
        FROM fuel_logs
        WHERE user_id = ?
        ORDER BY vehicle_id ASC, log_date DESC, id DESC
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $last_result = $stmt->get_result();

    $last_odometers = [];
    while ($last_row = $last_result->fetch_assoc()) {
        $vehicle_id = (int)$last_row['vehicle_id'];
        if (!array_key_exists($vehicle_id, $last_odometers)) {
            $last_odometers[$vehicle_id] = (int)$last_row['odometer'];
        }
    }
    $stmt->close();

    foreach ($vehicles as &$vehicle) {
        if (array_key_exists($vehicle['id'], $last_odometers)) {
            $vehicle['last_odometer'] = $last_odometers[$vehicle['id']];
        }
    }
    unset($vehicle);
}

$has_vehicles = count($vehicles) > 0;
$selected_vehicle_id = $has_vehicles ? (int)$vehicles[0]['id'] : null;

include_once __DIR__ . '/../includes/header.php';
?>

<div class="ql-wrap">
  <div class="ql-topbar">
    <div>
      <div class="ql-title">Quick Log</div>
      <div class="ql-subtitle">Capture your latest fill-up in seconds.</div>
    </div>
  </div>

  <?php if (!$has_vehicles): ?>
    <section class="ql-section">
      <div class="ql-empty">
        <span class="ql-empty-icon">⛽</span>
        <div class="ql-empty-title">No active vehicles yet</div>
        <div class="ql-empty-sub">Add a vehicle first to start logging your fuel fill-ups.</div>
        <a class="mm-btn mm-btn-primary mm-btn-sm" href="?page=vehicles">Add Vehicle</a>
      </div>
    </section>
  <?php else: ?>
    <section class="ql-section">
      <div class="ql-section-head">
        <div class="ql-section-title">Fill-up Details</div>
      </div>

      <form class="ql-form" action="#" method="post">
        <div class="ql-field">
          <label for="vehicle_id">Vehicle</label>
          <select id="vehicle_id" name="vehicle_id" class="ql-select" required>
            <?php foreach ($vehicles as $vehicle): ?>
              <option value="<?= (int)$vehicle['id'] ?>" <?= ((int)$vehicle['id'] === $selected_vehicle_id) ? 'selected' : '' ?>>
                <?= htmlspecialchars($vehicle['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="ql-grid">
          <div class="ql-field">
            <label for="log_date">Date</label>
            <input id="log_date" class="ql-input" type="date" name="log_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="ql-field">
            <label for="odometer">Odometer (km)</label>
            <input id="odometer" class="ql-input" type="number" name="odometer" min="0" step="1" placeholder="e.g. 52340" required>
            <p id="odometer-note" class="ql-help"></p>
            <p id="odometer-error" class="ql-error" role="alert"></p>
          </div>
        </div>

        <div class="ql-grid">
          <div class="ql-field">
            <label for="trip_distance">Trip Distance (km)</label>
            <div class="ql-trip-row">
              <input id="trip_distance" class="ql-input" type="number" name="trip_distance" min="0" step="0.01" placeholder="e.g. 128.4" required>
              <button id="trip-distance-edit" type="button" class="ql-edit-btn" aria-label="Override auto-computed trip distance" title="Edit trip distance">✎</button>
            </div>
            <p id="trip-distance-note" class="ql-help"></p>
          </div>
          <div class="ql-field">
            <label for="liters_filled">Liters Filled</label>
            <input id="liters_filled" class="ql-input" type="number" name="liters_filled" min="0" step="0.01" placeholder="e.g. 38.5" required>
          </div>
        </div>

        <div class="ql-field">
          <label for="fuel_price">Total Price (PHP)</label>
          <input id="fuel_price" class="ql-input" type="number" name="fuel_price" min="0" step="0.01" placeholder="e.g. 2450" required>
        </div>

        <div class="ql-field">
          <label for="is_full_tank">Full Tank</label>
          <div class="ql-toggle-row">
            <label class="ql-switch" aria-label="Full tank toggle">
              <input id="is_full_tank" name="is_full_tank" type="checkbox" checked>
              <span class="ql-slider" aria-hidden="true"></span>
            </label>
            <span id="full-tank-value" class="ql-toggle-label">Yes, full tank</span>
          </div>
          <p id="full-tank-note" class="ql-coach-note" hidden>Partial fill - efficiency estimate may vary.</p>
        </div>

        <div class="ql-notes-wrap">
          <button
            id="notes-toggle"
            class="ql-notes-toggle"
            type="button"
            aria-expanded="false"
            aria-controls="notes-panel"
          >
            <span class="ql-notes-toggle-text">Add Notes (Optional)</span>
            <span id="notes-toggle-icon" class="ql-notes-toggle-icon" aria-hidden="true">+</span>
          </button>
          <div id="notes-panel" class="ql-notes-panel" hidden>
            <label class="ql-notes-label" for="notes">Notes</label>
            <textarea
              id="notes"
              name="notes"
              class="ql-textarea"
              maxlength="200"
              rows="3"
              placeholder="e.g. Highway trip, heavy traffic, aircon on full"
            ></textarea>
            <div id="notes-count" class="ql-notes-count">0 / 200</div>
          </div>
        </div>

        <div class="ql-actions">
          <button type="button" class="mm-btn mm-btn-secondary mm-btn-sm" onclick="location.href='?page=dashboard'">Back to Dashboard</button>
          <button type="submit" class="mm-btn mm-btn-primary mm-btn-sm">Save Fill-Up</button>
        </div>
      </form>
      <script id="quick-log-vehicles-data" type="application/json"><?= htmlspecialchars(json_encode($vehicles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?></script>
    </section>
  <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
