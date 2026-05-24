(function () {
  'use strict';

  function fmtPeso(n) {
    return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  function fmtDate(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function esc(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  async function loadDashboard() {
    try {
      let vehicleId = localStorage.getItem('mileo_active_vehicle_id');
      const res = await fetch(`?api=dashboard/summary${vehicleId ? '&vehicle_id=' + vehicleId : ''}`);
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Failed to load dashboard data');

      if (data.vehicles && data.vehicles.length > 0) {
        const ids = data.vehicles.map(v => String(v.id));
        if (vehicleId !== null && vehicleId !== '' && !ids.includes(String(vehicleId))) {
          localStorage.removeItem('mileo_active_vehicle_id');
          vehicleId = null;
        }
        if (vehicleId === null) {
          const def = data.vehicles.find(v => v.is_default) || data.vehicles[0];
          localStorage.setItem('mileo_active_vehicle_id', def.id);
          loadDashboard();
          return;
        }
      }

      renderDashboard(data);
    } catch (err) {
      console.error(err);
      const stack = document.getElementById('db-overview-stack');
      if (stack) stack.innerHTML = '<div class="hist-error">Failed to load dashboard data.</div>';
    }
  }

  function renderDashboard(data) {
    const { stats, vehicles, has_vehicles, fillups, fleet } = data;

    const activeVehicleId = localStorage.getItem('mileo_active_vehicle_id') || '';
    const isAllVehicles = activeVehicleId === '';

    // Topbar action
    const topbarActions = document.getElementById('db-topbar-actions');
    if (has_vehicles) {
      topbarActions.style.display = 'flex';
      topbarActions.style.gap = '12px';
      topbarActions.style.alignItems = 'center';
    }

    // Vehicle Switcher
    const switcherContainer = document.getElementById('db-vehicle-switcher-container');
    if (switcherContainer) {
      if (vehicles.length > 1) {
        const currentId = localStorage.getItem('mileo_active_vehicle_id') || '';
        let options = `<option value="">All Vehicles</option>`;
        vehicles.forEach(v => {
          options += `<option value="${v.id}" ${String(v.id) === currentId ? 'selected' : ''}>${esc(v.name)}</option>`;
        });
        switcherContainer.innerHTML = `<select class="db-switcher" id="db-vehicle-switcher">${options}</select>`;

        // Initialize custom popover
        const selectEl = document.getElementById('db-vehicle-switcher');
        if (selectEl && window.Popover) {
          window.Popover.init(selectEl, {
            onChange: (value) => {
              localStorage.setItem('mileo_active_vehicle_id', value);
              loadDashboard();
            }
          });
        } else if (selectEl) {
          selectEl.addEventListener('change', (e) => {
            localStorage.setItem('mileo_active_vehicle_id', e.target.value);
            loadDashboard();
          });
        }
      } else {
        switcherContainer.innerHTML = '';
      }
    }

    // Time Switcher
    const timeContainer = document.getElementById('db-time-switcher-container');
    const currentTimeRange = localStorage.getItem('mileo_dashboard_time_range') || 'all_time';
    if (timeContainer) {
      timeContainer.innerHTML = `
        <select class="db-switcher" id="db-time-switcher">
          <option value="all_time" ${currentTimeRange === 'all_time' ? 'selected' : ''}>All Time</option>
          <option value="this_month" ${currentTimeRange === 'this_month' ? 'selected' : ''}>This Month</option>
        </select>
      `;

      // Initialize custom popover
      const selectEl = document.getElementById('db-time-switcher');
      if (selectEl && window.Popover) {
        window.Popover.init(selectEl, {
          onChange: (value) => {
            localStorage.setItem('mileo_dashboard_time_range', value);
            renderDashboard(data);
          }
        });
      } else if (selectEl) {
        selectEl.addEventListener('change', (e) => {
          localStorage.setItem('mileo_dashboard_time_range', e.target.value);
          renderDashboard(data);
        });
      }
    }

    // Stats
    const statsContainer = document.getElementById('db-stats-container');
    statsContainer.innerHTML = '';
    const statsData = [];
    const isAllTime = currentTimeRange === 'all_time';
    const subtextSuffix = isAllTime ? 'All time' : 'This month';

    if (stats.total_fillups === 0) {
      statsData.push({ label: 'Total Fill-ups', value: '—', sub_html: 'Start logging fill-ups' });
      statsData.push({ label: 'Total Distance', value: '—', sub_html: '—' });
      statsData.push({ label: 'Total Spent', value: '—', sub_html: '—' });
    } else {
      const displayFillups = isAllTime ? stats.total_fillups       : stats.month_fillups;
      const displaySpent   = isAllTime ? stats.total_spent         : stats.month_spent;
      const displayDist    = isAllTime ? stats.total_distance      : stats.month_total_distance;
      statsData.push({ label: 'Total Fill-ups', value: displayFillups, sub_html: subtextSuffix });
      statsData.push({
        label: 'Total Distance',
        value: displayDist !== null ? displayDist.toLocaleString('en-PH', { maximumFractionDigits: 0 }) + ' km' : '—',
        sub_html: subtextSuffix
      });
      statsData.push({ label: 'Total Spent', value: fmtPeso(displaySpent), sub_html: subtextSuffix });
    }

    statsContainer.style.gridTemplateColumns = '';

    statsData.forEach(s => {
      const div = document.createElement('div');
      div.className = 'db-card';
      div.innerHTML = `
        <div class="db-card-label">${esc(s.label)}</div>
        <div class="db-card-value">${s.value}</div>
        <div class="db-card-sub">${s.sub_html}</div>
      `;
      statsContainer.appendChild(div);
    });

    // Fleet breakdown
    const fleetSection = document.getElementById('db-fleet-section');
    if (fleetSection) {
      const visibleFleet = isAllVehicles
        ? fleet
        : fleet.filter(v => String(v.id) === activeVehicleId);
      if (visibleFleet && visibleFleet.length > 0) {
        const maxKml = Math.max(...visibleFleet.filter(v => v.avg_kml !== null).map(v => v.avg_kml), 0);
        const sectionTitle = isAllVehicles ? 'Fleet Overview' : 'Vehicle Overview';
        const showBar = visibleFleet.length > 1;
        let rowsHtml = '';
        visibleFleet.forEach(v => {
          const barPct = (maxKml > 0 && v.avg_kml !== null) ? Math.round((v.avg_kml / maxKml) * 100) : 0;
          rowsHtml += `
            <div class="db-fleet-row">
              <div class="db-fleet-header">
                <div class="db-fleet-name">${esc(v.name)}</div>
                <div class="db-fleet-total">${fmtPeso(v.total_spent)}</div>
              </div>
              <div class="db-fleet-metrics">
                <div class="db-fleet-metric">
                  <div class="db-fleet-metric-val db-fleet-metric-val--eff">${v.avg_kml !== null ? v.avg_kml.toFixed(1) + ' km/L' : '—'}</div>
                  <div class="db-fleet-metric-key">Efficiency</div>
                </div>
                <div class="db-fleet-metric">
                  <div class="db-fleet-metric-val db-fleet-metric-val--cost">${v.avg_cost_km !== null ? fmtPeso(v.avg_cost_km) + '/km' : '—'}</div>
                  <div class="db-fleet-metric-key">Cost per km</div>
                </div>
              </div>
              ${showBar ? `<div class="db-fleet-bar-track"><div class="db-fleet-bar-fill" style="width:${barPct}%"></div></div>` : ''}
            </div>
          `;
        });
        fleetSection.innerHTML = `
          <div class="db-section db-fleet-section">
            <div class="db-section-head"><div class="db-section-title">${sectionTitle}</div></div>
            ${rowsHtml}
          </div>
        `;
      } else {
        fleetSection.innerHTML = '';
      }
    }

    // Fillups
    const fillupsSection = document.getElementById('db-fillups-section');
    fillupsSection.innerHTML = `
      <div class="db-section-head">
        <div class="db-section-title">Recent Fill-ups</div>
      </div>
    `;

    if (fillups.length === 0 && !has_vehicles) {
      fillupsSection.innerHTML += `
        <div class="db-empty">
          <div class="db-empty-icon">&#128663;</div>
          <div class="db-empty-title">Start by adding your vehicle</div>
          <div class="db-empty-sub">Before you can track fuel, you'll need to <a href="?page=vehicles" class="db-empty-link">open the Vehicles page</a> first.</div>
        </div>
      `;
    } else if (fillups.length === 0) {
      fillupsSection.innerHTML += `
        <div class="db-empty">
          <div class="db-empty-icon">&#9981;</div>
          <div class="db-empty-title">No fill-ups logged yet</div>
          <div class="db-empty-sub"><a href="?page=history" class="db-empty-link">Log a fill-up</a> to start seeing your efficiency, cost, and spending trends here.</div>
        </div>
      `;
    } else {
      let tbodyHtml = '';
      fillups.forEach(row => {
        let effCell;
        if (!row.is_full_tank) {
          effCell = '<span class="db-badge hist-badge-partial" title="Partial fill — efficiency not calculated">'
            + '<svg class="hist-partial-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">'
            + '<rect x="1" y="8" width="14" height="7" rx="1.5" fill="currentColor" opacity=".35"/>'
            + '<rect x="1" y="1" width="14" height="7" rx="1.5" fill="currentColor"/>'
            + '<path d="M13 1v14" stroke="white" stroke-width="1"/>'
            + '</svg>'
            + 'Partial</span>';
        } else if (row.efficiency_kml === null) {
          effCell = '—';
        } else {
          let badge = 'yellow', icon = '';
          if (row.prior_efficiency_kml !== null) {
            const pct = ((row.efficiency_kml - row.prior_efficiency_kml) / row.prior_efficiency_kml) * 100;
            if (pct > 5) {
              badge = 'green';
              icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;vertical-align:middle;"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>';
            } else if (pct < -5) {
              badge = 'red';
              icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;vertical-align:middle;"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>';
            } else {
              badge = 'yellow';
              icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;vertical-align:middle;"><path d="M5 12h14"/></svg>';
            }
          } else {
            if (row.efficiency_kml >= 12.5) {
              badge = 'green';
              icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;vertical-align:middle;"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>';
            } else if (row.efficiency_kml >= 10) {
              badge = 'yellow';
              icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;vertical-align:middle;"><path d="M5 12h14"/></svg>';
            } else {
              badge = 'red';
              icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;vertical-align:middle;"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>';
            }
          }
          effCell = `<span class="db-badge db-badge-${badge}">${icon}${row.efficiency_kml.toFixed(1)} km/L</span>`;
        }

        tbodyHtml += `
          <tr>
            <td>
              <div>${esc(fmtDate(row.date))}</div>
              ${!isAllVehicles && row.notes ? `<div class="db-row-note">${esc(row.notes)}</div>` : ''}
            </td>
            ${isAllVehicles ? `<td><div>${esc(row.station)}</div>${row.notes ? `<div class="db-row-note">${esc(row.notes)}</div>` : ''}</td>` : ''}
            <td>${row.trip_distance !== null ? row.trip_distance.toFixed(1) + ' km' : '—'}</td>
            <td>${row.liters_filled.toFixed(2)} L</td>
            <td>${fmtPeso(row.cost_per_liter)}</td>
            <td>${fmtPeso(row.fuel_price)}</td>
            <td>${effCell}</td>
          </tr>
        `;
      });

      fillupsSection.innerHTML += `
        <table class="db-table">
          <thead>
            <tr>
              <th>Date</th>
              ${isAllVehicles ? '<th>Vehicle</th>' : ''}
              <th>Distance</th>
              <th>Liters</th>
              <th>Price / L</th>
              <th>Total</th>
              <th>Efficiency</th>
            </tr>
          </thead>
          <tbody>${tbodyHtml}</tbody>
        </table>
      `;
    }
  }

  document.addEventListener('DOMContentLoaded', loadDashboard);
})();
