(function () {
  'use strict';

  function esc(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function fmtDate(dateStr) {
    var d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function fmtPeso(n) {
    return '&#8369;' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function effBadgeClass(kml) {
    if (kml >= 12.5) return 'hist-badge--good';
    if (kml >= 10)   return 'hist-badge--neutral';
    return 'hist-badge--bad';
  }

  function effBadgeLabel(kml) {
    if (kml >= 12.5) return 'Above Average';
    if (kml >= 10)   return 'On Track';
    return 'Below Average';
  }

  function buildStatCard(label, value, delta) {
    return '<div class="hist-stat-card">' +
      '<div class="hist-stat-label">' + esc(label) + '</div>' +
      '<div class="hist-stat-value">' + value + '</div>' +
      (delta ? '<div class="hist-stat-delta">' + delta + '</div>' : '') +
    '</div>';
  }

  function buildDeltaHtml(current, prior, fmt, unit, higherIsBetter) {
    if (prior == null || current == null) return '';
    var diff = current - prior;
    var pct  = (diff / Math.abs(prior)) * 100;
    var sign  = diff >= 0 ? '+' : '';
    var arrow = diff > 0 ? '&#8593;' : (diff < 0 ? '&#8595;' : '&#8594;');
    var isGood = higherIsBetter ? diff >= 0 : diff <= 0;
    var cls = isGood ? 'hist-delta--good' : 'hist-delta--bad';
    return '<span class="fld-delta ' + cls + '">' + arrow + ' ' + sign + fmt(Math.abs(diff)) + unit + ' (' + sign + pct.toFixed(1) + '%)</span>';
  }

  var BACK_ICON = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>';

  function buildContent(data) {
    var log           = data.log;
    var prior         = data.prior;
    var fillup_number = data.fillup_number;

    var tankBadgeCls   = log.is_full_tank ? 'fld-tank-badge--full' : 'fld-tank-badge--partial';
    var tankBadgeLabel = log.is_full_tank ? 'Full Tank' : 'Partial';

    var html = '';

    // Topbar: back link left, Edit + Delete right
    html += '<div class="fld-topbar">' +
      '<a href="?page=history" class="fld-back-btn">' + BACK_ICON + ' History</a>' +
      '<div class="fld-topbar-actions">' +
        '<button id="fld-edit-btn">Edit</button>' +
        '<button id="fld-delete-btn">Delete</button>' +
      '</div>' +
    '</div>';

    // Header: date + fill-up meta + tank badge
    html += '<div class="fld-header">' +
      '<div class="fld-header-left">' +
        '<div class="fld-title">' + esc(fmtDate(log.log_date)) + '</div>' +
        '<div class="fld-meta">Fill-up #' + esc(String(fillup_number)) + ' &middot; <span class="fld-meta-vehicle">' + esc(log.vehicle_name) + '</span></div>' +
      '</div>' +
      '<span class="fld-tank-badge ' + tankBadgeCls + '">' + tankBadgeLabel + '</span>' +
    '</div>';

    // Scrollable content below header
    html += '<div class="fld-inner">';

    // Efficiency hero card with gauge
    if (log.is_full_tank && log.efficiency_kml !== null) {
      var kml      = log.efficiency_kml;
      var badgeCls = effBadgeClass(kml);
      var badgeLbl = effBadgeLabel(kml);

      var effDeltaHtml = '';
      if (prior && prior.efficiency_kml !== null) {
        var pct      = ((kml - prior.efficiency_kml) / prior.efficiency_kml) * 100;
        var sign     = pct >= 0 ? '+' : '';
        var arrow    = pct > 0 ? '↑' : (pct < 0 ? '↓' : '→');
        var deltaCls = pct >= 0 ? 'hist-delta--good' : 'hist-delta--bad';
        effDeltaHtml = '<div class="fld-perf-delta ' + deltaCls + '">' + arrow + ' ' + sign + pct.toFixed(1) + '% vs prior</div>';
      }

      var perfModifier = badgeCls.replace('hist-badge--', 'fld-perf-card--');
      html += '<div class="fld-perf-card ' + perfModifier + '">' +
        '<div class="fld-perf-left">' +
          '<div class="fld-perf-label">Efficiency</div>' +
          '<div class="fld-perf-value">' + esc(kml.toFixed(1)) + '<span class="fld-perf-unit"> km/L</span></div>' +
          effDeltaHtml +
        '</div>' +
        '<div class="fld-perf-gauge">' +
          '<div class="fld-gauge-ring">' +
            '<div class="fld-gauge-badge">' + badgeLbl + '</div>' +
          '</div>' +
        '</div>' +
      '</div>';
    }

    // Stats: Journey
    html += '<div class="fld-stats-section">' +
      '<div class="fld-stats-section-label">Journey</div>' +
      '<div class="hist-stats-grid hist-stats-grid--3col">' +
      buildStatCard('Odometer',      esc(log.odometer.toLocaleString('en-PH')) + ' km', '') +
      buildStatCard('Trip Distance', log.trip_distance !== null ? esc(Number(log.trip_distance).toFixed(1)) + ' km' : '&mdash;', '') +
      buildStatCard('Liters Filled', esc(log.liters_filled.toFixed(2)) + ' L', '') +
      '</div></div>';

    // Stats: Cost
    html += '<div class="fld-stats-section">' +
      '<div class="fld-stats-section-label">Cost</div>' +
      '<div class="hist-stats-grid hist-stats-grid--3col">' +
      buildStatCard('Total Cost',    fmtPeso(log.fuel_price), '') +
      buildStatCard('Price / Liter', log.cost_per_liter !== null ? fmtPeso(log.cost_per_liter) : '&mdash;', '') +
      buildStatCard('Cost / km',     log.cost_per_km !== null ? fmtPeso(log.cost_per_km) + '/km' : '&mdash;', '') +
      '</div></div>';

    // Comparison to prior fill-up
    if (prior && (prior.efficiency_kml !== null || prior.cost_per_km !== null)) {
      html += '<div class="fld-comparison"><div class="fld-section-title">vs Prior Fill-Up</div><div class="fld-comparison-grid">';

      if (log.efficiency_kml !== null && prior.efficiency_kml !== null) {
        var effPct   = ((log.efficiency_kml - prior.efficiency_kml) / prior.efficiency_kml) * 100;
        var effSign  = effPct >= 0 ? '+' : '';
        var effArrow = effPct > 0 ? '&#8593;' : (effPct < 0 ? '&#8595;' : '&#8594;');
        var effCls   = effPct >= 0 ? 'hist-delta--good' : 'hist-delta--bad';
        html += '<div class="fld-comparison-item">' +
          '<div class="fld-cmp-left">' +
            '<div class="fld-cmp-label">Efficiency</div>' +
            '<div class="fld-cmp-flow">' +
              '<span class="fld-cmp-from">' + esc(prior.efficiency_kml.toFixed(1)) + '</span>' +
              '<span class="fld-cmp-sep">→</span>' +
              '<span class="fld-cmp-to">' + esc(log.efficiency_kml.toFixed(1)) + ' km/L</span>' +
            '</div>' +
          '</div>' +
          '<div class="fld-cmp-delta ' + effCls + '">' + effArrow + ' ' + effSign + effPct.toFixed(1) + '%</div>' +
        '</div>';
      }

      if (log.cost_per_km !== null && prior.cost_per_km !== null) {
        var cstPct   = ((log.cost_per_km - prior.cost_per_km) / prior.cost_per_km) * 100;
        var cstSign  = cstPct >= 0 ? '+' : '';
        var cstArrow = cstPct < 0 ? '&#8595;' : (cstPct > 0 ? '&#8593;' : '&#8594;');
        var cstCls   = cstPct <= 0 ? 'hist-delta--good' : 'hist-delta--bad';
        html += '<div class="fld-comparison-item">' +
          '<div class="fld-cmp-left">' +
            '<div class="fld-cmp-label">Cost / km</div>' +
            '<div class="fld-cmp-flow">' +
              '<span class="fld-cmp-from">' + fmtPeso(prior.cost_per_km) + '</span>' +
              '<span class="fld-cmp-sep">→</span>' +
              '<span class="fld-cmp-to">' + fmtPeso(log.cost_per_km) + '/km</span>' +
            '</div>' +
          '</div>' +
          '<div class="fld-cmp-delta ' + cstCls + '">' + cstArrow + ' ' + cstSign + cstPct.toFixed(1) + '%</div>' +
        '</div>';
      }

      html += '</div></div>';
    }

    // Notes
    if (log.notes && log.notes.trim() !== '') {
      html += '<div class="fld-notes">' +
        '<div class="fld-section-title">Notes</div>' +
        '<div class="fld-notes-body">' + esc(log.notes) + '</div>' +
      '</div>';
    }

    html += '</div>'; // close .fld-inner

    return html;
  }

  function showToast(message, type) {
    var root = document.getElementById('mm-toast-root');
    if (!root) return;
    var toast = document.createElement('div');
    toast.className = 'ql-toast' + (type ? ' ' + type : '');
    toast.textContent = message;
    root.appendChild(toast);
    setTimeout(function () { toast.remove(); }, 5500);
  }

  var currentLogId = null;

  function openDeleteConfirm() {
    var overlay = document.getElementById('fld-confirm-overlay');
    if (overlay) {
      overlay.removeAttribute('hidden');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeDeleteConfirm() {
    var overlay = document.getElementById('fld-confirm-overlay');
    if (overlay) {
      overlay.setAttribute('hidden', '');
      document.body.style.overflow = '';
    }
  }

  document.addEventListener('click', function (e) {
    if (e.target.id === 'fld-edit-btn') {
      if (currentLogId && typeof window.openEditModal === 'function') {
        window.openEditModal(currentLogId);
      }
      return;
    }

    if (e.target.id === 'fld-delete-btn') {
      openDeleteConfirm();
      return;
    }

    if (e.target.closest('#fld-confirm-cancel-btn') || e.target.id === 'fld-confirm-overlay') {
      closeDeleteConfirm();
      return;
    }

    if (e.target.closest('#fld-confirm-delete-btn')) {
      var deleteBtn = document.getElementById('fld-confirm-delete-btn');
      if (!deleteBtn || deleteBtn.disabled) return;
      deleteBtn.disabled = true;
      deleteBtn.textContent = 'Deleting…';
      fetch('?api=fuel-logs/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentLogId }),
      })
        .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, data: d }; }); })
        .then(function (r) {
          if (!r.ok) throw new Error(r.data.error || 'Failed to delete fill-up.');
          showToast('Fill-up deleted.', 'success');
          closeDeleteConfirm();
          setTimeout(function () { location.href = '?page=history'; }, 700);
        })
        .catch(function (err) {
          showToast(err.message, 'error');
          if (deleteBtn) { deleteBtn.disabled = false; deleteBtn.textContent = 'Delete'; }
        });
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    var params = new URLSearchParams(window.location.search);
    var id     = parseInt(params.get('id'), 10);

    if (!id || id <= 0) {
      location.href = '?page=history';
      return;
    }

    currentLogId = id;

    fetch('?api=fuel-logs/detail&id=' + id)
      .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, data: d }; }); })
      .then(function (r) {
        var skeleton = document.getElementById('fld-skeleton');
        var content  = document.getElementById('fld-content');
        var error    = document.getElementById('fld-error');

        if (!r.ok) {
          if (skeleton) skeleton.setAttribute('hidden', '');
          if (error) {
            error.removeAttribute('hidden');
            error.innerHTML = '<div class="fld-error-msg">' + esc(r.data.error || 'Failed to load fill-up.') +
              ' <a href="?page=history" class="fld-back-btn">&#8592; Back to History</a></div>';
          }
          return;
        }

        if (skeleton) skeleton.setAttribute('hidden', '');
        if (content) {
          content.innerHTML = buildContent(r.data);
          content.removeAttribute('hidden');
        }
      })
      .catch(function (err) {
        var skeleton = document.getElementById('fld-skeleton');
        var error    = document.getElementById('fld-error');
        if (skeleton) skeleton.setAttribute('hidden', '');
        if (error) {
          error.removeAttribute('hidden');
          error.innerHTML = '<div class="fld-error-msg">' + esc(err.message) +
            ' <a href="?page=history" class="fld-back-btn">&#8592; Back to History</a></div>';
        }
      });
  });
})();
