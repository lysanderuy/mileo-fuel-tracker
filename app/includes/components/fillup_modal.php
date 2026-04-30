<!-- Modal for logging fuel fill-ups -->
<div class="hist-modal-overlay" id="hist-modal-overlay" hidden>
  <div class="hist-modal" id="hist-modal">
    <div class="hist-modal-head">
      <h2 class="hist-modal-title">Log Fill-Up</h2>
      <button class="hist-modal-close" id="hist-modal-close" aria-label="Close modal">×</button>
    </div>

    <div class="hist-modal-body">
      <form class="hist-fuel-form" id="hist-fuel-form">
        
        <!-- 1. Context Section -->
        <div class="hist-form-section context-section">
          <div class="hist-form-field">
            <label for="hist-vehicle_id">Vehicle</label>
            <select id="hist-vehicle_id" name="vehicle_id" class="hist-form-select" required>
              <!-- Options populated via JS -->
            </select>
          </div>

          <div class="hist-form-field">
            <label for="hist-log_date">Date</label>
            <input id="hist-log_date" class="hist-form-input" type="date" name="log_date" required>
          </div>

          <div class="hist-form-field odometer-field">
            <label for="hist-odometer">Odometer (km)</label>
            <input id="hist-odometer" class="hist-form-input odometer-primary" type="number" name="odometer" min="0" step="1" placeholder="Current reading" required>
            <p id="hist-odometer-helper" class="hist-form-help">Last recorded: -- km</p>
            <p id="hist-odometer-error" class="hist-form-error" role="alert"></p>
          </div>
        </div>

        <!-- 2. Fuel Input Section -->
        <div class="hist-form-section fuel-section">
          <div class="fuel-inputs-paired">
            <div class="hist-form-field">
              <label for="hist-liters_filled">Liters Filled</label>
              <input id="hist-liters_filled" class="hist-form-input" type="number" name="liters_filled" min="0" step="0.01" placeholder="0.00" required>
            </div>
            <div class="hist-form-field">
              <label for="hist-fuel_price">Total Price (PHP)</label>
              <input id="hist-fuel_price" class="hist-form-input" type="number" name="fuel_price" min="0" step="0.01" placeholder="0.00" required>
            </div>
          </div>
        </div>

        <!-- 3. Controls & Notes -->
        <div class="hist-form-section extras-section">
          <div class="hist-form-field toggle-field">
            <div class="toggle-container">
              <span class="toggle-label">Full tank</span>
              <label class="mileo-switch">
                <input id="hist-is_full_tank" name="is_full_tank" type="checkbox" checked>
                <span class="mileo-slider"></span>
              </label>
            </div>
          </div>

          <div class="hist-form-field notes-field">
            <label for="hist-notes">Notes (Optional)</label>
            <textarea
              id="hist-notes"
              name="notes"
              class="hist-form-textarea"
              maxlength="200"
              rows="2"
              placeholder="e.g. Heavy traffic, highway"
            ></textarea>
            <div id="hist-notes-count" class="hist-form-notes-count">0 / 200</div>
          </div>
        </div>

        <div class="hist-form-actions">
          <button type="button" class="mm-btn mm-btn-secondary mm-btn-sm" id="hist-form-cancel-btn">Cancel</button>
          <button type="submit" class="mm-btn mm-btn-primary mm-btn-sm">Save Entry</button>
        </div>
      </form>
    </div>
  </div>
</div>

