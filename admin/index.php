<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$tab = $_GET['tab'] ?? 'dashboard';

/* ============ HANDLE INVOICE GENERATION & PDF UPLOAD (IF POSTED) ============ */
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $shipowner_id = (int)$_POST['shipowner_id'];
    $billing_month = trim($_POST['billing_month']);
    $descriptions = $_POST['item_desc'] ?? [];
    $amounts = $_POST['item_amount'] ?? [];
    $seafarer_ids = $_POST['seafarer_id'] ?? [];
    $vessel_names = $_POST['vessel_name'] ?? [];
    $ranks = $_POST['rank_title'] ?? [];

    if ($shipowner_id <= 0 || empty($billing_month)) {
        $error = 'Please select a shipowner client and billing month.';
        $tab = 'invoices';
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

                    if (!move_uploaded_file($fileTmpPath, $dest_path)) {
                        $pdf_filename = null;
                    }
                } else {
                    throw new Exception('Only PDF files are allowed for invoice attachments.');
                }
            }

            // Check if pdf_file column exists defensively
            $hasPdfColumn = false;
            try {
                $colCheck = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'pdf_file'");
                $hasPdfColumn = $colCheck->fetch() ? true : false;
            } catch (Exception $e) {
                $hasPdfColumn = false;
            }

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

            // Insert Line Items & Automatically Create Active Crew Deployments
            for ($i = 0; $i < count($descriptions); $i++) {
                if (trim($descriptions[$i]) !== '') {
                    $desc = trim($descriptions[$i]);
                    $amt = (float)($amounts[$i] ?? 0);
                    $sf_id = (int)($seafarer_ids[$i] ?? 0);
                    $vessel = trim($vessel_names[$i] ?? '');
                    $rank = trim($ranks[$i] ?? '');

                    $deployment_id = null;
                    if ($sf_id > 0 && $vessel !== '') {
                        $depIns = $pdo->prepare("
                            INSERT INTO crew_deployments (shipowner_id, seafarer_id, vessel_name, rank_title, sign_on_date, status, stage, created_at)
                            VALUES (?, ?, ?, ?, CURDATE(), 'Onboard', 1, NOW())
                        ");
                        $depIns->execute([$shipowner_id, $sf_id, $vessel, $rank]);
                        $deployment_id = $pdo->lastInsertId();
                    }

                    $itemStmt = $pdo->prepare("
                        INSERT INTO invoice_items (invoice_id, description, amount, crew_deployment_id, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $itemStmt->execute([$invoice_id, $desc, $amt, $deployment_id]);
                }
            }

            $pdo->commit();
            $success = 'Invoice ' . $invoice_number . ' generated successfully with attached PDF & automatic crew deployment!';
            $tab = 'invoices';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error generating invoice: ' . $e->getMessage();
            $tab = 'invoices';
        }
    }
}

/* ============ HANDLE PAYMENT STATUS TOGGLE ============ */
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
        header('Location: /admin/index.php?tab=invoices');
        exit;
    }
}

