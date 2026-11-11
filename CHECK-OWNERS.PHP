<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Registered Shipowners in Database:</h2>";
$stmt = $pdo->query("SELECT id, company_name, email, status FROM shipowners");
$owners = $stmt->fetchAll();

if (empty($owners)) {
    echo "<p style='color:red;'><strong>No shipowners found in the database!</strong> (This explains why the login failed).</p>";
    
    // Auto-create your test shipowner safely without touching anything else
    $test_pass = password_hash('password123', PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO shipowners (company_name, contact_person, email, password_hash, status) VALUES (?, ?, ?, ?, ?)");
    $ins->execute(['Global Shipping Ltd', 'John Captain', 'owner@globalshipping.test', $test_pass, 'active']);
    echo "<p style='color:green;'>✅ Created test account! You can now log in using:</p>";
    echo "<ul><li><strong>Email:</strong> owner@globalshipping.test</li><li><strong>Password:</strong> password123</li></ul>";
} else {
    echo "<ul>";
    foreach ($owners as $o) {
        echo "<li>ID: {$o['id']} | Company: <strong>{$o['company_name']}</strong> | Email: <strong>{$o['email']}</strong> | Status: {$o['status']}</li>";
    }
    echo "</ul>";
}
?>