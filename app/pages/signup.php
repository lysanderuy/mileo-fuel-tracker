<?php
$error = $_SESSION['flash_error'] ?? '';
$form  = $_SESSION['flash_form'] ?? [];
unset($_SESSION['flash_error'], $_SESSION['flash_form']);

include_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-card">
    <h1 class="auth-title">Create your account</h1>
    <p class="auth-sub">Start tracking your fuel today</p>

    <?php if ($error): ?>
      <div class="auth-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="?api=auth/signup" class="auth-form">
      <label class="auth-label">
        <span>Name</span>
        <input
          type="text"
          name="name"
          class="auth-input"
          placeholder="Your name"
          required
          value="<?= htmlspecialchars($form['name'] ?? '') ?>"
        >
      </label>
      <label class="auth-label">
        <span>Email</span>
        <input
          type="email"
          name="email"
          class="auth-input"
          placeholder="you@email.com"
          required
          value="<?= htmlspecialchars($form['email'] ?? '') ?>"
        >
      </label>
      <label class="auth-label">
        <span>Password</span>
        <input type="password" name="password" class="auth-input" placeholder="Min. 8 characters" required>
      </label>
      <button type="submit" class="mm-btn mm-btn-primary" style="width:100%;height:48px;font-size:15px;margin-top:4px">
        Create Account
      </button>
    </form>

    <p class="auth-switch">Already have an account? <a href="?page=login">Sign in</a></p>
  </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
