<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/jobs.php'); }
csrf_check($_POST['csrf'] ?? null);
require_seafarer();

$job_id = (int)($_POST['job_id'] ?? 0);
$body   = trim($_POST['body'] ?? '');

if (!$job_id || mb_strlen($body) < 2) {
  flash('comment_error', 'Comment is too short.');
  redirect($_SERVER['HTTP_REFERER'] ?? '/jobs.php');
}

$pdo->prepare("INSERT INTO comments (job_id, seafarer_id, body) VALUES (?, ?, ?)")
    ->execute([$job_id, $_SESSION['seafarer']['id'], $body]);

$pdo->prepare("UPDATE jobs SET comments_count = comments_count + 1 WHERE id = ?")
    ->execute([$job_id]);

flash('comment_success', 'Comment posted.');
redirect('/job.php?slug=' . urlencode($_POST['slug'] ?? '') . '#comments');