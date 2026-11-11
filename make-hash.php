<?php
// TEMPORARY FILE — DELETE AFTER USING
$password = $_GET['p'] ?? 'AdminPass123!';
echo '<pre style="font-size:18px;padding:20px">';
echo 'Password: ' . htmlspecialchars($password) . "\n\n";
echo 'Hash (copy everything below):' . "\n\n";
echo password_hash($password, PASSWORD_DEFAULT);
echo '</pre>';
echo '<p style="padding:0 20px;color:#a00"><strong>⚠ Delete this file immediately after copying the hash.</strong></p>';