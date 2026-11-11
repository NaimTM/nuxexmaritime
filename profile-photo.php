<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
if (is_admin()) require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id || (!is_super_admin() && !(is_seafarer() && (int)current_seafarer()['id'] === (int)$id))) { http_response_code(404); exit; }
$q = $pdo->prepare('SELECT photo_path FROM seafarers WHERE id=?');
$q->execute([$id]);
$name = $q->fetchColumn();
if (!$name || basename((string)$name) !== (string)$name) { http_response_code(404); exit; }
$base = rtrim(PRIVATE_UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . (int)$id . DIRECTORY_SEPARATOR;
$path = $base . $name;
if (!is_file($path) || !is_readable($path)) { http_response_code(404); exit; }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: '';
if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) { http_response_code(404); exit; }
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($path));
header('Content-Disposition: inline; filename="profile-image"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
readfile($path);
