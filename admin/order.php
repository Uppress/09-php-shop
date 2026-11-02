<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_admin();

final class AdminOrderFailure extends RuntimeException
{
}

$rawOrderId = $_GET['id'] ?? '';
$orderId = is_string($rawOrderId) ? filter_var($rawOrderId, FILTER_VALIDATE_INT) : false;
$order = false;
$items = [];

if ($orderId !== false && $orderId > 0) {
    $orderStatement = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
    $orderStatement->execute(['id' => $orderId]);
    $order = $orderStatement->fetch();
    if ($order) {
        $itemsStatement = $pdo->prepare(
            'SELECT oi.product_id, oi.product_name, oi.unit_price, oi.quantity, p.stock
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :order_id
             ORDER BY oi.product_name'
        );
        $itemsStatement->execute(['order_id' => $orderId]);
        $items = $itemsStatement->fetchAll();
    }
}

if (!$order) {
    http_response_code(404);
}

$errorMessage = null;
if ($order && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $nextStatus = $_POST['status'] ?? '';
    if (!is_string($nextStatus) || !in_array($nextStatus, admin_statuses(), true)) {
        $errorMessage = 'Choose a valid order status.';
    } elseif (!admin_allowed_transition($order['status'], $nextStatus)) {
        $errorMessage = 'That status change is not allowed for this order.';
    } else {
        try {
            $pdo->beginTransaction();
            $lockOrder = $pdo->prepare('SELECT id, status FROM orders WHERE id = :id FOR UPDATE');
            $lockOrder->execute(['id' => $orderId]);
            $lockedOrder = $lockOrder->fetch();

            if (!$lockedOrder || !admin_allowed_transition($lockedOrder['status'], $nextStatus)) {
                throw new AdminOrderFailure('The order status changed or this transition is no longer allowed. Refresh and try again.');
            }

            if ($nextStatus === 'cancelled') {
                $lockItems = $pdo->prepare(
                    'SELECT product_id, quantity
                     FROM order_items
                     WHERE order_id = :order_id
                     ORDER BY product_id
                     FOR UPDATE'
                );
                $lockItems->execute(['order_id' => $orderId]);
                $orderItems = $lockItems->fetchAll();

                $lockProduct = $pdo->prepare('SELECT stock FROM products WHERE id = :id FOR UPDATE');
                $restoreStock = $pdo->prepare(
                    'UPDATE products SET stock = stock + ? WHERE id = ? AND stock <= 4294967295 - ?'
                );

                foreach ($orderItems as $item) {
                    $lockProduct->execute(['id' => $item['product_id']]);
                    if ($lockProduct->fetch() === false) {
                        throw new AdminOrderFailure('A product for this order no longer exists, so stock could not be restored.');
                    }

                    $quantity = (int) $item['quantity'];
                    $restoreStock->execute([$quantity, $item['product_id'], $quantity]);
                    if ($restoreStock->rowCount() !== 1) {
                        throw new AdminOrderFailure('Stock could not be restored safely. No order changes were saved.');
                    }
                }
            }

            $updateOrder = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
            $updateOrder->execute(['status' => $nextStatus, 'id' => $orderId]);
            $pdo->commit();
            set_flash('success', $nextStatus === 'cancelled'
                ? 'Order cancelled and item stock restored.'
                : 'Order status updated to ' . $nextStatus . '.');
            header('Location: ' . BASE_URL . '/admin/order.php?id=' . $orderId);
            exit;
        } catch (AdminOrderFailure $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errorMessage = $exception->getMessage();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Admin order update failed: ' . $exception->getMessage());
            $errorMessage = 'We could not update this order. No changes were saved; please try again.';
        }

        $orderStatement->execute(['id' => $orderId]);
        $order = $orderStatement->fetch();
        if ($order) {
            $itemsStatement->execute(['order_id' => $orderId]);
            $items = $itemsStatement->fetchAll();
        }
    }
}

$flash = take_flash();
$pageTitle = $order ? 'Order ' . $order['reference'] : 'Order not found';
require __DIR__ . '/_header.php';
?>
<section class="admin-section">
    <?php if (!$order): ?>
        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true">?</span>
            <h1>Order not found</h1>
            <p>Check the order ID and try again.</p>
            <a class="button button-primary" href="<?= e(BASE_URL) ?>/admin/orders.php">Back to orders</a>
        </div>
    <?php else: ?>
        <div class="section-heading cart-heading">
            <div>
                <p class="eyebrow">ORDER DETAILS</p>
                <h1><?= e($order['reference']) ?></h1>
            </div>
            <a class="back-link" href="<?= e(BASE_URL) ?>/admin/orders.php">&larr; All orders</a>
        </div>

        <?php if ($flash !== null): ?>
            <p class="notice notice-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
        <?php endif; ?>
        <?php if ($errorMessage !== null): ?>
            <p class="notice notice-error" role="alert"><?= e($errorMessage) ?></p>
        <?php endif; ?>

        <div class="admin-order-layout">
            <div class="admin-panel">
                <div class="admin-panel-heading">
                    <div>
                        <p class="eyebrow">PURCHASE</p>
                        <h2>Items ordered</h2>
                    </div>
                    <span class="status-pill status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span>
                </div>
                <div class="admin-table-wrap">
                    <table class="admin-table admin-line-items">
                        <thead>
                        <tr><th>Product</th><th>Unit price</th><th>Quantity</th><th>Line total</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td data-label="Product"><?= e($item['product_name']) ?></td>
                                <td data-label="Unit price"><?= e(money($item['unit_price'])) ?></td>
                                <td data-label="Quantity"><?= (int) $item['quantity'] ?></td>
                                <td data-label="Line total"><strong><?= e(money((float) $item['unit_price'] * (int) $item['quantity'])) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot><tr><th colspan="3">Order total</th><th><?= e(money($order['total'])) ?></th></tr></tfoot>
                    </table>
                </div>
            </div>

            <aside class="admin-panel admin-order-side">
                <p class="eyebrow">CUSTOMER</p>
                <h2><?= e($order['customer_name']) ?></h2>
                <dl class="order-facts">
                    <dt>Email</dt><dd><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></dd>
                    <dt>Phone</dt><dd><?= e($order['phone']) ?></dd>
                    <dt>Delivery address</dt><dd><?= nl2br(e($order['address'])) ?></dd>
                    <dt>Placed</dt><dd><?= e(date('M j, Y · H:i', strtotime($order['created_at']))) ?></dd>
                </dl>
                <?php if (admin_allowed_transition($order['status'], 'paid') || admin_allowed_transition($order['status'], 'shipped') || admin_allowed_transition($order['status'], 'delivered') || admin_allowed_transition($order['status'], 'cancelled')): ?>
                    <form class="status-change-form" method="post" action="<?= e(BASE_URL) ?>/admin/order.php?id=<?= (int) $order['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <label class="admin-filter-field" for="next-status">
                            <span>Change status</span>
                            <select id="next-status" name="status" required>
                                <option value="">Choose next status</option>
                                <?php foreach (admin_statuses() as $nextStatus): ?>
                                    <?php if (admin_allowed_transition($order['status'], $nextStatus)): ?>
                                        <option value="<?= e($nextStatus) ?>"><?= e(ucfirst($nextStatus)) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button class="button button-dark" type="submit">Save status</button>
                        <?php if (admin_allowed_transition($order['status'], 'cancelled')): ?>
                            <p class="cancel-warning">Cancelling restores ordered quantities to product stock.</p>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <p class="admin-empty-note">This order is closed and cannot be changed.</p>
                <?php endif; ?>
            </aside>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
