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

      // If no selection yet, and we have a default vehicle, use it
      if (vehicleId === null && data.vehicles && data.vehicles.length > 0) {
        const def = data.vehicles.find(v => v.is_default);
        if (def) {
          localStorage.setItem('mileo_active_vehicle_id', def.id);
          // Re-load to get the specific vehicle stats
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
    const { stats, vehicles, has_vehicles, fillups } = data;

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
        
        document.getElementById('db-vehicle-switcher').addEventListener('change', (e) => {
          localStorage.setItem('mileo_active_vehicle_id', e.target.value);
          loadDashboard();
        });
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
      document.getElementById('db-time-switcher').addEventListener('change', (e) => {
        localStorage.setItem('mileo_dashboard_time_range', e.target.value);
        renderDashboard(data);
      });
    }

    // Stats
    const statsContainer = document.getElementById('db-stats-container');
    statsContainer.innerHTML = '';
    const statsData = [];
    const isAllTime = currentTimeRange === 'all_time';
    const subtextSuffix = isAllTime ? 'All time' : 'This month';

    if (stats.total_fillups === 0) {
      statsData.push({ label: 'Total Fill-ups', value: '—', sub_html: 'Start logging fill-ups' });
      statsData.push({ label: 'Avg Efficiency', value: '—', sub_html: '—' });
      statsData.push({ label: 'Total Spent', value: '—', sub_html: '—' });
      statsData.push({ label: 'Avg Cost / km', value: '—', sub_html: '—' });
    } else {
      const displayFillups = isAllTime ? stats.total_fillups : stats.month_fillups;
      const displaySpent = isAllTime ? stats.total_spent : stats.month_spent;
      const displayKml = isAllTime ? stats.avg_kml : stats.month_avg_kml;
      const displayCostKm = isAllTime ? stats.avg_cost_km : stats.month_avg_cost_km;

      statsData.push({
        label: 'Total Fill-ups',
        value: displayFillups,
        sub_html: subtextSuffix
      });
      statsData.push({
        label: 'Avg Efficiency',
        value: displayKml !== null ? displayKml.toFixed(1) + ' km/L' : '—',
        sub_html: subtextSuffix
      });
      statsData.push({
        label: 'Total Spent',
        value: fmtPeso(displaySpent),
        sub_html: subtextSuffix
      });
      statsData.push({
        label: 'Avg Cost / km',
        value: displayCostKm !== null ? fmtPeso(displayCostKm) : '—',
        sub_html: subtextSuffix
      });
    }

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
        let badge = 'yellow', icon = '';
        if (row.efficiency_kml === null) { 
          badge = 'yellow'; 
        } else if (row.efficiency_kml >= 12.5) { 
          badge = 'green'; 
          icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;vertical-align:middle;"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg>';
        } else if (row.efficiency_kml >= 10) { 
          badge = 'yellow'; 
          icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;vertical-align:middle;"><path d="M5 12h14"/></svg>';
        } else { 
          badge = 'red'; 
          icon = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;vertical-align:middle;"><path d="M12 5v14"/><path d="m19 12-7 7-7-7"/></svg>';
        }

        tbodyHtml += `
          <tr>
            <td>${esc(fmtDate(row.date))}</td>
            <td>
              <div>${esc(row.station)}</div>
              ${row.notes ? `<div class="db-row-note">${esc(row.notes)}</div>` : ''}
            </td>
            <td>${row.liters_filled.toFixed(1)} L</td>
            <td>${fmtPeso(row.cost_per_liter)}</td>
            <td>${fmtPeso(row.fuel_price)}</td>
            <td>
              ${row.efficiency_kml !== null 
                ? `<span class="db-badge db-badge-${badge}">${icon}${row.efficiency_kml.toFixed(1)} km/L</span>` 
                : '—'}
            </td>
          </tr>
        `;
      });

      fillupsSection.innerHTML += `
        <table class="db-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Vehicle</th>
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
