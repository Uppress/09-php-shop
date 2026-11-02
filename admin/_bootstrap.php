<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

function admin_is_authenticated(): bool
{
    return ($_SESSION['admin_authenticated'] ?? false) === true;
}

function require_admin(): void
{
    if (!admin_is_authenticated()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function admin_statuses(): array
{
    return ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
}

function admin_allowed_transition(string $current, string $next): bool
{
    $transitions = [
        'pending' => ['paid', 'cancelled'],
        'paid' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    return in_array($next, $transitions[$current] ?? [], true);
}
