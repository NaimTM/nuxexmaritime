<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/jobs.php'); }
csrf_check($_POST['csrf'] ?? null);
require_seafarer();

$job_id     = (int)($_POST['job_id'] ?? 0);
$rank_title = trim($_POST['rank_title'] ?? '');
$cover      = trim($_POST['cover_note'] ?? '');
$sid        = (int)$_SESSION['seafarer']['id'];

// Already applied?
$chk = $pdo->prepare("SELECT 1 FROM applications WHERE job_id=? AND seafarer_id=?");
$chk->execute([$job_id, $sid]);
if ($chk->fetchColumn()) {
  flash('apply_error', 'You already applied to this job.');
  redirect('/job.php?slug=' . urlencode($_POST['slug'] ?? ''));
}

// Store new application CVs privately. If no new file is selected, reuse the current private CV first.
$cvPath = null;
if (!empty($_FILES['cv']['name'])) {
  if ($_FILES['cv']['error'] !== UPLOAD_ERR_OK) { flash('apply_error','CV upload failed. Please try again.'); redirect('/job.php?slug=' . urlencode($_POST['slug'] ?? '')); }
  $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
  $allowed = ['pdf'=>['application/pdf'],'doc'=>['application/msword','application/x-ole-storage','application/CDFV2'],'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip','application/octet-stream']];
  if (!isset($allowed[$ext]) || $_FILES['cv']['size'] < 1 || $_FILES['cv']['size'] > 10*1024*1024 || !is_uploaded_file($_FILES['cv']['tmp_name'])) { flash('apply_error','CV must be a valid PDF or Word document under 10 MB.'); redirect('/job.php?slug=' . urlencode($_POST['slug'] ?? '')); }
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['cv']['tmp_name']) ?: 'application/octet-stream';
  if (!in_array($mime,$allowed[$ext],true)) { flash('apply_error','The CV file content does not match its extension.'); redirect('/job.php?slug=' . urlencode($_POST['slug'] ?? '')); }
  $dir=rtrim(PRIVATE_UPLOAD_DIR,'/\\').DIRECTORY_SEPARATOR.'documents'.DIRECTORY_SEPARATOR.$sid.DIRECTORY_SEPARATOR;
  if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) { flash('apply_error','Secure CV storage is unavailable.'); redirect('/job.php?slug=' . urlencode($_POST['slug'] ?? '')); }
  $stored=bin2hex(random_bytes(20)).'.'.$ext; $destination=$dir.$stored;
  if (!move_uploaded_file($_FILES['cv']['tmp_name'],$destination)) { flash('apply_error','CV could not be saved.'); redirect('/job.php?slug=' . urlencode($_POST['slug'] ?? '')); }
  @chmod($destination,0600); $oldStored=null;
  try {
    $pdo->beginTransaction();
    $old=$pdo->prepare("SELECT id,stored_name FROM seafarer_documents WHERE seafarer_id=? AND document_type='cv' FOR UPDATE"); $old->execute([$sid]); $oldRow=$old->fetch(); $oldStored=$oldRow['stored_name']??null;
    if ($oldRow) {
      $pdo->prepare("UPDATE seafarer_documents SET stored_name=?,original_name=?,mime_type=?,file_size=?,issue_date=NULL,expiry_date=NULL,verification_status='pending',verification_note=NULL,uploaded_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$stored,basename($_FILES['cv']['name']),$mime,(int)$_FILES['cv']['size'],(int)$oldRow['id']]); $docId=(int)$oldRow['id'];
    } else {
      $pdo->prepare("INSERT INTO seafarer_documents (seafarer_id,document_type,stored_name,original_name,mime_type,file_size) VALUES (?,'cv',?,?,?,?)")->execute([$sid,$stored,basename($_FILES['cv']['name']),$mime,(int)$_FILES['cv']['size']]); $docId=(int)$pdo->lastInsertId();
    }
    $pdo->prepare("INSERT INTO seafarer_document_audit (document_id,seafarer_id,action,note) VALUES (?,?,?,?)")->execute([$docId,$sid,$oldRow?'replaced':'uploaded','CV uploaded during job application']);
    $pdo->commit(); $cvPath='private_doc:'.$docId;
    if($oldStored && $oldStored!==$stored && is_file($dir.basename($oldStored))) @unlink($dir.basename($oldStored));
  } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); if(is_file($destination))@unlink($destination); error_log('Nuzex application CV upload failed: '.$e->getMessage()); flash('apply_error','CV could not be saved. Please try again.'); redirect('/job.php?slug=' . urlencode($_POST['slug'] ?? '')); }
} else {
  $doc=$pdo->prepare("SELECT id FROM seafarer_documents WHERE seafarer_id=? AND document_type='cv'"); $doc->execute([$sid]); $docId=$doc->fetchColumn();
  if ($docId) $cvPath='private_doc:'.(int)$docId;
  else $cvPath=$_SESSION['seafarer']['cv_path'] ?? null;
  if (!$cvPath) { flash('apply_error','Please upload a CV in your profile first.'); redirect('/job.php?slug=' . urlencode($_POST['slug'] ?? '')); }
}

$pdo->prepare("INSERT INTO applications (job_id, seafarer_id, rank_title, cv_path, cover_note) VALUES (?,?,?,?,?)")
    ->execute([$job_id, $sid, $rank_title, $cvPath, $cover]);

flash('apply_success', 'Application submitted. We\'ll contact you if shortlisted.');
redirect('/job.php?slug=' . urlencode($_POST['slug'] ?? ''));