<?php
$admin_title = 'Crew Requests';
require __DIR__ . '/_nav.php';

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['mark_completed'], $_POST['id'])) {
  csrf_check($_POST['csrf'] ?? null);
  $newStatus = ($_POST['mark_completed'] === '1') ? 'completed' : 'contacted';
  $pdo->prepare("UPDATE crew_requests SET status=? WHERE id=?")->execute([$newStatus, (int)$_POST['id']]);
  flash('cr_msg', $newStatus === 'completed' ? 'Crew request marked completed.' : 'Crew request marked as contacted.');
  redirect('/admin/crew-requests.php');
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['status'], $_POST['id'])) {
  csrf_check($_POST['csrf'] ?? null);
  $allowed = ['new','contacted','completed','closed'];
  if (in_array($_POST['status'], $allowed, true)) {
    $pdo->prepare("UPDATE crew_requests SET status=? WHERE id=?")
        ->execute([$_POST['status'], (int)$_POST['id']]);
    flash('cr_msg', 'Status updated.');
  }
  redirect('/admin/crew-requests.php');
}

$requests = $pdo->query("SELECT * FROM crew_requests ORDER BY created_at DESC LIMIT 200")->fetchAll();
$statuses = ['new','contacted','completed','closed'];
?>

<div class="admin-topbar">
  <h1>Crew Requests (<?= count($requests) ?>)</h1>
</div>

<?php if ($m = flash('cr_msg')): ?><div class="alert alert-success"><?= e($m) ?></div><?php endif; ?>

<?php if (!$requests): ?>
  <div class="alert alert-info">No crew requests yet.</div>
<?php else: ?>
<table class="admin-table">
  <thead>
    <tr>
      <th>Company</th><th>Contact</th><th>Vessel / Ranks</th><th>Joining</th><th>Notes</th><th>Status</th><th>Completed?</th><th>Received</th><th></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($requests as $r): ?>
    <tr>
      <td><strong><?= e($r['company']) ?></strong></td>
      <td>
        <?= e($r['contact_name']) ?><br>
        <a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><br>
        <small class="muted"><?= e($r['phone']) ?></small>
      </td>
      <td>
        <small><?= e($r['vessel_type']) ?> · <?= e($r['flag']) ?></small><br>
        <strong><?= e($r['ranks_needed']) ?></strong><br>
        <small class="muted">Crew: <?= (int)$r['crew_count'] ?></small>
      </td>
      <td>
        <?= $r['joining_date'] ? date('M j, Y', strtotime($r['joining_date'])) : '—' ?><br>
        <small class="muted"><?= e($r['port']) ?></small>
      </td>
      <td style="max-width:280px"><small><?= e(mb_strimwidth($r['notes'] ?? '', 0, 140, '…')) ?></small></td>
      <td><span class="pill <?= e($r['status']) ?>"><?= e($r['status']) ?></span></td>
      <td>
        <form method="post" style="display:flex;align-items:center;gap:.35rem">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <input type="hidden" name="mark_completed" value="<?= $r['status']==='completed'?'0':'1' ?>">
          <input type="checkbox" aria-label="Mark crew request completed" <?= $r['status']==='completed'?'checked':'' ?> onchange="this.form.submit()">
          <small><?= $r['status']==='completed'?'Done':'Mark done' ?></small>
        </form>
      </td>
      <td><small class="muted"><?= time_ago($r['created_at']) ?></small></td>
      <td>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <select name="status" onchange="this.form.submit()" style="padding:.3rem .5rem;font-size:.82rem;border-radius:6px;border:1px solid var(--line)">
            <?php foreach ($statuses as $s): ?>
              <option value="<?= $s ?>" <?= $r['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
</main></div></body></html>