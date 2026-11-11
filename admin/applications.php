<?php
$admin_title = 'Applications';
require __DIR__ . '/_nav.php';

// Update status
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['status'], $_POST['id'])) {
  csrf_check($_POST['csrf'] ?? null);
  $allowed = ['applied','shortlisted','interview','offered','hired','rejected'];
  if (in_array($_POST['status'], $allowed, true)) {
    $pdo->prepare("UPDATE applications SET status=? WHERE id=?")
        ->execute([$_POST['status'], (int)$_POST['id']]);
    flash('app_msg', 'Status updated.');
  }
  redirect('/admin/applications.php');
}

// Filters
$filter_status = $_GET['status'] ?? '';
$filter_job    = (int)($_GET['job'] ?? 0);

$where = ['1=1']; $params = [];
if ($filter_status) { $where[] = "a.status = ?"; $params[] = $filter_status; }
if ($filter_job)    { $where[] = "a.job_id = ?"; $params[] = $filter_job; }

$stmt = $pdo->prepare("
  SELECT a.*, s.full_name, s.email, s.phone, s.nationality, s.rank_title AS profile_rank,
         j.title AS job_title, j.slug AS job_slug
  FROM applications a
  JOIN seafarers s ON s.id = a.seafarer_id
  JOIN jobs j      ON j.id = a.job_id
  WHERE " . implode(' AND ', $where) . "
  ORDER BY a.created_at DESC
  LIMIT 200
");
$stmt->execute($params);
$apps = $stmt->fetchAll();

$jobs = $pdo->query("SELECT id,title FROM jobs ORDER BY created_at DESC")->fetchAll();
$statuses = ['applied','shortlisted','interview','offered','hired','rejected'];
?>

<div class="admin-topbar">
  <h1>Applications (<?= count($apps) ?>)</h1>
  <form method="get" style="display:flex;gap:.5rem;flex-wrap:wrap">
    <select name="status" onchange="this.form.submit()" style="padding:.5rem .8rem;border:1.5px solid var(--line);border-radius:8px">
      <option value="">All statuses</option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= $s ?>" <?= $filter_status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="job" onchange="this.form.submit()" style="padding:.5rem .8rem;border:1.5px solid var(--line);border-radius:8px">
      <option value="">All jobs</option>
      <?php foreach ($jobs as $j): ?>
        <option value="<?= $j['id'] ?>" <?= $filter_job==$j['id']?'selected':'' ?>><?= e($j['title']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if ($m = flash('app_msg')): ?><div class="alert alert-success"><?= e($m) ?></div><?php endif; ?>

<?php if (!$apps): ?>
  <div class="alert alert-info">No applications yet.</div>
<?php else: ?>
<table class="admin-table">
  <thead>
    <tr>
      <th>Seafarer</th>
      <th>Applied For</th>
      <th>Rank</th>
      <th>Contact</th>
      <th>CV</th>
      <th>Status</th>
      <th>Applied</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($apps as $a): ?>
    <tr>
      <td>
        <strong><?= e($a['full_name']) ?></strong><br>
        <small class="muted"><?= e($a['nationality'] ?: '—') ?></small>
      </td>
      <td>
        <a href="/job.php?slug=<?= e($a['job_slug']) ?>" target="_blank">
          <?= e($a['job_title']) ?>
        </a>
      </td>
      <td><?= e($a['rank_title'] ?: $a['profile_rank']) ?></td>
      <td>
        <a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a><br>
        <small class="muted"><?= e($a['phone']) ?></small>
      </td>
      <td>
        <?php if ($a['cv_path']): ?>
          <?php if (strpos($a['cv_path'], 'private_doc:') === 0): ?><a class="btn btn-ghost btn-sm" href="/document-download.php?id=<?= (int)substr($a['cv_path'], 12) ?>">Download CV</a>
          <?php else: ?><a class="btn btn-ghost btn-sm" href="/<?= e($a['cv_path']) ?>" target="_blank">Open legacy CV</a><?php endif; ?>
        <?php else: ?><span class="muted">—</span><?php endif; ?>
      </td>
      <td><span class="pill <?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
      <td><small class="muted"><?= time_ago($a['created_at']) ?></small></td>
      <td>
        <form method="post" style="display:flex;gap:.3rem">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <select name="status" onchange="this.form.submit()" style="padding:.3rem .5rem;font-size:.82rem;border-radius:6px;border:1px solid var(--line)">
            <?php foreach ($statuses as $s): ?>
              <option value="<?= $s ?>" <?= $a['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
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