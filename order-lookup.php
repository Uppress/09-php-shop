<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$reference = '';
$email = '';
$order = false;
$items = [];
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $rawReference = $_POST['reference'] ?? '';
    $rawEmail = $_POST['email'] ?? '';
    $reference = is_string($rawReference) ? strtoupper(trim($rawReference)) : '';
    $email = is_string($rawEmail) ? trim($rawEmail) : '';
    $submitted = true;

    if ($reference !== '' && strlen($reference) <= 20 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 160) {
        $statement = $pdo->prepare(
            'SELECT id, reference, customer_name, total, status, created_at
             FROM orders
             WHERE reference = :reference AND email = :email
             LIMIT 1'
        );
        $statement->execute(['reference' => $reference, 'email' => $email]);
        $order = $statement->fetch();

        if ($order) {
            $itemStatement = $pdo->prepare(
                'SELECT product_name, unit_price, quantity
                 FROM order_items
                 WHERE order_id = :order_id
                 ORDER BY product_name'
            );
            $itemStatement->execute(['order_id' => $order['id']]);
            $items = $itemStatement->fetchAll();
        }
    }
}

$pageTitle = 'Track your order';
require __DIR__ . '/partials/header.php';
?>
<section class="lookup-section">
    <div class="section-heading cart-heading">
        <div>
            <p class="eyebrow">ORDER UPDATES</p>
            <h1>Track your order</h1>
        </div>
        <a class="back-link" href="<?= e(BASE_URL) ?>/index.php">&larr; Back to shop</a>
    </div>
    <p class="lookup-intro">Enter the reference from your confirmation and the email address used at checkout.</p>

    <div class="lookup-layout">
        <form class="checkout-form lookup-form" method="post" action="<?= e(BASE_URL) ?>/order-lookup.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label class="checkout-field" for="reference">
                <span>Order reference</span>
                <input id="reference" name="reference" type="text" maxlength="20" placeholder="ORD-20261102-ABCD" value="<?= e($reference) ?>" required>
            </label>
            <label class="checkout-field" for="email">
                <span>Email address</span>
                <input id="email" name="email" type="email" maxlength="160" autocomplete="email" value="<?= e($email) ?>" required>
            </label>
            <button class="button button-primary place-order-button" type="submit">Find my order <span aria-hidden="true">&rarr;</span></button>
        </form>

        <?php if ($submitted && !$order): ?>
            <p class="notice notice-warning lookup-result" role="status">We couldn't find an order matching that reference and email. Check the details and try again.</p>
        <?php elseif ($order): ?>
            <section class="admin-panel lookup-result" aria-live="polite">
                <div class="admin-panel-heading">
                    <div>
                        <p class="eyebrow">ORDER FOUND</p>
                        <h2><?= e($order['reference']) ?></h2>
                    </div>
                    <span class="status-pill status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span>
                </div>
                <p class="lookup-order-meta">Placed <?= e(date('M j, Y', strtotime($order['created_at']))) ?> · <?= e(money($order['total'])) ?></p>
                <div class="lookup-items">
                    <?php foreach ($items as $item): ?>
                        <div class="lookup-item">
                            <span><?= e($item['product_name']) ?> <small>× <?= (int) $item['quantity'] ?></small></span>
                            <strong><?= e(money((float) $item['unit_price'] * (int) $item['quantity'])) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
