<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, full_name, rank 
        FROM seafarers 
        WHERE full_name LIKE ? 
        LIMIT 10
    ");
    $stmt->execute(["%$query%"]);
    $seafarers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($seafarers);
} catch (Exception $e) {
    echo json_encode([]);
}