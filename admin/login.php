<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

if (($_SESSION['admin_authenticated'] ?? false) === true) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$errorMessage = null;
$flash = take_flash();
$configuredUsername = getenv('ADMIN_USERNAME');
$configuredPasswordHash = getenv('ADMIN_PASSWORD_HASH');
$isConfigured = is_string($configuredUsername)
    && $configuredUsername !== ''
    && is_string($configuredPasswordHash)
    && password_get_info($configuredPasswordHash)['algo'] !== null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$isConfigured) {
        $errorMessage = 'Admin login is not configured. Follow the setup instructions in README.md.';
    } elseif (
        is_string($username)
        && is_string($password)
        && hash_equals($configuredUsername, $username)
        && password_verify($password, $configuredPasswordHash)
    ) {
        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        header('Location: ' . BASE_URL . '/admin/index.php');
        exit;
    } else {
        $errorMessage = 'The username or password was not correct.';
    }
}

$pageTitle = 'Admin login';
require __DIR__ . '/_login_header.php';
?>
<section class="admin-login-section">
    <form class="checkout-form admin-login-form" method="post" action="<?= e(BASE_URL) ?>/admin/login.php">
        <p class="eyebrow">STORE MANAGEMENT</p>
        <h1>Admin sign in</h1>
        <p class="admin-login-intro">Sign in to manage orders and view store activity.</p>
        <?php if ($flash !== null): ?>
            <p class="notice notice-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
        <?php endif; ?>
        <?php if ($errorMessage !== null): ?>
            <p class="notice notice-error" role="alert"><?= e($errorMessage) ?></p>
        <?php endif; ?>
        <?php if (!$isConfigured): ?>
            <p class="notice notice-warning" role="status">Set <code>ADMIN_USERNAME</code> and a <code>password_hash()</code>-generated <code>ADMIN_PASSWORD_HASH</code> in Apache before signing in.</p>
        <?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="checkout-fields admin-login-fields">
            <label class="checkout-field" for="username">
                <span>Username</span>
                <input id="username" name="username" type="text" autocomplete="username" required>
            </label>
            <label class="checkout-field" for="password">
                <span>Password</span>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </label>
        </div>
        <button class="button button-primary place-order-button" type="submit">Sign in <span aria-hidden="true">&rarr;</span></button>
        <a class="back-link" href="<?= e(BASE_URL) ?>/index.php">&larr; Back to shop</a>
    </form>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
