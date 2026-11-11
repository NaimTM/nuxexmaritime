<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Ensure seafarer is logged in
if (!isset($_SESSION['seafarer'])) {
    header('Location: /login.php');
    exit;
}

$seafarer_id = $_SESSION['seafarer']['id'];
$success = '';
$error = '';

// 1. Handle Profile Information Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name   = trim($_POST['full_name'] ?? '');
    $dob         = trim($_POST['dob'] ?? null);
    $address     = trim($_POST['address'] ?? '');
    $rank        = trim($_POST['rank'] ?? '');
    $vessel_type = trim($_POST['vessel_type'] ?? '');
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    if ($full_name === '') {
        $error = 'Full name is required.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE seafarers 
            SET full_name = ?, dob = ?, address = ?, `rank` = ?, vessel_type = ?, is_available = ? 
            WHERE id = ?
        ");
        $stmt->execute([$full_name, $dob ?: null, $address, $rank, $vessel_type, $is_available, $seafarer_id]);
        
        $_SESSION['seafarer']['full_name'] = $full_name;
        $success = 'Profile updated successfully!';
    }
}

// 2. Handle Document Upload (CV, Passport, CDC, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    $doc_type    = trim($_POST['doc_type'] ?? 'General Certificate');
    $expiry_date = trim($_POST['expiry_date'] ?? null);
    
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['document_file']['tmp_name'];
        $fileName      = $_FILES['document_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        if (in_array($fileExtension, $allowedExts)) {
            $newFileName = 'doc_' . $seafarer_id . '_' . time() . '.' . $fileExtension;
            $uploadDir   = __DIR__ . '/uploads/';
            
            // Automatically create uploads directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $dest_path = $uploadDir . $newFileName;
            $db_path   = '/uploads/' . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $docStmt = $pdo->prepare("
                    INSERT INTO seafarer_documents (seafarer_id, doc_type, document_type, file_path, expiry_date, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $docStmt->execute([$seafarer_id, $doc_type, $doc_type, $db_path, $expiry_date ?: null]);
                $success = 'Document uploaded successfully and added to your vault!';
            } else {
                $error = 'CV / Document could not be saved to server directory. Please check folder permissions.';
            }
        } else {
            $error = 'Invalid file type. Please upload PDF, Word docs, or images.';
        }
    } else {
        $error = 'Please select a valid document file to upload (Error code: ' . ($_FILES['document_file']['error'] ?? 'unknown') . ').';
    }
}

// 3. Handle Document Deletion
if (isset($_GET['delete_doc'])) {
    $doc_id = (int)$_GET['delete_doc'];
    $checkStmt = $pdo->prepare("SELECT * FROM seafarer_documents WHERE id = ? AND seafarer_id = ?");
    $checkStmt->execute([$doc_id, $seafarer_id]);
    $docRecord = $checkStmt->fetch();
    
    if ($docRecord) {
        $fullPath = __DIR__ . $docRecord['file_path'];
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
        $delStmt = $pdo->prepare("DELETE FROM seafarer_documents WHERE id = ?");
        $delStmt->execute([$doc_id]);
        header('Location: /profile.php');
        exit;
    }
}

