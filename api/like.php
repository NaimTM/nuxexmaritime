<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

if (!is_seafarer()) {
  echo json_encode([
    'ok' => false,
    'needs_login' => true,
    'redirect' => '/register.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? '/jobs.php')
  ]);
  exit;
}

$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);
if (!$in) { $in = $_POST; }  // fallback to form data

$job_id = (int)($in['job_id'] ?? 0);
$sid    = (int)$_SESSION['seafarer']['id'];

if (!$job_id) {
  echo json_encode(['ok' => false, 'error' => 'Missing job_id']);
  exit;
}

// Ensure job exists
$chk = $pdo->prepare("SELECT 1 FROM jobs WHERE id = ?");
$chk->execute([$job_id]);
if (!$chk->fetchColumn()) {
  echo json_encode(['ok' => false, 'error' => 'Job not found']);
  exit;
}

$exists = $pdo->prepare("SELECT id FROM likes WHERE job_id=? AND seafarer_id=?");
$exists->execute([$job_id, $sid]);

if ($exists->fetchColumn()) {
  $pdo->prepare("DELETE FROM likes WHERE job_id=? AND seafarer_id=?")->execute([$job_id, $sid]);
  $pdo->prepare("UPDATE jobs SET likes_count = GREATEST(likes_count-1, 0) WHERE id=?")->execute([$job_id]);
  $liked = false;
} else {
  $pdo->prepare("INSERT INTO likes (job_id, seafarer_id) VALUES (?, ?)")->execute([$job_id, $sid]);
  $pdo->prepare("UPDATE jobs SET likes_count = likes_count+1 WHERE id=?")->execute([$job_id]);
  $liked = true;
}

$count = (int)$pdo->query("SELECT likes_count FROM jobs WHERE id=" . (int)$job_id)->fetchColumn();
echo json_encode(['ok' => true, 'liked' => $liked, 'count' => $count]);