<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function cart_normalized_data(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart)) {
        return [];
    }

    $normalizedCart = [];
    foreach ($cart as $productId => $quantity) {
        $id = filter_var((string) $productId, FILTER_VALIDATE_INT);
        if ($id !== false && $id > 0 && is_int($quantity) && $quantity > 0) {
            $normalizedCart[$id] = $quantity;
        }
    }

    return $normalizedCart;
}

function cart_add(int $id, int $qty): ?string
{
    if ($id < 1 || $qty < 1) {
        throw new InvalidArgumentException('Product and quantity must be positive integers.');
    }

    global $pdo;
    $statement = $pdo->prepare('SELECT stock, is_active FROM products WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $product = $statement->fetch();

    if (!$product || (int) $product['is_active'] !== 1 || (int) $product['stock'] < 1) {
        cart_remove($id);
        return 'This product is unavailable and was not added to your cart.';
    }

    $cart = cart_normalized_data();
    $currentQuantity = $cart[$id] ?? 0;
    $available = (int) $product['stock'];
    $newQuantity = min($currentQuantity + $qty, $available);
    $cart[$id] = $newQuantity;
    $_SESSION['cart'] = $cart;

    if ($newQuantity < $currentQuantity + $qty) {
        return "Quantity adjusted to {$newQuantity}; only {$available} in stock.";
    }

    return null;
}

function cart_update(int $id, int $qty): ?string
{
    if ($id < 1 || $qty < 1) {
        throw new InvalidArgumentException('Product and quantity must be positive integers.');
    }

    global $pdo;
    $statement = $pdo->prepare('SELECT stock, is_active FROM products WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $product = $statement->fetch();

    if (!$product || (int) $product['is_active'] !== 1 || (int) $product['stock'] < 1) {
        cart_remove($id);
        return 'This product is no longer available and was removed from your cart.';
    }

    $cart = cart_normalized_data();
    if (!isset($cart[$id])) {
        return 'This product is not in your cart.';
    }

    $available = (int) $product['stock'];
    $cart[$id] = min($qty, $available);
    $_SESSION['cart'] = $cart;

    if ($qty > $available) {
        return "Quantity adjusted to {$available} to match current stock.";
    }

    return null;
}

function cart_remove(int $id): void
{
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        unset($_SESSION['cart'][$id]);
    }
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function cart_items(): array
{
    $normalizedCart = cart_normalized_data();

    if ($normalizedCart === []) {
        $_SESSION['cart'] = [];
        return [];
    }

    global $pdo;
    $placeholders = [];
    $parameters = [];
    foreach (array_keys($normalizedCart) as $index => $id) {
        $placeholder = ':product_' . $index;
        $placeholders[] = $placeholder;
        $parameters[$placeholder] = $id;
    }

    $statement = $pdo->prepare(
        'SELECT id, name, slug, price, stock, image_url, is_active
         FROM products
         WHERE id IN (' . implode(', ', $placeholders) . ')'
    );
    $statement->execute($parameters);
    $productsById = [];
    foreach ($statement->fetchAll() as $product) {
        $productsById[(int) $product['id']] = $product;
    }

    $lines = [];
    $notices = [];
    foreach ($normalizedCart as $id => $quantity) {
        $product = $productsById[$id] ?? null;
        if (!$product || (int) $product['is_active'] !== 1) {
            unset($normalizedCart[$id]);
            $notices[] = 'An unavailable product was removed from your cart.';
            continue;
        }

        $stock = (int) $product['stock'];
        if ($stock < 1) {
            unset($normalizedCart[$id]);
            $notices[] = $product['name'] . ' is out of stock and was removed from your cart.';
            continue;
        }

        if ($quantity > $stock) {
            $quantity = $stock;
            $normalizedCart[$id] = $quantity;
            $notices[] = $product['name'] . ' quantity was adjusted to match current stock (' . $stock . ').';
        }

        $price = (string) $product['price'];
        $lines[] = [
            'product_id' => $id,
            'name' => $product['name'],
            'slug' => $product['slug'],
            'image_url' => $product['image_url'],
            'price' => $price,
            'quantity' => $quantity,
            'stock' => $stock,
            'stock_ok' => $quantity <= $stock,
            'line_total' => number_format(round((float) $price * $quantity, 2), 2, '.', ''),
        ];
    }

    $_SESSION['cart'] = $normalizedCart;
    if ($notices !== []) {
        set_flash('warning', implode(' ', array_unique($notices)));
    }

    return $lines;
}

function cart_total(): string
{
    $total = 0.0;
    foreach (cart_items() as $item) {
        $total += (float) $item['line_total'];
    }

    return number_format(round($total, 2), 2, '.', '');
}
