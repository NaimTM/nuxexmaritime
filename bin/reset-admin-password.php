<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
if ($argc < 2 || !filter_var($argv[1], FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php bin/reset-admin-password.php admin@example.com\n");
    exit(2);
}
$email = strtolower(trim($argv[1]));
$stmt = $pdo->prepare('SELECT id FROM admins WHERE email=?');
$stmt->execute([$email]);
$adminId = $stmt->fetchColumn();
if (!$adminId) { fwrite(STDERR, "No administrator account found for that email.\n"); exit(1); }

fwrite(STDOUT, 'New password (minimum 14 characters): ');
if (PHP_OS_FAMILY === 'Windows') {
    $password = rtrim((string)fgets(STDIN), "\r\n");
} else {
    system('stty -echo');
    $password = rtrim((string)fgets(STDIN), "\r\n");
    system('stty echo');
    fwrite(STDOUT, "\n");
}
if (strlen($password) < 14) { fwrite(STDERR, "Password must be at least 14 characters.\n"); exit(2); }
fwrite(STDOUT, 'Confirm new password: ');
if (PHP_OS_FAMILY === 'Windows') {
    $confirm = rtrim((string)fgets(STDIN), "\r\n");
} else {
    system('stty -echo');
    $confirm = rtrim((string)fgets(STDIN), "\r\n");
    system('stty echo');
    fwrite(STDOUT, "\n");
}
if (!hash_equals($password, $confirm)) { fwrite(STDERR, "Passwords do not match.\n"); exit(2); }
$update = $pdo->prepare('UPDATE admins SET password_hash=? WHERE id=?');
$update->execute([password_hash($password, PASSWORD_DEFAULT), (int)$adminId]);
fwrite(STDOUT, "Administrator password updated successfully.\n");
