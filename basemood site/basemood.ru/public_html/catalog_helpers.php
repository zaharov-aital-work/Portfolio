<?php

declare(strict_types=1);

/**
 * Фиксированные категории каталога (slug => подпись).
 *
 * @return array<string, string>
 */
function bm_product_categories(): array
{
    return [
        'hoodie' => 'Худи',
        'sweatshirt' => 'Свитшоты',
        'tshirt' => 'Футболки',
        'longsleeve' => 'Лонгсливы',
        'pants' => 'Брюки',
        'headwear' => 'Головные уборы',
        'bags' => 'Сумки',
        'socks' => 'Носки',
    ];
}

/** @return list<string> */
function bm_catalog_nav_clothing_slugs(): array
{
    return ['hoodie', 'sweatshirt', 'tshirt', 'longsleeve', 'pants'];
}

/** @return list<string> */
function bm_catalog_nav_accessories_slugs(): array
{
    return ['headwear', 'bags', 'socks'];
}

function bm_normalize_category(?string $raw): string
{
    $k = strtolower(trim((string) $raw));
    return array_key_exists($k, bm_product_categories()) ? $k : '';
}

function bm_category_label(string $slug): string
{
    $all = bm_product_categories();
    return $all[$slug] ?? $slug;
}

/**
 * Кэш полей таблицы products (для работы до/после migrate_products_catalog_v2.php).
 *
 * @return array<string, true>
 */
function bm_products_column_set(mysqli $conn): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $cache = [];
    $r = mysqli_query($conn, 'SHOW COLUMNS FROM products');
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $cache[$row['Field']] = true;
        }
    }
    return $cache;
}

function bm_products_has_column(mysqli $conn, string $name): bool
{
    return isset(bm_products_column_set($conn)[$name]);
}

/** Все поля каталога v2 добавлены миграцией. */
function bm_products_has_catalog_v2(mysqli $conn): bool
{
    return bm_products_has_column($conn, 'show_on_home')
        && bm_products_has_column($conn, 'show_in_catalog')
        && bm_products_has_column($conn, 'sort_order')
        && bm_products_has_column($conn, 'popularity');
}

/**
 * SQL выборки товаров для главной.
 */
function bm_sql_products_for_home(mysqli $conn): string
{
    $q = 'SELECT * FROM products';
    if (bm_products_has_column($conn, 'show_on_home')) {
        $q .= ' WHERE show_on_home = 1';
    }
    if (bm_products_has_column($conn, 'sort_order')) {
        $q .= ' ORDER BY sort_order ASC, id ASC';
    } else {
        $q .= ' ORDER BY id ASC';
    }
    return $q;
}

/**
 * SQL выборки для страницы каталога.
 */
function bm_sql_products_for_catalog(mysqli $conn): string
{
    $q = 'SELECT * FROM products';
    if (bm_products_has_column($conn, 'show_in_catalog')) {
        $q .= ' WHERE show_in_catalog = 1';
    }
    if (bm_products_has_column($conn, 'sort_order')) {
        $q .= ' ORDER BY sort_order ASC, id ASC';
    } else {
        $q .= ' ORDER BY id ASC';
    }
    return $q;
}
