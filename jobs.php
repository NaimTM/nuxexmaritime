<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// ---- Filters ----
$rank    = trim($_GET['rank']    ?? '');
$vessel  = trim($_GET['vessel']  ?? '');
$region  = trim($_GET['region']  ?? '');
$sort    = $_GET['sort']  ?? 'new';
$q       = trim($_GET['q'] ?? '');
$view    = $_GET['view'] ?? 'all';  // all | live | closed

$where  = ["status IN ('published','closed')"];
$params = [];

if ($view === 'live')   $where[] = "status = 'published'";
if ($view === 'closed') $where[] = "status = 'closed'";

if ($rank)   { $where[] = "rank_title LIKE ?";   $params[] = "%$rank%"; }
if ($vessel) { $where[] = "vessel_type LIKE ?";  $params[] = "%$vessel%"; }
if ($region) { $where[] = "joining_port LIKE ?"; $params[] = "%$region%"; }
if ($q)      {
  $where[] = "(title LIKE ? OR requirements LIKE ? OR description LIKE ?)";
  array_push($params, "%$q%", "%$q%", "%$q%");
}

$order = match($sort) {
  'liked'    => 'likes_count DESC, created_at DESC',
  'commented'=> 'comments_count DESC, created_at DESC',
  'closing'  => 'joining_date ASC',
  default    => "FIELD(status,'published','closed'), created_at DESC",
};

$sql = "SELECT * FROM jobs WHERE " . implode(' AND ', $where) . " ORDER BY $order LIMIT 30";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Counts for tabs
$countAll    = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status IN ('published','closed')")->fetchColumn();
$countLive   = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='published'")->fetchColumn();
$countClosed = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='closed'")->fetchColumn();

