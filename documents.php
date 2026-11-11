<?php
/**
 * Nuzex Maritime — Seafarer Document Vault
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$me = current_seafarer();
if (!$me) {
    header('Location: /login.php');
    exit;
}

$error = null;
$success = null;

// Handle Document Upload with Safe Path Validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document_file'])) {
    $doc_type = trim($_POST['doc_type'] ?? 'Other');
    $expiry = $_POST['expiry_date'] ?? null;

    $file = $_FILES['document_file'];

    if ($file['error'] === UPLOAD_ERR_OK || $file['error'] === UPLOAD_ERR_NO_TMP_DIR) {
        $tempSource = $file['tmp_name'] ?? '';

        if (!empty($tempSource) && file_exists($tempSource)) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'])) {
                $uploadDir = __DIR__ . '/uploads/documents/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = 'doc_' . $me['id'] . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                $destination = $uploadDir . $fileName;

                if (@move_uploaded_file($tempSource, $destination) || @copy($tempSource, $destination)) {
                    $filePath = 'uploads/documents/' . $fileName;
                    $stmt = $pdo->prepare("INSERT INTO seafarer_documents (seafarer_id, doc_type, file_path, expiry_date) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$me['id'], $doc_type, $filePath, $expiry ?: null]);
                    $success = 'Document uploaded successfully.';
                } else {
                    $error = 'Failed to save the uploaded file to the server folder.';
                }
            } else {
                $error = 'Invalid file format. Allowed formats: PDF, JPG, PNG, DOC, DOCX.';
            }
        } else {
            $error = 'Server temporary upload directory is missing (Error 6). Please update upload_tmp_dir in your Hangar php.ini file.';
        }
    } else {
        $error = 'Upload error encountered. Code: ' . $file['error'];
    }
}

// Handle Document Deletion
if (isset($_GET['delete_doc'])) {
    $docId = (int)$_GET['delete_doc'];
    $stmt = $pdo->prepare("SELECT * FROM seafarer_documents WHERE id = ? AND seafarer_id = ?");
    $stmt->execute([$docId, $me['id']]);
    $doc = $stmt->fetch();
    
    if ($doc) {
        if (file_exists(__DIR__ . '/' . $doc['file_path'])) {
            @unlink(__DIR__ . '/' . $doc['file_path']);
        }
        $del = $pdo->prepare("DELETE FROM seafarer_documents WHERE id = ?");
        $del->execute([$docId]);
        $success = 'Document deleted successfully.';
    }
}

// Fetch existing documents
$docsStmt = $pdo->prepare("SELECT * FROM seafarer_documents WHERE seafarer_id = ? ORDER BY created_at DESC");
$docsStmt->execute([$me['id']]);
$documents = $docsStmt->fetchAll();

$page_title = "Document Vault | Nuzex Maritime";
require __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-top: 40px;">
  <div class="container" style="max-width: 900px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
      <h1>My Document Vault</h1>
      <a href="/profile.php" class="btn btn-ghost btn-sm">← Back to Profile</a>
    </div>

    <?php if ($error): ?><div class="alert" style="background:#f8d7da; color:#721c24; padding:12px; border-radius:4px; margin-bottom:15px;"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert" style="background:#d4edda; color:#155724; padding:12px; border-radius:4px; margin-bottom:15px;"><?= e($success) ?></div><?php endif; ?>

    <!-- Upload Form Card -->
    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.05); margin-bottom:30px;">
      <h3>Upload New Document</h3>
      <form method="post" enctype="multipart/form-data" style="margin-top:15px;">
        <div class="form-row">
          <div class="form-group">
            <label>Document Type *</label>
            <select name="doc_type" required class="form-control">
              <option value="Passport">Passport</option>
              <option value="CDC">CDC (Continuous Discharge Certificate)</option>
              <option value="COC">COC (Certificate of Competency)</option>
              <option value="COP">COP (Certificate of Proficiency)</option>
              <option value="Medical Certificate">Medical Fitness Certificate</option>
              <option value="Yellow Fever">Yellow Fever Vaccination</option>
              <option value="Profile Photo">Profile Photo</option>
              <option value="Other">Other Certificate</option>
            </select>
          </div>
          <div class="form-group">
            <label>Expiry Date (if applicable)</label>
            <input type="date" name="expiry_date" class="form-control">
          </div>
        </div>
        <div class="form-group" style="margin-top:15px;">
          <label>Select File (PDF, JPG, PNG · Max 10MB) *</label>
          <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:15px;">Upload Document</button>
      </form>
    </div>

    <!-- Document List -->
    <div style="background:#fff; padding:25px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
      <h3>Uploaded Documents</h3>
      <?php if (empty($documents)): ?>
        <p style="color:#666; margin-top:10px;">No documents uploaded yet.</p>
      <?php else: ?>
        <div style="overflow-x:auto; margin-top:15px;">
          <table style="width:100%; border-collapse:collapse;">
            <thead>
              <tr style="border-bottom:2px solid #eee; text-align:left;">
                <th style="padding:10px;">Type</th>
                <th style="padding:10px;">Expiry Date</th>
                <th style="padding:10px;">Uploaded On</th>
                <th style="padding:10px; text-align:right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($documents as $doc): ?>
                <tr style="border-bottom:1px solid #f2f2f2;">
                  <td style="padding:12px;"><strong><?= e($doc['doc_type']) ?></strong></td>
                  <td style="padding:12px;"><?= e($doc['expiry_date'] ?: 'N/A') ?></td>
                  <td style="padding:12px;"><?= e($doc['created_at']) ?></td>
                  <td style="padding:12px; text-align:right;">
                    <a href="/<?= e($doc['file_path']) ?>" target="_blank" class="btn btn-ghost btn-sm">View</a>
                    <a href="?delete_doc=<?= $doc['id'] ?>" onclick="return confirm('Are you sure you want to delete this document?');" class="btn btn-sm" style="color:#dc3545;">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>