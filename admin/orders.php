<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_admin();

$status = isset($_GET['status']) && is_string($_GET['status']) ? $_GET['status'] : '';
$from = isset($_GET['from']) && is_string($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) && is_string($_GET['to']) ? $_GET['to'] : '';
$errorMessage = null;
$datePattern = '/^\d{4}-\d{2}-\d{2}$/D';

if ($status !== '' && !in_array($status, admin_statuses(), true)) {
    $errorMessage = 'Choose a valid order status filter.';
    $status = '';
}
foreach (['from', 'to'] as $dateKey) {
    $date = $dateKey === 'from' ? $from : $to;
    if ($date !== '' && (!preg_match($datePattern, $date) || !checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)))) {
        $errorMessage = 'Enter valid dates for the date filters.';
        if ($dateKey === 'from') {
            $from = '';
        } else {
            $to = '';
        }
    }
}

$conditions = [];
$parameters = [];
if ($status !== '') {
    $conditions[] = 'o.status = :status';
    $parameters['status'] = $status;
}
if ($from !== '') {
    $conditions[] = 'o.created_at >= :from_date';
    $parameters['from_date'] = $from . ' 00:00:00';
}
if ($to !== '') {
    $conditions[] = 'o.created_at < DATE_ADD(:to_date, INTERVAL 1 DAY)';
    $parameters['to_date'] = $to;
}

$sql = 'SELECT o.id, o.reference, o.customer_name, o.email, o.total, o.status, o.created_at,
               COALESCE(SUM(oi.quantity), 0) AS item_count,
               COUNT(oi.product_id) AS line_count
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id';
if ($conditions !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}
$sql .= ' GROUP BY o.id, o.reference, o.customer_name, o.email, o.total, o.status, o.created_at
          ORDER BY o.created_at DESC, o.id DESC';
$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$orders = $statement->fetchAll();
$flash = take_flash();

$pageTitle = 'Orders';
require __DIR__ . '/_header.php';
?>
<section class="admin-section">
    <div class="section-heading cart-heading">
        <div>
            <p class="eyebrow">FULFILMENT</p>
            <h1>Orders</h1>
        </div>
        <a class="back-link" href="<?= e(BASE_URL) ?>/admin/index.php">&larr; Dashboard</a>
    </div>

    <?php if ($flash !== null): ?>
        <p class="notice notice-<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'warning' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></p>
    <?php endif; ?>
    <?php if ($errorMessage !== null): ?>
        <p class="notice notice-error" role="alert"><?= e($errorMessage) ?></p>
    <?php endif; ?>

    <form class="admin-filter-form" method="get" action="<?= e(BASE_URL) ?>/admin/orders.php">
        <label class="admin-filter-field">
            <span>Status</span>
            <select name="status">
                <option value="">All statuses</option>
                <?php foreach (admin_statuses() as $option): ?>
                    <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e(ucfirst($option)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="admin-filter-field">
            <span>From</span>
            <input type="date" name="from" value="<?= e($from) ?>">
        </label>
        <label class="admin-filter-field">
            <span>To</span>
            <input type="date" name="to" value="<?= e($to) ?>">
        </label>
        <button class="button button-dark admin-filter-button" type="submit">Filter orders</button>
        <a class="admin-clear-filter" href="<?= e(BASE_URL) ?>/admin/orders.php">Clear</a>
    </form>

    <div class="admin-panel orders-panel">
        <div class="admin-panel-heading">
            <div>
                <p class="eyebrow">ORDER HISTORY</p>
                <h2><?= count($orders) ?> <?= count($orders) === 1 ? 'order' : 'orders' ?></h2>
            </div>
        </div>
        <?php if ($orders === []): ?>
            <p class="admin-empty-note">No orders match these filters.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td data-label="Reference"><strong><?= e($order['reference']) ?></strong></td>
                            <td data-label="Customer"><span class="admin-customer"><?= e($order['customer_name']) ?></span><small><?= e($order['email']) ?></small></td>
                            <td data-label="Date"><?= e(date('M j, Y · H:i', strtotime($order['created_at']))) ?></td>
                            <td data-label="Items"><?= (int) $order['item_count'] ?> <small>(<?= (int) $order['line_count'] ?> lines)</small></td>
                            <td data-label="Total"><strong><?= e(money($order['total'])) ?></strong></td>
                            <td data-label="Status"><span class="status-pill status-<?= e($order['status']) ?>"><?= e(ucfirst($order['status'])) ?></span></td>
                            <td data-label="Details"><a class="text-link" href="<?= e(BASE_URL) ?>/admin/order.php?id=<?= (int) $order['id'] ?>">Details &rarr;</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
