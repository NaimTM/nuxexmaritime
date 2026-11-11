<?php
$admin_title = 'Jobs';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// --- Toggle visibility ---
if (isset($_GET['toggle'])) {
  $id = (int)$_GET['toggle'];
  $row = $pdo->prepare("SELECT status FROM jobs WHERE id = ?");
  $row->execute([$id]);
  $cur = $row->fetchColumn();
  if ($cur !== false) {
    $new = ($cur === 'published') ? 'hidden' : 'published';
    $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?")->execute([$new, $id]);
    flash('job_msg', $new === 'published' ? 'Job is now LIVE.' : 'Job is now HIDDEN.');
  }
  redirect('/admin/jobs.php');
}

// --- Delete ---
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $pdo->prepare("DELETE FROM jobs WHERE id = ?")->execute([$id]);
  flash('job_msg', 'Job deleted permanently.');
  redirect('/admin/jobs.php');
}

// --- Filter ---
$filter = $_GET['status'] ?? '';
$where = $filter && in_array($filter, ['published','hidden','draft','closed'], true)
       ? "WHERE status = ?" : "";
$params = $where ? [$filter] : [];

$stmt = $pdo->prepare("
  SELECT id, title, rank_title, vessel_type, status,
         likes_count, comments_count, shares_count, views_count,
         created_at
  FROM jobs
  $where
  ORDER BY created_at DESC
");
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Counts
$counts = [
  'all'       => (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn(),
  'published' => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='published'")->fetchColumn(),
  'hidden'    => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='hidden'")->fetchColumn(),
  'draft'     => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='draft'")->fetchColumn(),
  'closed'    => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='closed'")->fetchColumn(),
];

$admin_title = 'Jobs';
require __DIR__ . '/_nav.php';
?>

<style>
  .dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:.4rem;vertical-align:middle}
  .dot-published{background:#1FA971;box-shadow:0 0 0 3px rgba(31,169,113,.2);animation:pulse-green 2s infinite}
  .dot-hidden{background:#D94A4A;box-shadow:0 0 0 3px rgba(217,74,74,.15)}
  .dot-draft{background:#E8A33D;box-shadow:0 0 0 3px rgba(232,163,61,.15)}
  .dot-closed{background:#8B98A5;box-shadow:0 0 0 3px rgba(139,152,165,.15)}
  @keyframes pulse-green{
    0%,100%{box-shadow:0 0 0 3px rgba(31,169,113,.25)}
    50%{box-shadow:0 0 0 6px rgba(31,169,113,.05)}
  }
  .status-cell{display:inline-flex;align-items:center;font-weight:600;font-size:.85rem}
  .row-hidden{opacity:.55;background:rgba(217,74,74,.03)}
  .row-hidden:hover td{background:rgba(217,74,74,.06) !important}
  .stat-chips{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
  .stat-chip{padding:.5rem 1rem;background:#fff;border:1.5px solid var(--line);border-radius:30px;font-size:.85rem;font-weight:600;color:var(--muted);text-decoration:none;transition:.2s;display:inline-flex;align-items:center;gap:.4rem}
  .stat-chip:hover{border-color:var(--teal);color:var(--teal)}
  .stat-chip.active{background:var(--navy);color:#fff;border-color:var(--navy)}
  .stat-chip .num{background:rgba(0,0,0,.06);padding:.05rem .5rem;border-radius:12px;font-size:.78rem}
  .stat-chip.active .num{background:rgba(255,255,255,.2)}
</style>

<div class="admin-topbar">
  <h1>Vacancies (<?= count($jobs) ?>)</h1>
  <a href="/admin/job-edit.php" class="btn btn-teal">+ Post New Vacancy</a>
</div>

<?php if ($m = flash('job_msg')): ?>
  <div class="alert alert-success"><?= e($m) ?></div>
<?php endif; ?>

<!-- Filter chips -->
<div class="stat-chips">
  <a href="?status=" class="stat-chip <?= $filter===''?'active':'' ?>">
    All <span class="num"><?= $counts['all'] ?></span>
  </a>
  <a href="?status=published" class="stat-chip <?= $filter==='published'?'active':'' ?>">
    <span class="dot dot-published"></span> Live <span class="num"><?= $counts['published'] ?></span>
  </a>
  <a href="?status=hidden" class="stat-chip <?= $filter==='hidden'?'active':'' ?>">
    <span class="dot dot-hidden"></span> Hidden <span class="num"><?= $counts['hidden'] ?></span>
  </a>
  <a href="?status=draft" class="stat-chip <?= $filter==='draft'?'active':'' ?>">
    <span class="dot dot-draft"></span> Draft <span class="num"><?= $counts['draft'] ?></span>
  </a>
  <a href="?status=closed" class="stat-chip <?= $filter==='closed'?'active':'' ?>">
    <span class="dot dot-closed"></span> Closed <span class="num"><?= $counts['closed'] ?></span>
  </a>
</div>

<?php if (!$jobs): ?>
  <div class="alert alert-info">
    No vacancies <?= $filter ? "with status '{$filter}'" : '' ?>. 
    <a href="/admin/job-edit.php">Post your first one →</a>
  </div>
<?php else: ?>
<table class="admin-table">
  <thead>
    <tr>
      <th>Status</th>
      <th>Title</th>
      <th>Rank / Vessel</th>
      <th>Engagement</th>
      <th>Posted</th>
      <th style="text-align:right">Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($jobs as $j):
      $dotClass = 'dot-' . $j['status'];
      $labels = [
        'published' => 'Live',
        'hidden'    => 'Hidden',
        'draft'     => 'Draft',
        'closed'    => 'Closed',
      ];
      $label = $labels[$j['status']] ?? ucfirst($j['status']);
    ?>
    <tr class="<?= $j['status'] !== 'published' ? 'row-hidden' : '' ?>">
      <td>
        <span class="status-cell">
          <span class="dot <?= $dotClass ?>"></span>
          <?= e($label) ?>
        </span>
      </td>
      <td>
        <strong><?= e($j['title']) ?></strong>
      </td>
      <td>
        <?= e($j['rank_title']) ?><br>
        <small class="muted"><?= e($j['vessel_type'] ?: '—') ?></small>
      </td>
      <td>
        <small>♥ <?= (int)$j['likes_count'] ?> · 💬 <?= (int)$j['comments_count'] ?> · ↗ <?= (int)$j['shares_count'] ?> · 👁 <?= (int)$j['views_count'] ?></small>
      </td>
      <td><small class="muted"><?= time_ago($j['created_at']) ?></small></td>
      <td style="text-align:right;white-space:nowrap">
        <?php if ($j['status'] === 'published'): ?>
          <a class="btn btn-ghost btn-sm" href="?toggle=<?= (int)$j['id'] ?>" title="Hide from site">🔴 Hide</a>
        <?php else: ?>
          <a class="btn btn-ghost btn-sm" href="?toggle=<?= (int)$j['id'] ?>" title="Make visible" style="color:var(--success);border-color:var(--success)">🟢 Publish</a>
        <?php endif; ?>

        <a class="btn btn-ghost btn-sm" href="/admin/job-edit.php?id=<?= (int)$j['id'] ?>">Edit</a>

        <a class="btn btn-ghost btn-sm" style="color:var(--danger);border-color:rgba(217,74,74,.4)"
           href="?delete=<?= (int)$j['id'] ?>"
           onclick="return confirm('Permanently delete this job? This cannot be undone.');">
           Delete
        </a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

</main></div></body></html>