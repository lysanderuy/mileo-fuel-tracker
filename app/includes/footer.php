</main>

<!-- FOOTER -->
<footer class="mm-footer">
  <div class="mm-footer-inner">
    <div class="mm-footer-top">
      <div>
        <div class="mm-footer-brand">mileo</div>
        <div class="mm-footer-tagline">Frictionless fuel tracking for drivers.</div>
      </div>
      <div class="mm-footer-links">
        <a class="mm-footer-link" href="#docs">Documentation</a>
        <a class="mm-footer-link" href="#privacy">Privacy</a>
        <a class="mm-footer-link" href="#terms">Terms</a>
        <a class="mm-footer-link" href="#contact">Contact</a>
      </div>
    </div>
    <div class="mm-footer-bottom">© 2026 Mileo. Built for drivers.</div>
  </div>
</footer>

<!-- MODAL + TOAST MOUNTS -->
<div id="mm-modal-root"></div>

<script src="js/utils.js"></script>
<?php if (in_array($page ?? '', ['dashboard', 'history', 'fuel-log-detail'])): ?>
<script src="js/popover.js"></script>
<script src="js/fillup-modal.js"></script>
<?php endif; ?>
<?php if (($page ?? '') === 'dashboard'): ?>
<script src="js/dashboard.js"></script>
<?php endif; ?>
<?php if (($page ?? '') === 'vehicles'): ?>
<script src="js/vehicles.js"></script>
<?php endif; ?>
<?php if (($page ?? '') === 'fuel-log'): ?>
<script src="js/fuel-log.js"></script>
<?php endif; ?>
<?php if (($page ?? '') === 'history'): ?>
<script src="js/history.js"></script>
<?php endif; ?>
<?php if (($page ?? '') === 'fuel-log-detail'): ?>
<script src="js/fuel-log-detail.js"></script>
<?php endif; ?>
<script src="js/landing.js"></script>
<div id="mm-toast-root"></div>
</body>
</html>
