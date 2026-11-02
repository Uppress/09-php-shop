<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';
require_admin();

$todayStatement = $pdo->query(
    "SELECT COALESCE(SUM(CASE WHEN status IN ('paid', 'shipped', 'delivered') THEN total ELSE 0 END), 0) AS sales_total,
            COUNT(*) AS order_count
     FROM orders
     WHERE created_at >= CURRENT_DATE
       AND created_at < CURRENT_DATE + INTERVAL 1 DAY"
);
$todayStats = $todayStatement->fetch();
$todaySales = (string) $todayStats['sales_total'];

$statusStatement = $pdo->query('SELECT status, COUNT(*) AS order_count FROM orders GROUP BY status');
$ordersByStatus = array_fill_keys(admin_statuses(), 0);
foreach ($statusStatement->fetchAll() as $row) {
    $ordersByStatus[$row['status']] = (int) $row['order_count'];
}

$topProducts = $pdo->query(
    "SELECT oi.product_id, oi.product_name, SUM(oi.quantity) AS quantity_sold,
            SUM(oi.quantity * oi.unit_price) AS sales_total
     FROM order_items oi
     INNER JOIN orders o ON o.id = oi.order_id
     WHERE o.status <> 'cancelled'
     GROUP BY oi.product_id, oi.product_name
     ORDER BY quantity_sold DESC, oi.product_name ASC
     LIMIT 5"
)->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/_header.php';
?>
<section class="admin-section">
    <div class="section-heading cart-heading">
        <div>
            <p class="eyebrow">STORE OVERVIEW</p>
            <h1>Dashboard</h1>
        </div>
        <a class="button button-primary" href="<?= e(BASE_URL) ?>/admin/orders.php">Manage orders <span aria-hidden="true">&rarr;</span></a>
    </div>

    <div class="admin-stat-grid">
        <article class="admin-stat-card admin-stat-featured">
            <span>Today's sales</span>
            <strong><?= e(money($todaySales)) ?></strong>
            <small>Paid, shipped and delivered orders</small>
        </article>
        <article class="admin-stat-card">
            <span>Pending orders</span>
            <strong><?= $ordersByStatus['pending'] ?></strong>
            <small>Awaiting payment or review</small>
        </article>
        <article class="admin-stat-card">
            <span>Orders today</span>
            <strong><?= (int) $todayStats['order_count'] ?></strong>
            <small>Orders placed today</small>
        </article>
    </div>

    <div class="admin-dashboard-grid">
        <section class="admin-panel">
            <div class="admin-panel-heading">
                <div>
                    <p class="eyebrow">ORDER PIPELINE</p>
                    <h2>Orders by status</h2>
                </div>
                <a class="text-link" href="<?= e(BASE_URL) ?>/admin/orders.php">View all</a>
            </div>
            <div class="status-count-list">
                <?php foreach (admin_statuses() as $status): ?>
                    <a class="status-count-row" href="<?= e(BASE_URL) ?>/admin/orders.php?status=<?= rawurlencode($status) ?>">
                        <span class="status-pill status-<?= e($status) ?>"><?= e(ucfirst($status)) ?></span>
                        <strong><?= $ordersByStatus[$status] ?></strong>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="admin-panel">
            <div class="admin-panel-heading">
                <div>
                    <p class="eyebrow">CUSTOMER FAVOURITES</p>
                    <h2>Top 5 products</h2>
                </div>
            </div>
            <?php if ($topProducts === []): ?>
                <p class="admin-empty-note">Product sales will appear here after the first order.</p>
            <?php else: ?>
                <div class="top-product-list">
                    <?php foreach ($topProducts as $index => $product): ?>
                        <div class="top-product-row">
                            <span class="top-product-rank"><?= $index + 1 ?></span>
                            <span class="top-product-name"><?= e($product['product_name']) ?></span>
                            <strong><?= (int) $product['quantity_sold'] ?> sold</strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
