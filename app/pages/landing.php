<?php include_once '../includes/header.php'; ?>

<!-- HERO -->
<section class="mm-hero">
  <div class="mm-hero-bg"></div>
  <div class="mm-hero-content mm-fade-up">
    <div class="mm-hero-badge">The Coach Approach</div>
    <h1 class="mm-hero-headline">
      <span class="mm-word" style="animation-delay:0s">Log</span>
      <span class="mm-word" style="animation-delay:0.08s">your</span>
      <span class="mm-word" style="animation-delay:0.16s">fuel.</span>
      <span class="mm-word mm-word-bold" style="animation-delay:0.24s">Done fast.</span>
    </h1>
    <p class="mm-hero-sub">
      See your stats instantly. <strong>Know what your fuel really costs.</strong>
    </p>
    <div class="mm-hero-ctas">
      <button class="mm-btn mm-btn-primary mm-btn-lg" data-nav="signup">Start Tracking Now</button>
      <button class="mm-btn mm-btn-ghost mm-btn-lg" data-scroll="#features">Explore Features</button>
    </div>
    <p class="mm-hero-trust">✓ Know your numbers &nbsp;•&nbsp; ✓ See the trends &nbsp;•&nbsp; ✓ Make better decisions</p>
  </div>

  <div class="mm-hero-visual">
    <div class="mm-receipt" id="mm-receipt">
      <div class="mm-receipt-head">
        <div class="mm-receipt-head-left">
          <div class="mm-receipt-pump">
            <div class="mm-receipt-pump-fill" data-receipt-fill></div>
          </div>
          <div class="mm-receipt-head-meta">
            <div class="mm-receipt-head-title">New fill-up</div>
            <div class="mm-receipt-head-sub">Toyota Vios · Shell Station</div>
          </div>
        </div>
        <div class="mm-receipt-timer">
          <span class="mm-receipt-timer-val" data-receipt-timer>0.0</span>
          <span class="mm-receipt-timer-unit">s</span>
        </div>
      </div>

      <div class="mm-receipt-fields" data-receipt-fields>
      </div>

      <div class="mm-receipt-stats" data-receipt-stats>
        <div class="mm-receipt-stats-label">Instant stats</div>
        <div class="mm-receipt-stats-row">
          <div class="mm-receipt-stat">
            <div class="mm-receipt-stat-value">₱2,319</div>
            <div class="mm-receipt-stat-label">Total cost</div>
          </div>
          <div class="mm-receipt-stat is-hero">
            <div class="mm-receipt-stat-value">12.7</div>
            <div class="mm-receipt-stat-label">km / L</div>
          </div>
          <div class="mm-receipt-stat">
            <div class="mm-receipt-stat-value">₱4.76</div>
            <div class="mm-receipt-stat-label">Cost / km</div>
          </div>
        </div>
        <div class="mm-receipt-stats-foot">
          <span style="color:#228A55;font-weight:600">↑ 0.8 km/L</span>
          <span>better than last fill</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURE TOUR -->
<section class="mm-section mm-features-bg" id="features">
  <div class="mm-section-inner">
    <div class="mm-ft-head">
      <div class="mm-ft-kicker">
        <span class="mm-ft-dot"></span>
        Product tour
      </div>
      <h2 class="mm-ft-heading">
        Six views. One job. <span class="mm-ft-amber">Done well.</span>
      </h2>
    </div>

    <div class="mm-ft-showcase">
      <div class="mm-ft-browser-wrap">
        <div class="mm-ft-browser">
          <div class="mm-ft-browser-chrome">
            <div class="mm-ft-browser-dots">
              <span class="mm-ft-dot-red"></span>
              <span class="mm-ft-dot-yellow"></span>
              <span class="mm-ft-dot-green"></span>
            </div>
            <div class="mm-ft-browser-url"></div>
          </div>
          <div class="mm-ft-browser-stage" data-tour-stage></div>
        </div>
      </div>

      <div class="mm-ft-list" data-tour-list>
      </div>
    </div>
  </div>
</section>

<!-- FINAL CTA -->
<section class="mm-final-cta">
  <div class="mm-cta-inner">
    <h2 class="mm-cta-headline">Ready to know what your fuel really costs?</h2>
    <p class="mm-cta-sub">Open Mileo, start tracking. It's free, forever. No ads, no paywalls, no surprises.</p>
    <button class="mm-btn mm-btn-lg mm-cta-btn" data-nav="signup">Open Mileo Now</button>
  </div>
</section>

<?php include_once '../includes/footer.php'; ?>
