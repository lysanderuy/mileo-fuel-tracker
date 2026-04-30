(function () {
  'use strict';

  let vehicles = new Map();
  let isInitialized = false;

  /**
   * API: Create Fuel Log
   */
  async function createFuelLog(payload) {
    const res = await fetch('?api=fuel-logs/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.error || 'Failed to save fuel log.');
    }
    return data;
  }

  /**
   * UI: Toast Notification
   */
  function showToast(message, type) {
    const root = document.getElementById('mm-toast-root');
    if (!root) return;
    const toast = document.createElement('div');
    toast.className = 'ql-toast' + (type ? ' ' + type : '');
    toast.textContent = message;
    root.appendChild(toast);
    setTimeout(() => toast.remove(), 3200);
  }

  /**
   * UI: Trip Distance State Management
   */
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
    const vehicle = getCurrentVehicle();
    const odometerInput = document.getElementById('hist-odometer');
    const helper = document.getElementById('hist-odometer-helper');

    if (!odometerInput || !helper) return;

    const hasPriorLog = vehicle && Number.isFinite(vehicle.lastOdometer);
    const odometerValue = Number(odometerInput.value);
    const hasOdometer = odometerInput.value !== '' && Number.isFinite(odometerValue);

    clearOdometerError();

    if (!hasPriorLog) {
      helper.textContent = 'First log for this vehicle.';
      return;
    }

    const last = vehicle.lastOdometer;

    if (hasOdometer && odometerValue < last) {
      showOdometerError('Odometer must be ≥ ' + String(last) + ' km.');
      helper.textContent = 'Last recorded: ' + String(last) + ' km';
    } else if (hasOdometer) {
      const computed = odometerValue - last;
      helper.textContent = 'Estimated trip: ' + computed.toFixed(1) + ' km';
    } else {
      helper.textContent = 'Last recorded: ' + String(last) + ' km';
    }
  }

  /**
   * Data Loading
   */
  async function fetchVehicles() {
    try {
      const res = await fetch('?api=vehicles/list');
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
      const id = Number(item.id);
      if (!Number.isFinite(id)) return;
      const last = item.odometer === null ? null : Number(item.odometer);
      map.set(id, {
        id,
        name: item.name || '',
        lastOdometer: Number.isFinite(last) ? last : null,
      });
    });
    return map;
  }

  function populateVehicleSelect(list) {
    const select = document.getElementById('hist-vehicle_id');
    if (!select) return;
    select.innerHTML = '';
    
    // Sort list so default is first (optional but good for UI)
    const sorted = [...list].sort((a, b) => (b.is_default ? 1 : 0) - (a.is_default ? 1 : 0));
    
    sorted.forEach(item => {
      if (item.is_archived) return;
      const opt = document.createElement('option');
      opt.value = item.id;
      opt.textContent = item.name;
      if (item.is_default) opt.selected = true;
      select.appendChild(opt);
    });
  }

  /**
   * Modal Actions
   */
  function openModal() {
    const overlay = document.getElementById('hist-modal-overlay');
    const form = document.getElementById('hist-fuel-form');
    if (!overlay || !form) return;

    overlay.removeAttribute('hidden');
    document.body.style.overflow = 'hidden';
    form.reset();
    
    const dateInput = document.getElementById('hist-log_date');
    if (dateInput) {
      dateInput.value = new Date().toISOString().split('T')[0];
    }

    applyTripState();
    applyNotesCount();
  }

  function closeModal() {
    const overlay = document.getElementById('hist-modal-overlay');
    if (overlay) {
      overlay.setAttribute('hidden', '');
      document.body.style.overflow = '';
    }
  }


  function applyNotesCount() {
    const input = document.getElementById('hist-notes');
    const count = document.getElementById('hist-notes-count');
    if (input && count) {
      count.textContent = String(input.value.length) + ' / 200';
    }
  }


  /**
   * Event Listeners Setup
   */
  function setupModalListeners() {
    const overlay = document.getElementById('hist-modal-overlay');
    const closeBtn = document.getElementById('hist-modal-close');
    const cancelBtn = document.getElementById('hist-form-cancel-btn');
    const form = document.getElementById('hist-fuel-form');
    
    const vehicleSelect = document.getElementById('hist-vehicle_id');
    const odometerInput = document.getElementById('hist-odometer');
    const fullTankInput = document.getElementById('hist-is_full_tank');
    const notesInput = document.getElementById('hist-notes');

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (overlay) {
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeModal();
      });
    }

    if (vehicleSelect) {
      vehicleSelect.addEventListener('change', applyTripState);
    }

    if (odometerInput) {
      odometerInput.addEventListener('input', applyTripState);
    }


    if (notesInput) {
      notesInput.addEventListener('input', applyNotesCount);
    }

    if (form) {
      form.addEventListener('submit', handleFormSubmit);
    }
  }

  async function handleFormSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');

    applyTripState();
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const vehicle = getCurrentVehicle();
    const lastOdo = vehicle ? vehicle.lastOdometer : null;
    const currentOdo = Number(document.getElementById('hist-odometer').value);
    
    const payload = {
      vehicle_id: Number(document.getElementById('hist-vehicle_id').value),
      log_date: String(document.getElementById('hist-log_date').value || ''),
      odometer: currentOdo,
      trip_distance: (lastOdo !== null && currentOdo >= lastOdo) ? (currentOdo - lastOdo) : 0,
      liters_filled: Number(document.getElementById('hist-liters_filled').value),
      fuel_price: Number(document.getElementById('hist-fuel_price').value),
      manual_trip_override: false,
      is_full_tank: document.getElementById('hist-is_full_tank').checked,
      notes: document.getElementById('hist-notes').value.trim(),
    };

    if (submitBtn) {
      submitBtn.disabled = true;
      var prevText = submitBtn.textContent;
      submitBtn.textContent = 'Saving...';
    }

    try {
      await createFuelLog(payload);
      const vehicle = vehicles.get(payload.vehicle_id);
      if (vehicle) {
        vehicle.lastOdometer = payload.odometer;
      }

      showToast('Fill-up saved successfully.', 'success');
      closeModal();

      setTimeout(() => {
        location.reload();
      }, 700);
    } catch (err) {
      showToast(err.message, 'error');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = prevText;
      }
    }
  }

  /**
   * Main Initialization
   */
  async function ensureInitialized() {
    if (isInitialized) return true;
    
    const overlay = document.getElementById('hist-modal-overlay');
    if (!overlay) return false;

    // Fetch vehicles and setup DOM-dependent state
    const list = await fetchVehicles();
    vehicles = buildVehicleMap(list);
    populateVehicleSelect(list);
    
    setupModalListeners();
    isInitialized = true;
    return true;
  }

  // Exposed Global API
  window.openFillupModal = async function(preferredId = null) {
    const ready = await ensureInitialized();
    if (ready) {
      if (preferredId) {
        const select = document.getElementById('hist-vehicle_id');
        if (select) select.value = preferredId;
      }
      openModal();
    } else {
      console.warn('FillupModal: Cannot open, modal DOM elements missing.');
    }
  };

  // Legacy compatibility
  window.initFillupModal = ensureInitialized;

  // Global Click Delegation - Always Active
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('#db-log-fillup-btn, #hist-log-fillup-btn');
    if (btn) {
      e.preventDefault();
      console.debug('FillupModal: Click detected on', btn.id);
      const activeId = localStorage.getItem('mileo_active_vehicle_id');
      window.openFillupModal(activeId);
    }
  });

  // Try to initialize if DOM is ready, but don't block
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      console.debug('FillupModal: DOMContentLoaded, ensuring initialization...');
      ensureInitialized();
    });
  } else {
    ensureInitialized();
  }
})();

