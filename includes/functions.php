<?php
declare(strict_types=1);

// Unified absolute session path to prevent session dropping & permission crashes
$sessionPath = dirname(__DIR__) . '/storage/sessions';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0777, true);
}
@session_save_path($sessionPath);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(?string $v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function slugify(string $t): string {
    $t = strtolower(trim($t));
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    return trim($t, '-');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): void {
    if (!$token || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        die('CSRF token mismatch.');
    }
}

function current_seafarer(): ?array {
    if (empty($_SESSION['seafarer']['id'])) {
        return null;
    }
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM seafarers WHERE id = ?");
        $stmt->execute([$_SESSION['seafarer']['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    } catch (Exception $e) {
        return $_SESSION['seafarer'] ?? null;
    }
}

function current_admin(): ?array {
    return $_SESSION['admin'] ?? null;
}

function current_shipowner(): ?array {
    return $_SESSION['shipowner'] ?? null;
}

function is_seafarer(): bool { return !empty($_SESSION['seafarer']); }
function is_admin(): bool    { return !empty($_SESSION['admin']); }
function is_shipowner(): bool { return !empty($_SESSION['shipowner']); }

function require_seafarer(): void {
    if (!is_seafarer()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function require_admin(): void {
    if (!is_admin()) {
        header('Location: /login.php');
        exit;
    }
}

function require_shipowner(): void {
    if (!is_shipowner()) {
        header('Location: /login.php');
        exit;
    }
}

function redirect(string $to): void {
    header('Location: ' . $to);
    exit;
}

function flash(string $key, ?string $msg = null): ?string {
    if ($msg !== null) { $_SESSION['flash'][$key] = $msg; return null; }
    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $v;
}

function time_ago(string $ts): string {
    $diff = time() - strtotime($ts);
    if ($diff < 60)       return 'just now';
    if ($diff < 3600)     return floor($diff/60) . 'm ago';
    if ($diff < 86400)    return floor($diff/3600) . 'h ago';
    if ($diff < 2592000)  return floor($diff/86400) . 'd ago';
    return date('M j, Y', strtotime($ts));
}

function money(?float $v, string $c = 'USD'): string {
    if ($v === null) return '—';
    return $c . ' ' . number_format($v, 0);
}