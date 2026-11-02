<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$categorySlug = isset($_GET['category']) && is_string($_GET['category']) ? trim($_GET['category']) : '';
$search = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$sort = isset($_GET['sort']) && is_string($_GET['sort']) ? $_GET['sort'] : 'newest';

$sortOptions = [
    'newest' => 'p.created_at DESC, p.id DESC',
    'price_asc' => 'p.price ASC, p.id DESC',
    'price_desc' => 'p.price DESC, p.id DESC',
];
if (!isset($sortOptions[$sort])) {
    $sort = 'newest';
}

$categories = $pdo->query('SELECT name, slug FROM categories ORDER BY name')->fetchAll();

$sql = 'SELECT p.id, p.name, p.slug, p.description, p.price, p.stock, p.image_url, c.name AS category_name
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.is_active = 1';
$parameters = [];

if ($categorySlug !== '') {
    $sql .= ' AND c.slug = :category';
    $parameters['category'] = $categorySlug;
}

if ($search !== '') {
    $sql .= ' AND (p.name LIKE :search_name OR p.description LIKE :search_description)';
    $searchTerm = '%' . $search . '%';
    $parameters['search_name'] = $searchTerm;
    $parameters['search_description'] = $searchTerm;
}

$sql .= ' ORDER BY ' . $sortOptions[$sort];
$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$products = $statement->fetchAll();

$pageTitle = 'Shop';
require __DIR__ . '/partials/header.php';
?>
<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">YOUR NEXT FAVOURITE THING</p>
        <h1>Tech that fits<br>your everyday.</h1>
        <p class="hero-description">Shop dependable phones, laptops and accessories at prices that make sense.</p>
        <a class="button button-primary" href="#products">Explore the shop <span aria-hidden="true">&rarr;</span></a>
    </div>
    <div class="hero-art" aria-hidden="true">
        <span class="hero-orbit orbit-one"></span>
        <span class="hero-orbit orbit-two"></span>
        <span class="hero-device device-phone"></span>
        <span class="hero-device device-laptop"></span>
        <span class="hero-sparkle sparkle-one">✦</span>
        <span class="hero-sparkle sparkle-two">✧</span>
    </div>
</section>

<section class="shop-section" id="products">
    <div class="section-heading">
        <div>
            <p class="eyebrow">THE GOOD STUFF</p>
            <h2>Find your next upgrade</h2>
        </div>
        <p class="result-count"><?= count($products) ?> <?= count($products) === 1 ? 'product' : 'products' ?> to explore</p>
    </div>

    <form class="filter-bar" method="get" action="<?= e(BASE_URL) ?>/index.php#products">
        <label class="filter-field search-field">
            <span class="sr-only">Search products</span>
            <span class="search-icon" aria-hidden="true">&#9906;</span>
            <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search products...">
        </label>
        <label class="filter-field">
            <span class="sr-only">Filter by category</span>
            <select name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['slug']) ?>" <?= $categorySlug === $category['slug'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="filter-field">
            <span class="sr-only">Sort products</span>
            <select name="sort">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
            </select>
        </label>
        <button class="button button-dark filter-submit" type="submit">Apply filters</button>
    </form>

    <?php if ($products === []): ?>
        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true">⌕</span>
            <h3>No products found</h3>
            <p>Try a different search or category to find what you need.</p>
            <a class="text-link" href="<?= e(BASE_URL) ?>/index.php#products">Clear filters</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <a class="product-image-link" href="<?= e(BASE_URL) ?>/product.php?slug=<?= rawurlencode($product['slug']) ?>">
                        <img
                            class="product-image"
                            src="<?= e($product['image_url'] ?: 'https://placehold.co/600x400?text=Product') ?>"
                            alt="<?= e($product['name']) ?>"
                            loading="lazy"
                        >
                        <?php if ((int) $product['stock'] < 1): ?>
                            <span class="stock-badge">Out of stock</span>
                        <?php endif; ?>
                    </a>
                    <div class="product-info">
                        <p class="product-category"><?= e($product['category_name']) ?></p>
                        <h3><a href="<?= e(BASE_URL) ?>/product.php?slug=<?= rawurlencode($product['slug']) ?>"><?= e($product['name']) ?></a></h3>
                        <div class="product-card-bottom">
                            <span class="product-price"><?= e(money($product['price'])) ?></span>
                            <a class="card-arrow" href="<?= e(BASE_URL) ?>/product.php?slug=<?= rawurlencode($product['slug']) ?>" aria-label="View <?= e($product['name']) ?>">&rarr;</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
