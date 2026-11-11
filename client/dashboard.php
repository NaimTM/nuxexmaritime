<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

require_shipowner();

$owner = current_shipowner();
$owner_id = $owner['id'] ?? $owner['shipowner_id'] ?? 1;
$owner_email = $owner['email'] ?? '';

// Fetch company invoices
$invoices = [];
try {
    $stmtInv = $pdo->prepare("SELECT * FROM invoices WHERE shipowner_id = ? ORDER BY created_at DESC");
    $stmtInv->execute([$owner_id]);
    $invoices = $stmtInv->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $invoices = [];
}

// Fetch deployments safely using LEFT JOIN so missing seafarer profiles never drop rows
$onboard_crew = [];
$past_crew = [];

try {
    $stmt = $pdo->prepare("
        SELECT cd.*, 
               COALESCE(
                   sf.full_name, 
                   IF(cd.seafarer_id IS NOT NULL AND cd.seafarer_id > 0, CONCAT('Seafarer ID #', cd.seafarer_id), 'Unassigned Seafarer')
               ) AS full_name, 
               COALESCE(sf.email, 'No email provided') AS seafarer_email, 
               COALESCE(sf.phone, '—') AS phone, 
               COALESCE(sf.rank_title, cd.rank_title, 'Crew') AS seafarer_rank, 
               COALESCE(sf.passport_no, 'Pending') AS passport_no, 
               COALESCE(sf.cdc_no, 'Pending') AS cdc_no, 
               COALESCE(sf.nationality, '—') AS nationality, 
               cd.seafarer_id
        FROM crew_deployments cd
        LEFT JOIN seafarers sf ON cd.seafarer_id = sf.id
        WHERE cd.shipowner_id = ?
        ORDER BY cd.id DESC
    ");
    $stmt->execute([$owner_id]);
    $all_deployments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $seen_onboard = [];
    $seen_past = [];

    foreach ($all_deployments as $d) {
        $status = strtolower(trim($d['status'] ?? 'onboard'));
        $sf_id = (!empty($d['seafarer_id']) && $d['seafarer_id'] > 0) ? $d['seafarer_id'] : 'dep_' . $d['id'];

        if ($status === 'completed') {
            if (!isset($seen_past[$sf_id])) {
                $seen_past[$sf_id] = true;
                $past_crew[] = $d;
            }
        } else {
            // Group active / onboard / any status into active roster
            if (!isset($seen_onboard[$sf_id])) {
                $seen_onboard[$sf_id] = true;
                $onboard_crew[] = $d;
            }
        }
    }
} catch (Exception $e) {
    $onboard_crew = [];
    $past_crew = [];
}

$page_title = "Client Dashboard | Nuzex Maritime";
require_once __DIR__ . '/../includes/header.php';
?>

<main class="main-content" style="padding: 40px 0 80px; background: #f8fafc; min-height: 70vh;">
    <div class="container" style="max-width: 1100px;">
        
        <!-- Welcome Banner -->
        <div style="background: #fff; padding: 2.5rem; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <h1 style="font-size: 2rem; color: #0a2540; margin: 0 0 8px 0; font-family: 'Sora', sans-serif;">Welcome back, <?= e($owner['contact_person'] ?? 'Captain') ?>!</h1>
                <p style="color: #64748b; margin: 0; font-size: 1rem;">Managing operations and billing for <strong style="color: #0a2540;"><?= e($owner['company_name'] ?? 'Your Company') ?></strong></p>
            </div>
            <div>
                <span style="background: #e6fffa; color: #047857; padding: 6px 14px; border-radius: 50px; font-size: 0.85rem; font-weight: 600; border: 1px solid #a7f3d0;">
                    ✓ Verified Shipowner
                </span>
            </div>
        </div>

        <!-- Stats Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 2.5rem;">
            <div style="background: #fff; padding: 1.5rem; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.01);">
                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700; margin-bottom: 6px;">Company Email</div>
                <div style="font-size: 1.05rem; color: #0a2540; font-weight: 600; word-break: break-all;"><?= e($owner['email'] ?? '') ?></div>
            </div>
            <div style="background: #fff; padding: 1.5rem; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.01);">
                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700; margin-bottom: 6px;">Active Onboard Crew</div>
                <div style="font-size: 1.5rem; color: #0fb5ae; font-weight: 700;"><?= count($onboard_crew) ?></div>
            </div>
            <div style="background: #fff; padding: 1.5rem; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.01);">
                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700; margin-bottom: 6px;">Total Invoices</div>
                <div style="font-size: 1.5rem; color: #3b82f6; font-weight: 700;"><?= count($invoices) ?></div>
            </div>
        </div>

        <!-- ACTIVE ONBOARD CREW SECTION -->
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 2.5rem;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; background: #fff; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.15rem; color: #0a2540; font-family: 'Sora', sans-serif;">Active Onboard Crew & Document Vault</h3>
                <span style="font-size: 0.8rem; color: #047857; background: #d1fae5; padding: 4px 10px; border-radius: 4px; font-weight: 600;">Live Fleet Roster</span>
            </div>
            
            <div style="padding: 0;">
                <?php if (empty($onboard_crew)): ?>
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <p style="margin-bottom: 4px; font-weight: 500;">No crew members currently signed on to your vessels.</p>
                        <small style="color: #94a3b8;">Active crew deployments will appear here automatically.</small>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                    <th style="padding: 1rem 1.5rem;">Seafarer Name</th>
                                    <th style="padding: 1rem 1.5rem;">Vessel & Rank</th>
                                    <th style="padding: 1rem 1.5rem;">Compliance & Docs</th>
                                    <th style="padding: 1rem 1.5rem;">Sign-On Date</th>
                                    <th style="padding: 1rem 1.5rem; text-align: right;">Profile & Documents</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($onboard_crew as $crew): ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9;">
                                        <td style="padding: 1rem 1.5rem;">
                                            <strong style="color: #0a2540; font-size: 0.95rem;"><?= e($crew['full_name']) ?></strong><br>
                                            <small style="color: #64748b;"><?= e($crew['seafarer_email']) ?></small>
                                        </td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <strong style="color: #334155;"><?= e($crew['vessel_name'] ?: 'Assigned Vessel') ?></strong><br>
                                            <span style="color: #0fb5ae; font-weight: 600; font-size: 0.85rem;"><?= e($crew['rank_title'] ?: $crew['seafarer_rank']) ?></span>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; font-size: 0.85rem; color: #475569;">
                                            CDC: <strong><?= e($crew['cdc_no']) ?></strong><br>
                                            Passport: <strong><?= e($crew['passport_no']) ?></strong>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; color: #334155; font-size: 0.9rem;">
                                            <?= e($crew['sign_on_date'] ?? 'Recent') ?>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; text-align: right;">
                                            <?php if (!empty($crew['seafarer_id']) && (int)$crew['seafarer_id'] > 0): ?>
                                                <a href="/admin/seafarers.php?id=<?= (int)$crew['seafarer_id'] ?>" target="_blank" style="background: #0a2540; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; font-weight: 500; display: inline-block;">
                                                    👤 View Profile
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- PAST SEAFARERS SECTION -->
        <?php if (!empty($past_crew)): ?>
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 2.5rem;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; background: #fff; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.15rem; color: #64748b; font-family: 'Sora', sans-serif;">Past Seafarers & Completed Contracts</h3>
                <span style="font-size: 0.8rem; color: #475569; background: #f1f5f9; padding: 4px 10px; border-radius: 4px; font-weight: 600;">Archive Roster</span>
            </div>
            
            <div style="padding: 0;">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                <th style="padding: 1rem 1.5rem;">Seafarer Name</th>
                                <th style="padding: 1rem 1.5rem;">Vessel & Rank</th>
                                <th style="padding: 1rem 1.5rem;">Service Period</th>
                                <th style="padding: 1rem 1.5rem; text-align: right;">Profile</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($past_crew as $pcrew): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; opacity: 0.85;">
                                    <td style="padding: 1rem 1.5rem;">
                                        <strong style="color: #0a2540; font-size: 0.95rem;"><?= e($pcrew['full_name']) ?></strong><br>
                                        <small style="color: #64748b;"><?= e($pcrew['seafarer_email']) ?></small>
                                    </td>
                                    <td style="padding: 1rem 1.5rem;">
                                        <strong style="color: #334155;"><?= e($pcrew['vessel_name'] ?: 'Vessel') ?></strong><br>
                                        <span style="color: #64748b; font-weight: 600; font-size: 0.85rem;"><?= e($pcrew['rank_title'] ?: $pcrew['seafarer_rank']) ?></span>
                                    </td>
                                    <td style="padding: 1rem 1.5rem; color: #334155; font-size: 0.9rem;">
                                        <?= e($pcrew['sign_on_date'] ?? 'N/A') ?> → <?= e($pcrew['sign_off_date'] ?? 'Completed') ?>
                                    </td>
                                    <td style="padding: 1rem 1.5rem; text-align: right;">
                                        <?php if (!empty($pcrew['seafarer_id']) && (int)$pcrew['seafarer_id'] > 0): ?>
                                            <a href="/admin/seafarers.php?id=<?= (int)$pcrew['seafarer_id'] ?>" target="_blank" style="background: #64748b; color: #fff; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; font-weight: 500;">
                                                👤 View Profile
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Invoices Section -->
        <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); overflow: hidden;">
            <div style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; background: #fff;">
                <h3 style="margin: 0; font-size: 1.15rem; color: #0a2540; font-family: 'Sora', sans-serif;">Your Company Invoices & Statements</h3>
            </div>
            
            <div style="padding: 0;">
                <?php if (empty($invoices)): ?>
                    <div style="text-align: center; padding: 3rem; color: #64748b;">
                        <p style="margin-bottom: 4px; font-weight: 500;">No invoices found for your account yet.</p>
                        <small style="color: #94a3b8;">Invoices issued by Nuzex Maritime administration will appear here.</small>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                    <th style="padding: 1rem 1.5rem;">Invoice #</th>
                                    <th style="padding: 1rem 1.5rem;">Billing Month</th>
                                    <th style="padding: 1rem 1.5rem;">Amount</th>
                                    <th style="padding: 1rem 1.5rem;">Status</th>
                                    <th style="padding: 1rem 1.5rem; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($invoices as $inv): ?>
                                    <?php 
                                        $invAmount = floatval($inv['amount'] ?? $inv['total'] ?? $inv['total_amount'] ?? $inv['grand_total'] ?? 0);
                                    ?>
                                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                                        <td style="padding: 1rem 1.5rem; font-weight: 600; color: #0a2540;"><?= e($inv['invoice_number'] ?? '#' . $inv['id']) ?></td>
                                        <td style="padding: 1rem 1.5rem; color: #334155;"><?= e($inv['billing_month'] ?? 'N/A') ?></td>
                                        <td style="padding: 1rem 1.5rem; font-weight: 600; color: #0a2540;"><?= money($invAmount) ?></td>
                                        <td style="padding: 1rem 1.5rem;">
                                            <?php 
                                                $status = strtolower($inv['status'] ?? 'pending');
                                                $badgeBg = ($status === 'paid') ? '#d1fae5' : '#fef3c7';
                                                $badgeColor = ($status === 'paid') ? '#065f46' : '#92400e';
                                            ?>
                                            <span style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                                                <?= e($status) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 1rem 1.5rem; text-align: right;">
                                            <?php if (!empty($inv['pdf_file'])): ?>
                                                <a href="/storage/invoices/<?= e($inv['pdf_file']) ?>" target="_blank" style="background: #0a2540; color: #fff; padding: 6px 14px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; font-weight: 500; display: inline-block;">View PDF</a>
                                            <?php else: ?>
                                                <span style="color: #94a3b8; font-size: 0.8rem; font-style: italic;">Pending PDF</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>