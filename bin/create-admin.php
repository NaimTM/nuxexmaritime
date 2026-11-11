<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/database.php';
if ($argc < 4) {
    fwrite(STDERR, "Usage: php bin/create-admin.php \"Full Name\" email@example.com role\nRoles: super, recruiter, viewer\n");
    exit(2);
}
$name = trim($argv[1]);
$email = strtolower(trim($argv[2]));
$role = $argv[3];
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['super','recruiter','viewer'], true)) {
    fwrite(STDERR, "Invalid name, email, or role.\n"); exit(2);
}
fwrite(STDOUT, 'Enter a strong password (minimum 14 characters): ');
if (PHP_OS_FAMILY === 'Windows') {
    $password = trim((string)fgets(STDIN));
} else {
    system('stty -echo');
    $password = rtrim((string)fgets(STDIN), "\r\n");
    system('stty echo');
    fwrite(STDOUT, "\n");
}
if (strlen($password) < 14) { fwrite(STDERR, "Password must be at least 14 characters.\n"); exit(2); }
$stmt = $pdo->prepare('INSERT INTO admins (name, email, password_hash, role) VALUES (?,?,?,?)');
try {
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    fwrite(STDOUT, "Administrator created successfully. Store credentials securely.\n");
} catch (Throwable $e) {
    fwrite(STDERR, "Could not create administrator. Check whether the email already exists and review server logs.\n");
    error_log($e->getMessage()); exit(1);
}
