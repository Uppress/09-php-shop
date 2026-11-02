<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/cart.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';
$statement = $pdo->prepare(
    'SELECT p.id, p.name, p.slug, p.description, p.price, p.stock, p.image_url, c.name AS category_name, c.slug AS category_slug
     FROM products p
     INNER JOIN categories c ON c.id = p.category_id
     WHERE p.slug = :slug AND p.is_active = 1
     LIMIT 1'
);
$statement->execute(['slug' => $slug]);
$product = $statement->fetch();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/partials/header.php';
    ?>
    <section class="empty-state not-found">
        <span class="empty-icon" aria-hidden="true">?</span>
        <h1>We couldn't find that product</h1>
        <p>It may have moved or is no longer available.</p>
        <a class="button button-primary" href="<?= e(BASE_URL) ?>/index.php">Back to the shop</a>
    </section>
    <?php
    require __DIR__ . '/partials/footer.php';
    exit;
}

$errorMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $rawQuantity = $_POST['quantity'] ?? null;
    $quantity = is_string($rawQuantity) ? filter_var($rawQuantity, FILTER_VALIDATE_INT) : false;
    if ($quantity === false || $quantity < 1) {
        $errorMessage = 'Choose a quantity of at least 1.';
    } else {
        $notice = cart_add((int) $product['id'], $quantity);
        set_flash($notice === null ? 'success' : 'warning', $notice ?? ($product['name'] . ' added to your cart.'));
        header('Location: ' . BASE_URL . '/product.php?slug=' . rawurlencode($product['slug']));
        exit;
    }
}

$flash = take_flash();
$pageTitle = $product['name'];
require __DIR__ . '/partials/header.php';
?>
<nav class="breadcrumbs" aria-label="Breadcrumb">
    <a href="<?= e(BASE_URL) ?>/index.php">Shop</a>
    <span aria-hidden="true">/</span>
    <a href="<?= e(BASE_URL) ?>/index.php?category=<?= rawurlencode($product['category_slug']) ?>#products"><?= e($product['category_name']) ?></a>
    <span aria-hidden="true">/</span>
    <span><?= e($product['name']) ?></span>
</nav>

<?php if ($flash !== null): ?>
    <p class="notice notice-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></p>
<?php endif; ?>

<section class="product-detail">
    <div class="detail-image-wrap">
        <img
            class="detail-image"
            src="<?= e($product['image_url'] ?: 'https://placehold.co/600x400?text=Product') ?>"
            alt="<?= e($product['name']) ?>"
        >
        <?php if ((int) $product['stock'] < 1): ?>
            <span class="stock-badge">Out of stock</span>
        <?php endif; ?>
    </div>
    <div class="detail-copy">
        <p class="product-category"><?= e($product['category_name']) ?></p>
        <h1><?= e($product['name']) ?></h1>
        <p class="detail-price"><?= e(money($product['price'])) ?></p>
        <p class="detail-description"><?= e($product['description']) ?></p>
        <p class="stock-status <?= (int) $product['stock'] > 0 ? 'in-stock' : 'out-stock' ?>">
            <?= (int) $product['stock'] > 0 ? 'In stock · ' . (int) $product['stock'] . ' available' : 'Currently unavailable' ?>
        </p>

        <?php if ($errorMessage !== null): ?>
            <p class="notice notice-error" role="alert"><?= e($errorMessage) ?></p>
        <?php endif; ?>

        <form class="add-to-cart-form" method="post" action="<?= e(BASE_URL) ?>/product.php?slug=<?= rawurlencode($product['slug']) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label class="quantity-label" for="quantity">Quantity</label>
            <div class="purchase-actions">
                <input
                    class="quantity-input"
                    id="quantity"
                    name="quantity"
                    type="number"
                    min="1"
                    max="<?= (int) $product['stock'] ?>"
                    value="<?= isset($quantity) && $quantity !== false ? (int) $quantity : 1 ?>"
                    required
                    <?= (int) $product['stock'] < 1 ? 'disabled' : '' ?>
                >
                <button class="button button-primary add-button" type="submit" <?= (int) $product['stock'] < 1 ? 'disabled' : '' ?>>
                    Add to cart <span aria-hidden="true">&rarr;</span>
                </button>
            </div>
        </form>
        <a class="back-link" href="<?= e(BASE_URL) ?>/index.php">&larr; Continue shopping</a>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
