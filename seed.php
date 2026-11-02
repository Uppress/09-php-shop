<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

require __DIR__ . '/app/bootstrap.php';

$products = [
    ['phones', 'Nova X5 Smartphone', 'nova-x5-smartphone', 'A dependable everyday smartphone with a vivid display, long-lasting battery and plenty of room for your photos.', 189999.00, 18],
    ['phones', 'Orbit Pro 5G', 'orbit-pro-5g', 'Enjoy fast 5G, smooth performance and a crisp camera system in a refined, pocket-friendly design.', 329999.00, 12],
    ['phones', 'PixelWave Lite', 'pixelwave-lite', 'A lightweight phone with a bright screen, reliable all-day battery and simple, intuitive software.', 149500.00, 25],
    ['phones', 'Core Mini 128GB', 'core-mini-128gb', 'Compact and capable, with 128GB of storage and a sharp camera for everyday moments.', 219000.00, 0],
    ['phones', 'Titan Max Smartphone', 'titan-max-smartphone', 'A large immersive display, generous storage and fast charging make this a great all-round upgrade.', 415000.00, 7],
    ['laptops', 'AeroBook 14', 'aerobook-14', 'A slim 14-inch laptop for study and work, with a responsive keyboard and all-day portability.', 685000.00, 9],
    ['laptops', 'WorkMate 15 Core i5', 'workmate-15-core-i5', 'Handle everyday multitasking with a capable Core i5 processor, 15-inch display and fast SSD storage.', 925000.00, 6],
    ['laptops', 'CreatorBook 16', 'creatorbook-16', 'A spacious high-resolution display and powerful hardware for creative projects and demanding workflows.', 1480000.00, 4],
    ['laptops', 'CampusBook 13', 'campusbook-13', 'A lightweight study companion with quick startup, dependable battery life and a comfortable compact design.', 549000.00, 0],
    ['laptops', 'Edge Gaming 15', 'edge-gaming-15', 'Responsive graphics and efficient cooling bring smooth gameplay and fast performance to your desk.', 1799000.00, 3],
    ['accessories', 'Pulse Wireless Earbuds', 'pulse-wireless-earbuds', 'Comfortable wireless earbuds with clear calls, rich sound and a pocket-sized charging case.', 48500.00, 30],
    ['accessories', 'ChargePro 30W Adapter', 'chargepro-30w-adapter', 'A compact 30W USB-C adapter for quick, convenient charging at home or on the go.', 19500.00, 42],
    ['accessories', 'Flex USB-C Hub 6-in-1', 'flex-usb-c-hub-6-in-1', 'Add useful ports to your laptop with HDMI, USB, card readers and pass-through USB-C charging.', 39500.00, 16],
    ['accessories', 'Everyday Laptop Sleeve', 'everyday-laptop-sleeve', 'Protect your laptop from everyday bumps and scratches with a soft-lined, water-resistant sleeve.', 24500.00, 0],
    ['accessories', 'PowerGo 20000mAh Bank', 'powergo-20000mah-bank', 'Keep your devices charged away from a wall outlet with a high-capacity power bank and dual outputs.', 56500.00, 11],
];

$categoryStatement = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug');
$productStatement = $pdo->prepare(
    'INSERT INTO products (category_id, name, slug, description, price, stock, image_url, is_active)
     VALUES (:category_id, :name, :slug, :description, :price, :stock, :image_url, 1)
     ON DUPLICATE KEY UPDATE
        category_id = VALUES(category_id),
        name = VALUES(name),
        description = VALUES(description),
        price = VALUES(price),
        image_url = VALUES(image_url),
        is_active = 1'
);

$pdo->beginTransaction();

try {
    foreach ($products as [$categorySlug, $name, $slug, $description, $price, $stock]) {
        $categoryStatement->execute(['slug' => $categorySlug]);
        $categoryId = $categoryStatement->fetchColumn();

        if ($categoryId === false) {
            throw new RuntimeException("Category '{$categorySlug}' was not found. Create the categories before seeding.");
        }

        $productStatement->execute([
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'price' => $price,
            'stock' => $stock,
            'image_url' => 'https://placehold.co/600x400?text=' . rawurlencode($name),
        ]);
    }

    $pdo->commit();
    fwrite(STDOUT, 'Seeded ' . count($products) . " products into swd_shop.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $exception;
}
