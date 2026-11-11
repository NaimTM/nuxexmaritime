<?php
// Nuzex Maritime — Safe Self-Healing Database Configuration
ini_set('display_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', '3306');
if (!defined('DB_NAME')) define('DB_NAME', 'nuzexmaritime'); // Corrected back to your original database name
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

if (!defined('SITE_NAME')) define('SITE_NAME', 'Nuzex Maritime');
if (!defined('SITE_URL')) define('SITE_URL', 'http://nuzexmaritime.test');
if (!defined('SITE_EMAIL')) define('SITE_EMAIL', 'info@nuzexmaritime.com');
if (!defined('WHATSAPP')) define('WHATSAPP', '+8801XXXXXXXXX');
if (!defined('UPLOAD_CV')) define('UPLOAD_CV', __DIR__ . '/../uploads/cv/');
if (!defined('PRIVATE_UPLOAD_DIR')) define('PRIVATE_UPLOAD_DIR', getenv('NUZEX_PRIVATE_UPLOAD_DIR') ?: dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'nuzexmaritime_private' . DIRECTORY_SEPARATOR);
if (!defined('UPLOAD_OG')) define('UPLOAD_OG', __DIR__ . '/../uploads/og/');

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    
    // Connect to your database safely
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `" . DB_NAME . "`");

    // Ensure all tables exist safely without touching existing data
    $pdo->exec("CREATE TABLE IF NOT EXISTS `jobs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `slug` VARCHAR(255) NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `rank_title` VARCHAR(100) DEFAULT NULL,
        `vessel_type` VARCHAR(100) DEFAULT NULL,
        `flag` VARCHAR(100) DEFAULT NULL,
        `salary_min` DECIMAL(10,2) DEFAULT 0,
        `salary_max` DECIMAL(10,2) DEFAULT 0,
        `currency` VARCHAR(10) DEFAULT 'USD',
        `contract_length` VARCHAR(100) DEFAULT NULL,
        `joining_date` DATE DEFAULT NULL,
        `joining_port` VARCHAR(255) DEFAULT NULL,
        `likes_count` INT DEFAULT 0,
        `comments_count` INT DEFAULT 0,
        `shares_count` INT DEFAULT 0,
        `status` VARCHAR(50) DEFAULT 'published',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `seafarers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `full_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(50) DEFAULT NULL,
        `date_of_birth` DATE DEFAULT NULL,
        `rank_title` VARCHAR(100) DEFAULT NULL,
        `vessel_experience` VARCHAR(255) DEFAULT NULL,
        `cv_file` VARCHAR(255) DEFAULT NULL,
        `status` VARCHAR(50) DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `shipowners` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `company_name` VARCHAR(255) NOT NULL,
        `contact_person` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `phone` VARCHAR(50) DEFAULT NULL,
        `address` TEXT DEFAULT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `status` VARCHAR(50) DEFAULT 'pending',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `crew_deployments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `shipowner_id` INT NOT NULL,
        `seafarer_id` INT NOT NULL,
        `vessel_name` VARCHAR(255) NOT NULL,
        `rank_title` VARCHAR(100) DEFAULT NULL,
        `sign_on_date` DATE DEFAULT NULL,
        `sign_off_date` DATE DEFAULT NULL,
        `status` VARCHAR(50) DEFAULT 'active',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `invoices` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `invoice_number` VARCHAR(100) NOT NULL,
        `shipowner_id` INT NOT NULL,
        `billing_month` VARCHAR(100) NOT NULL,
        `total_amount` DECIMAL(10,2) DEFAULT 0.00,
        `status` VARCHAR(50) DEFAULT 'unpaid',
        `issued_date` DATE DEFAULT NULL,
        `paid_date` DATE DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `invoice_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `invoice_id` INT NOT NULL,
        `description` VARCHAR(255) NOT NULL,
        `amount` DECIMAL(10,2) DEFAULT 0.00,
        `crew_deployment_id` INT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `applications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `job_id` INT NOT NULL,
        `seafarer_id` INT NOT NULL,
        `rank_title` VARCHAR(100) DEFAULT NULL,
        `status` VARCHAR(50) DEFAULT 'applied',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `sea_service_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `seafarer_id` INT NOT NULL,
        `vessel_name` VARCHAR(255) NOT NULL,
        `vessel_type` VARCHAR(100) DEFAULT NULL,
        `engine_type` VARCHAR(100) DEFAULT NULL,
        `grt` DECIMAL(10,2) DEFAULT NULL,
        `rank_held` VARCHAR(100) NOT NULL,
        `sign_on_date` DATE NOT NULL,
        `sign_off_date` DATE NOT NULL,
        `total_days` INT DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Automated Migration Checks
    $checkCol = $pdo->query("SHOW COLUMNS FROM `crew_deployments` LIKE 'sign_off_date'")->fetch();
    if (!$checkCol) {
        $pdo->exec("ALTER TABLE `crew_deployments` ADD COLUMN `sign_off_date` DATE NULL");
    }

    $checkDob = $pdo->query("SHOW COLUMNS FROM `seafarers` LIKE 'date_of_birth'")->fetch();
    if (!$checkDob) {
        $pdo->exec("ALTER TABLE `seafarers` ADD COLUMN `date_of_birth` DATE NULL");
    }

} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed: ' . $e->getMessage());
}