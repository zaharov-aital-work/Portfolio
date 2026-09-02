<?php
/**
 * Однократно: show_on_home, show_in_catalog, sort_order, popularity;
 * нормализация категорий не выполняется — задайте категорию в админке.
 * Откройте в браузере: migrate_products_catalog_v2.php
 */
require_once __DIR__ . '/config.php';

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

if (!column_exists($conn, 'products', 'sort_order')) {
    if (mysqli_query($conn, 'ALTER TABLE products ADD COLUMN sort_order INT NOT NULL DEFAULT 0')) {
        $msgs[] = 'Добавлена колонка sort_order';
    } else {
        die('Ошибка sort_order: ' . mysqli_error($conn));
    }
} else {
    $msgs[] = 'Колонка sort_order уже есть';
}

if (!column_exists($conn, 'products', 'show_on_home')) {
    if (mysqli_query($conn, 'ALTER TABLE products ADD COLUMN show_on_home TINYINT(1) NOT NULL DEFAULT 1')) {
        $msgs[] = 'Добавлена колонка show_on_home';
    } else {
        die('Ошибка show_on_home: ' . mysqli_error($conn));
    }
} else {
    $msgs[] = 'Колонка show_on_home уже есть';
}

if (!column_exists($conn, 'products', 'show_in_catalog')) {
    if (mysqli_query($conn, 'ALTER TABLE products ADD COLUMN show_in_catalog TINYINT(1) NOT NULL DEFAULT 1')) {
        $msgs[] = 'Добавлена колонка show_in_catalog';
    } else {
        die('Ошибка show_in_catalog: ' . mysqli_error($conn));
    }
} else {
    $msgs[] = 'Колонка show_in_catalog уже есть';
}

if (!column_exists($conn, 'products', 'popularity')) {
    if (mysqli_query($conn, 'ALTER TABLE products ADD COLUMN popularity INT NOT NULL DEFAULT 0')) {
        $msgs[] = 'Добавлена колонка popularity';
    } else {
        die('Ошибка popularity: ' . mysqli_error($conn));
    }
} else {
    $msgs[] = 'Колонка popularity уже есть';
}

// Заполнить sort_order там, где 0: по id (шаг 10)
mysqli_query($conn, 'UPDATE products SET sort_order = id * 10 WHERE sort_order = 0');

header('Content-Type: text/html; charset=utf-8');
echo '<pre>' . htmlspecialchars(implode("\n", $msgs), ENT_QUOTES, 'UTF-8') . "\nГотово.\n</pre>";

mysqli_close($conn);
