<?php include_once __DIR__ . '/../includes/header.php'; ?>

<div class="db-wrap">

  <div class="db-topbar">
    <div>
      <div class="db-title">Dashboard</div>
      <div class="db-subtitle">Your fuel tracking overview</div>
    </div>
    <button class="mm-btn mm-btn-primary mm-btn-sm">+ Log Fill-up</button>
  </div>

  <!-- STAT CARDS -->
  <div class="db-stats">
    <div class="db-card">
      <div class="db-card-label">Total Fill-ups</div>
      <div class="db-card-value">24</div>
      <div class="db-card-sub">This month: 3</div>
    </div>
    <div class="db-card">
      <div class="db-card-label">Avg Efficiency</div>
      <div class="db-card-value">12.4</div>
      <div class="db-card-sub"><span class="up">&#8593; 0.6</span> km/L vs last month</div>
    </div>
    <div class="db-card">
      <div class="db-card-label">Total Spent</div>
      <div class="db-card-value">&#8369;18,240</div>
      <div class="db-card-sub">This month: &#8369;2,150</div>
    </div>
    <div class="db-card">
      <div class="db-card-label">Avg Cost / km</div>
      <div class="db-card-value">&#8369;4.91</div>
      <div class="db-card-sub"><span class="down">&#8593; &#8369;0.12</span> vs last month</div>
    </div>
  </div>

  <!-- RECENT LOGS TABLE -->
  <div class="db-section">
    <div class="db-section-head">
      <div class="db-section-title">Recent Fill-ups</div>
    </div>
    <table class="db-table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Station</th>
          <th>Liters</th>
          <th>Price / L</th>
          <th>Total</th>
          <th>Efficiency</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Apr 20, 2026</td>
          <td>Shell EDSA</td>
          <td>40.0 L</td>
          <td>&#8369;63.50</td>
          <td>&#8369;2,540</td>
          <td>13.1 km/L</td>
          <td><span class="db-badge db-badge-green">Good</span></td>
        </tr>
        <tr>
          <td>Apr 12, 2026</td>
          <td>Petron Quirino</td>
          <td>35.5 L</td>
          <td>&#8369;62.00</td>
          <td>&#8369;2,201</td>
          <td>11.8 km/L</td>
          <td><span class="db-badge db-badge-yellow">Average</span></td>
        </tr>
        <tr>
          <td>Apr 3, 2026</td>
          <td>Caltex C5</td>
          <td>38.2 L</td>
          <td>&#8369;63.00</td>
          <td>&#8369;2,407</td>
          <td>12.3 km/L</td>
          <td><span class="db-badge db-badge-green">Good</span></td>
        </tr>
        <tr>
          <td>Mar 27, 2026</td>
          <td>Shell EDSA</td>
          <td>42.0 L</td>
          <td>&#8369;61.50</td>
          <td>&#8369;2,583</td>
          <td>11.9 km/L</td>
          <td><span class="db-badge db-badge-yellow">Average</span></td>
        </tr>
        <tr>
          <td>Mar 15, 2026</td>
          <td>Petron Quirino</td>
          <td>36.8 L</td>
          <td>&#8369;60.00</td>
          <td>&#8369;2,208</td>
          <td>12.9 km/L</td>
          <td><span class="db-badge db-badge-green">Good</span></td>
        </tr>
      </tbody>
    </table>
  </div>

</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
