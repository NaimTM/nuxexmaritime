<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_seafarer();

$id = (int)$_SESSION['seafarer']['id'];

$stmt = $pdo->prepare("
  SELECT a.*, j.title, j.slug, j.vessel_type, j.rank_title AS job_rank,
         j.salary_min, j.salary_max, j.currency, j.joining_port
  FROM applications a
  JOIN jobs j ON j.id = a.job_id
  WHERE a.seafarer_id = ?
  ORDER BY a.created_at DESC
");
$stmt->execute([$id]);
$apps = $stmt->fetchAll();

$page_title = "My Applications | Nuzex Maritime";
$meta_desc  = "Track all your job applications on Nuzex Maritime.";
require __DIR__ . '/includes/header.php';

$statusPill = [
  'applied'     => ['⏳','Applied'],
  'shortlisted' => ['⭐','Shortlisted'],
  'interview'   => ['📞','Interview'],
  'offered'     => ['🎉','Offered'],
  'hired'       => ['✅','Hired'],
  'rejected'    => ['❌','Not selected'],
];
?>

<section class="profile-hero" style="padding:40px 0 60px">
  <div class="container">
    <div class="profile-avatar"><?= e(strtoupper(substr($_SESSION['seafarer']['full_name'], 0, 1))) ?></div>
    <div class="profile-meta">
      <h1>My Applications</h1>
      <p><?= count($apps) ?> application<?= count($apps)===1?'':'s' ?> · updated live</p>
    </div>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container">

    <div class="profile-tabs" style="margin-top:-30px">
      <a href="/profile.php">Profile</a>
      <a href="/my-applications.php" class="active">Applications (<?= count($apps) ?>)</a>
      <a href="/settings.php">Settings</a>
      <a href="/jobs.php">Browse Jobs</a>
    </div>

    <?php if (!$apps): ?>
      <div class="alert alert-info" style="margin-top:1.5rem">
        You haven't applied to any jobs yet. <a href="/jobs.php">Browse live vacancies →</a>
      </div>
    <?php else: ?>
      <div class="jobs-feed" style="margin-top:1.5rem">
        <?php foreach ($apps as $a):
          [$icon, $label] = $statusPill[$a['status']] ?? ['•', ucfirst($a['status'])];
        ?>
          <article class="job-card">
            <div class="job-card-top">
              <div>
                <span class="job-tag"><?= e($a['vessel_type'] ?: 'Vessel') ?></span>
                <h3 style="margin-top:.5rem">
                  <a href="/job.php?slug=<?= e($a['slug']) ?>"><?= e($a['title']) ?></a>
                </h3>
              </div>
              <div class="job-salary">
                <?= money($a['salary_min'], $a['currency']) ?>
                <?= $a['salary_max'] ? '– ' . money($a['salary_max'], $a['currency']) : '' ?>
              </div>
            </div>

            <div class="job-meta">
              <span>📍 <?= e($a['joining_port'] ?: '—') ?></span>
              <span>🎯 Applied as <?= e($a['rank_title']) ?></span>
              <span>🕒 <?= time_ago($a['created_at']) ?></span>
            </div>

            <div class="job-actions">
              <span class="pill <?= e($a['status']) ?>" style="padding:.45rem .9rem;font-weight:600">
                <?= $icon ?> <?= e($label) ?>
              </span>
              <a href="/job.php?slug=<?= e($a['slug']) ?>" class="btn btn-ghost btn-sm">View Job</a>
              <?php if ($a['cv_path']): ?>
                <?php if (strpos($a['cv_path'], 'private_doc:') === 0): ?><a href="/document-download.php?id=<?= (int)substr($a['cv_path'], 12) ?>" class="action-btn">📄 My submitted CV</a>
                <?php else: ?><a href="/<?= e($a['cv_path']) ?>" target="_blank" class="action-btn">📄 My submitted CV</a><?php endif; ?>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>