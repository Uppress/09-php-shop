<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/cart.php';

final class CheckoutFailure extends RuntimeException
{
}

function checkout_reference(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $suffix = '';
    for ($index = 0; $index < 4; $index++) {
        $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    return 'ORD-' . date('Ymd') . '-' . $suffix;
}

function checkout_post_value(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function checkout_character_length(string $value): int
{
    $count = preg_match_all('/./us', $value);
    return $count === false ? PHP_INT_MAX : $count;
}

function checkout_decimal_total(int $cents): string
{
    return cart_cents_to_decimal($cents);
}

$values = [
    'customer_name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
];
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    foreach ($values as $key => $_) {
        $values[$key] = checkout_post_value($key);
    }

    $nameLength = checkout_character_length($values['customer_name']);
    $emailLength = checkout_character_length($values['email']);
    $phoneLength = checkout_character_length($values['phone']);
    $addressLength = checkout_character_length($values['address']);

    if ($nameLength < 2 || $nameLength > 120) {
        $errorMessage = 'Enter your name using 2 to 120 characters.';
    } elseif ($emailLength > 160 || filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errorMessage = 'Enter a valid email address (maximum 160 characters).';
    } elseif ($phoneLength < 7 || $phoneLength > 20 || !preg_match('/^[0-9+().\-\s]+$/D', $values['phone'])) {
        $errorMessage = 'Enter a valid phone number using 7 to 20 digits or common phone symbols.';
    } elseif ($addressLength < 5 || $addressLength > 5000) {
        $errorMessage = 'Enter a delivery address using 5 to 5000 characters.';
    } else {
        $cart = cart_normalized_data();
        if ($cart === []) {
            $errorMessage = 'Your cart is empty. Add an item before checking out.';
        } else {
            ksort($cart, SORT_NUMERIC);
            try {
                $pdo->beginTransaction();

                $lockProduct = $pdo->prepare(
                    'SELECT id, name, price, stock, is_active
                     FROM products
                     WHERE id = :id
                     FOR UPDATE'
                );
                $lockedProducts = [];
                $totalCents = 0;

                foreach ($cart as $productId => $quantity) {
                    $lockProduct->execute(['id' => $productId]);
                    $product = $lockProduct->fetch();

                    if (!$product || (int) $product['is_active'] !== 1) {
                        unset($_SESSION['cart'][$productId]);
                        throw new CheckoutFailure('A product in your cart is no longer available. Review your cart and try again.');
                    }

                    if ((int) $product['stock'] < $quantity) {
                        if ((int) $product['stock'] < 1) {
                            unset($_SESSION['cart'][$productId]);
                        } else {
                            $_SESSION['cart'][$productId] = (int) $product['stock'];
                        }
                        throw new CheckoutFailure(
                            $product['name'] . ' does not have enough stock for that quantity. Your cart has been updated; please review it and try again.'
                        );
                    }

                    $price = (string) $product['price'];
                    $priceCents = cart_price_to_cents($price);
                    if ($quantity > intdiv(PHP_INT_MAX, max(1, $priceCents))) {
                        throw new CheckoutFailure('Your order total is outside the supported range. Please reduce the quantities and try again.');
                    }
                    $lineCents = $priceCents * $quantity;
                    if ($lineCents > 999999999999 - $totalCents) {
                        throw new CheckoutFailure('Your order total is outside the supported range. Please reduce the quantities and try again.');
                    }

                    $totalCents += $lineCents;
                    $lockedProducts[$productId] = [
                        'name' => $product['name'],
                        'price' => $price,
                        'quantity' => $quantity,
                    ];
                }

                $reference = checkout_reference();
                $insertOrder = $pdo->prepare(
                    'INSERT INTO orders (reference, customer_name, email, phone, address, total)
                     VALUES (:reference, :customer_name, :email, :phone, :address, :total)'
                );
                $insertOrder->execute([
                    'reference' => $reference,
                    'customer_name' => $values['customer_name'],
                    'email' => $values['email'],
                    'phone' => $values['phone'],
                    'address' => $values['address'],
                    'total' => checkout_decimal_total($totalCents),
                ]);
                $orderId = (int) $pdo->lastInsertId();

                $insertOrderItem = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $updateStock = $pdo->prepare(
                    'UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?'
                );

                foreach ($lockedProducts as $productId => $product) {
                    $insertOrderItem->execute([
                        $orderId,
                        $productId,
                        $product['name'],
                        $product['price'],
                        $product['quantity'],
                    ]);
                    $updateStock->execute([
                        $product['quantity'],
                        $productId,
                        $product['quantity'],
                    ]);

                    if ($updateStock->rowCount() !== 1) {
                        throw new CheckoutFailure('Stock changed while placing your order. Please review your cart and try again.');
                    }
                }

                $pdo->commit();
                cart_clear();
                header('Location: ' . BASE_URL . '/order-success.php?ref=' . rawurlencode($reference));
                exit;
            } catch (CheckoutFailure $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errorMessage = $exception->getMessage();
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Checkout failed: ' . $exception->getMessage());
                $errorMessage = 'We could not place your order right now. Your cart has been kept; please try again.';
            }
        }
    }
}

$items = cart_items();
$flash = take_flash();
$subtotal = cart_total();
$pageTitle = 'Checkout';
require __DIR__ . '/partials/header.php';
?>
<section class="checkout-section">
    <div class="section-heading cart-heading">
        <div>
            <p class="eyebrow">ALMOST THERE</p>
            <h1>Checkout</h1>
        </div>
        <a class="back-link" href="<?= e(BASE_URL) ?>/cart.php">&larr; Back to cart</a>
    </div>

    <?php if ($flash !== null): ?>
        <p class="notice notice-<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'warning' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></p>
    <?php endif; ?>
    <?php if ($errorMessage !== null): ?>
        <p class="notice notice-error" role="alert"><?= e($errorMessage) ?></p>
    <?php endif; ?>

    <?php if ($items === []): ?>
        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true">⌑</span>
            <h2>Your cart is empty</h2>
            <p>Add products to your cart before placing an order.</p>
            <a class="button button-primary" href="<?= e(BASE_URL) ?>/index.php">Browse the shop <span aria-hidden="true">&rarr;</span></a>
        </div>
    <?php else: ?>
        <div class="checkout-layout">
            <form class="checkout-form" method="post" action="<?= e(BASE_URL) ?>/checkout.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <h2>Delivery details</h2>
                <div class="checkout-fields">
                    <label class="checkout-field" for="customer_name">
                        <span>Full name</span>
                        <input id="customer_name" name="customer_name" type="text" maxlength="120" autocomplete="name" value="<?= e($values['customer_name']) ?>" required>
                    </label>
                    <label class="checkout-field" for="email">
                        <span>Email address</span>
                        <input id="email" name="email" type="email" maxlength="160" autocomplete="email" value="<?= e($values['email']) ?>" required>
                    </label>
                    <label class="checkout-field" for="phone">
                        <span>Phone number</span>
                        <input id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" value="<?= e($values['phone']) ?>" required>
                    </label>
                    <label class="checkout-field" for="address">
                        <span>Delivery address</span>
                        <textarea id="address" name="address" rows="4" maxlength="5000" autocomplete="street-address" required><?= e($values['address']) ?></textarea>
                    </label>
                </div>
                <button class="button button-primary place-order-button" type="submit">
                    Place order <span aria-hidden="true">&rarr;</span>
                </button>
                <p class="checkout-note">Submitting places your order; payment arrangements can be made afterward.</p>
            </form>

            <aside class="cart-summary checkout-summary">
                <h2>Order summary</h2>
                <div class="checkout-items">
                    <?php foreach ($items as $item): ?>
                        <div class="checkout-item">
                            <span class="checkout-item-quantity"><?= (int) $item['quantity'] ?></span>
                            <div class="checkout-item-copy">
                                <span><?= e($item['name']) ?></span>
                                <small><?= e(money($item['price'])) ?> each</small>
                            </div>
                            <strong><?= e(money($item['line_total'])) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong><?= e(money($subtotal)) ?></strong>
                </div>
                <p class="summary-note">The final total is recalculated from locked, current product prices when you place the order.</p>
            </aside>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
