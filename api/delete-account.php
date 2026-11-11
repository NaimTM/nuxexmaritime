<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/settings.php');
csrf_check($_POST['csrf'] ?? null);
require_seafarer();

$id   = (int)$_SESSION['seafarer']['id'];
$conf = trim($_POST['confirm'] ?? '');
$pass = $_POST['password'] ?? '';

if ($conf !== 'DELETE') {
  flash('settings_error', 'Type DELETE exactly to confirm.');
  redirect('/settings.php');
}

$u = $pdo->prepare("SELECT password_hash FROM seafarers WHERE id = ?");
$u->execute([$id]);
$hash = $u->fetchColumn();

if (!password_verify($pass, $hash)) {
  flash('settings_error', 'Wrong password.');
  redirect('/settings.php');
}

$pdo->prepare("DELETE FROM seafarers WHERE id = ?")->execute([$id]);

session_destroy();
redirect('/?deleted=1');