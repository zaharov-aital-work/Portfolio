<?php
/**
 * Однократно: таблицы orders и order_items для учёта заказов в админке.
 * Откройте в браузере: migrate_orders.php (рядом с index.php в public_html).
 */
require_once __DIR__ . '/config.php';

if (!$conn) {
    die('Нет подключения к БД');
}

$msgs = [];

$ordersSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status VARCHAR(32) NOT NULL DEFAULT 'new',
    payment_status VARCHAR(32) NOT NULL DEFAULT 'pending',
    customer_name VARCHAR(255) NOT NULL DEFAULT '',
    customer_email VARCHAR(255) NOT NULL DEFAULT '',
    customer_phone VARCHAR(64) NOT NULL DEFAULT '',
    delivery_address TEXT NULL,
    delivery_method VARCHAR(64) NOT NULL DEFAULT '',
    total_amount INT NOT NULL DEFAULT 0,
    tracking_number VARCHAR(128) NOT NULL DEFAULT '',
    admin_notes TEXT NULL,
    user_id INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_orders_created (created_at),
    KEY idx_orders_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

if (mysqli_query($conn, $ordersSql)) {
    $msgs[] = 'Таблица orders готова';
} else {
    die('Ошибка orders: ' . mysqli_error($conn));
}

$itemsSql = <<<'SQL'
CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL DEFAULT 0,
    product_name VARCHAR(512) NOT NULL DEFAULT '',
    size VARCHAR(32) NOT NULL DEFAULT 'M',
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price INT NOT NULL DEFAULT 0,
    image_url VARCHAR(1024) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

if (mysqli_query($conn, $itemsSql)) {
    $msgs[] = 'Таблица order_items готова';
} else {
    die('Ошибка order_items: ' . mysqli_error($conn));
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre>' . htmlspecialchars(implode("\n", $msgs), ENT_QUOTES, 'UTF-8') . "\nГотово.\n</pre>";

mysqli_close($conn);
