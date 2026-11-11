<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// Ensure seafarers table has rank and vessel_type columns dynamically if missing
try {
    $cols = $pdo->query("SHOW COLUMNS FROM seafarers")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('rank', $cols)) {
        $pdo->exec("ALTER TABLE seafarers ADD COLUMN `rank` VARCHAR(100) NULL");
    }
    if (!in_array('vessel_type', $cols)) {
        $pdo->exec("ALTER TABLE seafarers ADD COLUMN vessel_type VARCHAR(100) NULL");
    }
} catch (Exception $e) {
    // Ignore if already present
}

// Handle viewing individual seafarer details & documents if an ID is passed
$selected_seafarer_id = $_GET['id'] ?? null;
$selected_seafarer = null;
$seafarer_docs = [];
$seafarer_deployments = [];

if ($selected_seafarer_id) {
    $stmt = $pdo->prepare("SELECT * FROM seafarers WHERE id = ?");
    $stmt->execute([$selected_seafarer_id]);
    $selected_seafarer = $stmt->fetch();

    if ($selected_seafarer) {
        // Fetch documents
        $docStmt = $pdo->prepare("SELECT * FROM seafarer_documents WHERE seafarer_id = ? ORDER BY created_at DESC");
        $docStmt->execute([$selected_seafarer_id]);
        $seafarer_docs = $docStmt->fetchAll();

        // Fetch deployments with lifecycle status
        $depStmt = $pdo->prepare("
            SELECT cd.*, s.company_name AS shipowner_name 
            FROM crew_deployments cd
            LEFT JOIN shipowners s ON cd.shipowner_id = s.id
            WHERE cd.seafarer_id = ?
            ORDER BY cd.created_at DESC
        ");
        $depStmt->execute([$selected_seafarer_id]);
        $seafarer_deployments = $depStmt->fetchAll();
    }
}

// Fetch filter & sort parameters
$search       = trim($_GET['search'] ?? '');
$rank_filter  = trim($_GET['rank'] ?? '');
$vessel_filter= trim($_GET['vessel'] ?? '');
$sort         = $_GET['sort'] ?? 'newest';

// Build SQL query dynamically with filters
$whereClauses = ["1=1"];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($rank_filter !== '') {
    $whereClauses[] = "rank = ?";
    $params[] = $rank_filter;
}

if ($vessel_filter !== '') {
    $whereClauses[] = "vessel_type = ?";
    $params[] = $vessel_filter;
}

$orderBy = "created_at DESC";
if ($sort === 'oldest') {
    $orderBy = "created_at ASC";
} elseif ($sort === 'name') {
    $orderBy = "full_name ASC";
} elseif ($sort === 'availability') {
    $orderBy = "is_available DESC";
}

$whereSql = implode(" AND ", $whereClauses);
$stmt = $pdo->prepare("SELECT * FROM seafarers WHERE $whereSql ORDER BY $orderBy");
$stmt->execute($params);
$seafarers = $stmt->fetchAll();

// Fetch unique ranks and vessel types for filter dropdowns
$available_ranks = $pdo->query("SELECT DISTINCT `rank` FROM seafarers WHERE `rank` IS NOT NULL AND `rank` != '' ORDER BY `rank`")->fetchAll(PDO::FETCH_COLUMN);
$available_vessels = $pdo->query("SELECT DISTINCT vessel_type FROM seafarers WHERE vessel_type IS NOT NULL AND vessel_type != '' ORDER BY vessel_type")->fetchAll(PDO::FETCH_COLUMN);

$admin_title = 'Seafarer Management';
require __DIR__ . '/_nav.php';
?>

<style>
  .seafarer-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 1.2rem 1.5rem;
    border: 1px solid var(--line);
    border-radius: 14px;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
    box-shadow: var(--shadow-sm);
  }
  .seafarer-toolbar form {
    display: flex;
    gap: .6rem;
    align-items: center;
    flex-wrap: wrap;
  }
  .table-avatar {
    width: 40px; height: 40px; border-radius: 50%;
    background: linear-gradient(135deg, var(--teal), #0891b2);
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: .95rem; font-family: 'Sora', sans-serif;
  }
  .profile-header {
    display: flex; align-items: center; gap: 1.5rem;
    margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--line);
  }
  .profile-avatar-lg {
    width: 72px; height: 72px; border-radius: 50%;
    background: linear-gradient(135deg, var(--navy), var(--navy-l));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1.8rem; font-family: 'Sora', sans-serif;
    box-shadow: 0 4px 12px rgba(10,37,64,0.15);
  }
  .info-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.2rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid var(--line);
  }
  .info-item label {
    display: block; font-size: .75rem; text-transform: uppercase; color: var(--muted); font-weight: 700; letter-spacing: .04em; margin-bottom: .3rem;
  }
  .info-item span {
    font-size: 1rem; color: var(--navy); font-weight: 600;
  }
  .status-badge {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .45rem .9rem; border-radius: 50px; font-weight: 600; font-size: .85rem;
  }
  .status-available {
    background: rgba(31, 169, 113, 0.12); color: #0f5132; border: 1px solid rgba(31, 169, 113, 0.3);
  }
  .status-unavailable {
    background: rgba(217, 74, 74, 0.1); color: #842029; border: 1px solid rgba(217, 74, 74, 0.3);
  }
  .pulse-dot {
    width: 8px; height: 8px; border-radius: 50%; display: inline-block;
    animation: pulse 2s infinite;
  }
  .status-available .pulse-dot { background: #1fa971; }
  .status-unavailable .pulse-dot { background: #d94a4a; animation: none; }
  @keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(31, 169, 113, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(31, 169, 113, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(31, 169, 113, 0); }
  }

  /* Visual Deployment Lifecycle Tracker Styles */
  .lifecycle-tracker {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin: 1.5rem 0 2rem 0;
    padding: 0 1rem;
  }
  .lifecycle-tracker::before {
    content: '';
    position: absolute;
    top: 15px;
    left: 40px;
    right: 40px;
    height: 3px;
    background: var(--line);
    z-index: 1;
  }
  .lifecycle-step {
    position: relative;
    z-index: 2;
    text-align: center;
    background: #fff;
    padding: 0 10px;
  }
  .lifecycle-node {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e2e8f0;
    color: var(--muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
    margin: 0 auto 6px auto;
    border: 2px solid #cbd5e1;
  }
  .lifecycle-step.completed .lifecycle-node {
    background: var(--teal);
    color: #fff;
    border-color: var(--teal-d);
  }
  .lifecycle-step.active .lifecycle-node {
    background: var(--navy);
    color: #fff;
    border-color: var(--navy);
    box-shadow: 0 0 0 4px rgba(10,37,64,0.15);
  }
  .lifecycle-step label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    display: block;
  }
  .lifecycle-step.active label, .lifecycle-step.completed label {
    color: var(--navy);
  }
</style>

<div class="admin-topbar">
    <h1>⚓ Seafarer Directory & Dossiers</h1>
    <p class="muted" style="font-size: .9rem; margin-top: .2rem;">Review qualified maritime personnel, certifications, and professional credentials.</p>
</div>

<?php if ($selected_seafarer): ?>
    <!-- ======================================================== -->
    <!-- ISOLATED SINGLE SEAFARER PROFILE VIEW                    -->
    <!-- ======================================================== -->
    <div class="dash-panel" style="margin-bottom: 2rem; border-left: 6px solid var(--teal-d); box-shadow: var(--shadow);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <a href="/admin/seafarers.php" class="btn btn-ghost btn-sm" style="font-weight: 600;">← Back to Directory</a>
            
            <div class="status-badge <?= $selected_seafarer['is_available'] ? 'status-available' : 'status-unavailable' ?>">
                <span class="pulse-dot"></span>
                <?= $selected_seafarer['is_available'] ? 'Available for Immediate Deployment' : 'Currently On Contract / Unavailable' ?>
            </div>
        </div>

        <div class="profile-header">
            <div class="profile-avatar-lg">
                <?= e(strtoupper(substr($selected_seafarer['full_name'], 0, 1))) ?>
            </div>
            <div>
                <h2 style="font-size: 1.6rem; color: var(--navy); margin-bottom: .2rem;"><?= e($selected_seafarer['full_name']) ?></h2>
                <p class="muted" style="font-size: .95rem; display:flex; align-items:center; gap:.8rem;">
                    <span>📧 <?= e($selected_seafarer['email']) ?></span>
                    <span>·</span>
                    <span>Registered <?= time_ago($selected_seafarer['created_at']) ?></span>
                </p>
            </div>
        </div>

        <div class="info-grid" style="margin-bottom: 2rem;">
            <div class="info-item">
                <label>Professional Rank</label>
                <span style="color: var(--teal-d); font-size: 1.1rem;"><?= e($selected_seafarer['rank'] ?: 'Not specified') ?></span>
            </div>
            <div class="info-item">
                <label>Preferred / Expert Vessel Type</label>
                <span><?= e($selected_seafarer['vessel_type'] ?: 'Not specified') ?></span>
            </div>
            <div class="info-item">
                <label>Date of Birth</label>
                <span><?= e($selected_seafarer['dob'] ?? 'Not specified') ?></span>
            </div>
            <div class="info-item">
                <label>System Dossier ID</label>
                <span>#<?= (int)$selected_seafarer['id'] ?></span>
            </div>
            <div class="info-item" style="grid-column: 1 / -1;">
                <label>Residential / Contact Address</label>
                <span style="font-weight: 400; line-height: 1.5;"><?= nl2br(e($selected_seafarer['address'] ?? 'Not provided')) ?></span>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- VISUAL DEPLOYMENT LIFECYCLE TRACKER                     -->
        <!-- ======================================================== -->
        <div style="background: #f8fafc; border: 1px solid var(--line); border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;">
            <h3 style="font-size: 1.05rem; margin-bottom: 1rem; color: var(--navy);">🚢 Visual Deployment Lifecycle</h3>
            
            <?php if (empty($seafarer_deployments)): ?>
                <p class="muted" style="font-size: .9rem; font-style: italic; margin: 0;">No active deployment or recruitment process initiated for this seafarer yet.</p>
            <?php else: ?>
                <?php foreach ($seafarer_deployments as $dep): 
                    // Determine current stage index (Applied -> Shortlisted -> Medical -> Visa -> Confirmed -> Onboard)
                    $status = strtolower($dep['status'] ?? 'applied');
                    $stages = ['applied', 'shortlisted', 'medical', 'visa', 'confirmed', 'active', 'onboard'];
                    
                    // Map common DB statuses to tracker nodes
                    $currentIndex = 0;
                    if ($status === 'shortlisted') $currentIndex = 1;
                    elseif ($status === 'medical' || $status === 'medical scheduled') $currentIndex = 2;
                    elseif ($status === 'visa' || $status === 'flag state processing') $currentIndex = 3;
                    elseif ($status === 'confirmed' || $status === 'confirmed joining') $currentIndex = 4;
                    elseif ($status === 'active' || $status === 'onboard') $currentIndex = 5;
                ?>
                    <div style="background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 1.2rem; margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <div>
                                <strong style="color: var(--navy); font-size: 1rem;"><?= e($dep['vessel_name']) ?></strong>
                                <span class="muted" style="font-size: .85rem; margin-left: .5rem;">(Rank: <?= e($dep['rank_title'] ?? $selected_seafarer['rank']) ?>)</span>
                            </div>
                            <span class="badge" style="background: rgba(15,181,174,0.12); color: var(--teal-d);">
                                Client: <?= e($dep['shipowner_name'] ?? 'Direct Agency') ?>
                            </span>
                        </div>

                        <!-- Lifecycle Progress Nodes -->
                        <div class="lifecycle-tracker">
                            <div class="lifecycle-step <?= $currentIndex >= 0 ? ($currentIndex > 0 ? 'completed' : 'active') : '' ?>">
                                <div class="lifecycle-node">1</div>
                                <label>Applied</label>
                            </div>
                            <div class="lifecycle-step <?= $currentIndex >= 1 ? ($currentIndex > 1 ? 'completed' : 'active') : '' ?>">
                                <div class="lifecycle-node">2</div>
                                <label>Shortlisted</label>
                            </div>
                            <div class="lifecycle-step <?= $currentIndex >= 2 ? ($currentIndex > 2 ? 'completed' : 'active') : '' ?>">
                                <div class="lifecycle-node">3</div>
                                <label>Medical</label>
                            </div>
                            <div class="lifecycle-step <?= $currentIndex >= 3 ? ($currentIndex > 3 ? 'completed' : 'active') : '' ?>">
                                <div class="lifecycle-node">4</div>
                                <label>Visa / Flag</label>
                            </div>
                            <div class="lifecycle-step <?= $currentIndex >= 4 ? ($currentIndex > 4 ? 'completed' : 'active') : '' ?>">
                                <div class="lifecycle-node">5</div>
                                <label>Confirmed</label>
                            </div>
                            <div class="lifecycle-step <?= $currentIndex >= 5 ? 'active' : '' ?>">
                                <div class="lifecycle-node">6</div>
                                <label>Onboard</label>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h3 style="font-size: 1.15rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between;">
            <span>📁 Verified Document Vault</span>
            <span class="badge" style="background: rgba(15,181,174,0.12); color: var(--teal-d); font-size: .85rem; padding: .3rem .7rem;">
                <?= count($seafarer_docs) ?> Documents Filed
            </span>
        </h3>

        <?php if (empty($seafarer_docs)): ?>
            <div class="dash-empty" style="background: #f8fafc; border-radius: 12px; border: 2px dashed var(--line); padding: 3rem 1rem;">
                <p style="font-size: 1rem; color: var(--navy); font-weight: 600; margin-bottom: .3rem;">No documents uploaded yet.</p>
                <span class="muted" style="font-size: .85rem;">This seafarer has not uploaded their certificates, passport, or CDC scans.</span>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Document Type / Certification</th>
                            <th>File Reference / Path</th>
                            <th>Expiry Date</th>
                            <th>Uploaded</th>
                            <th style="text-align: right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($seafarer_docs as $doc): ?>
                            <tr>
                                <td><strong>📄 <?= e($doc['doc_type']) ?></strong></td>
                                <td><small class="muted"><?= e($doc['file_path']) ?></small></td>
                                <td>
                                    <?php 
                                        $isExpiringSoon = !empty($doc['expiry_date']) && strtotime($doc['expiry_date']) < strtotime('+30 days');
                                    ?>
                                    <span style="<?= $isExpiringSoon ? 'color: var(--danger); font-weight: 700;' : '' ?>">
                                        <?= e($doc['expiry_date'] ?? 'N/A') ?> <?= $isExpiringSoon ? '⚠️ (Expiring)' : '' ?>
                                    </span>
                                </td>
                                <td><small class="muted"><?= time_ago($doc['created_at']) ?></small></td>
                                <td style="text-align: right">
                                    <a href="<?= e($doc['file_path']) ?>" target="_blank" class="btn btn-teal btn-sm">Inspect File ↗</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- ======================================================== -->
    <!-- SEAFARER DIRECTORY LISTING & ADVANCED FILTERS            -->
    <!-- ======================================================== -->
    <div class="seafarer-toolbar">
        <form method="GET">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search name or email..." class="form-control form-control-sm" style="width: 180px;">
            
            <select name="rank" class="form-control form-control-sm" style="width: auto;">
                <option value="">All Ranks</option>
                <?php foreach ($available_ranks as $r): ?>
                    <option value="<?= e($r) ?>" <?= $rank_filter === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                <?php endforeach; ?>
            </select>

            <select name="vessel" class="form-control form-control-sm" style="width: auto;">
                <option value="">All Vessel Types</option>
                <?php foreach ($available_vessels as $v): ?>
                    <option value="<?= e($v) ?>" <?= $vessel_filter === $v ? 'selected' : '' ?>><?= e($v) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <?php if ($search !== '' || $rank_filter !== '' || $vessel_filter !== ''): ?>
                <a href="/admin/seafarers.php" class="btn btn-ghost btn-sm">Reset</a>
            <?php endif; ?>
        </form>

        <div style="display: flex; gap: .5rem; align-items: center;">
            <span style="font-size: .82rem; font-weight: 600; color: var(--muted);">Sort:</span>
            <select class="form-control form-control-sm" style="width: auto;" onchange="location = this.value;">
                <?php 
                    $queryBase = "?search=".urlencode($search)."&rank=".urlencode($rank_filter)."&vessel=".urlencode($vessel_filter);
                ?>
                <option value="<?= $queryBase ?>&sort=newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest Registered</option>
                <option value="<?= $queryBase ?>&sort=oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest Registered</option>
                <option value="<?= $queryBase ?>&sort=name" <?= $sort === 'name' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
                <option value="<?= $queryBase ?>&sort=availability" <?= $sort === 'availability' ? 'selected' : '' ?>>Available First</option>
            </select>
        </div>
    </div>

    <div class="dash-panel">
        <h3 style="margin-bottom: 1.2rem; display: flex; justify-content: space-between; align-items: center;">
            <span>Registered Seafarer Roster</span>
            <span class="badge" style="background: rgba(10,37,64,0.08); color: var(--navy);"><?= count($seafarers) ?> Personnel Displayed</span>
        </h3>

        <?php if (empty($seafarers)): ?>
            <div class="dash-empty" style="padding: 3rem 1rem;">
                No seafarer records match your filter criteria.
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Seafarer Name</th>
                            <th>Rank</th>
                            <th>Vessel Experience</th>
                            <th>Availability Status</th>
                            <th>Joined</th>
                            <th style="text-align: right">Dossier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($seafarers as $s): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: .8rem;">
                                        <div class="table-avatar">
                                            <?= e(strtoupper(substr($s['full_name'], 0, 1))) ?>
                                        </div>
                                        <div>
                                            <strong><?= e($s['full_name']) ?></strong>
                                            <small class="muted" style="display: block;"><?= e($s['email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight: 600; color: var(--navy);">
                                        <?= e($s['rank'] ?: '—') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background: rgba(15,181,174,0.1); color: var(--teal-d);">
                                        <?= e($s['vessel_type'] ?: 'General') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($s['is_available']): ?>
                                        <span class="status-badge status-available" style="padding: .25rem .6rem; font-size: .78rem;">
                                            <span class="pulse-dot"></span> Available
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-unavailable" style="padding: .25rem .6rem; font-size: .78rem;">
                                            <span class="pulse-dot"></span> On Contract
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="muted"><?= time_ago($s['created_at']) ?></small></td>
                                <td style="text-align: right">
                                    <a href="/admin/seafarers.php?id=<?= (int)$s['id'] ?>" class="btn btn-teal btn-sm">View Dossier →</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

</main></div></body></html>