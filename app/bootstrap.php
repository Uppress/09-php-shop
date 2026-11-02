<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

const BASE_URL = '/swd/09-php-shop';

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'swd_shop';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPassword = getenv('DB_PASSWORD') ?: '';

$pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPassword,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(string|int|float $amount): string
{
    return '₦' . number_format((float) $amount, 2);
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function require_valid_csrf(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        http_response_code(400);
        exit('Invalid form token. Please reload the page and try again.');
    }
}

function cart_count(): int
{
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        return 0;
    }

    $count = 0;
    foreach ($cart as $productId => $quantity) {
        if (filter_var((string) $productId, FILTER_VALIDATE_INT) !== false && (int) $productId > 0 && is_int($quantity) && $quantity > 0) {
            $count += $quantity;
        }
    }

    return $count;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) && isset($flash['type'], $flash['message']) ? $flash : null;
}
