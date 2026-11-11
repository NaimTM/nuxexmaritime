<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id  = (int)($_GET['id'] ?? 0);
$job = [
  'id' => 0, 'title' => '', 'rank_title' => '', 'vessel_type' => '',
  'flag' => '', 'salary_min' => '', 'salary_max' => '', 'currency' => 'USD',
  'contract_length' => '', 'joining_date' => '', 'joining_port' => '',
  'requirements' => '', 'description' => '', 'status' => 'published',
];

if ($id) {
  $q = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
  $q->execute([$id]);
  $row = $q->fetch();
  if ($row) $job = $row;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check($_POST['csrf'] ?? null);

  $title           = trim($_POST['title'] ?? '');
  $rank_title      = trim($_POST['rank_title'] ?? '');
  $vessel_type     = trim($_POST['vessel_type'] ?? '');
  $flag            = trim($_POST['flag'] ?? '');
  $salary_min      = ($_POST['salary_min'] ?? '') !== '' ? (float)$_POST['salary_min'] : null;
  $salary_max      = ($_POST['salary_max'] ?? '') !== '' ? (float)$_POST['salary_max'] : null;
  $currency        = trim($_POST['currency'] ?? 'USD');
  $contract_length = trim($_POST['contract_length'] ?? '');
  $joining_date    = ($_POST['joining_date'] ?? '') !== '' ? $_POST['joining_date'] : null;
  $joining_port    = trim($_POST['joining_port'] ?? '');
  $requirements    = trim($_POST['requirements'] ?? '');
  $description     = trim($_POST['description'] ?? '');
  $status          = in_array($_POST['status'] ?? '', ['draft','published','hidden','closed'], true)
                      ? $_POST['status'] : 'published';

  if ($title === '' || $rank_title === '') {
    $error = 'Title and Rank are required.';
  } else {
    try {
      if ($job['id']) {
        $sql = "UPDATE jobs SET
          title=?, rank_title=?, vessel_type=?, flag=?,
          salary_min=?, salary_max=?, currency=?, contract_length=?,
          joining_date=?, joining_port=?, requirements=?, description=?, status=?
          WHERE id=?";
        $pdo->prepare($sql)->execute([
          $title, $rank_title, $vessel_type, $flag,
          $salary_min, $salary_max, $currency, $contract_length,
          $joining_date, $joining_port, $requirements, $description, $status,
          $job['id']
        ]);
        flash('job_saved', 'Job updated successfully.');
      } else {
        $slug = slugify($title) . '-' . substr(md5(uniqid('', true)), 0, 6);
        $sql = "INSERT INTO jobs
          (slug, title, rank_title, vessel_type, flag,
           salary_min, salary_max, currency, contract_length,
           joining_date, joining_port, requirements, description, status)
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute([
          $slug, $title, $rank_title, $vessel_type, $flag,
          $salary_min, $salary_max, $currency, $contract_length,
          $joining_date, $joining_port, $requirements, $description, $status
        ]);
        flash('job_saved', 'Job posted successfully.');
      }
      redirect('/admin/jobs.php');
    } catch (PDOException $e) {
      $error = 'Database error: ' . $e->getMessage();
    }
  }
}

$admin_title = $job['id'] ? 'Edit Job' : 'Post New Job';
require __DIR__ . '/_nav.php';
?>

<div class="admin-topbar">
  <h1><?= $job['id'] ? 'Edit' : 'Post New' ?> Vacancy</h1>
  <a href="/admin/jobs.php" class="btn btn-ghost btn-sm">← Back to Jobs</a>
</div>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<form method="post" class="form-card" style="max-width:820px">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

  <div class="form-group">
    <label>Job Title *</label>
    <input name="title" required value="<?= e($job['title']) ?>" placeholder="e.g. Chief Officer — VLCC">
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Rank *</label>
      <input name="rank_title" required value="<?= e($job['rank_title']) ?>" placeholder="Chief Officer">
    </div>
    <div class="form-group">
      <label>Vessel Type</label>
      <input name="vessel_type" value="<?= e($job['vessel_type']) ?>" placeholder="VLCC / Bulk / Container">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Flag</label>
      <input name="flag" value="<?= e($job['flag']) ?>" placeholder="Panama">
    </div>
    <div class="form-group">
      <label>Contract Length</label>
      <input name="contract_length" value="<?= e($job['contract_length']) ?>" placeholder="6 months">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Salary Min</label>
      <input type="number" step="0.01" name="salary_min" value="<?= e((string)$job['salary_min']) ?>">
    </div>
    <div class="form-group">
      <label>Salary Max</label>
      <input type="number" step="0.01" name="salary_max" value="<?= e((string)$job['salary_max']) ?>">
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Currency</label>
      <select name="currency">
        <?php foreach (['USD','EUR','GBP','SGD','AED'] as $c): ?>
          <option <?= $job['currency']===$c?'selected':'' ?>><?= $c ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Joining Date</label>
      <input type="date" name="joining_date" value="<?= e((string)$job['joining_date']) ?>">
    </div>
  </div>

  <div class="form-group">
    <label>Joining Port</label>
    <input name="joining_port" value="<?= e($job['joining_port']) ?>" placeholder="Singapore">
  </div>

  <div class="form-group">
    <label>Requirements</label>
    <textarea name="requirements" placeholder="COC, STCW, 24 months experience…"><?= e($job['requirements']) ?></textarea>
  </div>

  <div class="form-group">
    <label>Description</label>
    <textarea name="description" style="min-height:180px"><?= e($job['description']) ?></textarea>
  </div>

  <div class="form-group">
    <label>Status</label>
    <select name="status">
      <option value="published" <?= $job['status']==='published'?'selected':'' ?>>🟢 Published — live & open for applications</option>
      <option value="closed"    <?= $job['status']==='closed'?'selected':'' ?>>⚪ Closed — visible but no applications</option>
      <option value="hidden"    <?= $job['status']==='hidden'?'selected':'' ?>>🔴 Hidden — not visible on site</option>
      <option value="draft"     <?= $job['status']==='draft'?'selected':'' ?>>🟡 Draft — private, work in progress</option>
    </select>
    <p class="form-help">
      <strong>Published</strong> — appears live on /jobs.php, seafarers can apply. ·
      <strong>Closed</strong> — still visible as a card with a gray "CLOSED" badge, but Apply button is disabled. Good for showing filled positions. ·
      <strong>Hidden</strong> — removed from the public site entirely. ·
      <strong>Draft</strong> — only visible to you in the admin panel.
    </p>
  </div>

  <div style="display:flex;gap:.7rem;flex-wrap:wrap">
    <button class="btn btn-teal btn-lg"><?= $job['id'] ? 'Save Changes' : 'Post Job' ?></button>
    <a href="/admin/jobs.php" class="btn btn-ghost">Cancel</a>
  </div>
</form>

</main></div></body></html>