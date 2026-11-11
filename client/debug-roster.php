<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

echo "<h2>Session Shipowner Data:</h2>";
echo "<pre>";
print_r(current_shipowner());
echo "</pre>";

echo "<h2>All Shipowners in DB:</h2>";
$sos = $pdo->query("SELECT * FROM shipowners")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($sos);
echo "</pre>";

echo "<h2>All Crew Deployments in DB:</h2>";
$cds = $pdo->query("SELECT * FROM crew_deployments")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($cds);
echo "</pre>";

echo "<h2>All Invoices in DB:</h2>";
$invs = $pdo->query("SELECT id, invoice_number, shipowner_id, total_amount FROM invoices")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($invs);
echo "</pre>";
exit;