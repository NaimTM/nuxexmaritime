<?php
$admin_title = 'Comments';
require __DIR__ . '/_nav.php';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'], $_POST['id'])) {
  csrf_check($_POST['csrf'] ?? null);
  $id = (int)$_POST['id'];

  if ($_POST['action']==='delete') {
    $row = $pdo->prepare("SELECT job_id FROM comments WHERE id=?");
    $row->execute([$id]);
    $job = (int)$row->fetchColumn();
    $pdo->prepare("DELETE FROM comments WHERE id=?")->execute([$id]);
    if ($job) $pdo->prepare("UPDATE jobs SET comments_count=GREATEST(comments_count-1,0) WHERE id=?")->execute([$job]);
    flash('cm_msg', 'Comment deleted.');
  } elseif (in_array($_POST['action'], ['visible','hidden','flagged'], true)) {
    $pdo->prepare("UPDATE comments SET status=? WHERE id=?")->execute([$_POST['action'], $id]);
    flash('cm_msg', 'Comment updated.');
  }
  redirect('/admin/comments.php');
}

$filter = $_GET['status'] ?? '';
$where = $filter ? "WHERE c.status = ?" : "";
$params = $filter ? [$filter] : [];

$stmt = $pdo->prepare("
  SELECT c.*, s.full_name, s.email, j.title AS job_title, j.slug AS job_slug
  FROM comments c
  JOIN seafarers s ON s.id = c.seafarer_id
  JOIN jobs j ON j.id = c.job_id
  $where
  ORDER BY c.created_at DESC
  LIMIT 300
");
$stmt->execute($params);
$comments = $stmt->fetchAll();
?>

<div class="admin-topbar">
  <h1>Comments (<?= count($comments) ?>)</h1>
  <form method="get">
    <select name="status" onchange="this.form.submit()" style="padding:.5rem .8rem;border:1.5px solid var(--line);border-radius:8px">
      <option value="">All</option>
      <option value="visible" <?= $filter==='visible'?'selected':'' ?>>Visible</option>
      <option value="hidden"  <?= $filter==='hidden'?'selected':'' ?>>Hidden</option>
      <option value="flagged" <?= $filter==='flagged'?'selected':'' ?>>Flagged</option>
    </select>
  </form>
</div>

<?php if ($m = flash('cm_msg')): ?><div class="alert alert-success"><?= e($m) ?></div><?php endif; ?>

<?php if (!$comments): ?>
  <div class="alert alert-info">No comments yet.</div>
<?php else: ?>
<table class="admin-table">
  <thead>
    <tr><th>Author</th><th>Comment</th><th>On Job</th><th>Status</th><th>When</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($comments as $c): ?>
    <tr>
      <td><strong><?= e($c['full_name']) ?></strong><br><small class="muted"><?= e($c['email']) ?></small></td>
      <td style="max-width:380px"><?= e(mb_strimwidth($c['body'], 0, 200, '…')) ?></td>
      <td><a href="/job.php?slug=<?= e($c['job_slug']) ?>#comments" target="_blank"><?= e($c['job_title']) ?></a></td>
      <td><span class="pill <?= e($c['status']) ?>"><?= e($c['status']) ?></span></td>
      <td><small class="muted"><?= time_ago($c['created_at']) ?></small></td>
      <td style="white-space:nowrap">
        <form method="post" style="display:inline">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <input type="hidden" name="action" value="<?= $c['status']==='visible'?'hidden':'visible' ?>">
          <button class="btn btn-ghost btn-sm"><?= $c['status']==='visible'?'Hide':'Show' ?></button>
        </form>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete this comment?')">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <input type="hidden" name="action" value="delete">
          <button class="btn btn-ghost btn-sm" style="color:var(--danger)">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
</main></div></body></html>