<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

// Handle Invoice Generation, PDF Attachment & Automatic Crew Deployment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $shipowner_id = (int)$_POST['shipowner_id'];
    $billing_month = trim($_POST['billing_month']);
    $descriptions = $_POST['item_desc'] ?? [];
    $amounts = $_POST['item_amount'] ?? [];
    $seafarer_ids = $_POST['seafarer_id'] ?? [];
    $seafarer_texts = $_POST['seafarer_search_text'] ?? [];
    $vessel_names = $_POST['vessel_name'] ?? [];
    $ranks = $_POST['rank_title'] ?? [];

    if ($shipowner_id <= 0 || empty($billing_month)) {
        $error = 'Please select a shipowner and billing month.';
    } else {
        try {
            $pdo->beginTransaction();

            $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
            $total_amount = 0.00;

            foreach ($amounts as $amt) {
                $total_amount += (float)$amt;
            }

            // Handle PDF File Upload
            $pdf_filename = null;
            if (isset($_FILES['invoice_pdf']) && $_FILES['invoice_pdf']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['invoice_pdf']['tmp_name'];
                $fileName = $_FILES['invoice_pdf']['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if ($fileExtension === 'pdf') {
                    $uploadDir = __DIR__ . '/../storage/invoices/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $pdf_filename = $invoice_number . '_' . time() . '.pdf';
                    $dest_path = $uploadDir . $pdf_filename;

                    if(!move_uploaded_file($fileTmpPath, $dest_path)) {
                        $pdf_filename = null;
                    }
                } else {
                    throw new Exception('Only PDF files are allowed for invoice attachments.');
                }
            }

            // Check if pdf_file column exists defensively to prevent system crashes
            $hasPdfColumn = false;
            try {
                $colCheck = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'pdf_file'");
                $hasPdfColumn = $colCheck->fetch() ? true : false;
            } catch (Exception $e) {
                $hasPdfColumn = false;
            }

            // Insert Invoice Header dynamically based on column availability
            if ($hasPdfColumn) {
                $invStmt = $pdo->prepare("
                    INSERT INTO invoices (invoice_number, shipowner_id, billing_month, total_amount, pdf_file, status, issued_date, created_at)
                    VALUES (?, ?, ?, ?, ?, 'unpaid', CURDATE(), NOW())
                ");
                $invStmt->execute([$invoice_number, $shipowner_id, $billing_month, $total_amount, $pdf_filename]);
            } else {
                $invStmt = $pdo->prepare("
                    INSERT INTO invoices (invoice_number, shipowner_id, billing_month, total_amount, status, issued_date, created_at)
                    VALUES (?, ?, ?, ?, 'unpaid', CURDATE(), NOW())
                ");
                $invStmt->execute([$invoice_number, $shipowner_id, $billing_month, $total_amount]);
            }
            $invoice_id = $pdo->lastInsertId();

            // Insert Line Items & Link/Create Unique Crew Deployments Safely
            for ($i = 0; $i < count($descriptions); $i++) {
                if (trim($descriptions[$i]) !== '') {
                    $desc = trim($descriptions[$i]);
                    $amt = (float)($amounts[$i] ?? 0);
                    $sf_id = (int)($seafarer_ids[$i] ?? 0);
                    $sf_text = trim($seafarer_texts[$i] ?? '');
                    $vessel = trim($vessel_names[$i] ?? '');
                    $rank = trim($ranks[$i] ?? '');

                    // Fallback: search database by name if hidden seafarer_id was not populated by autocomplete
                    if ($sf_id === 0 && $sf_text !== '') {
                        $sfLookup = $pdo->prepare("SELECT id FROM seafarers WHERE full_name = ? LIMIT 1");
                        $sfLookup->execute([$sf_text]);
                        $foundSf = $sfLookup->fetch();
                        if ($foundSf) {
                            $sf_id = (int)$foundSf['id'];
                        }
                    }

                    $deployment_id = null;
                    
                    if ($sf_id > 0) {
                        if ($vessel === '') {
                            $vessel = 'Unassigned Vessel';
                        }

                        // Check if an active deployment already exists for this seafarer and vessel
                        $depCheck = $pdo->prepare("
                            SELECT id FROM crew_deployments 
                            WHERE shipowner_id = ? AND seafarer_id = ? AND vessel_name = ? 
                            LIMIT 1
                        ");
                        $depCheck->execute([$shipowner_id, $sf_id, $vessel]);
                        $existingDep = $depCheck->fetch();

                        if ($existingDep) {
                            $deployment_id = $existingDep['id'];
                        } else {
                            $depIns = $pdo->prepare("
                                INSERT INTO crew_deployments (shipowner_id, seafarer_id, vessel_name, rank_title, sign_on_date, status, created_at)
                                VALUES (?, ?, ?, ?, CURDATE(), 'Onboard', NOW())
                            ");
                            $depIns->execute([$shipowner_id, $sf_id, $vessel, $rank]);
                            $deployment_id = $pdo->lastInsertId();
                        }
                    }

                    $itemStmt = $pdo->prepare("
                        INSERT INTO invoice_items (invoice_id, description, amount, crew_deployment_id, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $itemStmt->execute([$invoice_id, $desc, $amt, $deployment_id]);
                }
            }

            $pdo->commit();
            $success = 'Invoice ' . $invoice_number . ' generated successfully with automatic crew deployment linkage!';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error generating invoice: ' . $e->getMessage();
        }
    }
}

// Handle Payment Status Toggle
if (isset($_GET['toggle_status'])) {
    $inv_id = (int)$_GET['toggle_status'];
    $statusStmt = $pdo->prepare("SELECT status FROM invoices WHERE id = ?");
    $statusStmt->execute([$inv_id]);
    $current = $statusStmt->fetch();

    if ($current) {
        $newStatus = ($current['status'] === 'paid') ? 'unpaid' : 'paid';
        $paidDate = ($newStatus === 'paid') ? date('Y-m-d') : null;

        $upd = $pdo->prepare("UPDATE invoices SET status = ?, paid_date = ? WHERE id = ?");
        $upd->execute([$newStatus, $paidDate, $inv_id]);
        header('Location: /admin/invoices.php');
        exit;
    }
}

// Fetch all shipowners for dropdown
$shipowners = $pdo->query("SELECT * FROM shipowners ORDER BY company_name")->fetchAll();

// Fetch all seafarers once for instant client-side autocomplete
$allSeafarers = $pdo->query("SELECT id, full_name, rank_title FROM seafarers ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all generated invoices with shipowner details
$invoices = [];
try {
    $invoices = $pdo->query("
        SELECT i.*, s.company_name, s.contact_person 
        FROM invoices i 
        JOIN shipowners s ON i.shipowner_id = s.id 
        ORDER BY i.created_at DESC
    ")->fetchAll();
} catch (Exception $e) {
    $invoices = [];
}

$admin_title = 'Client Invoices & Billing';
require __DIR__ . '/_nav.php';
?>
<style>
  .search-results-dropdown {
      position: absolute; background: #fff; border: 1px solid #cbd5e1;
      max-height: 180px; overflow-y: auto; width: 100%; z-index: 9999;
      border-radius: 0 0 6px 6px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  }
  .search-result-item {
      padding: 8px 12px; font-size: .85rem; cursor: pointer; border-bottom: 1px solid #e2e8f0; color: #1e293b;
  }
  .search-result-item:hover { background: #f1f5f9; }
</style>

<script>
const seafarerDatabase = <?= json_encode($allSeafarers); ?>;
</script>

<div class="admin-topbar">
    <h1 style="font-size:1.6rem; color:#0a2540; margin:0;">📑 Client Invoices & Crew Billing Manager</h1>
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

<!-- GENERATE INVOICE FORM -->
<form method="POST" enctype="multipart/form-data" style="background:#f8fafc; padding:1.5rem; border-radius:10px; border:1px solid #cbd5e1; margin-bottom:2.5rem;">
    <input type="hidden" name="create_invoice" value="1">
    <h3 style="font-size:1.1rem; color:#0a2540; margin-bottom:1rem;">Generate New Itemized Invoice</h3>

    <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
        <div>
            <label style="display:block; font-weight:600; font-size:.8rem; color:#4a5568; margin-bottom:.3rem;">Select Shipowner Client</label>
            <select name="shipowner_id" required style="width:100%; padding:.6rem; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
                <option value="">Choose Client Company</option>
                <?php foreach ($shipowners as $so): ?>
                    <option value="<?= $so['id'] ?>"><?= htmlspecialchars($so['company_name']) ?> (<?= htmlspecialchars($so['contact_person']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block; font-weight:600; font-size:.8rem; color:#4a5568; margin-bottom:.3rem;">Billing Month & Year</label>
            <input type="text" name="billing_month" placeholder="e.g. November 2026" required style="width:100%; padding:.6rem; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
        </div>
        <div>
            <label style="display:block; font-weight:600; font-size:.8rem; color:#4a5568; margin-bottom:.3rem;">Attach Invoice PDF</label>
            <input type="file" name="invoice_pdf" accept=".pdf" style="width:100%; padding:.45rem; border:1px solid #cbd5e1; border-radius:6px; background:#fff; font-size:.8rem;">
        </div>
    </div>

    <label style="display:block; font-weight:600; font-size:.85rem; color:#0a2540; margin-bottom:.5rem;">Invoice Line Items & Joined Crew Association</label>
    <div id="line-items-container" style="margin-bottom:1rem;">
        <div style="display:grid; grid-template-columns: 2fr 1fr 1.5fr 1.5fr 1fr auto; gap:.5rem; margin-bottom:.5rem; align-items:start;">
            <input type="text" name="item_desc[]" placeholder="Item description (e.g. Manning Fee)" required style="padding:.5rem; border:1px solid #cbd5e1; border-radius:6px;">
            <input type="number" step="0.01" name="item_amount[]" placeholder="Amount ($)" required style="padding:.5rem; border:1px solid #cbd5e1; border-radius:6px;">
            
            <div style="position:relative;">
                <input type="text" name="seafarer_search_text[]" class="seafarer-search-input" placeholder="Type seafarer name..." autocomplete="off" style="width:100%; padding:.5rem; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
                <input type="hidden" name="seafarer_id[]" class="seafarer-id-hidden">
                <div class="search-results-dropdown" style="display:none;"></div>
            </div>

            <input type="text" name="vessel_name[]" placeholder="Vessel Name" style="padding:.5rem; border:1px solid #cbd5e1; border-radius:6px;">
            <input type="text" name="rank_title[]" placeholder="Rank Onboard" style="padding:.5rem; border:1px solid #cbd5e1; border-radius:6px;">
            <span></span>
        </div>
    </div>

    <button type="button" onclick="addItemRow()" style="background:#e2e8f0; color:#0a2540; border:none; padding:.4rem 1rem; border-radius:6px; cursor:pointer; font-weight:600; font-size:.85rem; margin-bottom:1.5rem;">+ Add Another Item</button>
    
    <div>
        <button type="submit" style="background:#0a2540; color:#fff; border:none; padding:.7rem 1.5rem; font-weight:600; border-radius:6px; cursor:pointer; font-size:.95rem;">Issue Invoice to Client</button>
    </div>
</form>

<!-- EXISTING INVOICES TABLE -->
<h3 style="font-size:1.2rem; color:#0a2540; margin-bottom:1rem;">Issued Client Invoices</h3>
<?php if (empty($invoices)): ?>
    <p style="color:#64748b; font-size:.9rem; font-style:italic;">No invoices generated yet.</p>
<?php else: ?>
    <table style="width:100%; border-collapse:collapse; font-size:.9rem; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 2px 4px rgba(0,0,0,0.02);">
        <thead>
            <tr style="background:#f1f5f9; text-align:left;">
                <th style="padding:10px; border-bottom:1px solid #cbd5e1;">Invoice #</th>
                <th style="padding:10px; border-bottom:1px solid #cbd5e1;">Client Company</th>
                <th style="padding:10px; border-bottom:1px solid #cbd5e1;">Billing Period</th>
                <th style="padding:10px; border-bottom:1px solid #cbd5e1;">Total Amount</th>
                <th style="padding:10px; border-bottom:1px solid #cbd5e1;">PDF Status</th>
                <th style="padding:10px; border-bottom:1px solid #cbd5e1;">Status</th>
                <th style="padding:10px; border-bottom:1px solid #cbd5e1; text-align:right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td style="padding:12px 10px; border-bottom:1px solid #e2e8f0;"><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                    <td style="padding:12px 10px; border-bottom:1px solid #e2e8f0;"><?= htmlspecialchars($inv['company_name']) ?></td>
                    <td style="padding:12px 10px; border-bottom:1px solid #e2e8f0;"><?= htmlspecialchars($inv['billing_month']) ?></td>
                    <td style="padding:12px 10px; border-bottom:1px solid #e2e8f0; font-weight:700; color:#0a2540;">$<?= number_format($inv['total_amount'], 2) ?></td>
                    <td style="padding:12px 10px; border-bottom:1px solid #e2e8f0;">
                        <?php if (!empty($inv['pdf_file'])): ?>
                            <a href="/storage/invoices/<?= htmlspecialchars($inv['pdf_file']) ?>" target="_blank" style="color: #0fb5ae; font-weight: 600; text-decoration: none;">📄 View PDF</a>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: .8rem;">None Attached</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px 10px; border-bottom:1px solid #e2e8f0;">
                        <?php if ($inv['status'] === 'paid'): ?>
                            <span style="background:#d4edda; color:#155724; padding:.2rem .6rem; border-radius:50px; font-size:.75rem; font-weight:700;">PAID</span>
                        <?php else: ?>
                            <span style="background:#f8d7da; color:#721c24; padding:.2rem .6rem; border-radius:50px; font-size:.75rem; font-weight:700;">UNPAID</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px 10px; border-bottom:1px solid #e2e8f0; text-align:right;">
                        <a href="/admin/invoices.php?toggle_status=<?= (int)$inv['id'] ?>" style="background:<?= $inv['status'] === 'paid' ? '#64748b' : '#0fb5ae' ?>; color:#fff; padding:.4rem .8rem; text-decoration:none; border-radius:4px; font-weight:600; font-size:.8rem;">
                            <?= $inv['status'] === 'paid' ? 'Mark Unpaid' : 'Mark as Paid ✓' ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<script>
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('seafarer-search-input')) {
        const input = e.target;
        const container = input.closest('div');
        const dropdown = container.querySelector('.search-results-dropdown');
        const hiddenId = container.querySelector('.seafarer-id-hidden');
        const query = input.value.trim().toLowerCase();

        hiddenId.value = '';

        if (query.length < 1) {
            dropdown.style.display = 'none';
            return;
        }

        const matches = seafarerDatabase.filter(sf => sf.full_name.toLowerCase().includes(query)).slice(0, 10);

        dropdown.innerHTML = '';
        if (matches.length === 0) {
            dropdown.style.display = 'none';
            return;
        }

        matches.forEach(sf => {
            const div = document.createElement('div');
            div.className = 'search-result-item';
            div.innerHTML = `<strong>${sf.full_name}</strong> <small style="color:#64748b">(${sf.rank_title || 'Crew'})</small>`;
            div.onclick = function() {
                input.value = sf.full_name;
                hiddenId.value = sf.id;
                dropdown.style.display = 'none';
            };
            dropdown.appendChild(div);
        });
        dropdown.style.display = 'block';
    }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('div[style*="position:relative"]')) {
        document.querySelectorAll('.search-results-dropdown').forEach(d => d.style.display = 'none');
    }
});

function addItemRow() {
    const container = document.getElementById('line-items-container');
    const row = document.createElement('div');
    row.style.cssText = "display:grid; grid-template-columns: 2fr 1fr 1.5fr 1.5fr 1fr auto; gap:.5rem; margin-bottom:.5rem; align-items:start;";
    row.innerHTML = `
        <input type="text" name="item_desc[]" placeholder="Item description (e.g. Manning Fee)" required style="padding:.5rem; border:1px solid #cbd5e1; border-radius:6px;">
        <input type="number" step="0.01" name="item_amount[]" placeholder="Amount ($)" required style="padding:.5rem; border:1px solid #cbd5e1; border-radius:6px;">
        
        <div style="position:relative;">
            <input type="text" name="seafarer_search_text[]" class="seafarer-search-input" placeholder="Type seafarer name..." autocomplete="off" style="width:100%; padding:.5rem; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">
            <input type="hidden" name="seafarer_id[]" class="seafarer-id-hidden">
            <div class="search-results-dropdown" style="display:none;"></div>
        </div>

        <input type="text" name="vessel_name[]" placeholder="Vessel Name" style="padding:.5rem; border:1px solid #cbd5e1; border-radius:6px;">
        <input type="text" name="rank_title[]" placeholder="Rank Onboard" style="padding:.5rem; border:1px solid #cbd5e1; border-radius:6px;">
        <button type="button" onclick="this.parentElement.parentElement.remove()" style="background:#ef4444; color:#fff; border:none; padding:.5rem .8rem; border-radius:6px; cursor:pointer;">X</button>
    `;
    container.appendChild(row);
}
</script>

</main></div></body></html>