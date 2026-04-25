(function () {
  'use strict';

  function showToast(message, type) {
    const root = document.getElementById('mm-toast-root');
    if (!root) return;
    const toast = document.createElement('div');
    toast.className = 'ql-toast' + (type ? ' ' + type : '');
    toast.textContent = message;
    root.appendChild(toast);
    setTimeout(() => toast.remove(), 3200);
  }

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

  function parseVehiclesData() {
    const node = document.getElementById('quick-log-vehicles-data');
    if (!node || !node.textContent) return [];
    try {
      const data = JSON.parse(node.textContent);
      return Array.isArray(data) ? data : [];
    } catch (err) {
      return [];
    }
  }

  function toVehicleMap(list) {
    const map = new Map();
    list.forEach((item) => {
      const id = Number(item.id);
      if (!Number.isFinite(id)) return;
      const last = item.last_odometer === null ? null : Number(item.last_odometer);
      map.set(id, {
        id,
        name: item.name || '',
        lastOdometer: Number.isFinite(last) ? last : null,
      });
    });
    return map;
  }

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.ql-form');
    if (!form) return;

    const vehicles = toVehicleMap(parseVehiclesData());
    const vehicleSelect = document.getElementById('vehicle_id');
    const odometerInput = document.getElementById('odometer');
    const tripInput = document.getElementById('trip_distance');
    const editBtn = document.getElementById('trip-distance-edit');
    const odometerNote = document.getElementById('odometer-note');
    const odometerError = document.getElementById('odometer-error');
    const tripNote = document.getElementById('trip-distance-note');
    const submitBtn = form.querySelector('button[type="submit"]');
    const fullTankInput = document.getElementById('is_full_tank');
    const fullTankValue = document.getElementById('full-tank-value');
    const fullTankNote = document.getElementById('full-tank-note');
    const notesToggle = document.getElementById('notes-toggle');
    const notesToggleIcon = document.getElementById('notes-toggle-icon');
    const notesPanel = document.getElementById('notes-panel');
    const notesInput = document.getElementById('notes');
    const notesCount = document.getElementById('notes-count');

    if (!vehicleSelect || !odometerInput || !tripInput || !editBtn || !odometerNote || !odometerError || !tripNote || !submitBtn || !fullTankInput || !fullTankValue || !fullTankNote || !notesToggle || !notesToggleIcon || !notesPanel || !notesInput || !notesCount) {
      return;
    }

    let manualTripOverride = false;

    function currentVehicle() {
      const id = Number(vehicleSelect.value);
      return vehicles.get(id) || null;
    }

    function setTripReadOnly(isReadOnly) {
      tripInput.readOnly = isReadOnly;
      tripInput.classList.toggle('is-readonly', isReadOnly);
      editBtn.classList.toggle('is-active', !isReadOnly);
      editBtn.setAttribute('aria-pressed', !isReadOnly ? 'true' : 'false');
      if (isReadOnly) {
        tripInput.required = false;
      } else {
        tripInput.required = true;
      }
    }

    function clearOdometerError() {
      odometerError.textContent = '';
      odometerInput.classList.remove('is-error');
      odometerInput.setCustomValidity('');
    }

    function showOdometerError(message) {
      odometerError.textContent = message;
      odometerInput.classList.add('is-error');
      odometerInput.setCustomValidity(message);
    }

    function applyTripState() {
      const vehicle = currentVehicle();
      const hasPriorLog = vehicle && Number.isFinite(vehicle.lastOdometer);
      const odometerValue = Number(odometerInput.value);
      const hasOdometer = odometerInput.value !== '' && Number.isFinite(odometerValue);

      clearOdometerError();

      if (!hasPriorLog) {
        manualTripOverride = true;
        setTripReadOnly(false);
        if (!tripInput.value) {
          tripInput.value = '';
        }
        odometerNote.textContent = 'No prior log found for this vehicle.';
        tripNote.textContent = 'Enter trip distance manually (required).';
        return;
      }

      const last = vehicle.lastOdometer;
      odometerNote.textContent = 'Last logged odometer: ' + String(last) + ' km';

      if (hasOdometer && odometerValue < last) {
        showOdometerError('Odometer must be greater than or equal to ' + String(last) + ' km.');
      }

      if (manualTripOverride) {
        setTripReadOnly(false);
        tripNote.textContent = 'Manual override enabled for trip distance.';
        return;
      }

      setTripReadOnly(true);
      tripNote.textContent = 'Auto-computed as current odometer minus previous odometer.';

      if (!hasOdometer || odometerValue < last) {
        tripInput.value = '';
        return;
      }

      const computed = odometerValue - last;
      tripInput.value = computed.toFixed(2);
    }

    vehicleSelect.addEventListener('change', () => {
      manualTripOverride = false;
      applyTripState();
    });

    odometerInput.addEventListener('input', () => {
      applyTripState();
    });

    editBtn.addEventListener('click', () => {
      manualTripOverride = true;
      setTripReadOnly(false);
      tripNote.textContent = 'Manual override enabled for trip distance.';
      tripInput.focus();
    });

    function applyFullTankState() {
      if (fullTankInput.checked) {
        fullTankValue.textContent = 'Yes, full tank';
        fullTankNote.hidden = true;
      } else {
        fullTankValue.textContent = 'No, partial fill';
        fullTankNote.hidden = false;
      }
    }

    fullTankInput.addEventListener('change', applyFullTankState);

    function applyNotesCount() {
      const length = notesInput.value.length;
      notesCount.textContent = String(length) + ' / 200';
    }

    function setNotesExpanded(expanded) {
      notesPanel.hidden = !expanded;
      notesToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      notesToggleIcon.textContent = expanded ? '−' : '+';
      if (expanded) {
        notesInput.focus();
      }
    }

    notesToggle.addEventListener('click', () => {
      const isExpanded = notesToggle.getAttribute('aria-expanded') === 'true';
      setNotesExpanded(!isExpanded);
    });

    notesInput.addEventListener('input', applyNotesCount);

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      applyTripState();
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const payload = {
        vehicle_id: Number(vehicleSelect.value),
        log_date: String(document.getElementById('log_date').value || ''),
        odometer: Number(odometerInput.value),
        trip_distance: Number(tripInput.value),
        liters_filled: Number(document.getElementById('liters_filled').value),
        fuel_price: Number(document.getElementById('fuel_price').value),
        manual_trip_override: manualTripOverride,
        is_full_tank: fullTankInput.checked,
        notes: notesInput.value.trim(),
      };

      submitBtn.disabled = true;
      const prevText = submitBtn.textContent;
      submitBtn.textContent = 'Saving...';

      try {
        await createFuelLog(payload);
        const vehicle = currentVehicle();
        if (vehicle) {
          vehicle.lastOdometer = payload.odometer;
        }

        showToast('Fill-up saved successfully.', 'success');

        setTimeout(() => {
          window.location.href = '?page=dashboard';
        }, 700);
      } catch (err) {
        showToast(err.message, 'error');
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = prevText;
      }
    });

    applyTripState();
    applyFullTankState();
    applyNotesCount();
    setNotesExpanded(false);
  });
})();
