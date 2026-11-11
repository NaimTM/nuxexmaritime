<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$checks = [
  'PHP >= 8.2' => version_compare(PHP_VERSION, '8.2.0', '>='),
  'PDO extension' => extension_loaded('pdo'),
  'PDO MySQL driver' => extension_loaded('pdo_mysql'),
  'Fileinfo extension' => extension_loaded('fileinfo'),
  'JSON extension' => extension_loaded('json'),
];
$failed = false;
foreach ($checks as $label => $ok) { echo ($ok ? '[OK]   ' : '[FAIL] ') . $label . PHP_EOL; if (!$ok) $failed = true; }
if (getenv('DB_PASS') && getenv('DB_PASS') !== 'CHANGE_THIS_DATABASE_PASSWORD') {
  try { require __DIR__ . '/../config/database.php'; echo "[OK]   Database connection\n"; }
  catch (Throwable $e) { echo "[FAIL] Database connection; inspect server logs\n"; $failed = true; }
} else { echo "[WARN] DB_PASS is not set to a configured secret; database connection not attempted.\n"; }
exit($failed ? 1 : 0);
