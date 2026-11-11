<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

// Handle Clean Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_deployment'])) {
    $dep_id = (int)($_POST['deployment_id'] ?? 0);
    $sign_on = trim($_POST['sign_on_date'] ?? '');
    $sign_off = trim($_POST['sign_off_date'] ?? '');
    $status = trim($_POST['status'] ?? 'Onboard');

    if ($dep_id > 0) {
        try {
            $upd = $pdo->prepare("
                UPDATE crew_deployments 
                SET sign_on_date = ?, sign_off_date = ?, status = ? 
                WHERE id = ?
            ");
            $upd->execute([
                $sign_on !== '' ? $sign_on : null, 
                $sign_off !== '' ? $sign_off : null, 
                $status, 
                $dep_id
            ]);

            // Redirect to prevent form resubmission issues
            header("Location: /admin/deployments.php?saved=1");
            exit;
        } catch (Exception $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    } else {
        $error = 'Invalid Deployment ID received.';
    }
}

if (isset($_GET['saved'])) {
    $success = 'Deployment details updated successfully!';
}

// Fetch all deployments joined with shipowners and seafarers safely using LEFT JOIN
$deployments = [];
try {
    $deployments = $pdo->query("
        SELECT cd.*, 
               COALESCE(s.full_name, CONCAT('Seafarer ID #', cd.seafarer_id), 'Unassigned Seafarer') AS seafarer_name, 
               COALESCE(s.email, '—') AS seafarer_email, 
               COALESCE(s.rank_title, cd.rank_title, 'Crew') AS seafarer_rank,
               so.company_name, 
               so.contact_person
        FROM crew_deployments cd
        LEFT JOIN seafarers s ON cd.seafarer_id = s.id
        LEFT JOIN shipowners so ON cd.shipowner_id = so.id
        ORDER BY cd.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Error fetching deployments: ' . $e->getMessage();
}

$admin_title = 'Crew Deployments Tracker';
require __DIR__ . '/_nav.php';
?>

<div class="admin-topbar">
    <h1 style="font-size:1.6rem; color:#0a2540; margin:0;">🚢 Fleet Crew Deployments & Assignments</h1>
</div>

<?php if ($success): ?>
    <div style="background:#d4edda; color:#155724; padding:12px 15px; border-radius:8px; margin-bottom:1.5rem; font-size:.9rem; border:1px solid #c3e6cb;">
        ✅ <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background:#f8d7da; color:#721c24; padding:12px 15px; border-radius:8px; margin-bottom:1.5rem; font-size:.9rem; border:1px solid #f5c6cb;">
        ⚠️ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="dash-panel" style="background:#fff; padding:1.5rem; border-radius:12px; border:1px solid var(--line);">
    <h3 style="margin-bottom:1.2rem;">Active & Historical Seafarer Placements</h3>
    
    <?php if (empty($deployments)): ?>
        <p style="color:var(--muted); font-size:.9rem; font-style:italic;">No crew deployments recorded yet in the database. Generate an invoice with a seafarer to create one.</p>
    <?php else: ?>
        <table class="admin-table" style="width:100%; border-collapse:collapse; font-size:.9rem;">
            <thead>
                <tr style="background:#f1f5f9; text-align:left;">
                    <th style="padding:10px;">Seafarer & Company</th>
                    <th style="padding:10px;">Vessel & Rank</th>
                    <th style="padding:10px;">Deployment Controls (Sign-On, Sign-Off, Status)</th>
                    <th style="padding:10px; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($deployments as $d): ?>
                    <tr style="border-bottom:1px solid #e2e8f0;">
                        <td style="padding:12px 10px;">
                            <strong><?= htmlspecialchars($d['seafarer_name']) ?></strong><br>
                            <small style="color:var(--muted);">Client: <?= htmlspecialchars($d['company_name'] ?? 'Unassigned Client') ?></small>
                        </td>
                        <td style="padding:12px 10px;">
                            <strong><?= htmlspecialchars($d['vessel_name'] ?: '—') ?></strong><br>
                            <small style="color:var(--muted);"><?= htmlspecialchars($d['rank_title'] ?: $d['seafarer_rank']) ?></small>
                        </td>
                        <td style="padding:12px 10px;">
                            <form method="POST" action="/admin/deployments.php" style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap;">
                                <input type="hidden" name="update_deployment" value="1">
                                <input type="hidden" name="deployment_id" value="<?= (int)$d['id'] ?>">
                                
                                <div>
                                    <label style="font-size:.7rem; color:var(--muted); display:block;">Sign-On</label>
                                    <input type="date" name="sign_on_date" value="<?= htmlspecialchars($d['sign_on_date'] ?? '') ?>" style="padding:.3rem; border:1px solid #cbd5e1; border-radius:4px; font-size:.8rem;">
                                </div>
                                <div>
                                    <label style="font-size:.7rem; color:var(--muted); display:block;">Sign-Off</label>
                                    <input type="date" name="sign_off_date" value="<?= htmlspecialchars($d['sign_off_date'] ?? '') ?>" style="padding:.3rem; border:1px solid #cbd5e1; border-radius:4px; font-size:.8rem;">
                                </div>
                                <div>
                                    <label style="font-size:.7rem; color:var(--muted); display:block;">Contract Status</label>
                                    <select name="status" style="padding:.3rem; border:1px solid #cbd5e1; border-radius:4px; font-size:.8rem; background:#fff;">
                                        <?php 
                                            $currStatus = strtolower(trim($d['status'] ?? 'onboard'));
                                            if ($currStatus === 'active') $currStatus = 'onboard';
                                        ?>
                                        <option value="Onboard" <?= $currStatus === 'onboard' ? 'selected' : '' ?>>Onboard</option>
                                        <option value="Completed" <?= $currStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="Terminated" <?= $currStatus === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                                    </select>
                                </div>
                                <div style="align-self:flex-end;">
                                    <button type="submit" class="btn btn-sm btn-teal" style="padding:.35rem .9rem; font-size:.8rem; font-weight:600;">Save</button>
                                </div>
                            </form>
                        </td>
                        <td style="padding:12px 10px; text-align:right;">
                            <?php if (!empty($d['seafarer_id']) && (int)$d['seafarer_id'] > 0): ?>
                                <a href="/admin/seafarers.php?id=<?= (int)$d['seafarer_id'] ?>" class="btn btn-sm btn-ghost" style="text-decoration:none;">Profile</a>
                            <?php else: ?>
                                <span style="color:var(--muted); font-size:.8rem;">No Link</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</main></div></body></html>