// Fetch latest seafarer data
$stmt = $pdo->prepare("SELECT * FROM seafarers WHERE id = ?");
$stmt->execute([$seafarer_id]);
$seafarer = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Seafarer Profile & Document Vault - Nuzex Maritime</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background: #f8fafc; font-family: 'Inter', sans-serif; padding: 2rem;">
    <div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 2.5rem; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem;">
            <h1 style="font-size: 1.5rem; color: #0a2540; margin: 0;">⚓ Seafarer Portal & Dossier</h1>
            <a href="/" style="color: #0fb5ae; text-decoration: none; font-weight: 600; font-size: .9rem;">← Back to Home</a>
        </div>

        <?php if ($success): ?>
            <div style="background: #d4edda; color: #155724; padding: 12px 15px; border-radius: 8px; margin-bottom: 1.5rem; font-size: .9rem; border: 1px solid #c3e6cb;">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 12px 15px; border-radius: 8px; margin-bottom: 1.5rem; font-size: .9rem; border: 1px solid #f5c6cb;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- PROFILE EDIT FORM -->
        <form method="POST">
            <input type="hidden" name="update_profile" value="1">
            <h3 style="font-size: 1.1rem; color: #0a2540; margin-bottom: 1rem;">Personal & Professional Details</h3>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-weight: 600; font-size: .85rem; color: #4a5568; margin-bottom: .4rem;">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($seafarer['full_name'] ?? '') ?>" required style="width: 100%; padding: .6rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-weight: 600; font-size: .85rem; color: #4a5568; margin-bottom: .4rem;">Email Address (Read-only)</label>
                <input type="email" value="<?= htmlspecialchars($seafarer['email'] ?? '') ?>" disabled style="width: 100%; padding: .6rem; border: 1px solid #cbd5e1; border-radius: 6px; background: #f1f5f9; color: #64748b;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.2rem;">
                <div>
                    <label style="display: block; font-weight: 600; font-size: .85rem; color: #4a5568; margin-bottom: .4rem;">Professional Rank</label>
                    <select name="rank" style="width: 100%; padding: .6rem; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                        <option value="">Select Your Rank</option>
                        <optgroup label="Deck Department">
                            <option value="Master" <?= ($seafarer['rank'] ?? '') === 'Master' ? 'selected' : '' ?>>Master / Captain</option>
                            <option value="Chief Officer" <?= ($seafarer['rank'] ?? '') === 'Chief Officer' ? 'selected' : '' ?>>Chief Officer</option>
                            <option value="Second Officer" <?= ($seafarer['rank'] ?? '') === 'Second Officer' ? 'selected' : '' ?>>Second Officer</option>
                            <option value="Third Officer" <?= ($seafarer['rank'] ?? '') === 'Third Officer' ? 'selected' : '' ?>>Third Officer</option>
                            <option value="Deck Cadet" <?= ($seafarer['rank'] ?? '') === 'Deck Cadet' ? 'selected' : '' ?>>Deck Cadet</option>
                            <option value="Bosun" <?= ($seafarer['rank'] ?? '') === 'Bosun' ? 'selected' : '' ?>>Bosun</option>
                            <option value="Able Seaman (AB)" <?= ($seafarer['rank'] ?? '') === 'Able Seaman (AB)' ? 'selected' : '' ?>>Able Seaman (AB)</option>
                            <option value="Ordinary Seaman (OS)" <?= ($seafarer['rank'] ?? '') === 'Ordinary Seaman (OS)' ? 'selected' : '' ?>>Ordinary Seaman (OS)</option>
                        </optgroup>
                        <optgroup label="Engine Department">
                            <option value="Chief Engineer" <?= ($seafarer['rank'] ?? '') === 'Chief Engineer' ? 'selected' : '' ?>>Chief Engineer</option>
                            <option value="Second Engineer" <?= ($seafarer['rank'] ?? '') === 'Second Engineer' ? 'selected' : '' ?>>Second Engineer</option>
                            <option value="Third Engineer" <?= ($seafarer['rank'] ?? '') === 'Third Engineer' ? 'selected' : '' ?>>Third Engineer</option>
                            <option value="Fourth Engineer" <?= ($seafarer['rank'] ?? '') === 'Fourth Engineer' ? 'selected' : '' ?>>Fourth Engineer</option>
                            <option value="Engine Cadet" <?= ($seafarer['rank'] ?? '') === 'Engine Cadet' ? 'selected' : '' ?>>Engine Cadet</option>
                            <option value="Fitter" <?= ($seafarer['rank'] ?? '') === 'Fitter' ? 'selected' : '' ?>>Fitter</option>
                            <option value="Oiler" <?= ($seafarer['rank'] ?? '') === 'Oiler' ? 'selected' : '' ?>>Oiler</option>
                            <option value="Wiper" <?= ($seafarer['rank'] ?? '') === 'Wiper' ? 'selected' : '' ?>>Wiper</option>
                        </optgroup>
                        <optgroup label="Catering / Other">
                            <option value="Chief Cook" <?= ($seafarer['rank'] ?? '') === 'Chief Cook' ? 'selected' : '' ?>>Chief Cook</option>
                            <option value="Messman" <?= ($seafarer['rank'] ?? '') === 'Messman' ? 'selected' : '' ?>>Messman</option>
                            <option value="Electrical Officer (ETO)" <?= ($seafarer['rank'] ?? '') === 'Electrical Officer (ETO)' ? 'selected' : '' ?>>Electrical Officer (ETO)</option>
                        </optgroup>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; font-size: .85rem; color: #4a5568; margin-bottom: .4rem;">Preferred / Expert Vessel Type</label>
                    <select name="vessel_type" style="width: 100%; padding: .6rem; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                        <option value="">Select Vessel Type</option>
                        <option value="Oil Tanker" <?= ($seafarer['vessel_type'] ?? '') === 'Oil Tanker' ? 'selected' : '' ?>>Oil Tanker</option>
                        <option value="Chemical Tanker" <?= ($seafarer['vessel_type'] ?? '') === 'Chemical Tanker' ? 'selected' : '' ?>>Chemical Tanker</option>
                        <option value="LNG / LPG Carrier" <?= ($seafarer['vessel_type'] ?? '') === 'LNG / LPG Carrier' ? 'selected' : '' ?>>LNG / LPG Carrier</option>
                        <option value="Container Ship" <?= ($seafarer['vessel_type'] ?? '') === 'Container Ship' ? 'selected' : '' ?>>Container Ship</option>
                        <option value="Bulk Carrier" <?= ($seafarer['vessel_type'] ?? '') === 'Bulk Carrier' ? 'selected' : '' ?>>Bulk Carrier</option>
                        <option value="General Cargo" <?= ($seafarer['vessel_type'] ?? '') === 'General Cargo' ? 'selected' : '' ?>>General Cargo</option>
                        <option value="Offshore / Tug" <?= ($seafarer['vessel_type'] ?? '') === 'Offshore / Tug' ? 'selected' : '' ?>>Offshore / Tug</option>
                        <option value="Passenger / Ro-Ro" <?= ($seafarer['vessel_type'] ?? '') === 'Passenger / Ro-Ro' ? 'selected' : '' ?>>Passenger / Ro-Ro</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-weight: 600; font-size: .85rem; color: #4a5568; margin-bottom: .4rem;">Date of Birth</label>
                <input type="date" name="dob" value="<?= htmlspecialchars($seafarer['dob'] ?? '') ?>" style="width: 100%; padding: .6rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-weight: 600; font-size: .85rem; color: #4a5568; margin-bottom: .4rem;">Residential Address</label>
                <textarea name="address" rows="3" style="width: 100%; padding: .6rem; border: 1px solid #cbd5e1; border-radius: 6px;"><?= htmlspecialchars($seafarer['address'] ?? '') ?></textarea>
            </div>

            <div style="margin-bottom: 1.5rem; display: flex; align-items: center; gap: .8rem;">
                <input type="checkbox" name="is_available" id="is_available" value="1" <?= !empty($seafarer['is_available']) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                <label for="is_available" style="font-weight: 600; font-size: .9rem; color: #0a2540; cursor: pointer;">I am currently available for employment / deployment</label>
            </div>

            <button type="submit" style="background: #0fb5ae; color: #fff; border: none; padding: .7rem 1.5rem; font-weight: 600; border-radius: 6px; cursor: pointer; font-size: .95rem;">Save Profile Changes</button>
        </form>

        <!-- DOCUMENT VAULT SECTION -->
        <div style="margin-top: 3rem; border-top: 1px solid #e2e8f0; padding-top: 2rem;">
            <h3 style="font-size: 1.2rem; color: #0a2540; margin-bottom: .5rem;">📁 Professional Document Vault</h3>
            <p style="font-size: .9rem; color: #64748b; margin-bottom: 1.5rem;">Upload your CV, Passport, CDC, or STCW certificates so admins and shipping clients can review your credentials.</p>

            <!-- Document Upload Form -->
            <form method="POST" enctype="multipart/form-data" style="background: #f8fafc; padding: 1.5rem; border-radius: 10px; border: 1px solid #cbd5e1; margin-bottom: 2rem;">
                <input type="hidden" name="upload_document" value="1">
                <h4 style="font-size: 1rem; color: #0a2540; margin-bottom: 1rem;">Upload New Document</h4>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 600; font-size: .8rem; color: #4a5568; margin-bottom: .3rem;">Document Type / Title</label>
                        <select name="doc_type" required style="width: 100%; padding: .6rem; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                            <option value="CV / Resume">CV / Resume</option>
                            <option value="Passport">Passport</option>
                            <option value="CDC (Continuous Discharge Certificate)">CDC</option>
                            <option value="STCW Certificate">STCW Certificate</option>
                            <option value="CoC (Certificate of Competency)">CoC (Certificate of Competency)</option>
                            <option value="Medical Certificate">Medical Certificate</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; font-size: .8rem; color: #4a5568; margin-bottom: .3rem;">Expiry Date (If applicable)</label>
                        <input type="date" name="expiry_date" style="width: 100%; padding: .6rem; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="display: block; font-weight: 600; font-size: .8rem; color: #4a5568; margin-bottom: .3rem;">Select File (PDF, DOCX, JPG, PNG)</label>
                    <input type="file" name="document_file" required style="width: 100%; padding: .5rem; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <button type="submit" style="background: #0a2540; color: #fff; border: none; padding: .6rem 1.4rem; font-weight: 600; border-radius: 6px; cursor: pointer; font-size: .9rem;">Upload to Vault</button>
            </form>

            <!-- Listed Documents -->
            <?php
                $myDocsStmt = $pdo->prepare("SELECT * FROM seafarer_documents WHERE seafarer_id = ? ORDER BY created_at DESC");
                $myDocsStmt->execute([$seafarer_id]);
                $myDocs = $myDocsStmt->fetchAll();
            ?>

            <?php if (empty($myDocs)): ?>
                <p style="font-size: .9rem; color: #64748b; font-style: italic;">No documents uploaded to your vault yet.</p>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; font-size: .9rem;">
                    <thead>
                        <tr style="background: #f1f5f9; text-align: left;">
                            <th style="padding: 10px; border-bottom: 1px solid #cbd5e1;">Document Type</th>
                            <th style="padding: 10px; border-bottom: 1px solid #cbd5e1;">Expiry Date</th>
                            <th style="padding: 10px; border-bottom: 1px solid #cbd5e1; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myDocs as $doc): ?>
                            <tr>
                                <td style="padding: 12px 10px; border-bottom: 1px solid #e2e8f0;"><strong>📄 <?= htmlspecialchars($doc['doc_type']) ?></strong></td>
                                <td style="padding: 12px 10px; border-bottom: 1px solid #e2e8f0;"><?= htmlspecialchars($doc['expiry_date'] ?? 'N/A') ?></td>
                                <td style="padding: 12px 10px; border-bottom: 1px solid #e2e8f0; text-align: right;">
                                    <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" style="color: #0fb5ae; text-decoration: none; margin-right: 15px; font-weight: 600;">View ↗</a>
                                    <a href="/profile.php?delete_doc=<?= (int)$doc['id'] ?>" onclick="return confirm('Are you sure you want to delete this document?');" style="color: #d94a4a; text-decoration: none; font-weight: 600;">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>