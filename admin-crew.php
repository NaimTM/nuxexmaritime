<?php
/**
 * Nuzex Maritime — Admin Crew Management Dashboard
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Simple Admin check (You can add stricter role checks if required)
$rankFilter = $_GET['rank'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT * FROM seafarers WHERE 1=1";
$params = [];

if ($rankFilter) {
    $sql .= " AND rank_title = ?";
    $params[] = $rankFilter;
}
if ($statusFilter !== '') {
    $sql .= " AND is_available = ?";
    $params[] = (int)$statusFilter;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$seafarers = $stmt->fetchAll();

$page_title = "Crew Pool Dashboard | Admin";
require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top: 40px;">
  <div class="container" style="max-width: 1200px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
      <h1>Admin Crew Management</h1>
      <span class="badge" style="background:#0A2540; color:#fff; padding:6px 14px; border-radius:4px;">Total Seafarers: <?= count($seafarers) ?></span>
    </div>

    <!-- Filters -->
    <form method="get" style="background:#fff; padding:15px 20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:20px; display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
      <div>
        <label style="font-weight:600; font-size:0.9rem;">Filter by Rank:</label>
        <select name="rank" onchange="this.form.submit()" class="form-control" style="margin-top:4px;">
          <option value="">All Ranks</option>
          <?php foreach (['Master','Chief Officer','2nd Officer','3rd Officer','Chief Engineer','2nd Engineer','3rd Engineer','4th Engineer','ETO','Bosun','AB','Oiler','Fitter','Cook','Steward','Messman'] as $r): ?>
            <option value="<?= $r ?>" <?= $rankFilter === $r ? 'selected' : '' ?>><?= $r ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="font-weight:600; font-size:0.9rem;">Availability:</label>
        <select name="status" onchange="this.form.submit()" class="form-control" style="margin-top:4px;">
          <option value="">All Status</option>
          <option value="1" <?= $statusFilter === '1' ? 'selected' : '' ?>>Available (Green)</option>
          <option value="0" <?= $statusFilter === '0' ? 'selected' : '' ?>>Unavailable (Red)</option>
        </select>
      </div>

      <?php if ($rankFilter || $statusFilter !== ''): ?>
        <a href="/admin-crew.php" class="btn btn-ghost btn-sm" style="margin-top:15px;">Reset Filters</a>
      <?php endif; ?>
    </form>

    <!-- Seafarers Table -->
    <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.05); overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse; text-align:left;">
        <thead>
          <tr style="border-bottom:2px solid #eee;">
            <th style="padding:12px;">Status</th>
            <th style="padding:12px;">Name & Rank</th>
            <th style="padding:12px;">Contact</th>
            <th style="padding:12px;">Certificates</th>
            <th style="padding:12px;">Experience</th>
            <th style="padding:12px; text-align:right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($seafarers)): ?>
            <tr><td colspan="6" style="padding:20px; text-align:center; color:#666;">No seafarers found matching criteria.</td></tr>
          <?php else: ?>
            <?php foreach ($seafarers as $s): 
              // Fetch docs for this seafarer
              $docStmt = $pdo->prepare("SELECT * FROM seafarer_documents WHERE seafarer_id = ?");
              $docStmt->execute([$s['id']]);
              $sDocs = $docStmt->fetchAll();
            ?>
              <tr style="border-bottom:1px solid #f2f2f2;">
                <td style="padding:12px;">
                  <span title="<?= $s['is_available'] ? 'Available' : 'Unavailable' ?>" style="width:14px; height:14px; border-radius:50%; background:<?= $s['is_available'] ? '#28a745' : '#dc3545' ?>; display:inline-block;"></span>
                </td>
                <td style="padding:12px;">
                  <strong><?= e($s['full_name']) ?></strong><br>
                  <small style="color:#666;"><?= e($s['rank_title']) ?> &bull; <?= e($s['nationality']) ?></small>
                </td>
                <td style="padding:12px;">
                  <?= e($s['email']) ?><br>
                  <small style="color:#666;"><?= e($s['phone']) ?></small>
                </td>
                <td style="padding:12px;">
                  <?php if (!empty($s['cv_path'])): ?>
                    <a href="/<?= e($s['cv_path']) ?>" target="_blank" style="color:#007bff; text-decoration:underline; font-size:0.85rem;">CV</a> &bull; 
                  <?php endif; ?>
                  <span style="font-size:0.85rem; color:#555;"><?= count($sDocs) ?> doc(s)</span>
                </td>
                <td style="padding:12px; font-size:0.9rem;"><?= e($s['vessel_experience'] ?: 'N/A') ?></td>
                <td style="padding:12px; text-align:right;">
                  <button onclick="toggleDetails(<?= $s['id'] ?>)" class="btn btn-ghost btn-sm">View Files</button>
                </td>
              </tr>
              <!-- Expandable Document Row -->
              <tr id="details-<?= $s['id'] ?>" style="display:none; background:#f9f9f9;">
                <td colspan="6" style="padding:15px 20px;">
                  <strong>Uploaded Documents for <?= e($s['full_name']) ?>:</strong>
                  <ul style="margin:8px 0 0 20px; padding:0;">
                    <?php if (empty($sDocs) && empty($s['cv_path'])): ?>
                      <li>No documents or CV uploaded.</li>
                    <?php else: ?>
                      <?php if (!empty($s['cv_path'])): ?>
                        <li>Primary CV: <a href="/<?= e($s['cv_path']) ?>" target="_blank">Download CV</a></li>
                      <?php endif; ?>
                      <?php foreach ($sDocs as $d): ?>
                        <li><?= e($d['doc_type']) ?> (Exp: <?= e($d['expiry_date'] ?: 'N/A') ?>): <a href="/<?= e($d['file_path']) ?>" target="_blank">View File</a></li>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </ul>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</section>

<script>
function toggleDetails(id) {
    var row = document.getElementById('details-' + id);
    if (row.style.display === 'none') {
        row.style.display = 'table-row';
    } else {
        row.style.display = 'none';
    }
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>