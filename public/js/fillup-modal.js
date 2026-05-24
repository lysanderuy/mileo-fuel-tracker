(function () {
  'use strict';

  var showToast = window.MileoUtils.showToast;
  var fmtPeso   = window.MileoUtils.fmtPeso;

  let vehicles      = new Map();
  let isInitialized = false;

  // Edit-mode state
  let isEditMode              = false;
  let editModePrevOdometer    = null;
  let editModeNextOdometer    = null;
  let editModeOriginalTrip    = null;

  // ── API ────────────────────────────────────────────────────────────────────

  async function createFuelLog(payload) {
    const res = await fetch('?api=fuel-logs/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Failed to save fuel log.');
    return data;
  }

  async function updateFuelLog(payload) {
    const res = await fetch('?api=fuel-logs/update', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Failed to update fuel log.');
    return data;
  }

  async function fetchLog(logId) {
    const res = await fetch('?api=fuel-logs/get&id=' + logId);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Failed to load log.');
    return data;
  }

  // ── Odometer Helpers ───────────────────────────────────────────────────────

  function clearOdometerError() {
    const input = document.getElementById('hist-odometer');
    const error = document.getElementById('hist-odometer-error');
    if (!error) return;
    error.textContent = '';
    if (input) {
      input.classList.remove('is-error');
      input.setCustomValidity('');
    }
  }

  function showOdometerError(message) {
    const input = document.getElementById('hist-odometer');
    const error = document.getElementById('hist-odometer-error');
    if (!error) return;
    error.textContent = message;
    if (input) {
      input.classList.add('is-error');
      input.setCustomValidity(message);
    }
  }

  function getCurrentVehicle() {
    const select = document.getElementById('hist-vehicle_id');
    const id = select ? Number(select.value) : null;
    return id ? vehicles.get(id) || null : null;
  }

  function applyTripState() {
    const odometerInput = document.getElementById('hist-odometer');
    const helper        = document.getElementById('hist-odometer-helper');
    if (!odometerInput || !helper) return;

    // In edit mode, use the fetched prev odometer; in create mode, use vehicle's last odometer.
    const lastOdo = isEditMode
      ? editModePrevOdometer
      : (function () {
          const v = getCurrentVehicle();
          return v ? v.lastOdometer : null;
        }());

    const hasPriorLog   = lastOdo !== null && Number.isFinite(lastOdo);
    const odometerValue = Number(odometerInput.value);
    const hasOdometer   = odometerInput.value !== '' && Number.isFinite(odometerValue);

    clearOdometerError();

    // Show/hide manual trip distance field (only when there is no prior log)
    const tripSection = document.getElementById('hist-trip-distance-section');
    if (tripSection) {
      tripSection.style.display = (!hasPriorLog && isEditMode) ? '' : 'none';
    }

    if (!hasPriorLog) {
      helper.textContent = isEditMode ? 'No previous fill-up for this vehicle.' : 'First log for this vehicle.';
      return;
    }

    if (hasOdometer && odometerValue < lastOdo) {
      showOdometerError('Odometer must be ≥ ' + String(lastOdo) + ' km.');
      helper.textContent = 'Last recorded: ' + String(lastOdo) + ' km';
    } else if (hasOdometer && editModeNextOdometer !== null && odometerValue > editModeNextOdometer) {
      showOdometerError('Odometer must be ≤ ' + String(editModeNextOdometer) + ' km (next fill-up).');
      helper.textContent = 'Last recorded: ' + String(lastOdo) + ' km';
    } else if (hasOdometer) {
      const computed = odometerValue - lastOdo;
      helper.textContent = 'Estimated trip: ' + computed.toFixed(1) + ' km';
    } else {
      helper.textContent = 'Last recorded: ' + String(lastOdo) + ' km';
    }
  }

  // ── Vehicle Select ─────────────────────────────────────────────────────────

  async function fetchVehicles() {
    try {
      const res  = await fetch('?api=vehicles/list');
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Failed to load vehicles');
      return data.vehicles || [];
    } catch (err) {
      console.error('FillupModal fetchVehicles error:', err);
      return [];
    }
  }

  function buildVehicleMap(list) {
    const map = new Map();
    list.forEach((item) => {
      const id   = Number(item.id);
      if (!Number.isFinite(id)) return;
      const last = item.odometer === null ? null : Number(item.odometer);
      map.set(id, { id, name: item.name || '', lastOdometer: Number.isFinite(last) ? last : null });
    });
    return map;
  }

  function populateVehicleSelect(list) {
    const select = document.getElementById('hist-vehicle_id');
    if (!select) return;
    select.innerHTML = '';
    const sorted = [...list].sort((a, b) => (b.is_default ? 1 : 0) - (a.is_default ? 1 : 0));
    sorted.forEach((item) => {
      if (item.is_archived) return;
      const opt = document.createElement('option');
      opt.value       = item.id;
      opt.textContent = item.name;
      if (item.is_default) opt.selected = true;
      select.appendChild(opt);
    });

    // Initialize custom popover to replace native select
    if (window.Popover && !select._popoverInitialized) {
      select._popoverInstance = window.Popover.init(select, {
        placeholder: 'Select vehicle',
        onChange: () => applyTripState()
      });
      select._popoverInitialized = true;
    } else if (select._popoverInstance) {
      select._popoverInstance.refresh();
    }
  }

  // ── Modal Open / Close ─────────────────────────────────────────────────────

  function resetToCreateMode() {
    isEditMode           = false;
    editModePrevOdometer = null;
    editModeNextOdometer = null;
    editModeOriginalTrip = null;

    const logIdInput = document.getElementById('hist-log-id');
    if (logIdInput) logIdInput.value = '';

    const title = document.getElementById('hist-modal-title');
    if (title) title.textContent = 'Log Fill-Up';

    const submitBtn = document.getElementById('hist-form-submit-btn');
    if (submitBtn) { submitBtn.textContent = 'Save Entry'; submitBtn.disabled = false; }

    const vehicleSelect = document.getElementById('hist-vehicle_id');
    if (vehicleSelect) vehicleSelect.disabled = false;

    const tripSection = document.getElementById('hist-trip-distance-section');
    if (tripSection) tripSection.style.display = 'none';

    const form = document.getElementById('hist-fuel-form');
    if (form) form.removeAttribute('hidden');
    const statsPanel = document.getElementById('hist-instant-stats');
    if (statsPanel) { statsPanel.setAttribute('hidden', ''); delete statsPanel.dataset.needsReload; }
  }

  function openModal() {
    const overlay = document.getElementById('hist-modal-overlay');
    const form    = document.getElementById('hist-fuel-form');
    if (!overlay || !form) return;

    overlay.removeAttribute('hidden');
    document.body.style.overflow = 'hidden';
    form.reset();

    const dateInput = document.getElementById('hist-log_date');
    if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

    applyTripState();
    applyNotesCount();
  }

  function closeModal() {
    const overlay     = document.getElementById('hist-modal-overlay');
    const statsPanel  = document.getElementById('hist-instant-stats');
    const needsReload = statsPanel && statsPanel.dataset.needsReload === 'true';

    if (overlay) {
      overlay.setAttribute('hidden', '');
      document.body.style.overflow = '';
    }
    resetToCreateMode();

    if (needsReload) {
      location.reload();
    }
  }

  function applyNotesCount() {
    const input = document.getElementById('hist-notes');
    const count = document.getElementById('hist-notes-count');
    if (input && count) count.textContent = String(input.value.length) + ' / 200';
  }

  // ── Form Submit ────────────────────────────────────────────────────────────

  async function handleFormSubmit(event) {
    event.preventDefault();
    const form      = event.target;
    const submitBtn = document.getElementById('hist-form-submit-btn');

    applyTripState();
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const currentOdo = Number(document.getElementById('hist-odometer').value);
    const hasPrev    = editModePrevOdometer !== null;

    const payload = {
      log_date:      String(document.getElementById('hist-log_date').value || ''),
      odometer:      currentOdo,
      liters_filled: Number(document.getElementById('hist-liters_filled').value),
      fuel_price:    Number(document.getElementById('hist-fuel_price').value),
      is_full_tank:  document.getElementById('hist-is_full_tank').checked,
      notes:         document.getElementById('hist-notes').value.trim(),
    };

    if (isEditMode) {
      payload.id = Number(document.getElementById('hist-log-id').value);

      if (!hasPrev) {
        // First log — backend cannot auto-compute trip_distance; send original value.
        const tripInput = document.getElementById('hist-trip_distance');
        payload.manual_trip_override = true;
        payload.trip_distance = tripInput && tripInput.value !== '' ? Number(tripInput.value) : (editModeOriginalTrip || 0);
      } else {
        payload.manual_trip_override = false;
        payload.trip_distance = 0; // backend recomputes from odometer diff
      }
    } else {
      const vehicle = getCurrentVehicle();
      const lastOdo = vehicle ? vehicle.lastOdometer : null;
      payload.vehicle_id           = Number(document.getElementById('hist-vehicle_id').value);
      payload.trip_distance        = (lastOdo !== null && currentOdo >= lastOdo) ? (currentOdo - lastOdo) : 0;
      payload.manual_trip_override = false;
    }

    let prevText;
    if (submitBtn) {
      submitBtn.disabled    = true;
      prevText              = submitBtn.textContent;
      submitBtn.textContent = isEditMode ? 'Saving…' : 'Saving...';
    }

    try {
      if (isEditMode) {
        await updateFuelLog(payload);
        showToast('Fill-up updated.', 'success');
        closeModal();
        setTimeout(() => location.reload(), 700);
      } else {
        const result = await createFuelLog(payload);
        const v = vehicles.get(payload.vehicle_id);
        if (v) v.lastOdometer = payload.odometer;

        let summary = null;
        try {
          const sRes = await fetch('?api=dashboard/summary&vehicle_id=' + payload.vehicle_id);
          if (sRes.ok) summary = await sRes.json();
        } catch (_) { /* summary is best-effort */ }

        showInstantStats(payload, result.fuel_log, summary);
      }
    } catch (err) {
      showToast(err.message, 'error');
      if (submitBtn) {
        submitBtn.disabled    = false;
        submitBtn.textContent = prevText;
      }
    }
  }

  // ── Instant Stats ──────────────────────────────────────────────────────────

  function showInstantStats(payload, log, summary) {
    const form       = document.getElementById('hist-fuel-form');
    const statsPanel = document.getElementById('hist-instant-stats');

    if (!statsPanel) {
      showToast('Fill-up saved successfully.', 'success');
      closeModal();
      setTimeout(() => location.reload(), 700);
      return;
    }

    if (form) form.setAttribute('hidden', '');
    statsPanel.removeAttribute('hidden');
    statsPanel.dataset.needsReload = 'true';

    const title = document.getElementById('hist-modal-title');
    if (title) title.textContent = 'Fill-Up Logged';

    const liters    = payload.liters_filled;
    const totalCost = payload.fuel_price;
    const tripKm    = log.trip_distance;
    const isFullT   = log.is_full_tank;
    const costPerL  = liters > 0 ? totalCost / liters : null;
    const effKml    = log.efficiency_kml !== undefined
      ? log.efficiency_kml
      : (isFullT && tripKm > 0 && liters > 0 ? tripKm / liters : null);
    const costPerKm = (tripKm !== null && tripKm > 0) ? totalCost / tripKm : null;

    const stats      = summary && summary.stats ? summary.stats : null;
    const totalLogs  = stats ? stats.total_fillups : null;
    const fleetEntry = summary && summary.fleet
      ? summary.fleet.find(function (v) { return String(v.id) === String(payload.vehicle_id); })
      : null;
    const avgKml    = fleetEntry ? fleetEntry.avg_kml     : null;
    const avgCostKm = fleetEntry ? fleetEntry.avg_cost_km : null;

    renderStatsHeader(payload.log_date, totalLogs);
    renderStatsPerf(effKml, avgKml);
    renderStatsGrid(liters, totalCost, costPerL, tripKm, costPerKm, avgCostKm);
  }

  function renderStatsHeader(logDate, totalLogs) {
    const el = document.getElementById('hist-stats-header');
    if (!el) return;

    const d = new Date(logDate + 'T00:00:00');
    const dateStr = d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
    const meta = [totalLogs !== null ? 'Fill-up #' + totalLogs : '', dateStr].filter(Boolean).join(' · ');

    el.innerHTML =
      '<div class="hist-stats-check" aria-hidden="true">'
      + '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>'
      + '</div>'
      + '<div>'
      + '<div class="hist-stats-headline">Fill-Up Logged!</div>'
      + '<div class="hist-stats-meta">' + meta + '</div>'
      + '</div>';
  }

  function renderStatsPerf(effKml, avgKml) {
    const el = document.getElementById('hist-stats-perf');
    if (!el) return;

    if (effKml === null) { el.setAttribute('hidden', ''); return; }
    el.removeAttribute('hidden');

    let badgeClass = 'hist-badge--neutral';
    let badgeText  = 'On Track';
    let deltaHtml  = '';

    if (avgKml !== null && avgKml > 0) {
      const pct   = ((effKml - avgKml) / avgKml) * 100;
      const sign  = pct >= 0 ? '+' : '';
      const arrow = pct > 0 ? '↑' : (pct < 0 ? '↓' : '→');
      const cls   = pct >= 0 ? 'hist-delta--good' : 'hist-delta--bad';

      if (pct >= 5)       { badgeClass = 'hist-badge--good';    badgeText = 'Above Average'; }
      else if (pct >= -5) { badgeClass = 'hist-badge--neutral'; badgeText = 'On Track'; }
      else                { badgeClass = 'hist-badge--bad';     badgeText = 'Below Average'; }

      deltaHtml = '<div class="hist-perf-delta ' + cls + '">'
                + arrow + ' ' + sign + pct.toFixed(1) + '% vs your avg ' + avgKml.toFixed(1) + ' km/L'
                + '</div>';
    }

    el.innerHTML =
      '<div class="hist-perf-left">'
      + '<div class="hist-stat-label">Efficiency</div>'
      + '<div class="hist-perf-value">' + effKml.toFixed(1) + '<span class="hist-perf-unit"> km/L</span></div>'
      + deltaHtml
      + '</div>'
      + '<span class="hist-badge ' + badgeClass + '">' + badgeText + '</span>';
  }

  function renderStatsGrid(liters, totalCost, costPerL, tripKm, costPerKm, avgCostKm) {
    const el = document.getElementById('hist-stats-grid');
    if (!el) return;

    const cards = [];
    cards.push({ label: 'Liters Filled', value: liters.toFixed(2) + ' L' });
    cards.push({ label: 'Total Cost',    value: fmtPeso(totalCost, 0) });
    cards.push({ label: 'Price / Liter', value: costPerL !== null ? fmtPeso(costPerL, 0) : '—' });

    if (tripKm > 0) {
      cards.push({ label: 'Trip Distance', value: tripKm.toFixed(1) + ' km' });
    }

    if (costPerKm !== null) {
      let deltaHtml = '';
      if (avgCostKm !== null && avgCostKm > 0) {
        const pct   = ((costPerKm - avgCostKm) / avgCostKm) * 100;
        const sign  = pct >= 0 ? '+' : '';
        const arrow = pct < 0 ? '↓' : (pct > 0 ? '↑' : '→');
        // Cost/km: lower is better → negative pct is good
        const cls   = pct <= 0 ? 'hist-delta--good' : 'hist-delta--bad';
        deltaHtml = '<div class="hist-stat-delta ' + cls + '">' + arrow + ' ' + sign + pct.toFixed(1) + '% vs avg</div>';
      }
      cards.push({ label: 'Cost / km', value: fmtPeso(costPerKm, 0), delta: deltaHtml });
    }

    // ≤3 cards → 3-col single row; 4+ cards → 2-col
    el.className = 'hist-stats-grid ' + (cards.length <= 3 ? 'hist-stats-grid--3col' : 'hist-stats-grid--2col');
    el.innerHTML = cards.map(function (c) {
      return '<div class="hist-stat-card">'
           + '<div class="hist-stat-label">' + c.label + '</div>'
           + '<div class="hist-stat-value">' + c.value + '</div>'
           + (c.delta || '')
           + '</div>';
    }).join('');
  }

  // ── Listeners Setup ────────────────────────────────────────────────────────

  function setupModalListeners() {
    const overlay       = document.getElementById('hist-modal-overlay');
    const closeBtn      = document.getElementById('hist-modal-close');
    const cancelBtn     = document.getElementById('hist-form-cancel-btn');
    const form          = document.getElementById('hist-fuel-form');
    const vehicleSelect = document.getElementById('hist-vehicle_id');
    const odometerInput = document.getElementById('hist-odometer');
    const notesInput    = document.getElementById('hist-notes');

    const doneBtn = document.getElementById('hist-stats-done-btn');

    if (closeBtn)  closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (doneBtn)   doneBtn.addEventListener('click', closeModal);
    if (overlay)   overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });

    if (vehicleSelect) vehicleSelect.addEventListener('change', applyTripState);
    if (odometerInput) odometerInput.addEventListener('input', applyTripState);
    if (notesInput)    notesInput.addEventListener('input', applyNotesCount);
    if (form)          form.addEventListener('submit', handleFormSubmit);
  }

  // ── Initialization ─────────────────────────────────────────────────────────

  async function ensureInitialized() {
    if (isInitialized) return true;
    const overlay = document.getElementById('hist-modal-overlay');
    if (!overlay) return false;

    const list = await fetchVehicles();
    vehicles   = buildVehicleMap(list);
    populateVehicleSelect(list);
    setupModalListeners();
    isInitialized = true;
    return true;
  }

  // ── Public API ─────────────────────────────────────────────────────────────

  window.openFillupModal = async function (preferredId = null) {
    const ready = await ensureInitialized();
    if (!ready) { console.warn('FillupModal: DOM elements missing.'); return; }

    resetToCreateMode();
    openModal();

    if (preferredId) {
      const select = document.getElementById('hist-vehicle_id');
      if (select) { select.value = preferredId; applyTripState(); }
    }
  };

  window.openEditModal = async function (logId) {
    const ready = await ensureInitialized();
    if (!ready) { console.warn('FillupModal: DOM elements missing.'); return; }

    let logData;
    try {
      logData = await fetchLog(logId);
    } catch (err) {
      showToast(err.message, 'error');
      return;
    }

    const { log, prev_odometer, next_odometer } = logData;

    // Set edit-mode state
    isEditMode           = true;
    editModePrevOdometer = prev_odometer;   // null if first log
    editModeNextOdometer = next_odometer;   // null if last log
    editModeOriginalTrip = log.trip_distance;

    // Populate form fields
    const form = document.getElementById('hist-fuel-form');
    if (form) form.reset();

    const logIdInput = document.getElementById('hist-log-id');
    if (logIdInput) logIdInput.value = log.id;

    const vehicleSelect = document.getElementById('hist-vehicle_id');
    if (vehicleSelect) {
      vehicleSelect.value    = log.vehicle_id;
      vehicleSelect.disabled = true;
    }

    const dateInput = document.getElementById('hist-log_date');
    if (dateInput) dateInput.value = log.log_date;

    const odometerInput = document.getElementById('hist-odometer');
    if (odometerInput) odometerInput.value = log.odometer;

    const litersInput = document.getElementById('hist-liters_filled');
    if (litersInput) litersInput.value = log.liters_filled;

    const priceInput = document.getElementById('hist-fuel_price');
    if (priceInput) priceInput.value = log.fuel_price;

    const fullTankInput = document.getElementById('hist-is_full_tank');
    if (fullTankInput) fullTankInput.checked = log.is_full_tank;

    const notesInput = document.getElementById('hist-notes');
    if (notesInput) notesInput.value = log.notes || '';

    // Pre-fill trip distance for first-log edits
    const tripInput = document.getElementById('hist-trip_distance');
    if (tripInput) tripInput.value = log.trip_distance !== null ? log.trip_distance : 0;

    // Update modal chrome
    const title = document.getElementById('hist-modal-title');
    if (title) title.textContent = 'Edit Fill-Up';

    const submitBtn = document.getElementById('hist-form-submit-btn');
    if (submitBtn) submitBtn.textContent = 'Save Changes';

    // Open overlay
    const overlay = document.getElementById('hist-modal-overlay');
    if (overlay) {
      overlay.removeAttribute('hidden');
      document.body.style.overflow = 'hidden';
    }

    applyTripState();
    applyNotesCount();
  };

  // Legacy compatibility
  window.initFillupModal = ensureInitialized;

  // Global click delegation for Log Fill-Up buttons
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('#db-log-fillup-btn, #hist-log-fillup-btn');
    if (btn) {
      e.preventDefault();
      const activeId = localStorage.getItem('mileo_active_vehicle_id');
      window.openFillupModal(activeId);
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => ensureInitialized());
  } else {
    ensureInitialized();
  }
})();
