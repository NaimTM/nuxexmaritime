<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check($_POST['csrf'] ?? null);
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';
  $q = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
  $q->execute([$email]);
  $a = $q->fetch();
  if ($a && password_verify($pass, $a['password_hash'])) {
    $_SESSION['admin'] = $a;
    redirect('/admin/index.php');
  }
  $error = 'Invalid credentials.';
}
?><!doctype html>
<html><head>
<meta charset="utf-8"><title>Admin Login · Nuzex</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/assets/css/style.css">
</head><body style="background:var(--navy);display:flex;align-items:center;justify-content:center;min-height:100vh">
<div class="form-card" style="width:380px">
  <h2 style="margin-bottom:1.5rem">Nuzex Admin</h2>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
    <button class="btn btn-primary" style="width:100%">Log In</button>
  </form>
</div></body></html>