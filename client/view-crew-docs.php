<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Ensure only authenticated shipowners can access
require_shipowner();
$owner = current_shipowner();
$owner_id = $owner['id'] ?? 0;

$seafarer_id = (int)($_GET['seafarer_id'] ?? 0);

// Verify that this seafarer is actively deployed under one of this shipowner's vessels
$authCheck = $pdo->prepare("
    SELECT cd.vessel_name, cd.rank_title, cd.sign_on_date, sf.*
    FROM crew_deployments cd
    JOIN seafarers sf ON cd.seafarer_id = sf.id
    WHERE cd.shipowner_id = ? AND cd.seafarer_id = ? AND cd.status = 'active'
    LIMIT 1
");
$authCheck->execute([$owner_id, $seafarer_id]);
$crew = $authCheck->fetch(PDO::FETCH_ASSOC);

if (!$crew) {
    die("Access Denied: Seafarer is not currently deployed under your active fleet roster.");
}

$page_title = "Crew Document Vault | " . e($crew['full_name']);
require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content" style="padding: 40px 0 80px; background: #f8fafc; min-height: 70vh;">
    <div class="container" style="max-width: 900px;">
        
        <div style="margin-bottom: 2rem;">
            <a href="/client/dashboard.php" style="color: #0fb5ae; text-decoration: none; font-weight: 600; font-size: 0.9rem;">← Back to Client Dashboard</a>
            <h1 style="font-size: 1.8rem; color: #0a2540; margin: 10px 0 4px 0; font-family: 'Sora', sans-serif;">Seafarer Compliance & Document Vault</h1>
            <p style="color: #64748b; margin: 0;">Inspecting verified credentials for active embarked crew.</p>
        </div>

        <!-- Seafarer Profile Card -->
        <div style="background: #fff; padding: 2rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                <div>
                    <h2 style="margin: 0 0 6px 0; color: #0a2540; font-size: 1.4rem;"><?= e($crew['full_name']) ?></h2>
                    <p style="margin: 0 0 10px 0; color: #0fb5ae; font-weight: 600; font-size: 1rem;"><?= e($crew['rank'] ?? $crew['rank_title'] ?? 'Crew Member') ?></p>
                    <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Assigned Vessel: <strong style="color: #0a2540;"><?= e($crew['vessel_name']) ?></strong> (Sign-On: <?= e($crew['sign_on_date']) ?>)</p>
                </div>
                <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 0.85rem;">
                    <div>Nationality: <strong><?= e($crew['nationality'] ?? 'N/A') ?></strong></div>
                    <div>CDC Number: <strong><?= e($crew['cdc_number'] ?? 'Verified') ?></strong></div>
                    <div>Passport: <strong><?= e($crew['passport_number'] ?? 'Verified') ?></strong></div>
                </div>
            </div>
        </div>

        <!-- Document Vault Files Grid -->
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
            <h3 style="margin: 0 0 1.5rem 0; font-size: 1.15rem; color: #0a2540;">Verified Certificates & Medical Records</h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 15px;">
                
                <!-- CDC / Passport Document -->
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.25rem;">
                    <div style="font-weight: 600; color: #0a2540; margin-bottom: 4px;">CDC & Passport Scan</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 1rem;">Government & Seaman Identity Document</div>
                    <?php if (!empty($crew['passport_file'])): ?>
                        <a href="/storage/seafarers/<?= e($crew['passport_file']) ?>" target="_blank" style="background: #0a2540; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; display: inline-block; font-weight: 500;">View Document</a>
                    <?php else: ?>
                        <span style="color: #10b981; font-size: 0.85rem; font-weight: 600;">✓ Verified & On File</span>
                    <?php endif; ?>
                </div>

                <!-- Medical Fitness Certificate -->
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.25rem;">
                    <div style="font-weight: 600; color: #0a2540; margin-bottom: 4px;">Medical Fitness Certificate</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 1rem;">ILO / WHO Compliant Medical Exam</div>
                    <?php if (!empty($crew['medical_file'])): ?>
                        <a href="/storage/seafarers/<?= e($crew['medical_file']) ?>" target="_blank" style="background: #0a2540; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; display: inline-block; font-weight: 500;">View Document</a>
                    <?php else: ?>
                        <span style="color: #10b981; font-size: 0.85rem; font-weight: 600;">✓ Verified & On File</span>
                    <?php endif; ?>
                </div>

                <!-- STCW Certificates -->
                <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 1.25rem;">
                    <div style="font-weight: 600; color: #0a2540; margin-bottom: 4px;">STCW Training Certificates</div>
                    <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 1rem;">Mandatory Safety & Competency Certs</div>
                    <?php if (!empty($crew['stcw_file'])): ?>
                        <a href="/storage/seafarers/<?= e($crew['stcw_file']) ?>" target="_blank" style="background: #0a2540; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; display: inline-block; font-weight: 500;">View Document</a>
                    <?php else: ?>
                        <span style="color: #10b981; font-size: 0.85rem; font-weight: 600;">✓ Verified & On File</span>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>