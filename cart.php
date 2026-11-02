<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/cart.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $action = $_POST['action'] ?? '';
    $rawProductId = $_POST['product_id'] ?? null;
    $productId = is_string($rawProductId) ? filter_var($rawProductId, FILTER_VALIDATE_INT) : false;

    if ($productId === false || $productId < 1) {
        set_flash('warning', 'Choose a valid cart item.');
    } elseif ($action === 'update') {
        $rawQuantity = $_POST['quantity'] ?? null;
        $quantity = is_string($rawQuantity) ? filter_var($rawQuantity, FILTER_VALIDATE_INT) : false;

        if ($quantity === false || $quantity < 1) {
            set_flash('warning', 'Choose a quantity of at least 1.');
        } else {
            $notice = cart_update($productId, $quantity);
            set_flash($notice === null ? 'success' : 'warning', $notice ?? 'Cart quantity updated.');
        }
    } elseif ($action === 'remove') {
        cart_remove($productId);
        set_flash('success', 'Item removed from your cart.');
    } else {
        set_flash('warning', 'That cart action is not available.');
    }

    header('Location: ' . BASE_URL . '/cart.php');
    exit;
}

$items = cart_items();
$flash = take_flash();
$subtotal = cart_total();
$pageTitle = 'Your cart';
require __DIR__ . '/partials/header.php';
?>
<section class="cart-section">
    <div class="section-heading cart-heading">
        <div>
            <p class="eyebrow">YOUR SELECTION</p>
            <h1>Your shopping cart</h1>
        </div>
        <a class="back-link" href="<?= e(BASE_URL) ?>/index.php">&larr; Continue shopping</a>
    </div>

    <?php if ($flash !== null): ?>
        <p class="notice notice-<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'warning' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></p>
    <?php endif; ?>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true">⌑</span>
            <h2>Your cart is waiting</h2>
            <p>Find something you love and it will show up here.</p>
            <a class="button button-primary" href="<?= e(BASE_URL) ?>/index.php">Browse the shop <span aria-hidden="true">&rarr;</span></a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <div class="cart-table-wrap">
                <table class="cart-table">
                    <thead>
                    <tr>
                        <th scope="col">Product</th>
                        <th scope="col">Price</th>
                        <th scope="col">Quantity</th>
                        <th scope="col">Total</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td data-label="Product">
                                <div class="cart-product">
                                    <a class="cart-product-image" href="<?= e(BASE_URL) ?>/product.php?slug=<?= rawurlencode($item['slug']) ?>">
                                        <img src="<?= e($item['image_url'] ?: 'https://placehold.co/600x400?text=Product') ?>" alt="">
                                    </a>
                                    <div>
                                        <a class="cart-product-name" href="<?= e(BASE_URL) ?>/product.php?slug=<?= rawurlencode($item['slug']) ?>"><?= e($item['name']) ?></a>
                                        <span class="cart-stock"><?= (int) $item['stock'] ?> in stock</span>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Price"><?= e(money($item['price'])) ?></td>
                            <td data-label="Quantity">
                                <form class="cart-update-form" id="update-<?= (int) $item['product_id'] ?>" method="post" action="<?= e(BASE_URL) ?>/cart.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                    <label class="sr-only" for="quantity-<?= (int) $item['product_id'] ?>">Quantity for <?= e($item['name']) ?></label>
                                    <input
                                        class="quantity-input cart-quantity"
                                        id="quantity-<?= (int) $item['product_id'] ?>"
                                        form="update-<?= (int) $item['product_id'] ?>"
                                        type="number"
                                        name="quantity"
                                        min="1"
                                        max="<?= (int) $item['stock'] ?>"
                                        value="<?= (int) $item['quantity'] ?>"
                                        required
                                    >
                                </form>
                            </td>
                            <td data-label="Total" class="cart-line-total"><?= e(money($item['line_total'])) ?></td>
                            <td data-label="Actions">
                                <div class="cart-actions">
                                    <button class="button button-dark cart-small-button" type="submit" form="update-<?= (int) $item['product_id'] ?>">Update</button>
                                    <form method="post" action="<?= e(BASE_URL) ?>/cart.php">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                        <button class="remove-button" type="submit">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <aside class="cart-summary">
                <h2>Order summary</h2>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong><?= e(money($subtotal)) ?></strong>
                </div>
                <p class="summary-note">Shipping and any applicable fees are calculated at checkout.</p>
                <a class="button button-primary checkout-button" href="<?= e(BASE_URL) ?>/checkout.php">
                    Proceed to checkout <span aria-hidden="true">&rarr;</span>
                </a>
                <p class="secure-note"><span aria-hidden="true">✓</span> Your order total uses current product prices.</p>
            </aside>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
