<?php
$pageTitle = $pageTitle ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | SWD Shop Admin</title>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner admin-header-inner">
        <a class="brand" href="<?= e(BASE_URL) ?>/admin/index.php">
            <span class="brand-mark">S</span>
            <span> SWD<span class="brand-light">ADMIN</span></span>
        </a>
        <nav class="main-nav admin-nav" aria-label="Admin navigation">
            <a href="<?= e(BASE_URL) ?>/admin/index.php">Dashboard</a>
            <a href="<?= e(BASE_URL) ?>/admin/orders.php">Orders</a>
            <a href="<?= e(BASE_URL) ?>/index.php">View shop</a>
            <form class="admin-logout-form" method="post" action="<?= e(BASE_URL) ?>/admin/logout.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="admin-nav-button" type="submit">Log out</button>
            </form>
        </nav>
    </div>
</header>
<main class="page-shell">
