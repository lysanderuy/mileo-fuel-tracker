(function () {
  'use strict';

  let currentVehicleId = null;
  let currentPage      = 1;
  let isLoading        = false;
  let hasMore          = false;
  let vehicles = new Map();

  async function deleteFuelLog(logId) {
    const res = await fetch('?api=fuel-logs/delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: logId }),
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Failed to delete fill-up.');
    return data;
  }

  async function fetchLogs(vehicleId, page) {
    const res  = await fetch(`?api=fuel-logs/list&vehicle_id=${vehicleId}&page=${page}`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Failed to load history');
    return data;
  }

  function esc(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function fmtDate(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function fmtPeso(n) {
    return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  var EDIT_ICON  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg>';
  var TRASH_ICON = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';

  function buildRow(log) {
    const tr = document.createElement('tr');

    let effHtml;
    if (!log.is_full_tank) {
      effHtml = '<span class="hist-badge hist-badge-partial" title="Partial fill — efficiency estimate may vary">'
              + '<svg class="hist-partial-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">'
              + '<rect x="1" y="8" width="14" height="7" rx="1.5" fill="currentColor" opacity=".35"/>'
              + '<rect x="1" y="1" width="14" height="7" rx="1.5" fill="currentColor"/>'
              + '<path d="M13 1v14" stroke="white" stroke-width="1"/>'
              + '</svg>'
              + 'Partial</span>';
    } else if (log.efficiency_kml !== null) {
      const kml = log.efficiency_kml;
      let cls = 'yellow', icon = '';
      if (kml >= 12.5) {
        cls = 'green';
        icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>';
      } else if (kml >= 10) {
        cls = 'yellow';
        icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M5 12h14"/></svg>';
      } else {
        cls = 'red';
        icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>';
      }
      effHtml = '<span class="hist-badge hist-badge-' + cls + '">' + icon + kml.toFixed(1) + ' km/L</span>';
    } else {
      effHtml = '<span class="hist-eff-na">—</span>';
    }

    var total = log.fuel_price * log.liters_filled;

    tr.innerHTML =
      '<td class="hist-td hist-td-date">'    + esc(fmtDate(log.log_date))              + '</td>' +
      '<td class="hist-td">'                 + esc(log.vehicle_name)                   + '</td>' +
      '<td class="hist-td hist-td-vol">'     + esc(log.liters_filled.toFixed(2) + ' L') + '</td>' +
      '<td class="hist-td hist-td-price">'   + esc(fmtPeso(log.fuel_price))             + '</td>' +
      '<td class="hist-td hist-td-cost">'    + esc(fmtPeso(total))                      + '</td>' +
      '<td class="hist-td hist-td-eff">'     + effHtml                                  + '</td>' +
      '<td class="hist-td hist-td-actions">' +
        '<button class="hist-edit-btn" data-id="' + log.id + '" aria-label="Edit fill-up">' + EDIT_ICON + '</button>' +
        '<button class="hist-delete-btn" data-id="' + log.id + '" aria-label="Delete fill-up">' + TRASH_ICON + '</button>' +
      '</td>';

    return tr;
  }

  function getWrap()        { return document.getElementById('hist-table-wrap'); }
  function getLoadMoreWrap(){ return document.getElementById('hist-load-more-wrap'); }
  function getLoadMoreBtn() { return document.getElementById('hist-load-more-btn'); }

  function showSkeleton() {
    const wrap = getWrap();
    if (!wrap) return;
    wrap.innerHTML =
      '<div class="hist-skeleton-row"></div>' +
      '<div class="hist-skeleton-row"></div>' +
      '<div class="hist-skeleton-row"></div>' +
      '<div class="hist-skeleton-row"></div>';
  }

  function showEmpty() {
    const wrap = getWrap();
    if (!wrap) return;
    wrap.innerHTML =
      '<div class="hist-empty">' +
        '<span class="hist-empty-icon">&#9981;</span>' +
        '<div class="hist-empty-title">No fill-ups logged yet</div>' +
        '<div class="hist-empty-sub">Click the "Log Fill-Up" button above to log your first fuel fill-up.</div>' +
      '</div>';
  }

  function showApiError(msg) {
    const wrap = getWrap();
    if (!wrap) return;
    wrap.innerHTML = '<div class="hist-error">' + esc(msg) + '</div>';
  }

  function buildTable(logs) {
    const table  = document.createElement('table');
    table.className = 'hist-table';
    table.innerHTML =
      '<thead><tr>' +
        '<th class="hist-th">Date</th>' +
        '<th class="hist-th">Vehicle</th>' +
        '<th class="hist-th">Liters</th>' +
        '<th class="hist-th">Price / L</th>' +
        '<th class="hist-th">Total</th>' +
        '<th class="hist-th">Efficiency</th>' +
        '<th class="hist-th hist-th-actions"></th>' +
      '</tr></thead>';
    const tbody = document.createElement('tbody');
    tbody.id = 'hist-tbody';
    logs.forEach(function (log) { tbody.appendChild(buildRow(log)); });
    table.appendChild(tbody);
    return table;
  }

  // Edit button click delegation
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.hist-edit-btn');
    if (!btn) return;
    e.preventDefault();
    var logId = Number(btn.dataset.id);
    if (logId && typeof window.openEditModal === 'function') {
      window.openEditModal(logId);
    }
  });

  // ── Toast ─────────────────────────────────────────────────────────────────

  function showToast(message, type) {
    var root = document.getElementById('mm-toast-root');
    if (!root) return;
    var toast = document.createElement('div');
    toast.className = 'ql-toast' + (type ? ' ' + type : '');
    toast.textContent = message;
    root.appendChild(toast);
    setTimeout(function () { toast.remove(); }, 5500);
  }

  // ── Delete confirmation modal ──────────────────────────────────────────────

  var pendingDeleteId = null;

  function openDeleteConfirm(logId) {
    pendingDeleteId = logId;
    var overlay = document.getElementById('hist-confirm-overlay');
    if (overlay) {
      overlay.removeAttribute('hidden');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeDeleteConfirm() {
    pendingDeleteId = null;
    var overlay = document.getElementById('hist-confirm-overlay');
    if (overlay) {
      overlay.setAttribute('hidden', '');
      document.body.style.overflow = '';
    }
  }

  document.addEventListener('click', function (e) {
    if (e.target.closest('#hist-confirm-cancel-btn') || e.target.id === 'hist-confirm-overlay') {
      closeDeleteConfirm();
      return;
    }

    if (e.target.closest('#hist-confirm-delete-btn')) {
      var deleteBtn = document.getElementById('hist-confirm-delete-btn');
      if (!deleteBtn || deleteBtn.disabled) return;
      var logId = pendingDeleteId;
      if (!logId) return;
      deleteBtn.disabled = true;
      deleteBtn.textContent = 'Deleting…';
      deleteFuelLog(logId).then(function () {
        showToast('Fill-up deleted.', 'success');
        closeDeleteConfirm();
        setTimeout(function () { location.reload(); }, 700);
      }).catch(function (err) {
        showToast(err.message, 'error');
        if (deleteBtn) { deleteBtn.disabled = false; deleteBtn.textContent = 'Delete'; }
      });
      return;
    }

    var btn = e.target.closest('.hist-delete-btn');
    if (btn) {
      e.preventDefault();
      openDeleteConfirm(Number(btn.dataset.id));
    }
  });

  async function loadPage(vehicleId, page, append) {
    if (isLoading) return;
    isLoading = true;

    const btn  = getLoadMoreBtn();
    const bwrap = getLoadMoreWrap();

    if (btn) { btn.disabled = true; btn.textContent = 'Loading…'; }

    try {
      var data = await fetchLogs(vehicleId, page);

      if (vehicleId !== currentVehicleId) return;

      hasMore = data.has_more;

      if (!append) {
        var wrap = getWrap();
        if (!wrap) return;

        if (data.logs.length === 0) {
          showEmpty();
          if (bwrap) bwrap.style.display = 'none';
          return;
        }

        wrap.innerHTML = '';
        wrap.appendChild(buildTable(data.logs));
      } else {
        var tbody = document.getElementById('hist-tbody');
        if (tbody) {
          data.logs.forEach(function (log) { tbody.appendChild(buildRow(log)); });
        }
      }

      if (bwrap) bwrap.style.display = hasMore ? '' : 'none';
      if (btn)   { btn.disabled = false; btn.textContent = 'Load more'; }

    } catch (err) {
      if (vehicleId === currentVehicleId && !append) showApiError(err.message);
      if (btn) { btn.disabled = false; btn.textContent = 'Load more'; }
    } finally {
      isLoading = false;
    }
  }

  function switchVehicle(vehicleId) {
    currentVehicleId = vehicleId;
    currentPage      = 1;
    hasMore          = false;
    showSkeleton();
    var bwrap = getLoadMoreWrap();
    if (bwrap) bwrap.style.display = 'none';
    loadPage(vehicleId, 1, false);
  }

  async function init() {
    try {
      const res = await fetch('?api=vehicles/list');
      const data = await res.json();
      const list = data.vehicles || [];

      const contentArea = document.getElementById('hist-content-area');
      
      if (list.length === 0) {
        contentArea.innerHTML = `
          <div class="hist-empty">
            <span class="hist-empty-icon">&#128663;</span>
            <div class="hist-empty-title">No vehicles yet</div>
            <div class="hist-empty-sub">
              Add a vehicle on the <a href="?page=vehicles" class="hist-empty-link">Vehicles page</a> before browsing history.
            </div>
          </div>
        `;
        return;
      }

      const topbarActions = document.getElementById('hist-topbar-actions');
      topbarActions.style.display = 'flex';
      topbarActions.style.gap = '12px';
      topbarActions.style.alignItems = 'center';

      // Vehicle Switcher
      const switcherContainer = document.getElementById('hist-vehicle-switcher-container');
      if (switcherContainer) {
        if (list.length > 1) {
          const currentId = localStorage.getItem('mileo_active_vehicle_id') || '';
          let options = `<option value="">All Vehicles</option>`;
          list.forEach(v => {
            options += `<option value="${v.id}" ${String(v.id) === currentId ? 'selected' : ''}>${esc(v.name)}</option>`;
          });
          // Note: using hist-switcher for styling or reusing db-switcher style
          switcherContainer.innerHTML = `<select class="hist-switcher" id="hist-vehicle-select">${options}</select>`;
          
          document.getElementById('hist-vehicle-select').addEventListener('change', (e) => {
            const newId = e.target.value;
            localStorage.setItem('mileo_active_vehicle_id', newId);
            switchVehicle(newId);
          });
        } else {
          switcherContainer.innerHTML = '';
        }
      }

      const btn = document.getElementById('hist-load-more-btn');
      if (btn) {
        btn.addEventListener('click', function () {
          currentPage++;
          loadPage(currentVehicleId, currentPage, true);
        });
      }

      let initialVehicleId = localStorage.getItem('mileo_active_vehicle_id');
      if (initialVehicleId === null && list.length > 0) {
        const def = list.find(v => v.is_default);
        if (def) {
          initialVehicleId = String(def.id);
          localStorage.setItem('mileo_active_vehicle_id', initialVehicleId);
        }
      }
      switchVehicle(initialVehicleId || '');

    } catch (err) {
      console.error(err);
      document.getElementById('hist-content-area').innerHTML = '<div class="hist-error">Failed to load vehicles.</div>';
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();
