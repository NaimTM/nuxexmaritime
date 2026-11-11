<?php
// Change DB_NAME here to test your previous database name
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'nuzexmaritime'); // without the underscore
define('DB_USER', 'root');
define('DB_PASS', '');

$dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
$pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

echo "<h2>Connected to Database: " . DB_NAME . "</h2>";

$seafarers = $pdo->query("SELECT id, full_name, email FROM seafarers")->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Seafarers (" . count($seafarers) . "):</h3>";
echo "<pre>" . print_r($seafarers, true) . "</pre>";

$shipowners = $pdo->query("SELECT id, company_name, email FROM shipowners")->fetchAll(PDO::FETCH_ASSOC);
echo "<h3>Shipowners (" . count($shipowners) . "):</h3>";
echo "<pre>" . print_r($shipowners, true) . "</pre>";
?>