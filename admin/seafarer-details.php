<?php
$admin_title='Seafarer Details'; require __DIR__.'/_nav.php';
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT); if(!$id){http_response_code(404);exit('Seafarer not found');}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['document_id'],$_POST['verification_status'])) {
 csrf_check($_POST['csrf']??null); $status=$_POST['verification_status'];
 if(in_array($status,['pending','verified','rejected'],true)) {
  $docId=(int)$_POST['document_id']; $note=trim($_POST['verification_note']??'');
  $check=$pdo->prepare('SELECT id FROM seafarer_documents WHERE id=? AND seafarer_id=?'); $check->execute([$docId,$id]);
  if($check->fetch()) { $pdo->prepare('UPDATE seafarer_documents SET verification_status=?,verification_note=? WHERE id=?')->execute([$status,$note?:null,$docId]);
   $adminId=(int)($_SESSION['admin']['id']??0); $pdo->prepare('INSERT INTO seafarer_document_audit (document_id,seafarer_id,admin_id,action,note) VALUES (?,?,?,?,?)')->execute([$docId,$id,$adminId?:null,'verification_'.$status,$note?:null]); }
 }
 redirect('/admin/seafarer-details.php?id='.$id);
}
$stmt=$pdo->prepare('SELECT * FROM seafarers WHERE id=?');$stmt->execute([$id]);$s=$stmt->fetch();if(!$s){http_response_code(404);exit('Seafarer not found');}
$d=$pdo->prepare('SELECT * FROM seafarer_documents WHERE seafarer_id=? ORDER BY document_type');$d->execute([$id]);$docs=$d->fetchAll();
?>
<div class="admin-topbar"><h1><?= e($s['full_name']) ?></h1><a class="btn btn-ghost" href="/admin/seafarers.php">← Back to seafarers</a></div>
<div class="form-card"><h2>Candidate profile</h2><div class="form-row"><div><strong>Rank</strong><p><?= e($s['rank_title']?:'—') ?></p></div><div><strong>Nationality</strong><p><?= e($s['nationality']?:'—') ?></p></div><div><strong>Email / Phone</strong><p><?= e($s['email']) ?><br><?= e($s['phone']) ?></p></div></div>
<div class="form-row"><div><strong>Passport number</strong><p><?= e($s['passport_no']?:'—') ?></p></div><div><strong>CoC / CoP / CDC</strong><p><?= e($s['coc_no']?:'—') ?> / <?= e($s['cop_no']?:'—') ?> / <?= e($s['cdc_no']?:'—') ?></p></div><div><strong>Availability</strong><p><?= e(ucwords(str_replace('_',' ',$s['availability_status']??'unavailable'))) ?><?= $s['availability']?' · '.e($s['availability']):'' ?></p></div></div>
<div class="form-row"><div><strong>Voyages at current rank</strong><p><?= $s['current_rank_voyages']===null?'Not entered':(int)$s['current_rank_voyages'] ?></p></div><div><strong>Years at current rank</strong><p><?= e((string)($s['years_at_current_rank']??'—')) ?></p></div><div><strong>Total sea service</strong><p><?= $s['total_sea_service_months']===null?'Not entered':(int)$s['total_sea_service_months'].' months' ?></p></div></div>
<p><strong>Vessel experience:</strong><br><?= nl2br(e($s['vessel_experience']?:'—')) ?></p></div>
<h2 style="margin-top:2rem">Private documents</h2>
<?php if(!$docs): ?><div class="alert alert-info">No private documents have been uploaded.</div><?php else: ?><div style="overflow-x:auto"><table class="admin-table"><thead><tr><th>Document</th><th>Issue / expiry</th><th>Current status</th><th>File</th><th>Verify</th></tr></thead><tbody>
<?php $labels=['cv'=>'CV','cdc'=>'CDC / Seaman’s Book','coc'=>'CoC','cop'=>'CoP','passport'=>'Passport','medical'=>'Medical Certificate','stcw'=>'STCW / Training','other'=>'Other']; foreach($docs as $doc): $expired=$doc['expiry_date'] && $doc['expiry_date']<date('Y-m-d'); ?><tr>
<td><strong><?= e($labels[$doc['document_type']]??$doc['document_type']) ?></strong><?= $doc['label']?'<br>'.e($doc['label']):'' ?><br><small><?= e($doc['original_name']) ?></small></td><td><?= $doc['issue_date']?e($doc['issue_date']):'—' ?><br><small style="color:<?= $expired?'var(--danger)':'inherit' ?>">Expires: <?= $doc['expiry_date']?e($doc['expiry_date']):'Not provided' ?><?= $expired?' · EXPIRED':'' ?></small></td>
<td><?= e(ucfirst($doc['verification_status'])) ?><?= $doc['verification_note']?'<br><small>'.e($doc['verification_note']).'</small>':'' ?></td><td><a class="btn btn-ghost btn-sm" href="/document-download.php?id=<?= (int)$doc['id'] ?>">Download securely</a></td>
<td><form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="document_id" value="<?= (int)$doc['id'] ?>"><select name="verification_status"><option value="pending" <?= $doc['verification_status']==='pending'?'selected':'' ?>>Pending</option><option value="verified" <?= $doc['verification_status']==='verified'?'selected':'' ?>>Verified</option><option value="rejected" <?= $doc['verification_status']==='rejected'?'selected':'' ?>>Rejected</option></select><input name="verification_note" maxlength="500" placeholder="Note / rejection reason" value="<?= e($doc['verification_note']??'') ?>"><button class="btn btn-teal btn-sm" style="margin-top:.3rem">Save</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</main></div></body></html>
