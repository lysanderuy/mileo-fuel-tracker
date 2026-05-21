(function () {
  'use strict';

  const FUEL_TYPES = ['Gasoline', 'Diesel', 'Electric', 'Hybrid'];

  /** @type {{ id: number, name: string, make: string|null, model: string|null, year: number|null, fuel_type: string|null, color: string|null, plate_number: string|null, tank_capacity: number|null, odometer: number|null, is_archived: boolean }[]} */
  let vehicles = [];
  let activeTab = 'active';

  /* ── API ────────────────────────────────── */

  async function api(endpoint, body = null) {
    const opts = {
      method: body ? 'POST' : 'GET',
      headers: body ? { 'Content-Type': 'application/json' } : {},
    };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch('?api=vehicles/' + endpoint, opts);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Request failed');
    return data;
  }

  /* ── Toast ──────────────────────────────── */

  function showToast(message, type = '') {
    const root = document.getElementById('mm-toast-root');
    const el = document.createElement('div');
    el.className = 'veh-toast' + (type ? ' ' + type : '');
    el.textContent = message;
    root.appendChild(el);
    setTimeout(() => el.remove(), 5500);
  }

  /* ── Render helpers ─────────────────────── */

  function vehicleMeta(v) {
    const parts = [];
    if (v.make)      parts.push(v.make);
    if (v.model)     parts.push(v.model);
    if (v.year)      parts.push(String(v.year));
    if (v.fuel_type) parts.push(v.fuel_type);
    return parts.join(' · ') || 'No details added';
  }

  function buildRow(v) {
    const row = document.createElement('div');
    row.className = 'veh-row';
    row.dataset.id = String(v.id);

    let actions;
    if (v.is_archived) {
      actions = `
        <button class="veh-btn" data-action="unarchive">Restore</button>
        <button class="veh-btn veh-btn-danger" data-action="delete">Delete</button>
      `;
    } else {
      actions = `
        <button class="veh-btn" data-action="edit">Edit</button>
        <button class="veh-btn" data-action="archive">Archive</button>
        <button class="veh-btn veh-btn-danger" data-action="delete">Delete</button>
      `;
    }

    row.innerHTML = `
      <button class="veh-star-btn ${v.is_default ? 'is-default' : ''}" data-action="set-default" title="${v.is_default ? 'Default vehicle' : 'Set as default'}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="${v.is_default ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
        </svg>
      </button>
      <div class="veh-icon ${v.is_archived ? 'veh-icon-archived' : ''}">
        <span class="veh-icon-mark">&#x1F697;</span>
      </div>
      <div class="veh-info">
        <div class="veh-head">
          <div class="veh-name">${esc(v.name)}</div>
          <div class="veh-meta">
            ${esc(vehicleMeta(v))}
            ${v.plate_number ? `<span class="veh-meta-sep">•</span><span class="veh-plate-inline">${esc(v.plate_number)}</span>` : ''}
          </div>
        </div>
        <div class="veh-badges">
          ${v.tank_capacity != null ? `<span class="veh-badge">&#x26FD; ${v.tank_capacity}L tank</span>` : ''}
          ${v.odometer     != null ? `<span class="veh-badge">&#x1F6E3; ${v.odometer.toLocaleString()} km</span>` : ''}
        </div>
      </div>
      <div class="veh-side">
        <div class="veh-actions">${actions}</div>
      </div>
    `;

    row.querySelector('[data-action]') && row.querySelectorAll('[data-action]').forEach(btn => {
      btn.addEventListener('click', () => handleRowAction(v.id, btn.dataset.action));
    });

    return row;
  }

  function esc(str) {
    if (!str) return '';
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function renderLists() {
    const active   = vehicles.filter(v => !v.is_archived);
    const archived = vehicles.filter(v =>  v.is_archived);

    renderList('veh-active-list', active, 'No active vehicles yet.');
    renderList('veh-archived-list', archived, 'No archived vehicles yet.');

    const countEl = document.getElementById('veh-active-count');
    if (countEl) countEl.textContent = String(active.length);

    const archivedCount   = document.getElementById('veh-archived-count');
    if (archivedCount) archivedCount.textContent = String(archived.length);
    updateTabViews();
  }

  function updateTabViews() {
    const activePanel = document.getElementById('veh-active-panel');
    const archivedPanel = document.getElementById('veh-archived-panel');
    const activeTabBtn = document.getElementById('veh-tab-active');
    const archivedTabBtn = document.getElementById('veh-tab-archived');

    if (activePanel) activePanel.style.display = activeTab === 'active' ? '' : 'none';
    if (archivedPanel) archivedPanel.style.display = activeTab === 'archived' ? '' : 'none';

    if (activeTabBtn) {
      activeTabBtn.classList.toggle('is-active', activeTab === 'active');
      activeTabBtn.setAttribute('aria-selected', activeTab === 'active' ? 'true' : 'false');
    }
    if (archivedTabBtn) {
      archivedTabBtn.classList.toggle('is-active', activeTab === 'archived');
      archivedTabBtn.setAttribute('aria-selected', activeTab === 'archived' ? 'true' : 'false');
    }
  }

  function renderList(containerId, list, emptyMessage) {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '';

    if (list.length === 0 && emptyMessage) {
      const empty = document.createElement('div');
      empty.className = 'veh-empty';
      empty.innerHTML = `
        <span class="veh-empty-icon">&#x1F697;</span>
        <div class="veh-empty-title">${esc(emptyMessage)}</div>
      `;
      el.appendChild(empty);
      return;
    }

    list.forEach(v => el.appendChild(buildRow(v)));
  }

  /* ── Load ───────────────────────────────── */

  async function loadVehicles() {
    try {
      const data = await api('list');
      vehicles = data.vehicles;
      renderLists();
    } catch (err) {
      showToast('Failed to load vehicles.', 'error');
    }
  }

  /* ── Row actions ────────────────────────── */

  async function handleRowAction(id, action) {
    if (action === 'edit')        { openModal(id); return; }
    if (action === 'archive')     { await doArchive(id, true);  return; }
    if (action === 'unarchive')   { await doArchive(id, false); return; }
    if (action === 'delete')      { await confirmDelete(id);    return; }
    if (action === 'set-default') { await doSetDefault(id);     return; }
  }

  async function doSetDefault(id) {
    try {
      await api('set-default', { id });
      vehicles.forEach(v => {
        v.is_default = (v.id === id);
      });
      renderLists();
      showToast('Default vehicle updated.', 'success');
    } catch (err) {
      showToast(err.message, 'error');
    }
  }

  async function doArchive(id, archived) {
    try {
      await api('archive', { id, archived });
      const v = vehicles.find(x => x.id === id);
      if (v) v.is_archived = archived;
      renderLists();
      showToast(archived ? 'Vehicle archived.' : 'Vehicle restored.', 'success');
    } catch (err) {
      showToast(err.message, 'error');
    }
  }

  function confirmDelete(id) {
    const v = vehicles.find(x => x.id === id);
    const name = v ? v.name : 'this vehicle';

    const overlay = document.createElement('div');
    overlay.className = 'veh-confirm-overlay';
    overlay.innerHTML = `
      <div class="veh-confirm">
        <div class="veh-confirm-icon">&#x1F6A8;</div>
        <div class="veh-confirm-title">Delete vehicle?</div>
        <div class="veh-confirm-body">
          <strong>${esc(name)}</strong> and all its fuel logs will be permanently deleted.
          This cannot be undone.
        </div>
        <div class="veh-confirm-actions">
          <button class="mm-btn mm-btn-secondary mm-btn-sm" id="veh-cancel-del">Cancel</button>
          <button class="mm-btn mm-btn-sm" id="veh-confirm-del" style="background:#D93520;color:#fff;border-color:#D93520">Delete</button>
        </div>
      </div>
    `;

    document.getElementById('mm-modal-root').appendChild(overlay);

    overlay.querySelector('#veh-cancel-del').addEventListener('click', () => overlay.remove());
    overlay.querySelector('#veh-confirm-del').addEventListener('click', async () => {
      overlay.remove();
      try {
        await api('delete', { id });
        vehicles = vehicles.filter(x => x.id !== id);
        renderLists();
        showToast('Vehicle deleted.', 'success');
      } catch (err) {
        showToast(err.message, 'error');
      }
    });

    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
  }

  /* ── Modal (add / edit) ─────────────────── */

  function openModal(editId = null) {
    const isEdit = editId !== null;
    const v = isEdit ? vehicles.find(x => x.id === editId) : null;

    const overlay = document.createElement('div');
    overlay.className = 'veh-modal-overlay';

    const fuelOptions = FUEL_TYPES.map(ft =>
      `<option value="${ft}"${v && v.fuel_type === ft ? ' selected' : ''}>${ft}</option>`
    ).join('');

    overlay.innerHTML = `
      <div class="veh-modal">
        <div class="veh-modal-head">
          <span class="veh-modal-title">${isEdit ? 'Edit Vehicle' : 'Add Vehicle'}</span>
          <button class="veh-modal-close" id="veh-modal-close-btn">&#x2715;</button>
        </div>
        <div class="veh-modal-body">
          <div class="veh-field">
            <label>Name <span class="veh-required">*</span></label>
            <input class="veh-input" id="veh-f-name" type="text" placeholder="e.g. Daily Driver" maxlength="100" value="${esc(v ? v.name : '')}">
          </div>
          <div class="veh-form-row">
            <div class="veh-field">
              <label>Make</label>
              <input class="veh-input" id="veh-f-make" type="text" placeholder="Toyota" maxlength="50" value="${esc(v && v.make ? v.make : '')}">
            </div>
            <div class="veh-field">
              <label>Model</label>
              <input class="veh-input" id="veh-f-model" type="text" placeholder="Vios" maxlength="50" value="${esc(v && v.model ? v.model : '')}">
            </div>
          </div>
          <div class="veh-form-row">
            <div class="veh-field">
              <label>Year</label>
              <input class="veh-input" id="veh-f-year" type="number" placeholder="2020" min="1900" max="2100" value="${v && v.year ? String(v.year) : ''}">
            </div>
            <div class="veh-field">
              <label>Fuel Type</label>
              <select class="veh-select" id="veh-f-fuel">
                <option value="">— Select —</option>
                ${fuelOptions}
              </select>
            </div>
          </div>
          <div class="veh-form-row">
            <div class="veh-field">
              <label>Color</label>
              <input class="veh-input" id="veh-f-color" type="text" placeholder="White" maxlength="50" value="${esc(v && v.color ? v.color : '')}">
            </div>
            <div class="veh-field">
              <label>Plate Number</label>
              <input class="veh-input" id="veh-f-plate" type="text" placeholder="ABC 1234" maxlength="20" value="${esc(v && v.plate_number ? v.plate_number : '')}">
            </div>
          </div>
          <div class="veh-form-row">
            <div class="veh-field">
              <label>Tank Capacity <span class="veh-field-unit">(liters)</span></label>
              <input class="veh-input" id="veh-f-tank" type="number" placeholder="60" min="1" max="9999" step="0.1" value="${v && v.tank_capacity != null ? v.tank_capacity : ''}">
            </div>
            <div class="veh-field">
              <label>Odometer <span class="veh-field-unit">(km)</span></label>
              <input class="veh-input" id="veh-f-odometer" type="number" placeholder="12500" min="0" max="9999999" value="${v && v.odometer != null ? v.odometer : ''}">
            </div>
          </div>
          <div id="veh-modal-error" style="display:none;color:#D93520;font-size:0.8125rem;font-weight:500;padding:8px 12px;background:#FDECEA;border-radius:8px;"></div>
        </div>
        <div class="veh-modal-footer">
          <button class="mm-btn mm-btn-secondary mm-btn-sm" id="veh-modal-cancel">Cancel</button>
          <button class="mm-btn mm-btn-primary mm-btn-sm" id="veh-modal-save">${isEdit ? 'Save Changes' : 'Add Vehicle'}</button>
        </div>
      </div>
    `;

    document.getElementById('mm-modal-root').appendChild(overlay);

    const nameInput  = overlay.querySelector('#veh-f-name');
    const errorBox   = overlay.querySelector('#veh-modal-error');
    const saveBtn    = overlay.querySelector('#veh-modal-save');

    function closeModal() { overlay.remove(); }

    overlay.querySelector('#veh-modal-close-btn').addEventListener('click', closeModal);
    overlay.querySelector('#veh-modal-cancel').addEventListener('click', closeModal);
    overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });

    function showError(msg) {
      errorBox.textContent = msg;
      errorBox.style.display = '';
    }

    saveBtn.addEventListener('click', async () => {
      const payload = {
        name:          overlay.querySelector('#veh-f-name').value.trim(),
        make:          overlay.querySelector('#veh-f-make').value.trim(),
        model:         overlay.querySelector('#veh-f-model').value.trim(),
        year:          overlay.querySelector('#veh-f-year').value.trim(),
        fuel_type:     overlay.querySelector('#veh-f-fuel').value,
        color:         overlay.querySelector('#veh-f-color').value.trim(),
        plate_number:  overlay.querySelector('#veh-f-plate').value.trim(),
        tank_capacity: overlay.querySelector('#veh-f-tank').value.trim(),
        odometer:      overlay.querySelector('#veh-f-odometer').value.trim(),
      };

      if (!payload.name) {
        nameInput.classList.add('error');
        showError('Vehicle name is required.');
        nameInput.focus();
        return;
      }
      nameInput.classList.remove('error');
      errorBox.style.display = 'none';

      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving…';

      try {
        if (isEdit) {
          payload.id = editId;
          await api('update', payload);
          const idx = vehicles.findIndex(x => x.id === editId);
          if (idx !== -1) {
            vehicles[idx] = {
              ...vehicles[idx],
              name:          payload.name,
              make:          payload.make          || null,
              model:         payload.model         || null,
              year:          payload.year          !== '' ? parseInt(payload.year, 10)          : null,
              fuel_type:     payload.fuel_type     || null,
              color:         payload.color         || null,
              plate_number:  payload.plate_number  || null,
              tank_capacity: payload.tank_capacity !== '' ? parseFloat(payload.tank_capacity) : null,
              odometer:      payload.odometer      !== '' ? parseInt(payload.odometer, 10)     : null,
            };
          }
          showToast('Vehicle updated.', 'success');
        } else {
          const res = await api('create', payload);
          vehicles.push(res.vehicle);
          showToast('Vehicle added.', 'success');
        }
        renderLists();
        closeModal();
      } catch (err) {
        showError(err.message);
        saveBtn.disabled = false;
        saveBtn.textContent = isEdit ? 'Save Changes' : 'Add Vehicle';
      }
    });

    nameInput.focus();
  }

  /* ── Init ───────────────────────────────── */

  document.addEventListener('DOMContentLoaded', () => {
    const addBtn = document.getElementById('veh-add-btn');
    if (addBtn) {
      addBtn.addEventListener('click', () => openModal());
    }
    const activeTabBtn = document.getElementById('veh-tab-active');
    const archivedTabBtn = document.getElementById('veh-tab-archived');
    if (activeTabBtn) {
      activeTabBtn.addEventListener('click', () => {
        activeTab = 'active';
        updateTabViews();
      });
    }
    if (archivedTabBtn) {
      archivedTabBtn.addEventListener('click', () => {
        activeTab = 'archived';
        updateTabViews();
      });
    }
    updateTabViews();
    if (document.getElementById('veh-active-list')) {
      loadVehicles();
    }
  });
})();
