<!doctype html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Mileo — Frictionless fuel tracking</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Sora:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/design-tokens.css">
<link rel="stylesheet" href="css/landing.css">
<?php if (($page ?? '') === 'dashboard'): ?>
<link rel="stylesheet" href="css/dashboard.css">
<?php endif; ?>
<?php if (($page ?? '') === 'vehicles'): ?>
<link rel="stylesheet" href="css/vehicles.css">
<?php endif; ?>
<?php if (($page ?? '') === 'quick-log'): ?>
<link rel="stylesheet" href="css/quick-log.css">
<?php endif; ?>
<?php if (in_array($page ?? '', ['login', 'signup'])): ?>
<link rel="stylesheet" href="css/auth.css">
<?php endif; ?>
<style>html,body{margin:0;padding:0;background:#FAFAF8}body{display:flex;flex-direction:column;min-height:100vh}</style>
</head>
<body class="mileo-marketing" data-theme="light">

<!-- NAVBAR -->
<nav class="mm-navbar">
  <div class="mm-navbar-inner">
    <div class="mm-brand" data-nav="home">
      <span class="mm-brand-icon">⚡</span>
      <span class="mm-brand-name">mileo</span>
    </div>
    <div class="mm-navbar-actions">
      <?php if (isset($_SESSION['user_id'])): ?>
        <div class="mm-nav-tabs" aria-label="Primary sections">
          <button class="mm-nav-tab<?= ($page ?? '') === 'dashboard' ? ' is-active' : '' ?>" onclick="location.href='?page=dashboard'">Dashboard</button>
          <button class="mm-nav-tab<?= ($page ?? '') === 'vehicles' ? ' is-active' : '' ?>" onclick="location.href='?page=vehicles'">Vehicles</button>
          <button class="mm-nav-tab<?= ($page ?? '') === 'quick-log' ? ' is-active' : '' ?>" onclick="location.href='?page=quick-log'">Quick Log</button>
        </div>
        <button class="mm-btn mm-btn-secondary mm-btn-sm" onclick="location.href='?page=logout'">Logout</button>
      <?php else: ?>
        <button class="mm-btn mm-btn-secondary mm-btn-sm" onclick="location.href='?page=login'">Sign In</button>
        <button class="mm-btn mm-btn-primary mm-btn-sm" onclick="location.href='?page=signup'">Get Started</button>
      <?php endif; ?>
    </div>
  </div>
</nav>
<main style="flex:1">
