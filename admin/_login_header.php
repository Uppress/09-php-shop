<?php
$pageTitle = $pageTitle ?? 'Admin login';
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
    <div class="header-inner">
        <a class="brand" href="<?= e(BASE_URL) ?>/index.php">
            <span class="brand-mark">S</span>
            <span> SWD<span class="brand-light">ADMIN</span></span>
        </a>
        <a class="back-link admin-shop-link" href="<?= e(BASE_URL) ?>/index.php">View shop</a>
    </div>
</header>
<main class="page-shell">
