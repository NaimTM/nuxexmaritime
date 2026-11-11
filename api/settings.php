<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/settings.php');
csrf_check($_POST['csrf'] ?? null);
require_seafarer();

$id     = (int)$_SESSION['seafarer']['id'];
$action = $_POST['action'] ?? '';

$u = $pdo->prepare("SELECT * FROM seafarers WHERE id = ?");
$u->execute([$id]);
$me = $u->fetch();

if (!password_verify($_POST['current_password'] ?? '', $me['password_hash'])) {
  flash('settings_error', 'Current password is incorrect.');
  redirect('/settings.php');
}

if ($action === 'email') {
  $newEmail = trim($_POST['email'] ?? '');
  if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    flash('settings_error', 'Invalid email.');
    redirect('/settings.php');
  }
  $chk = $pdo->prepare("SELECT 1 FROM seafarers WHERE email = ? AND id <> ?");
  $chk->execute([$newEmail, $id]);
  if ($chk->fetchColumn()) {
    flash('settings_error', 'That email is already in use.');
    redirect('/settings.php');
  }
  $pdo->prepare("UPDATE seafarers SET email = ? WHERE id = ?")->execute([$newEmail, $id]);
  $_SESSION['seafarer']['email'] = $newEmail;
  flash('settings_success', 'Email updated.');
  redirect('/settings.php');
}

if ($action === 'password') {
  $new     = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';
  if (strlen($new) < 8) {
    flash('settings_error', 'Password must be at least 8 characters.');
    redirect('/settings.php');
  }
  if ($new !== $confirm) {
    flash('settings_error', 'New passwords do not match.');
    redirect('/settings.php');
  }
  $pdo->prepare("UPDATE seafarers SET password_hash = ? WHERE id = ?")
      ->execute([password_hash($new, PASSWORD_DEFAULT), $id]);
  flash('settings_success', 'Password changed.');
  redirect('/settings.php');
}

redirect('/settings.php');