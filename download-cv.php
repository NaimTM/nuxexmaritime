<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$type = (string)($_GET['type'] ?? '');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id || !in_array($type, ['profile','application'], true)) { http_response_code(404); exit('File not found.'); }
$path = null;
if (is_admin() && in_array((string)(current_admin()['role'] ?? ''), ['super','recruiter'], true)) {
    if ($type === 'profile') {
        $q = $pdo->prepare('SELECT cv_path FROM seafarers WHERE id = ?'); $q->execute([$id]); $path = $q->fetchColumn();
    } else {
        $q = $pdo->prepare('SELECT cv_path FROM applications WHERE id = ?'); $q->execute([$id]); $path = $q->fetchColumn();
    }
} elseif (is_seafarer()) {
    $sid = (int)current_seafarer()['id'];
    if ($type === 'profile' && $id === $sid) {
        $q = $pdo->prepare('SELECT cv_path FROM seafarers WHERE id = ?'); $q->execute([$sid]); $path = $q->fetchColumn();
    } elseif ($type === 'application') {
        $q = $pdo->prepare('SELECT cv_path FROM applications WHERE id = ? AND seafarer_id = ?'); $q->execute([$id, $sid]); $path = $q->fetchColumn();
    }
}
$file = resolve_cv_file(is_string($path) ? $path : null);
if (!$file) { http_response_code(404); exit('File not found or you do not have permission to access it.'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file) ?: 'application/octet-stream';
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$mime = ['pdf'=>'application/pdf','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'][$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($file));
header('Content-Disposition: attachment; filename="seafarer-cv.' . preg_replace('/[^a-z0-9]/', '', $ext) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($file); exit;
