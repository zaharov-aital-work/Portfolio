<?php
/**
 * Расширение магазина: доставка, промокоды, остатки, CMS, медиатека, поля заказа, настройки.
 * Запускать после migrate_orders.php. Откройте в браузере: migrate_shop_full.php
 */
require_once __DIR__ . '/config.php';

if (!$conn) {
    die('Нет подключения к БД');
}

function bm_mig_column_exists(mysqli $conn, string $table, string $column): bool
{
    $c = mysqli_real_escape_string($conn, $column);
    $t = mysqli_real_escape_string($conn, $table);
    $r = mysqli_query($conn, "SHOW COLUMNS FROM `$t` LIKE '$c'");
    return $r && mysqli_num_rows($r) > 0;
}

$msgs = [];

$settingsSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS shop_settings (
    setting_key VARCHAR(64) NOT NULL,
    setting_value TEXT NOT NULL,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
if (mysqli_query($conn, $settingsSql)) {
    $msgs[] = 'shop_settings OK';
} else {
    die('shop_settings: ' . mysqli_error($conn));
}

$delSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS delivery_methods (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(128) NOT NULL DEFAULT '',
    price_rub INT NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_delivery_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
mysqli_query($conn, $delSql) || die('delivery_methods: ' . mysqli_error($conn));
$msgs[] = 'delivery_methods OK';

$promoSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS promocodes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(32) NOT NULL,
    discount_type VARCHAR(16) NOT NULL DEFAULT 'percent',
    discount_value INT NOT NULL DEFAULT 0,
    min_order_rub INT NOT NULL DEFAULT 0,
    valid_until DATE NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_promo_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
mysqli_query($conn, $promoSql) || die('promocodes: ' . mysqli_error($conn));
$msgs[] = 'promocodes OK';

$stockSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS product_stock (
    product_id INT UNSIGNED NOT NULL,
    size VARCHAR(16) NOT NULL,
    stock_qty INT NOT NULL DEFAULT 0,
    PRIMARY KEY (product_id, size),
    KEY idx_stock_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
mysqli_query($conn, $stockSql) || die('product_stock: ' . mysqli_error($conn));
$msgs[] = 'product_stock OK';

$cmsSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS cms_pages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(64) NOT NULL,
    title VARCHAR(255) NOT NULL DEFAULT '',
    body_html MEDIUMTEXT NOT NULL,
    meta_description VARCHAR(512) NOT NULL DEFAULT '',
    published TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cms_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
mysqli_query($conn, $cmsSql) || die('cms_pages: ' . mysqli_error($conn));
$msgs[] = 'cms_pages OK';

$mediaSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS media_files (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    path VARCHAR(512) NOT NULL DEFAULT '',
    original_name VARCHAR(255) NOT NULL DEFAULT '',
    mime VARCHAR(96) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_media_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;
mysqli_query($conn, $mediaSql) || die('media_files: ' . mysqli_error($conn));
$msgs[] = 'media_files OK';

if (!bm_mig_column_exists($conn, 'orders', 'subtotal_amount')) {
    mysqli_query($conn, 'ALTER TABLE orders ADD COLUMN subtotal_amount INT NOT NULL DEFAULT 0')
        || die('ALTER subtotal_amount: ' . mysqli_error($conn));
    $msgs[] = 'orders.subtotal_amount добавлен';
} else {
    $msgs[] = 'orders.subtotal_amount уже есть';
}

if (!bm_mig_column_exists($conn, 'orders', 'discount_amount')) {
    mysqli_query($conn, 'ALTER TABLE orders ADD COLUMN discount_amount INT NOT NULL DEFAULT 0')
        || die('ALTER discount_amount: ' . mysqli_error($conn));
    $msgs[] = 'orders.discount_amount добавлен';
} else {
    $msgs[] = 'orders.discount_amount уже есть';
}

if (!bm_mig_column_exists($conn, 'orders', 'delivery_fee')) {
    mysqli_query($conn, 'ALTER TABLE orders ADD COLUMN delivery_fee INT NOT NULL DEFAULT 0')
        || die('ALTER delivery_fee: ' . mysqli_error($conn));
    $msgs[] = 'orders.delivery_fee добавлен';
} else {
    $msgs[] = 'orders.delivery_fee уже есть';
}

if (!bm_mig_column_exists($conn, 'orders', 'promo_code')) {
    mysqli_query($conn, 'ALTER TABLE orders ADD COLUMN promo_code VARCHAR(64) NOT NULL DEFAULT \'\'')
        || die('ALTER promo_code: ' . mysqli_error($conn));
    $msgs[] = 'orders.promo_code добавлен';
} else {
    $msgs[] = 'orders.promo_code уже есть';
}

if (!bm_mig_column_exists($conn, 'orders', 'delivery_method_id')) {
    mysqli_query($conn, 'ALTER TABLE orders ADD COLUMN delivery_method_id INT UNSIGNED NULL')
        || die('ALTER delivery_method_id: ' . mysqli_error($conn));
    $msgs[] = 'orders.delivery_method_id добавлен';
} else {
    $msgs[] = 'orders.delivery_method_id уже есть';
}

$c = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM delivery_methods');
$row = $c ? mysqli_fetch_assoc($c) : ['c' => 0];
if ((int) ($row['c'] ?? 0) === 0) {
    mysqli_query($conn, "INSERT INTO delivery_methods (title, price_rub, sort_order, active) VALUES
        ('СДЭК / ПВЗ', 350, 10, 1),
        ('Курьер', 500, 20, 1),
        ('Самовывоз (по согласованию)', 0, 30, 1)");
    $msgs[] = 'Добавлены примеры способов доставки';
}

mysqli_query($conn, "INSERT INTO shop_settings (setting_key, setting_value) VALUES ('mail_from', 'noreply@basemood.ru') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
mysqli_query($conn, "INSERT INTO shop_settings (setting_key, setting_value) VALUES ('mail_from_name', 'BASEMOOD') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
mysqli_query($conn, "INSERT INTO shop_settings (setting_key, setting_value) VALUES ('admin_notify_email', '') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");

header('Content-Type: text/html; charset=utf-8');
echo '<pre>' . htmlspecialchars(implode("\n", $msgs), ENT_QUOTES, 'UTF-8') . "\nГотово.\n</pre>";

mysqli_close($conn);
