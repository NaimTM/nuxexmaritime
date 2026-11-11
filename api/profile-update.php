<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('/profile.php');
csrf_check($_POST['csrf'] ?? null);
require_seafarer();

$id = (int)$_SESSION['seafarer']['id'];

$data = [
  'full_name'         => trim($_POST['full_name'] ?? ''),
  'nationality'       => trim($_POST['nationality'] ?? ''),
  'dob'               => $_POST['dob'] ?: null,
  'passport_no'       => trim($_POST['passport_no'] ?? ''),
  'rank_title'        => trim($_POST['rank_title'] ?? ''),
  'availability'      => $_POST['availability'] ?: null,
  'coc_no'            => trim($_POST['coc_no'] ?? ''),
  'cop_no'            => trim($_POST['cop_no'] ?? ''),
  'cdc_no'            => trim($_POST['cdc_no'] ?? ''),
  'vessel_experience' => trim($_POST['vessel_experience'] ?? ''),
  'current_rank_voyages' => ($_POST['current_rank_voyages'] ?? '') === '' ? null : max(0, min(10000, (int)$_POST['current_rank_voyages'])),
  'years_at_current_rank' => ($_POST['years_at_current_rank'] ?? '') === '' ? null : max(0, min(80, (float)$_POST['years_at_current_rank'])),
  'total_sea_service_months' => ($_POST['total_sea_service_months'] ?? '') === '' ? null : max(0, min(1200, (int)$_POST['total_sea_service_months'])),
  'availability_status' => in_array($_POST['availability_status'] ?? 'unavailable', ['available','from_date','unavailable'], true) ? ($_POST['availability_status'] ?? 'unavailable') : 'unavailable',
];

if (!$data['full_name'] || !$data['rank_title']) {
  flash('profile_error', 'Full name and rank are required.');
  redirect('/profile.php');
}

// Profile form updates profile metadata only. Documents are uploaded through the private document handler.
$sql = "UPDATE seafarers SET
  full_name=?, nationality=?, dob=?, passport_no=?, rank_title=?,
  availability=?, availability_status=?, coc_no=?, cop_no=?, cdc_no=?, vessel_experience=?,
  current_rank_voyages=?, years_at_current_rank=?, total_sea_service_months=?
  WHERE id=?";
$pdo->prepare($sql)->execute([
  $data['full_name'], $data['nationality'], $data['dob'], $data['passport_no'], $data['rank_title'],
  $data['availability'], $data['availability_status'], $data['coc_no'], $data['cop_no'], $data['cdc_no'],
  $data['vessel_experience'], $data['current_rank_voyages'], $data['years_at_current_rank'], $data['total_sea_service_months'], $id
]);

// Refresh session
$u = $pdo->prepare("SELECT * FROM seafarers WHERE id = ?");
$u->execute([$id]);
$_SESSION['seafarer'] = $u->fetch();

flash('profile_success', 'Profile updated.');
redirect('/profile.php');