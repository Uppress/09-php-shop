<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}

require_valid_csrf();
unset($_SESSION['admin_authenticated']);
session_regenerate_id(true);
set_flash('success', 'You have been signed out.');
header('Location: ' . BASE_URL . '/admin/login.php');
exit;