// Distinct values for filter dropdowns
$vessels = $pdo->query("SELECT DISTINCT vessel_type FROM jobs WHERE vessel_type <> '' AND status IN ('published','closed') ORDER BY vessel_type")->fetchAll(PDO::FETCH_COLUMN);
$ranks   = $pdo->query("SELECT DISTINCT rank_title FROM jobs WHERE rank_title <> '' AND status IN ('published','closed') ORDER BY rank_title")->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Live Seafarer Jobs & Vacancies | Nuzex Maritime";
$meta_desc  = "Browse live vacancies for officers and ratings. Apply with CV, like, comment, and share jobs with your network.";
require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:50px">
  <div class="container">

    <div style="display:flex;justify-content:space-between;align-items:end;flex-wrap:wrap;gap:1rem">
      <div>
        <h1 style="font-size:2rem">Vacancies</h1>
        <p style="margin:0">
          <?= count($jobs) ?> position<?= count($jobs)===1?'':'s' ?> shown
          · updated live
        </p>
      </div>
      <?php if (is_seafarer()): ?>
        <a href="/profile.php" class="btn btn-ghost btn-sm">My Profile →</a>
      <?php else: ?>
        <a href="/register.php" class="btn btn-teal btn-sm">Register to Apply</a>
      <?php endif; ?>
    </div>

    <!-- View tabs -->
    <div class="view-tabs">
      <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'all'])) ?>"
         class="view-tab <?= $view==='all'?'active':'' ?>">
        All <span class="tab-count"><?= $countAll ?></span>
      </a>
      <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'live'])) ?>"
         class="view-tab <?= $view==='live'?'active':'' ?>">
        <span class="live-dot"></span> Live <span class="tab-count"><?= $countLive ?></span>
      </a>
      <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'closed'])) ?>"
         class="view-tab <?= $view==='closed'?'active':'' ?>">
        🔒 Closed <span class="tab-count"><?= $countClosed ?></span>
      </a>
    </div>

    <!-- Filter bar -->
    <form method="get" class="form-card" style="margin-top:1.2rem;padding:1.2rem">
      <input type="hidden" name="view" value="<?= e($view) ?>">
      <div class="form-row" style="grid-template-columns:2fr 1fr 1fr 1fr 1fr;gap:.8rem;margin:0">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="Search jobs…">
        <select name="rank">
          <option value="">All ranks</option>
          <?php foreach ($ranks as $r): ?>
            <option value="<?= e($r) ?>" <?= $rank===$r?'selected':'' ?>><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="vessel">
          <option value="">All vessels</option>
          <?php foreach ($vessels as $v): ?>
            <option value="<?= e($v) ?>" <?= $vessel===$v?'selected':'' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="sort">
          <option value="new"       <?= $sort==='new'?'selected':'' ?>>Newest</option>
          <option value="liked"     <?= $sort==='liked'?'selected':'' ?>>Most Liked</option>
          <option value="commented" <?= $sort==='commented'?'selected':'' ?>>Most Discussed</option>
          <option value="closing"   <?= $sort==='closing'?'selected':'' ?>>Closing Soon</option>
        </select>
        <button class="btn btn-primary">Filter</button>
      </div>
    </form>

    <?php if (!$jobs): ?>
      <div class="alert alert-info" style="margin-top:2rem">
        No vacancies match your filters right now. Try clearing them, or
        <a href="/register.php">register</a> to get alerts when new jobs are posted.
      </div>
    <?php else: ?>
      <div class="jobs-feed" style="margin-top:1.5rem">
        <?php foreach ($jobs as $j):
          $isClosed = ($j['status'] === 'closed');
          $liked = false;
          if (is_seafarer()) {
            $lq = $pdo->prepare("SELECT 1 FROM likes WHERE job_id=? AND seafarer_id=?");
            $lq->execute([$j['id'], $_SESSION['seafarer']['id']]);
            $liked = (bool)$lq->fetchColumn();
          }
          $jobUrl   = SITE_URL . '/job.php?slug=' . e($j['slug']);
          $jobTitle = $j['title'] . ' — ' . SITE_NAME;
        ?>
          <article class="job-card" style="<?= $isClosed ? 'border-left-color:#8B98A5;opacity:.92' : '' ?>">
            <div class="job-card-top">
              <div>
                <?php if ($isClosed): ?>
                  <span class="job-tag" style="background:rgba(139,152,165,.15);color:#5A6B7B">🔒 CLOSED</span>
                <?php else: ?>
                  <span class="job-tag" style="background:rgba(31,169,113,.1);color:#1FA971">
                    <span class="live-dot"></span> LIVE
                  </span>
                <?php endif; ?>
                <span class="job-tag" style="margin-left:.4rem"><?= e($j['vessel_type'] ?: 'Vessel') ?></span>

                <h3 style="margin-top:.5rem">
                  <a href="/job.php?slug=<?= e($j['slug']) ?>"><?= e($j['title']) ?></a>
                </h3>
              </div>
              <div class="job-salary">
                <?= money($j['salary_min'], $j['currency']) ?>
                <?= $j['salary_max'] ? '– ' . money($j['salary_max'], $j['currency']) : '' ?>
              </div>
            </div>

            <div class="job-meta">
              <?php if ($j['flag']): ?><span>🏴 <?= e($j['flag']) ?></span><?php endif; ?>
              <?php if ($j['contract_length']): ?><span>⏱ <?= e($j['contract_length']) ?></span><?php endif; ?>
              <?php if ($j['joining_port']): ?><span>📍 <?= e($j['joining_port']) ?></span><?php endif; ?>
              <?php if ($j['joining_date']): ?><span>📅 <?= date('M j, Y', strtotime($j['joining_date'])) ?></span><?php endif; ?>
              <span>🕒 <?= time_ago($j['created_at']) ?></span>
            </div>

            <div class="job-actions">
              <?php if ($isClosed): ?>
                <a href="/job.php?slug=<?= e($j['slug']) ?>" class="btn btn-ghost btn-sm" style="color:#8B98A5;border-color:#8B98A5">
                  🔒 View Details
                </a>
                <span class="action-btn" style="cursor:not-allowed;opacity:.7">Applications Closed</span>
              <?php else: ?>
                <a href="/job.php?slug=<?= e($j['slug']) ?>#apply" class="btn btn-primary btn-sm">Apply with CV</a>
              <?php endif; ?>

              <button class="action-btn <?= $liked ? 'liked' : '' ?>" data-like="<?= (int)$j['id'] ?>">
                <span class="like-icon"><?= $liked ? '♥' : '♡' ?></span>
                <span class="count"><?= (int)$j['likes_count'] ?></span>
              </button>

              <a class="action-btn" href="/job.php?slug=<?= e($j['slug']) ?>#comments">
                💬 <?= (int)$j['comments_count'] ?>
              </a>

              <button class="action-btn"
                      data-share="native"
                      data-id="<?= (int)$j['id'] ?>"
                      data-url="<?= $jobUrl ?>"
                      data-title="<?= e($jobTitle) ?>">
                ↗ Share
              </button>

              <button class="action-btn"
                      data-share="copy"
                      data-id="<?= (int)$j['id'] ?>"
                      data-url="<?= $jobUrl ?>">
                🔗 Copy Link
              </button>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>