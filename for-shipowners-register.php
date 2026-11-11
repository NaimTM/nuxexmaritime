<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($company_name) || empty($contact_person) || empty($email) || empty($password)) {
        $error = 'Please fill in all required company details.';
    } else {
        // Check if email already exists
        $chk = $pdo->prepare("SELECT id FROM shipowners WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'An account with this email address already exists.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO shipowners (company_name, contact_person, email, phone, address, password_hash, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([$company_name, $contact_person, $email, $phone, $address, $password_hash]);
                $success = 'Registration submitted successfully! Your account is currently pending admin review and activation.';
            } catch (Exception $e) {
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Register as Shipowner | Nuzex Maritime';
$meta_desc  = 'Register your shipping company with Nuzex Maritime for reliable crew management, billing, and global crewing solutions.';
require __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 600px; margin: 3rem auto; padding: 0 1rem;">
    <div style="background: #fff; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #cbd5e1;">
        
        <div style="text-align: center; margin-bottom: 2rem;">
            <span style="font-size: 2.5rem;">🚢</span>
            <h1 style="font-size: 1.8rem; color: #0a2540; margin-top: 0.5rem; margin-bottom: 0.3rem;">Shipowner Registration</h1>
            <p style="color: #64748b; font-size: .95rem; margin: 0;">Partner with Nuzex Maritime for global crew placement and structured billing.</p>
        </div>

        <?php if ($success): ?>
            <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: .9rem; border: 1px solid #c3e6cb;">
                ✅ <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: .9rem; border: 1px solid #f5c6cb;">
                ⚠️ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-weight: 600; font-size: .85rem; color: #1e293b; margin-bottom: .4rem;">Company Name *</label>
                <input type="text" name="company_name" required placeholder="e.g. Global Shipping Line Ltd." style="width: 100%; padding: .7rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: .95rem;">
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-weight: 600; font-size: .85rem; color: #1e293b; margin-bottom: .4rem;">Contact Person Name *</label>
                <input type="text" name="contact_person" required placeholder="e.g. Capt. James Robert" style="width: 100%; padding: .7rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: .95rem;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.2rem;">
                <div>
                    <label style="display: block; font-weight: 600; font-size: .85rem; color: #1e293b; margin-bottom: .4rem;">Corporate Email *</label>
                    <input type="email" name="email" required placeholder="name@company.com" style="width: 100%; padding: .7rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: .95rem;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; font-size: .85rem; color: #1e293b; margin-bottom: .4rem;">Phone Number</label>
                    <input type="text" name="phone" placeholder="+1 (555) 019-2834" style="width: 100%; padding: .7rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: .95rem;">
                </div>
            </div>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; font-weight: 600; font-size: .85rem; color: #1e293b; margin-bottom: .4rem;">Office Address</label>
                <textarea name="address" rows="2" placeholder="Street, City, Country" style="width: 100%; padding: .7rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: .95rem;"></textarea>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; font-size: .85rem; color: #1e293b; margin-bottom: .4rem;">Password *</label>
                <input type="password" name="password" required placeholder="Create a secure password" style="width: 100%; padding: .7rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: .95rem;">
            </div>

            <button type="submit" style="width: 100%; background: #0a2540; color: #fff; border: none; padding: .8rem; font-weight: 600; border-radius: 6px; cursor: pointer; font-size: 1rem;">Register Company Profile</button>
        </form>

        <div style="text-align: center; margin-top: 1.5rem; font-size: .9rem; color: #64748b;">
            Already have an account? <a href="/login.php" style="color: #0fb5ae; text-decoration: none; font-weight: 600;">Log in here</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>