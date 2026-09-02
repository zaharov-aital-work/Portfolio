<?php
/**
 * Однократный запуск: добавляет card_images и gallery_images, переносит старые image_url / image_back_url в card_images.
 * Откройте в браузере или выполните: php migrate_products_card_gallery.php
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/product_images_helpers.php';

if (!$conn) {
    die('Нет подключения к БД');
}

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $c = mysqli_real_escape_string($conn, $column);
    $t = mysqli_real_escape_string($conn, $table);
    $r = mysqli_query($conn, "SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $r && mysqli_num_rows($r) > 0;
}

$msgs = [];

if (!column_exists($conn, 'products', 'card_images')) {
    if (mysqli_query($conn, 'ALTER TABLE products ADD COLUMN card_images TEXT NULL')) {
        $msgs[] = 'Добавлена колонка card_images';
    } else {
        die('Ошибка ALTER card_images: ' . mysqli_error($conn));
    }
} else {
    $msgs[] = 'Колонка card_images уже есть';
}

if (!column_exists($conn, 'products', 'gallery_images')) {
    if (mysqli_query($conn, 'ALTER TABLE products ADD COLUMN gallery_images TEXT NULL')) {
        $msgs[] = 'Добавлена колонка gallery_images';
    } else {
        die('Ошибка ALTER gallery_images: ' . mysqli_error($conn));
    }
} else {
    $msgs[] = 'Колонка gallery_images уже есть';
}

$res = mysqli_query($conn, 'SELECT id, image_url, image_back_url, card_images FROM products');
if (!$res) {
    die('Ошибка выборки: ' . mysqli_error($conn));
}

$updated = 0;
while ($row = mysqli_fetch_assoc($res)) {
    if (!empty($row['card_images'])) {
        continue;
    }
    $urls = [];
    if (!empty($row['image_url'])) {
        $t = trim((string) $row['image_url']);
        if ($t !== '') {
            $urls[] = $t;
        }
    }
    if (!empty($row['image_back_url'])) {
        $t = trim((string) $row['image_back_url']);
        if ($t !== '') {
            $urls[] = $t;
        }
    }
    if (count($urls) === 0) {
        continue;
    }
    $json = mysqli_real_escape_string($conn, json_encode($urls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    $id = (int) $row['id'];
    if (mysqli_query($conn, "UPDATE products SET card_images = '$json' WHERE id = $id")) {
        $updated++;
    }
}

$msgs[] = "Заполнено card_images из старых полей для строк: $updated";

mysqli_close($conn);

header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $msgs) . "\nГотово.\n";