/* ============ STATS ============ */
$stats = [
  'jobs_total'     => (int)$pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn(),
  'jobs_live'      => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='published'")->fetchColumn(),
  'jobs_closed'    => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status='closed'")->fetchColumn(),
  'jobs_hidden'    => (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status IN ('hidden','draft')")->fetchColumn(),
  'seafarers'      => (int)$pdo->query("SELECT COUNT(*) FROM seafarers")->fetchColumn(),
  'applications'   => (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
  'apps_new'       => (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status='applied'")->fetchColumn(),
  'crew_requests'  => (int)$pdo->query("SELECT COUNT(*) FROM crew_requests")->fetchColumn(),
  'requests_new'   => (int)$pdo->query("SELECT COUNT(*) FROM crew_requests WHERE status='new'")->fetchColumn(),
  'comments'       => (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status='visible'")->fetchColumn(),
];

/* ============ UPCOMING BIRTHDAYS (NEXT 7 DAYS) ============ */
$upcoming_birthdays = [];
try {
    $bdayStmt = $pdo->query("
        SELECT id, full_name, phone, email, date_of_birth,
               DATE_FORMAT(date_of_birth, '%m-%d') as bday_md
        FROM `seafarers`
        WHERE date_of_birth IS NOT NULL 
        AND (
            STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-', DATE_FORMAT(date_of_birth, '%m-%d')), '%Y-%m-%d') 
            BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        )
        ORDER BY bday_md ASC
    ");
    $upcoming_birthdays = $bdayStmt->fetchAll();
} catch (Exception $e) {}

/* ============ RECENT APPLICATIONS ============ */
$recent_apps = $pdo->query("
  SELECT a.id, a.status, a.created_at, a.rank_title,
         s.full_name, s.nationality,
         j.title AS job_title, j.slug AS job_slug
  FROM applications a
  JOIN seafarers s ON s.id = a.seafarer_id
  JOIN jobs j ON j.id = a.job_id
  ORDER BY a.created_at DESC
  LIMIT 6
")->fetchAll();

/* ============ RECENT CREW REQUESTS ============ */
$recent_requests = $pdo->query("
  SELECT id, company, contact_name, vessel_type, ranks_needed, status, created_at
  FROM crew_requests
  ORDER BY created_at DESC
  LIMIT 5
")->fetchAll();

/* ============ RECENT JOBS ============ */
$recent_jobs = $pdo->query("
  SELECT id, title, rank_title, vessel_type, status,
         likes_count, comments_count, views_count, created_at
  FROM jobs
  ORDER BY created_at DESC
  LIMIT 5
")->fetchAll();

/* ============ BILLING DATA ============ */
$shipowners = $pdo->query("SELECT * FROM shipowners ORDER BY company_name")->fetchAll();
$seafarers  = $pdo->query("SELECT id, full_name FROM seafarers ORDER BY full_name")->fetchAll();
$invoices   = [];
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

$admin_title = 'Dashboard';
require __DIR__ . '/_nav.php';

$adminName = $_SESSION['admin']['name'] ?? $_SESSION['admin']['full_name'] ?? 'Administrator';
$firstName = explode(' ', trim($adminName))[0];
?>

<style>
  .dash-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:1rem;margin-bottom:2rem;
  }
  .stat-card{
    background:#fff;border:1px solid var(--line);border-radius:14px;
    padding:1.3rem;position:relative;overflow:hidden;
    transition:.2s;
  }
  .stat-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg)}
  .stat-card .stat-icon{
    width:42px;height:42px;border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    font-size:1.3rem;margin-bottom:.9rem;
  }
  .stat-card .stat-value{
    font-family:'Sora',sans-serif;font-size:1.9rem;font-weight:800;
    color:var(--navy);line-height:1;margin-bottom:.3rem;
  }
  .stat-card .stat-label{
    font-size:.82rem;color:var(--muted);font-weight:600;
    text-transform:uppercase;letter-spacing:.04em;
  }
  .stat-card .stat-sub{
    font-size:.78rem;color:var(--muted);margin-top:.35rem;
  }
  .stat-card .stat-sub strong{color:var(--teal-d)}
  .stat-card.highlight{
    background:linear-gradient(135deg,var(--navy),var(--navy-l));
    color:#fff;border:none;
  }
  .stat-card.highlight .stat-value{color:#fff}
  .stat-card.highlight .stat-label{color:rgba(255,255,255,.7)}
  .stat-card.highlight .stat-sub{color:rgba(255,255,255,.65)}

  .stat-icon.blue{background:rgba(15,181,174,.12);color:var(--teal-d)}
  .stat-icon.gold{background:rgba(212,175,55,.15);color:#8a6d1c}
  .stat-icon.green{background:rgba(31,169,113,.12);color:var(--success)}
  .stat-icon.red{background:rgba(217,74,74,.1);color:var(--danger)}
  .stat-icon.navy{background:rgba(10,37,64,.08);color:var(--navy)}
  .stat-icon.white{background:rgba(255,255,255,.15);color:#fff}

  .dash-columns{
    display:grid;grid-template-columns:1.4fr 1fr;gap:1.5rem;
  }
  .dash-panel{
    background:#fff;border:1px solid var(--line);border-radius:14px;
    padding:1.5rem;
  }
  .dash-panel h3{
    font-size:1.05rem;margin-bottom:1rem;
    display:flex;justify-content:space-between;align-items:center;
  }
  .dash-panel h3 a{
    font-size:.82rem;font-weight:600;color:var(--teal-d);
  }
  .dash-item{
    display:flex;align-items:center;gap:.8rem;
    padding:.75rem 0;border-bottom:1px solid var(--line);
  }
  .dash-item:last-child{border-bottom:none}
  .dash-item-avatar{
    width:36px;height:36px;border-radius:50%;flex-shrink:0;
    background:linear-gradient(135deg,var(--teal),#0891b2);
    color:#fff;display:flex;align-items:center;justify-content:center;
    font-weight:700;font-size:.85rem;font-family:'Sora',sans-serif;
  }
  .dash-item-body{flex:1;min-width:0}
  .dash-item-body strong{
    display:block;color:var(--navy);font-size:.9rem;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
  }
  .dash-item-body small{
    color:var(--muted);font-size:.78rem;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;
  }
  .dash-empty{
    text-align:center;padding:2rem 1rem;color:var(--muted);font-size:.88rem;
  }
  .quick-actions{
    display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:2rem;
  }
  @media(max-width:900px){
    .dash-columns{grid-template-columns:1fr}
  }
</style>

<!-- ============ WELCOME ============ -->
<div class="admin-topbar">
  <h1>Welcome back, <?= e($firstName) ?> 👋</h1>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <a href="/admin/index.php?tab=invoices" class="btn btn-primary btn-sm">📑 Client Invoices</a>
    <a href="/admin/job-edit.php" class="btn btn-teal btn-sm">+ Post Vacancy</a>
    <a href="/admin/crew-requests.php" class="btn btn-primary btn-sm">
      Requests <?php if ($stats['requests_new']): ?><span class="badge" style="margin-left:.4rem"><?= $stats['requests_new'] ?></span><?php endif; ?>
    </a>
  </div>
</div>

<!-- ============ QUICK ACTIONS ============ -->
<div class="quick-actions">
  <a href="/admin/index.php?tab=dashboard" class="btn <?= $tab === 'dashboard' ? 'btn-teal' : 'btn-ghost' ?> btn-sm">📊 Dashboard</a>
  <a href="/admin/index.php?tab=invoices" class="btn <?= $tab === 'invoices' ? 'btn-teal' : 'btn-ghost' ?> btn-sm">📑 Invoices & Billing</a>
  <a href="/admin/jobs.php" class="btn btn-ghost btn-sm">💼 Manage Jobs</a>
  <a href="/admin/applications.php" class="btn btn-ghost btn-sm">📨 Applications</a>
  <a href="/admin/seafarers.php" class="btn btn-ghost btn-sm">⚓ Seafarers</a>
  <a href="/admin/comments.php" class="btn btn-ghost btn-sm">💬 Comments</a>
  <a href="/" target="_blank" class="btn btn-ghost btn-sm">🌐 View Site</a>
</div>

<?php if ($tab === 'invoices'): ?>
<!-- ============ CLIENT INVOICES & BILLING TAB ============ -->
<div class="dash-panel" style="margin-bottom:2rem;">
    <h3 style="margin-bottom:1.5rem;">📑 Client Invoices & Crew Billing Manager</h3>

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

    <form method="POST" enctype="multipart/form-data" style="background:#f8fafc; padding:1.5rem; border-radius:10px; border:1px solid var(--line); margin-bottom:2.5rem;">
        <input type="hidden" name="create_invoice" value="1">
        <h4 style="margin-bottom:1rem; color:var(--navy);">Generate New Itemized Invoice</h4>

        <div style="display:grid; grid-template-columns: 2fr 1fr 1fr; gap:1rem; margin-bottom:1.5rem;">
            <div>
                <label style="display:block; font-weight:600; font-size:.8rem; color:var(--muted); margin-bottom:.3rem;">Select Shipowner Client</label>
                <select name="shipowner_id" required style="width:100%; padding:.6rem; border:1px solid var(--line); border-radius:6px; background:#fff;">
                    <option value="">Choose Client Company</option>
                    <?php foreach ($shipowners as $so): ?>
                        <option value="<?= $so['id'] ?>"><?= htmlspecialchars($so['company_name']) ?> (<?= htmlspecialchars($so['contact_person']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block; font-weight:600; font-size:.8rem; color:var(--muted); margin-bottom:.3rem;">Billing Month & Year</label>
                <input type="text" name="billing_month" placeholder="e.g. November 2026" required style="width:100%; padding:.6rem; border:1px solid var(--line); border-radius:6px; background:#fff;">
            </div>
            <div>
                <label style="display:block; font-weight:600; font-size:.8rem; color:var(--muted); margin-bottom:.3rem;">Attach Invoice PDF</label>
                <input type="file" name="invoice_pdf" accept=".pdf" style="width:100%; padding:.45rem; border:1px solid var(--line); border-radius:6px; background:#fff; font-size:.8rem;">
            </div>
        </div>

        <label style="display:block; font-weight:600; font-size:.85rem; color:var(--navy); margin-bottom:.5rem;">Invoice Line Items & Joined Crew Association</label>
        <div id="line-items-container" style="margin-bottom:1rem;">
            <div style="display:grid; grid-template-columns: 2fr 1fr 1.5fr 1.5fr 1fr auto; gap:.5rem; margin-bottom:.5rem; align-items:center;">
                <input type="text" name="item_desc[]" placeholder="Item description (e.g. Manning Fee / Working Gear)" required style="padding:.5rem; border:1px solid var(--line); border-radius:6px;">
                <input type="number" step="0.01" name="item_amount[]" placeholder="Amount ($)" required style="padding:.5rem; border:1px solid var(--line); border-radius:6px;">
                <select name="seafarer_id[]" style="padding:.5rem; border:1px solid var(--line); border-radius:6px; background:#fff;">
                    <option value="">Select Crew Member</option>
                    <?php foreach ($seafarers as $sf): ?>
                        <option value="<?= $sf['id'] ?>"><?= htmlspecialchars($sf['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="vessel_name[]" placeholder="Vessel Name" style="padding:.5rem; border:1px solid var(--line); border-radius:6px;">
                <input type="text" name="rank_title[]" placeholder="Rank Onboard" style="padding:.5rem; border:1px solid var(--line); border-radius:6px;">
                <span></span>
            </div>
        </div>

        <button type="button" onclick="addItemRow()" style="background:var(--line); color:var(--navy); border:none; padding:.4rem 1rem; border-radius:6px; cursor:pointer; font-weight:600; font-size:.85rem; margin-bottom:1.5rem;">+ Add Another Item</button>
        
        <div>
            <button type="submit" class="btn btn-primary" style="padding:.7rem 1.5rem; font-weight:600; font-size:.95rem;">Issue Invoice to Client</button>
        </div>
    </form>

    <h4 style="margin-bottom:1rem; color:var(--navy);">Issued Client Invoices</h4>
    <?php if (empty($invoices)): ?>
        <p style="color:var(--muted); font-size:.9rem; font-style:italic;">No invoices generated yet.</p>
    <?php else: ?>
        <table class="admin-table" style="box-shadow:none; border-radius:0">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Client Company</th>
                    <th>Billing Period</th>
                    <th>Total Amount</th>
                    <th>PDF Status</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($inv['invoice_number']) ?></strong></td>
                        <td><?= htmlspecialchars($inv['company_name']) ?></td>
                        <td><?= htmlspecialchars($inv['billing_month']) ?></td>
                        <td><strong>$<?= number_format($inv['total_amount'], 2) ?></strong></td>
                        <td>
                            <?php if (!empty($inv['pdf_file'])): ?>
                                <a href="/storage/invoices/<?= htmlspecialchars($inv['pdf_file']) ?>" target="_blank" style="color:var(--teal-d); font-weight:600; text-decoration:none;">📄 View PDF</a>
                            <?php else: ?>
                                <span style="color:var(--muted); font-size:.8rem;">None Attached</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($inv['status'] === 'paid'): ?>
                                <span style="background:#d4edda; color:#155724; padding:.2rem .6rem; border-radius:50px; font-size:.75rem; font-weight:700;">PAID</span>
                            <?php else: ?>
                                <span style="background:#f8d7da; color:#721c24; padding:.2rem .6rem; border-radius:50px; font-size:.75rem; font-weight:700;">UNPAID</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <a href="/admin/index.php?tab=invoices&toggle_status=<?= (int)$inv['id'] ?>" class="btn btn-sm <?= $inv['status'] === 'paid' ? 'btn-ghost' : 'btn-teal' ?>">
                                <?= $inv['status'] === 'paid' ? 'Mark Unpaid' : 'Mark as Paid ✓' ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
    function addItemRow() {
        const container = document.getElementById('line-items-container');
        const row = document.createElement('div');
        row.style.cssText = "display:grid; grid-template-columns: 2fr 1fr 1.5fr 1.5fr 1fr auto; gap:.5rem; margin-bottom:.5rem; align-items:center;";
        row.innerHTML = `
            <input type="text" name="item_desc[]" placeholder="Item description (e.g. Manning Fee / Working Gear)" required style="padding:.5rem; border:1px solid var(--line); border-radius:6px;">
            <input type="number" step="0.01" name="item_amount[]" placeholder="Amount ($)" required style="padding:.5rem; border:1px solid var(--line); border-radius:6px;">
            <select name="seafarer_id[]" style="padding:.5rem; border:1px solid var(--line); border-radius:6px; background:#fff;">
                <option value="">Select Crew Member</option>
                <?php foreach ($seafarers as $sf): ?>
                    <option value="<?= $sf['id'] ?>"><?= htmlspecialchars($sf['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="vessel_name[]" placeholder="Vessel Name" style="padding:.5rem; border:1px solid var(--line); border-radius:6px;">
            <input type="text" name="rank_title[]" placeholder="Rank Onboard" style="padding:.5rem; border:1px solid var(--line); border-radius:6px;">
            <button type="button" onclick="this.parentElement.remove()" style="background:var(--danger); color:#fff; border:none; padding:.5rem .8rem; border-radius:6px; cursor:pointer;">X</button>
        `;
        container.appendChild(row);
    }
</script>

<?php else: ?>
<!-- ============ ORIGINAL DASHBOARD VIEW ============ -->

<!-- ============ BIRTHDAY NOTIFICATION WIDGET ============ -->
<?php if (!empty($upcoming_birthdays)): ?>
    <div style="background:#fff3cd; color:#856404; border:1px solid #ffeeba; border-radius:12px; padding:1.2rem 1.5rem; margin-bottom:2rem; box-shadow:var(--shadow);">
        <h5 style="font-size:1rem; font-weight:700; margin-bottom:.5rem; display:flex; align-items:center; gap:.5rem;">
            🎉 Upcoming Seafarer Birthdays (Next 7 Days)
        </h5>
        <ul style="margin:0; padding-left:1.2rem; font-size:.9rem;">
            <?php foreach ($upcoming_birthdays as $bday): ?>
                <li style="margin-bottom:.3rem;">
                    <strong><?= htmlspecialchars($bday['full_name']) ?></strong> 
                    (DOB: <?= htmlspecialchars($bday['date_of_birth']) ?>) — 
                    Phone: <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $bday['phone'] ?? '') ?>" target="_blank" style="color:#0f5132; font-weight:600; text-decoration:underline;"><?= htmlspecialchars($bday['phone'] ?? 'No phone recorded') ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- ============ STAT CARDS ============ -->
<div class="dash-grid">
  <div class="stat-card highlight">
    <div class="stat-icon white">💼</div>
    <div class="stat-value"><?= $stats['jobs_live'] ?></div>
    <div class="stat-label">Live Jobs</div>
    <div class="stat-sub">
      <?= $stats['jobs_closed'] ?> closed · <?= $stats['jobs_hidden'] ?> hidden/draft
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon green">📨</div>
    <div class="stat-value"><?= $stats['applications'] ?></div>
    <div class="stat-label">Applications</div>
    <div class="stat-sub">
      <strong><?= $stats['apps_new'] ?></strong> awaiting review
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue">⚓</div>
    <div class="stat-value"><?= $stats['seafarers'] ?></div>
    <div class="stat-label">Seafarers</div>
    <div class="stat-sub">Registered profiles</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon gold">🚢</div>
    <div class="stat-value"><?= $stats['crew_requests'] ?></div>
    <div class="stat-label">Crew Requests</div>
    <div class="stat-sub">
      <strong><?= $stats['requests_new'] ?></strong> new inquiries
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon navy">💬</div>
    <div class="stat-value"><?= $stats['comments'] ?></div>
    <div class="stat-label">Comments</div>
    <div class="stat-sub">On live job posts</div>
  </div>
</div>

<!-- ============ COLUMNS ============ -->
<div class="dash-columns">

  <!-- Recent Applications -->
  <div class="dash-panel">
    <h3>
      Recent Applications
      <a href="/admin/applications.php">View all →</a>
    </h3>

    <?php if (!$recent_apps): ?>
      <div class="dash-empty">
        No applications yet.<br>
        <small>They'll appear here when seafarers apply to your jobs.</small>
      </div>
    <?php else: ?>
      <?php foreach ($recent_apps as $a): ?>
        <div class="dash-item">
          <div class="dash-item-avatar">
            <?= e(strtoupper(substr($a['full_name'], 0, 1))) ?>
          </div>
          <div class="dash-item-body">
            <strong><?= e($a['full_name']) ?> · <?= e($a['rank_title']) ?></strong>
            <small>
              Applied for <em><?= e($a['job_title']) ?></em> · <?= time_ago($a['created_at']) ?>
            </small>
          </div>
          <span class="pill <?= e($a['status']) ?>"><?= e($a['status']) ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Recent Crew Requests -->
  <div class="dash-panel">
    <h3>
      Recent Crew Requests
      <a href="/admin/crew-requests.php">View all →</a>
    </h3>

    <?php if (!$recent_requests): ?>
      <div class="dash-empty">
        No crew requests yet.<br>
        <small>Shipowner inquiries will appear here.</small>
      </div>
    <?php else: ?>
      <?php foreach ($recent_requests as $r): ?>
        <div class="dash-item">
          <div class="dash-item-avatar" style="background:linear-gradient(135deg,var(--navy),var(--navy-l))">
            <?= e(strtoupper(substr($r['company'], 0, 1))) ?>
          </div>
          <div class="dash-item-body">
            <strong><?= e($r['company']) ?></strong>
            <small>
              <?= e($r['ranks_needed']) ?> · <?= e($r['vessel_type'] ?: 'Vessel') ?> · <?= time_ago($r['created_at']) ?>
            </small>
          </div>
          <span class="pill <?= e($r['status']) ?>"><?= e($r['status']) ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<!-- ============ RECENT JOBS ============ -->
<div class="dash-panel" style="margin-top:1.5rem">
  <h3>
    Recent Vacancies
    <a href="/admin/jobs.php">View all →</a>
  </h3>

  <?php if (!$recent_jobs): ?>
    <div class="dash-empty">
      No jobs posted yet.<br>
      <a href="/admin/job-edit.php" class="btn btn-teal btn-sm" style="margin-top:1rem">Post Your First Vacancy</a>
    </div>
  <?php else: ?>
    <table class="admin-table" style="box-shadow:none;border-radius:0">
      <thead>
        <tr>
          <th>Status</th>
          <th>Title</th>
          <th>Rank / Vessel</th>
          <th>Engagement</th>
          <th>Posted</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent_jobs as $j):
          $dotClass = 'dot-' . $j['status'];
          $labels = ['published'=>'Live','hidden'=>'Hidden','draft'=>'Draft','closed'=>'Closed'];
        ?>
        <tr>
          <td>
            <span style="display:inline-flex;align-items:center;font-weight:600;font-size:.85rem">
              <span class="dot <?= $dotClass ?>"></span>
              <?= e($labels[$j['status']] ?? ucfirst($j['status'])) ?>
            </span>
          </td>
          <td><strong><?= e($j['title']) ?></strong></td>
          <td><?= e($j['rank_title']) ?><br><small class="muted"><?= e($j['vessel_type'] ?: '—') ?></small></td>
          <td><small>♥ <?= (int)$j['likes_count'] ?> · 💬 <?= (int)$j['comments_count'] ?> · 👁 <?= (int)$j['views_count'] ?></small></td>
          <td><small class="muted"><?= time_ago($j['created_at']) ?></small></td>
          <td style="text-align:right">
            <a class="btn btn-ghost btn-sm" href="/admin/job-edit.php?id=<?= (int)$j['id'] ?>">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php endif; ?>

</main></div></body></html>