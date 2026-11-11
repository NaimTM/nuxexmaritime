<?php
require_once __DIR__ . '/../config/database.php';

try {
    $email = 'owner@globalshipping.test';
    // Password is 'password'
    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    $stmt = $pdo->prepare("SELECT id FROM shipowners WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo "<div style='font-family:Arial; padding:30px; text-align:center;'><h3>Test shipowner account already exists!</h3><br><a href='/client/login.php'>Go to Client Login →</a></div>";
    } else {
        $ins = $pdo->prepare("
            INSERT INTO shipowners (company_name, contact_person, email, password_hash, status, created_at) 
            VALUES ('Global Shipping Line', 'Captain Arthur', ?, ?, 'active', NOW())
        ");
        $ins->execute([$email, $hash]);
        echo "<div style='font-family:Arial; padding:30px; text-align:center;'><h3 style='color:green;'>Test shipowner account created successfully!</h3><br><a href='/client/login.php' style='background:#0a2540; color:#fff; padding:10px 20px; text-decoration:none; border-radius:4px;'>Go to Client Login →</a></div>";
    }
} catch (Exception $e) {
    echo "<div style='font-family:Arial; padding:30px; color:red;'><h3>Error:</h3> " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>