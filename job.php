<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Support both ?slug=... and ?id=... parameters
$slug = trim($_GET['slug'] ?? '');
$id   = (int)($_GET['id'] ?? 0);

if ($slug !== '') {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE slug = ?");
    $stmt->execute([$slug]);
} elseif ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$id]);
} else {
    header('Location: /jobs.php');
    exit;
}

$job = $stmt->fetch();

if (!$job) {
    echo "<div style='font-family:Arial; padding:40px; text-align:center;'><h2>Job vacancy not found.</h2><br><a href='/jobs.php'>← Back to Vacancies</a></div>";
    exit;
}

$job_id = (int)$job['id'];
$success = '';
$error = '';

// Handle Job Application Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
    if (!isset($_SESSION['seafarer'])) {
        header('Location: /login.php');
        exit;
    }

    $seafarer_id = $_SESSION['seafarer']['id'];

    // Check if already applied
    $checkApp = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND seafarer_id = ?");
    $checkApp->execute([$job_id, $seafarer_id]);
    
    if ($checkApp->fetch()) {
        $error = 'You have already applied for this position.';
    } else {
        $cv_path = null;

        if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['cv_file']['tmp_name'];
            $fileName      = $_FILES['cv_file']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExts = ['pdf', 'doc', 'docx'];
            if (in_array($fileExtension, $allowedExts)) {
                $newFileName = 'cv_' . $seafarer_id . '_' . time() . '.' . $fileExtension;
                $uploadDir   = __DIR__ . '/uploads/';
                
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                
                $dest_path = $uploadDir . $newFileName;
                $cv_path   = '/uploads/' . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Save to document vault
                    try {
                        $docStmt = $pdo->prepare("
                            INSERT INTO seafarer_documents (seafarer_id, doc_type, document_type, file_path, created_at) 
                            VALUES (?, 'CV / Resume', 'CV / Resume', ?, NOW())
                        ");
                        $docStmt->execute([$seafarer_id, $cv_path]);
                    } catch (Exception $ex) {
                        // Non-fatal if duplicate entry
                    }
                } else {
                    $error = 'CV could not be saved to server directory. Please check folder permissions.';
                }
            } else {
                $error = 'Invalid file format. Please upload PDF, DOC, or DOCX.';
            }
        } else {
            $error = 'Please select a valid CV file to upload.';
        }

        if (empty($error)) {
            $appStmt = $pdo->prepare("
                INSERT INTO applications (job_id, seafarer_id, rank_title, status, cv_path, created_at) 
                VALUES (?, ?, ?, 'applied', ?, NOW())
            ");
            $appStmt->execute([$job_id, $seafarer_id, $job['rank_title'] ?? $job['title'], $cv_path]);
            $success = 'Application and CV submitted successfully!';
        }
    }
}

$page_title = e($job['title']) . " | Nuzex Maritime";
require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top:40px">
  <div class="container" style="max-width:800px">
        
    <div style="margin-bottom:1.5rem">
        <a href="/jobs.php" style="color:#0fb5ae;text-decoration:none;font-weight:600;font-size:.9rem">← Back to Job Openings</a>
    </div>

    <div style="background:#fff;padding:2.5rem;border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 4px 6px rgba(0,0,0,0.02)">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:1rem">
            <h1 style="font-size:1.8rem;color:#0a2540;margin:0"><?= e($job['title']) ?></h1>
            <span class="job-tag" style="background:rgba(15,181,174,0.1);color:#0fb5ae"><?= e($job['vessel_type'] ?? 'Maritime Vacancy') ?></span>
        </div>

        <p style="color:#64748b;font-size:.95rem;margin-bottom:1.5rem;border-bottom:1px solid #e2e8f0;padding-bottom:1rem">
            <strong>Required Rank:</strong> <?= e($job['rank_title'] ?? 'Not specified') ?> &nbsp;|&nbsp; 
            <strong>Posted:</strong> <?= time_ago($job['created_at']) ?>
        </p>

        <?php if ($success): ?>
            <div style="background:#d4edda;color:#155724;padding:12px 15px;border-radius:8px;margin-bottom:1.5rem;font-size:.95rem;border:1px solid #c3e6cb;">
                ✅ <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background:#f8d7da;color:#721c24;padding:12px 15px;border-radius:8px;margin-bottom:1.5rem;font-size:.95rem;border:1px solid #f5c6cb;">
                ⚠️ <?= e($error) ?>
            </div>
        <?php endif; ?>

        <div style="margin-bottom:2rem;line-height:1.6;color:#334155">
            <h3 style="color:#0a2540;font-size:1.1rem;margin-bottom:.5rem">Job Description</h3>
            <p><?= nl2br(e($job['description'] ?? 'No description provided.')) ?></p>
        </div>

        <?php if (!empty($job['requirements'])): ?>
            <div style="margin-bottom:2rem;line-height:1.6;color:#334155">
                <h3 style="color:#0a2540;font-size:1.1rem;margin-bottom:.5rem">Requirements & Certificates</h3>
                <p><?= nl2br(e($job['requirements'])) ?></p>
            </div>
        <?php endif; ?>

        <!-- APPLICATION BOX -->
        <div id="apply" style="background:#f8fafc;padding:2rem;border-radius:10px;border:1px solid #cbd5e1;margin-top:2rem">
            <h3 style="font-size:1.2rem;color:#0a2540;margin-bottom:1rem">Apply for this Position</h3>

            <?php if (!isset($_SESSION['seafarer'])): ?>
                <div style="background:#fff3cd;color:#856404;padding:12px;border-radius:6px;margin-bottom:1rem;border:1px solid #ffeeba">
                    You must be logged in as a seafarer to submit your application.
                </div>
                <a href="/login.php" class="btn btn-primary">Login as Seafarer to Apply</a>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="apply_job" value="1">
                    
                    <div style="margin-bottom:1.2rem">
                        <label style="display:block;font-weight:600;font-size:.85rem;color:#4a5568;margin-bottom:.4rem">Upload Your CV / Resume (PDF or Word)</label>
                        <input type="file" name="cv_file" required accept=".pdf,.doc,.docx" style="width:100%;padding:.5rem;background:#fff;border:1px solid #cbd5e1;border-radius:6px">
                        <small style="color:#64748b;display:block;margin-top:.3rem">Your CV will be securely submitted to Nuzex Maritime and saved to your profile vault.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>