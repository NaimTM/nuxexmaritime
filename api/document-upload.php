<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$upload_dir = '../uploads/documents/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_type = sanitize_input($_POST['document_type'] ?? 'Other');
    
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['document']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['document']['name']);
        $target_path = $upload_dir . $file_name;
        
        $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed_exts)) {
            if (move_uploaded_file($file_tmp, $target_path)) {
                $stmt = $pdo->prepare("INSERT INTO seafarer_documents (user_id, document_type, document_name, file_path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $doc_type, $_FILES['document']['name'], 'uploads/documents/' . $file_name]);
                $_SESSION['success'] = "Document uploaded successfully!";
            } else {
                $_SESSION['error'] = "Failed to upload file.";
            }
        } else {
            $_SESSION['error'] = "Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX.";
        }
    }
}

header("Location: ../profile.php");
exit();
?>
