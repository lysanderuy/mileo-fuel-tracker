/* Mileo Marketing — vanilla JS
 * Drives: receipt animation, feature-tour picker, smooth-scroll, auth modal, toast.
 */
(function () {
  'use strict';

  // Utility
  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const h  = (tag, attrs = {}, ...children) => {
    const el = document.createElement(tag);
    for (const k in attrs) {
      const v = attrs[k];
      if (v == null || v === false) continue;
      if (k === 'class') el.className = v;
      else if (k === 'style' && typeof v === 'object') Object.assign(el.style, v);
      else if (k.startsWith('on') && typeof v === 'function') el.addEventListener(k.slice(2).toLowerCase(), v);
      else if (k === 'html') el.innerHTML = v;
      else el.setAttribute(k, v);
    }
    for (const c of children.flat()) {
      if (c == null || c === false) continue;
      el.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
    }
    return el;
  };

  // Hero receipt animation
  function initReceipt() {
    const fieldsRoot = $('[data-receipt-fields]');
    const statsEl    = $('[data-receipt-stats]');
    const fillEl     = $('[data-receipt-fill]');
    const timerEl    = $('[data-receipt-timer]');
    if (!fieldsRoot) return;

    // CONFIG - edit values here
    const CONFIG = {
      odometer: 83318,
      trip: 345,
      liters: 35.74,
      pricePerLiter: 83.40,  // Edit this, totalPrice auto-calculated
    };

    const state = { ...CONFIG };

    function computeDerived() {
      state.totalPrice = state.pricePerLiter * state.liters;
    }

    function computeStats() {
      const totalCost = state.totalPrice;
      const efficiency = state.liters > 0 ? state.trip / state.liters : 0;
      const costPerKm = efficiency > 0 ? totalCost / state.trip : 0;
      return { totalCost, efficiency, costPerKm };
    }

    function formatStats() {
      const { totalCost, efficiency, costPerKm } = computeStats();
      return {
        totalCost: `₱${totalCost.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`,
        efficiency: efficiency.toFixed(1),
        costPerKm: `₱${costPerKm.toFixed(2)}`,
      };
    }

    computeDerived();

    const steps = [
      { key: 'odometer', label: 'Odometer (km)', value: () => state.odometer.toLocaleString(), unit: 'km', duration: 1200 },
      { key: 'trip', label: 'Trip', value: () => state.trip.toLocaleString(), unit: 'km', duration: 900 },
      { key: 'liters', label: 'Liters Filled', value: () => state.liters.toFixed(1), unit: 'L', duration: 900 },
      { key: 'totalPrice', label: 'Total Price', value: () => `₱${state.totalPrice.toLocaleString('en-PH', { minimumFractionDigits: 2 })}`, unit: '₱', duration: 900 },
    ];
    const TOTAL = steps.reduce((s, x) => s + x.duration, 0) + 800;
    const LOOP  = TOTAL + 2800;

    const fieldEls = steps.map((s) => {
      const prefix = s.unit === '₱'
        ? h('span', { class: 'mm-receipt-field-unit-prefix' }, '₱')
        : null;
      const num    = h('span', { class: 'mm-receipt-field-num' }, '');
      const suffix = s.unit !== '₱'
        ? h('span', { class: 'mm-receipt-field-unit' }, s.unit)
        : null;
      const caret  = h('span', { class: 'mm-receipt-caret' });

      const wrap = h('div', { class: 'mm-receipt-field' },
        h('div', { class: 'mm-receipt-field-label' }, s.label),
        h('div', { class: 'mm-receipt-field-value' }, prefix, num, suffix, caret),
      );
      return { wrap, num, caret, key: s.key };
    });
    fieldEls.forEach(f => fieldsRoot.appendChild(f.wrap));

    const start = performance.now();
    function tick(now) {
      const t = (now - start) % LOOP;

      let acc = 0;
      const progress = steps.map((s) => {
        const local = t - acc;
        acc += s.duration;
        if (local < 0) return 0;
        if (local >= s.duration) return 1;
        return local / s.duration;
      });

      for (let i = 0; i < steps.length; i++) {
        const p = progress[i];
        const f = fieldEls[i];
        const active = p > 0 && p < 1;
        const done   = p >= 1;
        f.wrap.classList.toggle('is-active', active);
        f.wrap.classList.toggle('is-done', done);
        f.caret.style.display = active ? '' : 'none';
        const shown = done ? steps[i].value() : steps[i].value().slice(0, Math.floor(steps[i].value().length * p));
        f.num.textContent = shown;
      }

      const allDone = t >= TOTAL - 800;
      if (allDone) {
        statsEl.classList.add('is-visible');
        const stats = formatStats();
        const statValues = $$('.mm-receipt-stat-value', statsEl);
        if (statValues[0]) statValues[0].textContent = stats.totalCost;
        if (statValues[1]) statValues[1].textContent = stats.efficiency;
        if (statValues[2]) statValues[2].textContent = stats.costPerKm;
      }

      fillEl.style.width = `${Math.min(100, (t / TOTAL) * 100)}%`;
      const elapsed = Math.min(15, (t / TOTAL) * 15);
      timerEl.textContent = elapsed.toFixed(1);

      requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  // Feature tour
  const TOUR = [
    {
      key: 'log',
      kicker: 'At the pump',
      title: 'Fuel Log',
      desc: 'Simple form, fast entry.',
      render: () => `
        <div class="mm-mock mm-mock-log-actual">
          <div class="mm-mock-log-header">
            <div class="mm-mock-log-title">Log Fill-Up</div>
          </div>
          <div class="mm-mock-log-sections">
            <div class="mm-mock-log-context">
              <div class="mm-mock-field-row">
                <div class="mm-mock-field">
                  <div class="mm-mock-field-label">Vehicle</div>
                  <div class="mm-mock-field-val mm-mock-field-val-sm">Toyota Vios</div>
                </div>
                <div class="mm-mock-field">
                  <div class="mm-mock-field-label">Date</div>
                  <div class="mm-mock-field-val mm-mock-field-val-sm">May 25, 2026</div>
                </div>
              </div>
              <div class="mm-mock-field">
                <div class="mm-mock-field-label">Odometer (km)</div>
                <div class="mm-mock-field-val">42,318</div>
                <div class="mm-mock-field-help">Last recorded: 41,906 km</div>
              </div>
            </div>
            <div class="mm-mock-log-fuel">
              <div class="mm-mock-field">
                <div class="mm-mock-field-label">Liters</div>
                <div class="mm-mock-field-val">32.8 L</div>
              </div>
              <div class="mm-mock-field">
                <div class="mm-mock-field-label">Total Price</div>
                <div class="mm-mock-field-val">₱2,706</div>
              </div>
            </div>
            <div class="mm-mock-log-extras">
              <div class="mm-mock-toggle-row">
                <span class="mm-mock-toggle-label">Full tank</span>
                <div class="mm-mock-toggle-on"></div>
              </div>
            </div>
          </div>
          <div class="mm-mock-log-actions">
            <button class="mm-mock-btn-primary">Save Entry</button>
          </div>
        </div>`,
    },
    {
      key: 'stats',
      kicker: 'Right after save',
      title: 'Instant Stats',
      desc: 'Performance at a glance.',
      render: () => `
        <div class="mm-mock mm-mock-stats-actual">
          <div class="mm-mock-stats-header">
            <div class="mm-mock-stats-check">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"><path d="M20 6L9 17l-5-5"/></svg>
            </div>
            <div>
              <div class="mm-mock-stats-headline">Fill-Up Logged!</div>
              <div class="mm-mock-stats-meta">Fill-up #47 · May 25, 2026</div>
            </div>
          </div>
          <div class="mm-mock-stats-perf">
            <div class="mm-mock-perf-left">
              <div class="mm-mock-stat-label">Efficiency</div>
              <div class="mm-mock-perf-value">12.6<span class="mm-mock-perf-unit"> km/L</span></div>
              <div class="mm-mock-perf-delta hist-delta--good">↑ +0.7 vs last</div>
            </div>
            <div class="mm-mock-badge mm-mock-badge--good">Better than avg</div>
          </div>
          <div class="mm-mock-stats-grid mm-mock-stats-grid--2col">
            ${mockStatCard('32.8 L', 'Liters')}
            ${mockStatCard('₱2,706', 'Total Cost')}
            ${mockStatCard('₱82.50', '/ Liter')}
            ${mockStatCard('₱6.58', 'Cost / km')}
          </div>
        </div>`,
    },
    {
      key: 'dash',
      kicker: 'Every morning',
      title: 'Dashboard',
      desc: 'Your fuel story.',
      render: () => `
        <div class="mm-mock mm-mock-dash-actual">
          <div class="mm-mock-dash-topbar">
            <div>
              <div class="mm-mock-dash-title">Dashboard</div>
              <div class="mm-mock-dash-subtitle">Your fuel tracking overview</div>
            </div>
            <div class="mm-mock-dash-actions">
              <div class="mm-mock-dash-vehicle-select">Toyota Vios</div>
              <button class="mm-mock-dash-log-btn">+ Log Fill-Up</button>
            </div>
          </div>
          <div class="mm-mock-dash-stats">
            <div class="mm-mock-dash-stat-card">
              <div class="mm-mock-dash-stat-label">Total Fill-ups</div>
              <div class="mm-mock-dash-stat-val">47</div>
              <div class="mm-mock-dash-stat-sub">All time</div>
            </div>
            <div class="mm-mock-dash-stat-card">
              <div class="mm-mock-dash-stat-label">Total Distance</div>
              <div class="mm-mock-dash-stat-val">18,432<span class="mm-mock-dash-stat-unit">km</span></div>
              <div class="mm-mock-dash-stat-sub">All time</div>
            </div>
            <div class="mm-mock-dash-stat-card">
              <div class="mm-mock-dash-stat-label">Total Spent</div>
              <div class="mm-mock-dash-stat-val">₱28,450</div>
              <div class="mm-mock-dash-stat-sub">All time</div>
            </div>
          </div>
          <div class="mm-mock-dash-fleet">
            <div class="mm-mock-dash-fleet-head">
              <span>Fleet Overview</span>
              <span class="mm-mock-dash-fleet-total">₱28,450</span>
            </div>
            <div class="mm-mock-dash-fleet-row">
              <div class="mm-mock-dash-fleet-left">
                <div class="mm-mock-dash-fleet-name">Toyota Vios</div>
                <div class="mm-mock-dash-fleet-metrics">
                  <div class="mm-mock-dash-fleet-metric">
                    <div class="mm-mock-dash-fleet-val mm-mock-dash-fleet-val--eff">12.7</div>
                    <div class="mm-mock-dash-fleet-key">km/L</div>
                  </div>
                  <div class="mm-mock-dash-fleet-metric">
                    <div class="mm-mock-dash-fleet-val mm-mock-dash-fleet-val--cost">4.76</div>
                    <div class="mm-mock-dash-fleet-key">₱/km</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="mm-mock-dash-fleet-bar-track"><div class="mm-mock-dash-fleet-bar-fill" style="width:100%"></div></div>
          </div>
        </div>`,
    },
    {
      key: 'cars',
      kicker: 'Your fleet',
      title: 'Multiple Vehicles',
      desc: 'Manage your garage.',
      render: () => `
        <div class="mm-mock mm-mock-cars-actual">
          <div class="mm-mock-cars-tabs">
            <div class="mm-mock-cars-tab is-active">Active <span class="mm-mock-cars-tab-count">2</span></div>
            <div class="mm-mock-cars-tab">Archived <span class="mm-mock-cars-tab-count">1</span></div>
          </div>
          <div class="mm-mock-cars-list">
            ${mockCarActual('Toyota Vios', 'ABC 1234', '12.7 km/L', '₱4.76/km', true, true)}
            ${mockCarActual('Honda Click 125', 'XYZ 5678', '48.2 km/L', '₱1.85/km', false, false)}
          </div>
        </div>`,
    },
    {
      key: 'history',
      kicker: 'Anytime',
      title: 'Full History',
      desc: 'Every fill-up tracked.',
      render: () => `
        <div class="mm-mock mm-mock-history-actual">
          <div class="mm-mock-history-header">
            <div>
              <div class="mm-mock-history-title">History</div>
              <div class="mm-mock-history-subtitle">All fill-ups for your selected vehicle</div>
            </div>
            <div class="mm-mock-history-actions">
              <div class="mm-mock-history-vehicle-select">Toyota Vios</div>
              <button class="mm-mock-history-log-btn">+ Log Fill-Up</button>
            </div>
          </div>
          <div class="mm-mock-history-table">
            <div class="mm-mock-history-row mm-mock-history-head">
              <div class="mm-mock-history-th">Date</div>
              <div class="mm-mock-history-th">Distance</div>
              <div class="mm-mock-history-th">Liters</div>
              <div class="mm-mock-history-th">Price / L</div>
              <div class="mm-mock-history-th">Total</div>
              <div class="mm-mock-history-th">Efficiency</div>
            </div>
            ${mockHistoryRow('Apr 18, 2026', '412 km', '32.8 L', '₱82.50', '₱2,706', '12.6', 'up')}
            ${mockHistoryRow('Apr 04, 2026', '389 km', '34.2 L', '₱71.50', '₱2,458', '11.9', 'up')}
            ${mockHistoryRow('Mar 22, 2026', '445 km', '40.1 L', '₱72.00', '₱2,891', '11.1', 'down')}
          </div>
        </div>`,
    },
  ];

  function mockFieldSimple(label, value, unit, prefix, active) {
    return `
      <div class="mm-mockfield-simple ${active ? 'is-active' : ''}">
        <div class="mm-mockfield-simple-label">${label}</div>
        <div class="mm-mockfield-simple-val">
          ${prefix ? `<span class="mm-mockfield-simple-unit">${unit}</span>` : ''}
          ${value}
          ${!prefix ? `<span class="mm-mockfield-simple-unit">${unit}</span>` : ''}
        </div>
      </div>`;
  }
  function mockStatCompact(v, l) {
    return `<div class="mm-mockstat-compact"><div class="mm-mockstat-compact-v">${v}</div><div class="mm-mockstat-compact-l">${l}</div></div>`;
  }
  function mockCarSimple(name, stat, active, color) {
    const initials = name.split(' ').map(w => w[0]).join('').slice(0, 2);
    return `
      <div class="mm-mockcar-simple ${active ? 'is-active' : ''}">
        <div class="mm-mockcar-simple-badge" style="background:${color}">${initials}</div>
        <div class="mm-mockcar-simple-body">
          <div class="mm-mockcar-simple-name">${name}</div>
          <div class="mm-mockcar-simple-stat">${stat}</div>
        </div>
      </div>`;
  }

  function mockStatCard(value, label) {
    return `
      <div class="mm-mock-stat-card">
        <div class="mm-mock-stat-card-val">${value}</div>
        <div class="mm-mock-stat-card-label">${label}</div>
      </div>`;
  }

  function mockCarActual(name, plate, eff, cost, active, isDefault) {
    const initials = name.split(' ').map(w => w[0]).join('').slice(0, 2);
    const color = active ? '#F59500' : '#2B72C0';
    return `
      <div class="mm-mock-veh-row ${active ? 'is-active' : ''}">
        <button class="mm-mock-veh-star ${isDefault ? 'is-default' : ''}" aria-label="Set as default">${isDefault ? '★' : '☆'}</button>
        <div class="mm-mock-veh-icon">
          <span class="mm-mock-veh-icon-mark">🚗</span>
        </div>
        <div class="mm-mock-veh-info">
          <div class="mm-mock-veh-head">
            <div class="mm-mock-veh-name">${name}</div>
            <div class="mm-mock-veh-meta">
              <span class="mm-mock-veh-plate-inline">${plate}</span>
            </div>
          </div>
          <div class="mm-mock-veh-badges">
            <span class="mm-mock-veh-badge">Eff: ${eff}</span>
            <span class="mm-mock-veh-badge">Cost: ${cost}</span>
          </div>
        </div>
        <div class="mm-mock-veh-actions">
          <button class="mm-mock-veh-btn">Edit</button>
          <button class="mm-mock-veh-btn mm-mock-veh-btn-danger">Delete</button>
        </div>
      </div>`;
  }

  function mockHistoryRow(date, distance, liters, price, total, eff, direction) {
    const effClass = direction === 'up' ? 'up' : 'down';
    const arrow = direction === 'up' ? '↑' : '↓';
    return `
      <div class="mm-mock-history-row">
        <div class="mm-mock-history-cell">${date}</div>
        <div class="mm-mock-history-cell">${distance}</div>
        <div class="mm-mock-history-cell">${liters}</div>
        <div class="mm-mock-history-cell">${price}</div>
        <div class="mm-mock-history-cell">${total}</div>
        <div class="mm-mock-history-eff ${effClass}">${arrow} ${eff}</div>
      </div>`;
  }

  function initFeatureTour() {
    const list  = $('[data-tour-list]');
    const stage = $('[data-tour-stage]');
    if (!list || !stage) return;

    let idx = 0;

    TOUR.forEach((t, i) => {
      const btn = h('button', {
        class: `mm-ft-item ${i === 0 ? 'is-active' : ''}`,
        type: 'button',
        'data-idx': i,
        onClick: () => select(i),
      },
        h('span', { class: 'mm-ft-item-num' }, String(i + 1).padStart(2, '0')),
        h('span', { class: 'mm-ft-item-body' },
          h('span', { class: 'mm-ft-item-title' }, t.title),
          h('span', { class: 'mm-ft-item-desc' }, t.desc),
        ),
        h('span', { class: 'mm-ft-item-chev' }, '→'),
      );
      list.appendChild(btn);
    });

    function select(i) {
      idx = i;
      $$('.mm-ft-item', list).forEach((el, j) => el.classList.toggle('is-active', j === i));
      stage.innerHTML = TOUR[i].render();
      stage.classList.remove('is-swap');
      void stage.offsetWidth;
      stage.classList.add('is-swap');
    }

    select(0);
  }

  // Nav / scroll / auth
  function initNav() {
    document.addEventListener('click', (e) => {
      const nav    = e.target.closest('[data-nav]');
      const scroll = e.target.closest('[data-scroll]');
      if (nav) {
        const k = nav.getAttribute('data-nav');
        if (k === 'signup') window.location.href = '?page=signup';
        else if (k === 'login') window.location.href = '?page=login';
        else if (k === 'home') window.location.href = '?page=landing';
      }
      if (scroll) {
        const sel = scroll.getAttribute('data-scroll');
        const el  = document.querySelector(sel);
        if (el) window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - 60, behavior: 'smooth' });
      }
    });
  }

  // Boot
  function boot() {
    initReceipt();
    initFeatureTour();
    initNav();
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
