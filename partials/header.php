<?php
$pageTitle = $pageTitle ?? 'Swd Shop';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8fa">
    <title><?= e($pageTitle) ?> | Swd Shop</title>
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?= e(BASE_URL) ?>/index.php" aria-label="Swd Shop home">
            <span class="brand-mark">S</span>
            <span> SWD<span class="brand-light">SHOP</span></span>
        </a>
        <nav class="main-nav" aria-label="Main navigation">
            <a href="<?= e(BASE_URL) ?>/index.php">Shop</a>
            <a class="cart-link" href="<?= e(BASE_URL) ?>/cart.php">
                Cart <span class="cart-count" aria-label="<?= cart_count() ?> items in cart"><?= cart_count() ?></span>
            </a>
        </nav>
    </div>
</header>
<main class="page-shell">
