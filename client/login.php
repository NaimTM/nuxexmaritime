<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM shipowners WHERE email = ?");
        $stmt->execute([$email]);
        $owner = $stmt->fetch();

        if ($owner && password_verify($password, $owner['password_hash'])) {
            if ($owner['status'] === 'pending') {
                $error = 'Your company account is pending verification by Nuzex Maritime administration.';
            } else {
                $_SESSION['shipowner'] = [
                    'id' => $owner['id'],
                    'company_name' => $owner['company_name'],
                    'contact_person' => $owner['contact_person'],
                    'email' => $owner['email']
                ];
                header('Location: /client/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shipowner Portal Login - Nuzex Maritime</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body style="background: #f8fafc; font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">
    <div style="width: 100%; max-width: 420px; background: #fff; padding: 2.5rem; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
        
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1 style="font-size: 1.5rem; color: #0a2540; margin: 0 0 .4rem 0;">⚓ Shipowner Portal</h1>
            <p style="color: #64748b; font-size: .9rem; margin: 0;">Log in to manage your fleet crew and billing</p>
        </div>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px 14px; border-radius: 6px; margin-bottom: 1.2rem; font-size: .85rem; border: 1px solid #f5c6cb;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-weight: 600; font-size: .85rem; color: #4a5568; margin-bottom: .4rem;">Business Email</label>
                <input type="email" name="email" required style="width: 100%; padding: .7rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; font-size: .85rem; color: #4a5568; margin-bottom: .4rem;">Password</label>
                <input type="password" name="password" required style="width: 100%; padding: .7rem; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <button type="submit" style="width: 100%; background: #0a2540; color: #fff; border: none; padding: .75rem; font-weight: 600; border-radius: 6px; cursor: pointer; font-size: .95rem;">Client Login</button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: .85rem; color: #64748b;">
            <a href="/" style="color: #0fb5ae; text-decoration: none; font-weight: 600;">← Back to Home</a>
        </div>
    </div>
</body>
</html>