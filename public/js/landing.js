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

    const steps = [
      { label: 'Odometer',  value: '42,318', unit: 'km', duration: 1200 },
      { label: 'Trip',      value: '487',    unit: 'km', duration: 900  },
      { label: 'Price / L', value: '60.40',  unit: '₱',  duration: 900  },
      { label: 'Liters',    value: '38.4',   unit: 'L',  duration: 900  },
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
      return { wrap, num, caret };
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
        const shown = done ? steps[i].value : steps[i].value.slice(0, Math.floor(steps[i].value.length * p));
        f.num.textContent = shown;
      }

      const allDone = t >= TOTAL - 800;
      statsEl.classList.toggle('is-visible', allDone);

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
      title: 'Quick Log',
      desc: 'Enter four fields in seconds. Done.',
      render: () => `
        <div class="mm-mock mm-mock-log">
          <div class="mm-mock-head">
            <div class="mm-mock-back">←</div>
            <div class="mm-mock-title">New fill-up</div>
            <div class="mm-mock-save">Save</div>
          </div>
          <div class="mm-mock-grid">
            ${mockField('Odometer', '42,318', 'km')}
            ${mockField('Trip', '487', 'km', false, true)}
            ${mockField('Price / L', '60.40', '₱', true)}
            ${mockField('Liters', '38.4', 'L')}
          </div>
          <button class="mm-mock-cta">Save fill-up</button>
        </div>`,
    },
    {
      key: 'stats',
      kicker: 'Right after save',
      title: 'Instant Stats',
      desc: 'Cost breakdown and fuel efficiency, instantly.',
      render: () => `
        <div class="mm-mock mm-mock-stats">
          <div class="mm-mock-stats-top">
            <div class="mm-mock-stats-label">This fill-up</div>
            <div class="mm-mock-stats-hero">12.7<span>km/L</span></div>
            <div class="mm-mock-stats-delta">↑ 0.8 better than last</div>
          </div>
          <div class="mm-mock-stats-row">
            ${mockStat('₱2,319', 'Total')}
            ${mockStat('₱4.76', 'per km')}
            ${mockStat('38.4 L', 'Filled')}
          </div>
          <div class="mm-mock-stats-chip">Your cheapest km this month</div>
        </div>`,
    },
    {
      key: 'dash',
      kicker: 'Every morning',
      title: 'Dashboard',
      desc: 'Monthly trends and spending patterns at a glance.',
      render: () => `
        <div class="mm-mock mm-mock-dash">
          <div class="mm-mock-dash-header">
            <div class="mm-mock-dash-period">April 2026</div>
            <div class="mm-mock-dash-metrics">
              <div class="mm-mock-dash-metric"><div class="mm-mock-dash-metric-v">₱9,240</div><div class="mm-mock-dash-metric-k">Total</div></div>
              <div class="mm-mock-dash-metric"><div class="mm-mock-dash-metric-v">12.4</div><div class="mm-mock-dash-metric-k">km/L</div></div>
              <div class="mm-mock-dash-metric is-down"><div class="mm-mock-dash-metric-v">-12%</div><div class="mm-mock-dash-metric-k">vs Mar</div></div>
            </div>
          </div>
          ${mockChart()}
        </div>`,
    },
    {
      key: 'cars',
      kicker: 'Garage',
      title: 'Multiple Vehicles',
      desc: 'Track up to 10 cars with independent stats.',
      render: () => `
        <div class="mm-mock mm-mock-cars">
          <div class="mm-mock-cars-head">Your garage · 3 of 10</div>
          ${mockCar('Toyota Vios', 'ABC 1234', '12.7 km/L', true, '#F59500')}
          ${mockCar('Honda Click 125', 'XYZ 5678', '48.2 km/L', false, '#228A55')}
          ${mockCar('Ford Ranger', 'TRK 4201', '8.1 km/L', false, '#2B72C0')}
        </div>`,
    },
    {
      key: 'history',
      kicker: 'Anytime',
      title: 'Full History',
      desc: 'Every fill-up sortable and filterable by date, efficiency, or vehicle.',
      render: () => `
        <div class="mm-mock mm-mock-history">
          <div class="mm-mock-hist-head">
            <div class="mm-mock-hist-title">History</div>
            <div class="mm-mock-hist-sort">Sort · km/L ↓</div>
          </div>
          ${mockRow('Apr 18', '38.4', '₱2,319', '12.7', 'up')}
          ${mockRow('Apr 04', '34.2', '₱2,054', '11.9', 'up')}
          ${mockRow('Mar 22', '40.1', '₱2,389', '11.1', 'down')}
          ${mockRow('Mar 08', '37.8', '₱2,252', '12.3', 'up')}
          ${mockRow('Feb 24', '35.6', '₱2,115', '11.8', 'flat')}
        </div>`,
    },
    {
      key: 'export',
      kicker: 'Your data',
      title: 'Data Export',
      desc: 'Download your entire log as CSV. Your data, always yours.',
      render: () => `
        <div class="mm-mock mm-mock-export">
          <div class="mm-mock-export-header">
            <div class="mm-mock-export-icon">📥</div>
            <div>
              <div class="mm-mock-export-title">Export your data</div>
              <div class="mm-mock-export-sub">CSV · 47 fill-ups · 8.2 KB</div>
            </div>
          </div>
          <div class="mm-mock-export-opts">
            <label class="mm-mock-export-opt is-on"><span class="mm-mock-check">✓</span>All fill-ups</label>
            <label class="mm-mock-export-opt is-on"><span class="mm-mock-check">✓</span>Vehicle metadata</label>
            <label class="mm-mock-export-opt"><span class="mm-mock-check"></span>Calculated stats</label>
          </div>
          <button class="mm-mock-cta">Download CSV</button>
        </div>`,
    },
  ];

  function mockField(label, value, unit, prefix, active) {
    return `
      <div class="mm-mockfield ${active ? 'is-active' : ''}">
        <div class="mm-mockfield-label">${label}</div>
        <div class="mm-mockfield-val">
          ${prefix ? `<span class="mm-mockfield-unit">${unit}</span>` : ''}
          ${value}
          ${!prefix ? `<span class="mm-mockfield-unit">${unit}</span>` : ''}
        </div>
      </div>`;
  }
  function mockStat(v, l) {
    return `<div class="mm-mockstat"><div class="mm-mockstat-v">${v}</div><div class="mm-mockstat-l">${l}</div></div>`;
  }
  function mockChart() {
    const bars = [9800, 10200, 11100, 10500, 10500, 9240];
    const months = ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'];
    const max = Math.max(...bars);
    return `<div class="mm-mockchart">${bars.map((b, i) => `
      <div class="mm-mockchart-col">
        <div class="mm-mockchart-bar" style="height:${(b / max) * 100}%;background:${i === bars.length - 1 ? '#F59500' : '#E5E3DE'}"></div>
        <div class="mm-mockchart-lbl">${months[i]}</div>
      </div>`).join('')}</div>`;
  }
  function mockCar(name, plate, stat, active, color) {
    const initials = name.split(' ').map(w => w[0]).join('').slice(0, 2);
    return `
      <div class="mm-mockcar ${active ? 'is-active' : ''}">
        <div class="mm-mockcar-badge" style="background:${color}">${initials}</div>
        <div style="flex:1;min-width:0">
          <div class="mm-mockcar-name">${name}</div>
          <div class="mm-mockcar-plate">${plate}</div>
        </div>
        <div class="mm-mockcar-stat">${stat}</div>
      </div>`;
  }
  function mockRow(date, liters, price, eff, trend) {
    const arrow = trend === 'up' ? '↑' : trend === 'down' ? '↓' : '—';
    return `
      <div class="mm-mockrow">
        <div class="mm-mockrow-date">${date}</div>
        <div class="mm-mockrow-mid">
          <div class="mm-mockrow-price">${price}</div>
          <div class="mm-mockrow-liters">${liters} L</div>
        </div>
        <div class="mm-mockrow-eff is-${trend}">${arrow} ${eff}<span>km/L</span></div>
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
        if (k === 'signup' || k === 'login') openModal(k);
      }
      if (scroll) {
        const sel = scroll.getAttribute('data-scroll');
        const el  = document.querySelector(sel);
        if (el) window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - 60, behavior: 'smooth' });
      }
    });
  }

  function openModal(mode) {
    const root = $('#mm-modal-root');
    closeModal();

    const inputStyle = 'height:48px;padding:0 16px;border:1px solid #E5E3DE;border-radius:12px;font-family:JetBrains Mono,monospace;font-size:16px;color:#38362F;background:#fff;outline:none;';
    const labelStyle = 'display:flex;flex-direction:column;gap:6px;';
    const spanStyle  = 'font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;color:#57544C;';

    const overlay = h('div', {
      style: 'position:fixed;inset:0;z-index:500;background:rgba(12,18,33,0.60);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:20px;animation:mm-fadeUp 250ms ease-out;',
      onClick: (e) => { if (e.target === overlay) closeModal(); },
    });

    const switchLink = h('a', {
      style: 'color:#CC7A00;font-weight:600;cursor:pointer;',
      onClick: () => openModal(mode === 'signup' ? 'login' : 'signup'),
    }, mode === 'signup' ? 'Sign in' : 'Create account');

    const modal = h('div', {
      style: 'background:#fff;border-radius:20px;padding:32px;width:100%;max-width:420px;box-shadow:0 24px 48px rgba(54,48,32,0.18);display:flex;flex-direction:column;gap:16px;',
      onClick: (e) => e.stopPropagation(),
    },
      h('div', { style: 'display:flex;align-items:center;gap:10px' },
        h('span', { style: 'font-size:22px' }, '⚡'),
        h('h3', { style: 'font-family:Space Grotesk,sans-serif;font-size:22px;font-weight:700;color:#38362F;margin:0;letter-spacing:-0.02em;' },
          mode === 'signup' ? 'Create your account' : 'Welcome back')
      ),
      h('p', { style: 'color:#57544C;font-size:14px;margin:0' },
        mode === 'signup' ? 'Free forever. No credit card.' : 'Sign in to log your next fill-up.'),
      h('label', { style: labelStyle },
        h('span', { style: spanStyle }, 'Email'),
        (() => {
          const inp = h('input', { type: 'email', value: 'driver@mileo.app', style: inputStyle });
          inp.addEventListener('focus', () => { inp.style.border = '1.5px solid #F59500'; inp.style.padding = '0 15.5px'; inp.style.boxShadow = '0 0 0 3px rgba(245,149,0,0.25)'; });
          inp.addEventListener('blur',  () => { inp.style.border = '1px solid #E5E3DE'; inp.style.padding = '0 16px'; inp.style.boxShadow = 'none'; });
          return inp;
        })(),
      ),
      h('label', { style: labelStyle },
        h('span', { style: spanStyle }, 'Password'),
        h('input', { type: 'password', value: '••••••••', style: inputStyle }),
      ),
      h('button', {
        class: 'mm-btn mm-btn-primary mm-btn-md',
        style: 'margin-top:4px',
        onClick: () => { closeModal(); showToast(mode === 'signup' ? 'Welcome to Mileo 👋' : 'Signed in'); },
      }, mode === 'signup' ? 'Create account' : 'Sign in'),
      h('div', { style: 'text-align:center;font-size:13px;color:#57544C' },
        mode === 'signup' ? 'Already have one? ' : 'New here? ',
        switchLink,
      ),
    );

    overlay.appendChild(modal);
    root.appendChild(overlay);

    document.addEventListener('keydown', onEsc);
  }
  function onEsc(e) { if (e.key === 'Escape') closeModal(); }
  function closeModal() {
    $('#mm-modal-root').innerHTML = '';
    document.removeEventListener('keydown', onEsc);
  }

  function showToast(msg) {
    const root = $('#mm-toast-root');
    const el = h('div', {
      style: 'position:fixed;bottom:32px;left:50%;transform:translateX(-50%);z-index:600;background:#fff;border:1px solid #F2F1EE;border-radius:12px;padding:12px 20px;box-shadow:0 8px 16px rgba(54,48,32,0.10);font-family:Plus Jakarta Sans,sans-serif;font-size:14px;font-weight:500;color:#38362F;display:flex;align-items:center;gap:10px;animation:mm-fadeUp 300ms ease-out;',
    },
      h('span', { style: 'width:8px;height:8px;background:#228A55;border-radius:9999px' }),
      msg,
    );
    root.appendChild(el);
    setTimeout(() => { el.remove(); }, 2400);
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
