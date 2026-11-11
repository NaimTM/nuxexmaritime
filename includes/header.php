<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$page_title = $page_title ?? SITE_NAME . ' | Marine Manning & Crew Management';
$meta_desc  = $meta_desc  ?? 'Nuzex Maritime connects shipowners with qualified seafarers worldwide.';
$current    = basename($_SERVER['PHP_SELF']);

// Check all role states
$seafarer  = current_seafarer();
$admin     = current_admin();
$shipowner = current_shipowner();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($meta_desc) ?>">
<meta name="theme-color" content="#0A2540">
<link rel="canonical" href="<?= SITE_URL . $_SERVER['REQUEST_URI'] ?>">

<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<meta property="og:title" content="<?= e($page_title) ?>">
<meta property="og:description" content="<?= e($meta_desc) ?>">
<meta property="og:url" content="<?= SITE_URL . $_SERVER['REQUEST_URI'] ?>">
<meta property="og:image" content="<?= SITE_URL ?>/assets/img/og-default.jpg">

<link rel="icon" href="/assets/img/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="site-header">
  <div class="container nav-wrap">
    <a class="logo" href="/">
      <span class="logo-mark">⚓</span>
      <span class="logo-text">NUZEX<small>MARITIME</small></span>
    </a>

    <nav class="main-nav" id="mainNav">
      <a href="/" class="<?= $current==='index.php'?'active':'' ?>">Home</a>
      <a href="/about.php" class="<?= $current==='about.php'?'active':'' ?>">About</a>
      <a href="/services.php" class="<?= $current==='services.php'?'active':'' ?>">Services</a>
      <a href="/for-shipowners.php" class="<?= $current==='for-shipowners.php'?'active':'' ?>">For Shipowners</a>
      <a href="/for-seafarers.php" class="<?= $current==='for-seafarers.php'?'active':'' ?>">For Seafarers</a>
      <a href="/jobs.php" class="<?= $current==='jobs.php'?'active':'' ?>">Jobs</a>
      <a href="/contact.php" class="<?= $current==='contact.php'?'active':'' ?>">Contact</a>
    </nav>

    <div class="nav-cta">
      <?php if ($shipowner): ?>
        <!-- ============ LOGGED IN: SHIPOWNER ============ -->
        <div class="user-menu" id="userMenu">
          <button class="user-toggle" id="userToggle" aria-haspopup="true" aria-expanded="false">
            <span class="user-avatar"><?= e(strtoupper(substr($shipowner['company_name'], 0, 1))) ?></span>
            <span class="user-name"><?= e($shipowner['company_name']) ?></span>
            <svg class="caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>

          <div class="user-dropdown" id="userDropdown">
            <div class="user-dropdown-header">
              <div class="user-dropdown-avatar"><?= e(strtoupper(substr($shipowner['company_name'], 0, 1))) ?></div>
              <div>
                <strong><?= e($shipowner['company_name']) ?></strong>
                <small>Shipowner Client</small>
              </div>
            </div>

            <div class="user-dropdown-section">
              <a href="/client/dashboard.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Client Dashboard</span>
              </a>
            </div>

            <div class="user-dropdown-section">
              <a href="/logout.php" class="danger">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Logout</span>
              </a>
            </div>
          </div>
        </div>

      <?php elseif ($admin): ?>
        <!-- ============ LOGGED IN: ADMIN ============ -->
        <div class="user-menu" id="userMenu">
          <button class="user-toggle" id="userToggle" aria-haspopup="true" aria-expanded="false">
            <span class="user-avatar">A</span>
            <span class="user-name"><?= e($admin['full_name'] ?? 'Admin') ?></span>
            <svg class="caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>

          <div class="user-dropdown" id="userDropdown">
            <div class="user-dropdown-header">
              <div class="user-dropdown-avatar">A</div>
              <div>
                <strong><?= e($admin['full_name'] ?? 'Administrator') ?></strong>
                <small>System Admin</small>
              </div>
            </div>

            <div class="user-dropdown-section">
              <a href="/admin/index.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Admin Panel</span>
              </a>
            </div>

            <div class="user-dropdown-section">
              <a href="/logout.php" class="danger">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Logout</span>
              </a>
            </div>
          </div>
        </div>

      <?php elseif ($seafarer): ?>
        <!-- ============ LOGGED IN: SEAFARER ============ -->
        <div class="user-menu" id="userMenu">
          <button class="user-toggle" id="userToggle" aria-haspopup="true" aria-expanded="false">
            <span class="user-avatar"><?= e(strtoupper(substr($seafarer['full_name'], 0, 1))) ?></span>
            <span class="user-name"><?= e(explode(' ', $seafarer['full_name'])[0]) ?></span>
            <svg class="caret" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </button>

          <div class="user-dropdown" id="userDropdown">
            <div class="user-dropdown-header">
              <div class="user-dropdown-avatar"><?= e(strtoupper(substr($seafarer['full_name'], 0, 1))) ?></div>
              <div>
                <strong><?= e($seafarer['full_name']) ?></strong>
                <small><?= e($seafarer['rank'] ?? $seafarer['rank_title'] ?? 'Seafarer') ?></small>
              </div>
            </div>

            <div class="user-dropdown-section">
              <a href="/profile.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>My Profile</span>
              </a>
              <a href="/my-applications.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>My Applications</span>
              </a>
            </div>

            <div class="user-dropdown-section">
              <a href="/logout.php" class="danger">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Logout</span>
              </a>
            </div>
          </div>
        </div>

      <?php else: ?>
        <!-- ============ LOGGED OUT ============ -->
        <a href="/login.php" class="btn btn-ghost btn-sm">Login</a>
      <?php endif; ?>

      <button class="hamburger" id="hamburger" aria-label="Menu">☰</button>
    </div>
  </div>
</header>