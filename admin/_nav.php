<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$current = basename($_SERVER['PHP_SELF']);
$pending_apps     = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status='applied'")->fetchColumn();
$new_requests     = (int)$pdo->query("SELECT COUNT(*) FROM crew_requests WHERE status='new'")->fetchColumn();
$flagged_comments = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status='flagged'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($admin_title ?? 'Admin') ?> · Nuzex Maritime</title>
<link rel="stylesheet" href="/assets/css/style.css">
<style>
  .admin-wrap{display:grid;grid-template-columns:240px 1fr;min-height:100vh;background:var(--bg)}
  .admin-side{background:var(--navy);color:#fff;padding:1.5rem 0;position:sticky;top:0;height:100vh;overflow-y:auto}
  .admin-side .logo{color:#fff;padding:0 1.5rem;margin-bottom:2rem}
  .admin-side a{display:flex;align-items:center;justify-content:space-between;padding:.8rem 1.5rem;color:rgba(255,255,255,.75);font-size:.92rem;border-left:3px solid transparent}
  .admin-side a:hover,.admin-side a.active{background:rgba(15,181,174,.12);color:#fff;border-left-color:var(--teal)}
  .admin-side .badge{background:var(--gold);color:var(--navy);font-size:.7rem;font-weight:700;padding:.15rem .45rem;border-radius:10px}
  .admin-main{padding:2rem}
  .admin-main h1{margin-bottom:1.5rem}
  .admin-topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem}
  .admin-table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:var(--shadow);font-size:.9rem}
  .admin-table th{background:var(--navy);color:#fff;text-align:left;padding:12px 14px;font-weight:600;font-size:.82rem;text-transform:uppercase;letter-spacing:.03em}
  .admin-table td{padding:12px 14px;border-bottom:1px solid var(--line);vertical-align:middle}
  .admin-table tr:last-child td{border-bottom:none}
  .admin-table tr:hover td{background:rgba(15,181,174,.03)}
  .pill{display:inline-block;padding:.2rem .6rem;border-radius:20px;font-size:.75rem;font-weight:600}
  .pill.new{background:rgba(232,163,61,.15);color:#a86f00}
  .pill.applied{background:rgba(15,181,174,.12);color:var(--teal-d)}
  .pill.shortlisted{background:rgba(212,175,55,.18);color:#8a6d1c}
  .pill.hired,.pill.contacted{background:rgba(31,169,113,.15);color:var(--success)}
  .pill.rejected,.pill.closed,.pill.hidden{background:rgba(217,74,74,.12);color:var(--danger)}
  @media(max-width:820px){
    .admin-wrap{grid-template-columns:1fr}
    .admin-side{position:static;height:auto;padding:1rem 0}
    .admin-side .logo{margin-bottom:1rem}
  }
</style>
</head>
<body>
<div class="admin-wrap">
  <aside class="admin-side">
    <a class="logo" href="/admin/index.php"><span class="logo-mark">⚓</span><span class="logo-text">NUZEX<small>ADMIN</small></span></a>

    <a href="/admin/index.php" class="<?= $current==='index.php'?'active':'' ?>">📊 Dashboard</a>
    <a href="/admin/jobs.php" class="<?= $current==='jobs.php'||$current==='job-edit.php'?'active':'' ?>">💼 Jobs</a>
    <a href="/admin/applications.php" class="<?= $current==='applications.php'?'active':'' ?>">
      📨 Applications <?php if($pending_apps): ?><span class="badge"><?= $pending_apps ?></span><?php endif; ?>
    </a>
    <a href="/admin/seafarers.php" class="<?= $current==='seafarers.php'?'active':'' ?>">⚓ Seafarers</a>
    
    <!-- NEW INVOICES SIDEBAR LINK -->
    <a href="/admin/invoices.php" class="<?= $current==='invoices.php'?'active':'' ?>">📑 Client Invoices</a>
    <a href="/admin/deployments.php" class="<?= $current==='deployments.php'?'active':'' ?>">🚢 Crew Deployments</a>

    <a href="/admin/crew-requests.php" class="<?= $current==='crew-requests.php'?'active':'' ?>">
      🚢 Crew Requests <?php if($new_requests): ?><span class="badge"><?= $new_requests ?></span><?php endif; ?>
    </a>
    <a href="/admin/comments.php" class="<?= $current==='comments.php'?'active':'' ?>">
      💬 Comments <?php if($flagged_comments): ?><span class="badge"><?= $flagged_comments ?></span><?php endif; ?>
    </a>
    <a href="/" target="_blank">🌐 View Site</a>
    <a href="/admin/logout.php">🚪 Logout</a>
  </aside>
  <main class="admin-main">