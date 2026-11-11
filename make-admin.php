<?php
/**
 * Nuzex Maritime — Enterprise Schema & Billing Synchronizer
 */
require_once __DIR__ . '/config/database.php';

$email = 'admin@nuzexmaritime.test';
$password = password_hash('AdminSecure123!', PASSWORD_DEFAULT);
$fullName = 'System Administrator';

try {
    // 1. Ensure ALL core system & new enterprise billing tables exist safely
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS crew_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            seafarer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS comments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            comment_text TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            seafarer_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS seafarers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS seafarer_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            seafarer_id INT NOT NULL,
            doc_type VARCHAR(100) NOT NULL,
            document_type VARCHAR(100) NULL,
            file_path VARCHAR(255) NOT NULL,
            expiry_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        -- NEW: Shipowner Client Accounts
        CREATE TABLE IF NOT EXISTS shipowners (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_name VARCHAR(150) NOT NULL,
            contact_person VARCHAR(150) NOT NULL,
            email VARCHAR(150) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NULL,
            billing_address TEXT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        -- NEW: Active Crew Deployments (Joined Crews)
        CREATE TABLE IF NOT EXISTS crew_deployments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            shipowner_id INT NOT NULL,
            seafarer_id INT NOT NULL,
            vessel_name VARCHAR(150) NOT NULL,
            rank_title VARCHAR(100) NOT NULL,
            sign_on_date DATE NOT NULL,
            contract_months INT DEFAULT 6,
            status VARCHAR(50) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        -- NEW: Client Invoices Ledger
        CREATE TABLE IF NOT EXISTS invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(50) UNIQUE NOT NULL,
            shipowner_id INT NOT NULL,
            billing_month VARCHAR(50) NOT NULL,
            total_amount DECIMAL(10,2) DEFAULT 0.00,
            status VARCHAR(50) DEFAULT 'unpaid',
            issued_date DATE NOT NULL,
            paid_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        -- NEW: Itemized Invoice Line Items (Manning fees, working gear, etc.)
        CREATE TABLE IF NOT EXISTS invoice_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            description VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            crew_deployment_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2. Synchronize columns for existing tables
    $jobCols = $pdo->query("SHOW COLUMNS FROM jobs")->fetchAll(PDO::FETCH_COLUMN);
    $requiredJobs = [
        'rank_title'     => "VARCHAR(100) NULL",
        'vessel_type'    => "VARCHAR(100) NULL",
        'description'    => "TEXT NULL",
        'requirements'   => "TEXT NULL",
        'status'         => "VARCHAR(50) DEFAULT 'published'",
        'slug'           => "VARCHAR(191) UNIQUE NULL",
        'likes_count'    => "INT DEFAULT 0",
        'comments_count' => "INT DEFAULT 0",
        'views_count'    => "INT DEFAULT 0"
    ];
    foreach ($requiredJobs as $col => $definition) {
        if (!in_array($col, $jobCols)) {
            $pdo->exec("ALTER TABLE jobs ADD COLUMN `$col` $definition");
        }
    }

    $appCols = $pdo->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('cv_path', $appCols)) {
        $pdo->exec("ALTER TABLE applications ADD COLUMN cv_path VARCHAR(255) NULL");
    }

    // 3. Verify or Create Admin User
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin) {
        $update = $pdo->prepare("UPDATE admins SET password_hash = ?, full_name = ? WHERE email = ?");
        $update->execute([$password, $fullName, $email]);
        $message = "Enterprise database schema synchronized successfully! Billing and shipowner tables added.";
    } else {
        $insert = $pdo->prepare("INSERT INTO admins (full_name, email, password_hash, created_at) VALUES (?, ?, ?, NOW())");
        $insert->execute([$fullName, $email, $password]);
        $message = "Database fully provisioned with shipowner billing capabilities!";
    }

    echo "<div style='font-family:Arial; padding:30px; max-width:600px; margin:50px auto; background:#f4f9f4; border:1px solid #d4edda; border-radius:8px;'>";
    echo "<h2 style='color:#155724;'>{$message}</h2>";
    echo "<p><strong>Admin Email:</strong> {$email}</p>";
    echo "<br><a href='/admin/login.php' style='background:#007bff; color:#fff; padding:10px 20px; text-decoration:none; border-radius:4px;'>Go to Admin Portal →</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='font-family:Arial; padding:30px; max-width:600px; margin:50px auto; background:#f8d7da; border:1px solid #f5c6cb; border-radius:8px;'>";
    echo "<h3 style='color:#721c24;'>Database Error Encountered</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div>";
}
?>