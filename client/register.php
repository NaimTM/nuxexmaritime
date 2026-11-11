<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name   = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $password       = $_POST['password'] ?? '';

    if (empty($company_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Check if email already exists
        $check = $pdo->prepare("SELECT id FROM shipowners WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert with 'pending' status so admins can review before approval
            $stmt = $pdo->prepare("
                INSERT INTO shipowners (company_name, contact_person, email, password_hash, status, created_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([$company_name, $contact_person, $email, $password_hash]);
            
            $success = 'Registration submitted successfully! Your account is pending review by Nuzex Maritime administration.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shipowner Registration - Nuzex Maritime</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background:#f8fafc; font-family:'Inter', sans-serif; padding:3rem 1rem;">
    <div style="max-width:450px; margin:0 auto; background:#fff; padding:2.5rem; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 4px 6px rgba(0,0,0,0.02);">
        <h2 style="color:#0a2540; margin-bottom:0.5rem; font-size:1.5rem;">Shipowner Portal Registration</h2>
        <p style="color:#64748b; font-size:0.9rem; margin-bottom:1.5rem;">Register your shipping company to manage crew rosters and view invoices.</p>

        <?php if ($success): ?>
            <div style="background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin-bottom:1.5rem; font-size:0.9rem; border:1px solid #c3e6cb;">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background:#f8d7da; color:#721c24; padding:12px; border-radius:8px; margin-bottom:1.5rem; font-size:0.9rem; border:1px solid #f5c6cb;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-weight:600; font-size:0.85rem; color:#4a5568; margin-bottom:0.3rem;">Company Name</label>
                <input type="text" name="company_name" required style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-weight:600; font-size:0.85rem; color:#4a5568; margin-bottom:0.3rem;">Contact Person</label>
                <input type="text" name="contact_person" required style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-weight:600; font-size:0.85rem; color:#4a5568; margin-bottom:0.3rem;">Corporate Email</label>
                <input type="email" name="email" required style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
            </div>
            <div style="margin-bottom:1.5rem;">
                <label style="display:block; font-weight:600; font-size:0.85rem; color:#4a5568; margin-bottom:0.3rem;">Password</label>
                <input type="password" name="password" required style="width:100%; padding:0.6rem; border:1px solid #cbd5e1; border-radius:6px;">
            </div>
            <button type="submit" style="width:100%; background:#0a2540; color:#fff; border:none; padding:0.75rem; font-weight:600; border-radius:6px; cursor:pointer;">Register Company Account</button>
        </form>
    </div>
</body>
</html>