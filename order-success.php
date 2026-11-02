<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$reference = isset($_GET['ref']) && is_string($_GET['ref']) ? trim($_GET['ref']) : '';
$order = false;

if (preg_match('/^ORD-\d{8}-[A-Z2-9]{4}$/D', $reference)) {
    $statement = $pdo->prepare('SELECT reference, created_at FROM orders WHERE reference = :reference LIMIT 1');
    $statement->execute(['reference' => $reference]);
    $order = $statement->fetch();
}

if (!$order) {
    http_response_code(404);
}

$pageTitle = $order ? 'Order confirmed' : 'Order not found';
require __DIR__ . '/partials/header.php';
?>
<section class="order-confirmation">
    <?php if ($order): ?>
        <span class="confirmation-icon" aria-hidden="true">✓</span>
        <p class="eyebrow">ORDER RECEIVED</p>
        <h1>Thank you for your order.</h1>
        <p class="confirmation-copy">Your order has been placed. Keep this reference for your records.</p>
        <div class="reference-card">
            <span>Order reference</span>
            <strong><?= e($order['reference']) ?></strong>
        </div>
        <a class="button button-primary" href="<?= e(BASE_URL) ?>/index.php">Continue shopping <span aria-hidden="true">&rarr;</span></a>
    <?php else: ?>
        <span class="confirmation-icon" aria-hidden="true">?</span>
        <p class="eyebrow">ORDER LOOKUP</p>
        <h1>We couldn't find that order.</h1>
        <p class="confirmation-copy">Check the reference in your confirmation link, or return to the shop.</p>
        <a class="button button-primary" href="<?= e(BASE_URL) ?>/index.php">Back to the shop <span aria-hidden="true">&rarr;</span></a>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
