<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
$docId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$docId) { http_response_code(404); exit('Document not found.'); }
if (!is_seafarer() && !is_admin()) { http_response_code(403); exit('Access denied. Please log in.'); }
$stmt=$pdo->prepare('SELECT d.*,s.id AS owner_id FROM seafarer_documents d JOIN seafarers s ON s.id=d.seafarer_id WHERE d.id=?');
$stmt->execute([$docId]); $doc=$stmt->fetch();
if (!$doc) { http_response_code(404); exit('Document not found.'); }
if (is_seafarer() && (int)$_SESSION['seafarer']['id'] !== (int)$doc['owner_id']) { http_response_code(403); exit('Access denied.'); }
if (is_admin()) {
  $adminId=(int)($_SESSION['admin']['id'] ?? 0);
  try { $pdo->prepare("INSERT INTO seafarer_document_audit (document_id,seafarer_id,admin_id,action) VALUES (?,?,?,'viewed_by_admin')")->execute([(int)$doc['id'],(int)$doc['owner_id'],$adminId ?: null]); } catch (Throwable $e) { error_log('Nuzex audit log failed: '.$e->getMessage()); }
}
$base=rtrim(PRIVATE_UPLOAD_DIR, '/\\').DIRECTORY_SEPARATOR.'documents'.DIRECTORY_SEPARATOR.(int)$doc['owner_id'].DIRECTORY_SEPARATOR;
$path=$base.basename($doc['stored_name']);
if (!is_file($path) || !is_readable($path)) { http_response_code(404); exit('The stored file is unavailable.'); }
$ext=strtolower(pathinfo($doc['original_name'],PATHINFO_EXTENSION));
$allowed=['pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$mime=$allowed[$ext] ?? 'application/octet-stream';
$downloadName=preg_replace('/[^A-Za-z0-9._-]/','_',basename($doc['original_name'])) ?: 'document';
header('X-Content-Type-Options: nosniff'); header('Cache-Control: private, no-store, max-age=0'); header('Pragma: no-cache');
header('Content-Type: '.$mime); header('Content-Length: '.filesize($path)); header('Content-Disposition: attachment; filename="'.$downloadName.'"');
readfile($path); exit;
