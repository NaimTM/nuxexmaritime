<?php
declare(strict_types=1);

// Configure these values in your hosting environment. Do not commit production secrets.
function env_value(string $key, string $default = ''): string {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

if (!defined('APP_ENV')) define('APP_ENV', env_value('APP_ENV', 'production'));
if (!defined('SITE_NAME')) define('SITE_NAME', 'Nuzex Maritime');
if (!defined('SITE_URL')) define('SITE_URL', rtrim(env_value('SITE_URL', 'http://localhost'), '/'));
if (!defined('SITE_EMAIL')) define('SITE_EMAIL', env_value('SITE_EMAIL', 'info@nuzexmaritime.com'));
if (!defined('WHATSAPP')) define('WHATSAPP', env_value('WHATSAPP', ''));
if (!defined('DB_HOST')) define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
if (!defined('DB_NAME')) define('DB_NAME', env_value('DB_NAME', 'nuzex_maritime'));
if (!defined('DB_USER')) define('DB_USER', env_value('DB_USER', 'nuzex_app'));
if (!defined('DB_PASS')) define('DB_PASS', env_value('DB_PASS', 'CHANGE_THIS_DATABASE_PASSWORD'));
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Prefer a directory outside public_html. If not configured, the bundled .htaccess
// blocks direct Apache access to this fallback directory.
$privateStorage = env_value('CV_STORAGE_DIR', dirname(__DIR__) . '/private_uploads/cv');
if (!defined('UPLOAD_CV')) define('UPLOAD_CV', rtrim($privateStorage, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
if (!defined('UPLOAD_OG')) define('UPLOAD_OG', __DIR__ . '/../uploads/og/');

if (APP_ENV !== 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

try {
    if (DB_PASS === 'CHANGE_THIS_DATABASE_PASSWORD') {
        throw new RuntimeException('Database credentials have not been configured.');
    }
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ]);
} catch (Throwable $e) {
    error_log('[Nuzex Maritime] Database initialization failed: ' . $e->getMessage());
    http_response_code(500);
    if (APP_ENV !== 'production') {
        echo 'Database initialization failed. Check server logs and configuration.';
    } else {
        echo 'The website is temporarily unavailable. Please try again later.';
    }
    exit;
}
