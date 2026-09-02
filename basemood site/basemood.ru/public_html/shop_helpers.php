<?php

declare(strict_types=1);

/**
 * Общие функции магазина: доставка, промокоды, остатки, настройки, письма.
 * Требует активное подключение $conn (mysqli).
 */

function bm_shop_table_exists(mysqli $conn, string $table): bool
{
    $t = mysqli_real_escape_string($conn, $table);
    $r = mysqli_query($conn, "SHOW TABLES LIKE '$t'");
    return $r && mysqli_num_rows($r) > 0;
}

function bm_orders_column_exists(mysqli $conn, string $col): bool
{
    $c = mysqli_real_escape_string($conn, $col);
    $r = mysqli_query($conn, 'SHOW COLUMNS FROM orders LIKE \'' . $c . '\'');
    return $r && mysqli_num_rows($r) > 0;
}

function bm_shop_settings_get(mysqli $conn, string $key, string $default = ''): string
{
    if (!bm_shop_table_exists($conn, 'shop_settings')) {
        return $default;
    }
    $k = mysqli_real_escape_string($conn, $key);
    $r = mysqli_query($conn, "SELECT setting_value FROM shop_settings WHERE setting_key='$k' LIMIT 1");
    if ($r && ($row = mysqli_fetch_assoc($r))) {
        return (string) $row['setting_value'];
    }
    return $default;
}

function bm_shop_settings_set(mysqli $conn, string $key, string $value): void
{
    if (!bm_shop_table_exists($conn, 'shop_settings')) {
        return;
    }
    $k = mysqli_real_escape_string($conn, $key);
    $v = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO shop_settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value='$v'");
}

/** @return list<array<string,mixed>> */
function bm_delivery_methods_active(mysqli $conn): array
{
    if (!bm_shop_table_exists($conn, 'delivery_methods')) {
        return [];
    }
    $out = [];
    $r = mysqli_query($conn, 'SELECT id, title, price_rub, sort_order FROM delivery_methods WHERE active=1 ORDER BY sort_order ASC, id ASC');
    while ($r && ($row = mysqli_fetch_assoc($r))) {
        $out[] = $row;
    }
    return $out;
}

/**
 * Способы доставки на checkout (фиксированный список; тарифы без «ПВЗ»).
 *
 * @return list<array{id:int,title:string,price_rub:int,label:string}>
 */
function bm_checkout_fixed_delivery_options(): array
{
    return [
        [
            'id' => 8801,
            'title' => 'Почта России (дни отправки: понедельник и пятница)',
            'price_rub' => 666,
            'label' => 'Почта России (дни отправки: понедельник и пятница) от 19 дней 666,01 ₽',
        ],
        [
            'id' => 8802,
            'title' => 'СДЭК (дни отправки: понедельник и пятница)',
            'price_rub' => 1399,
            'label' => 'СДЭК (дни отправки: понедельник и пятница) от 3 дней от 1 398,75 ₽',
        ],
        [
            'id' => 8803,
            'title' => 'Доставка за пределы РФ',
            'price_rub' => 1500,
            'label' => 'Доставка за пределы РФ от 20 дней 1 500 ₽',
        ],
        [
            'id' => 8804,
            'title' => 'Самовывоз (Якутск)',
            'price_rub' => 0,
            'label' => 'Самовывоз (Якутск), адрес согласуем после заказа — 0 ₽',
        ],
    ];
}

/**
 * Подбор тарифа доставки: сначала фиксированные варианты checkout, иначе запись из БД.
 *
 * @return array{title:string,price_rub:int}|null
 */
function bm_checkout_resolve_delivery(mysqli $conn, int $deliveryId): ?array
{
    foreach (bm_checkout_fixed_delivery_options() as $opt) {
        if ((int) $opt['id'] === $deliveryId) {
            return ['title' => (string) $opt['title'], 'price_rub' => (int) $opt['price_rub']];
        }
    }
    if (!bm_shop_table_exists($conn, 'delivery_methods')) {
        return null;
    }
    $did = mysqli_real_escape_string($conn, (string) $deliveryId);
    $dr = mysqli_query($conn, "SELECT id, title, price_rub FROM delivery_methods WHERE id=$did AND active=1 LIMIT 1");
    if ($dr && ($drow = mysqli_fetch_assoc($dr))) {
        return ['title' => (string) $drow['title'], 'price_rub' => (int) $drow['price_rub']];
    }
    return null;
}

/** @return array<string,int> size => qty (только явные строки в БД; отсутствие размера = без лимита) */
function bm_stock_map_for_product(mysqli $conn, int $productId): array
{
    if (!bm_shop_table_exists($conn, 'product_stock') || $productId <= 0) {
        return [];
    }
    $map = [];
    $r = mysqli_query($conn, 'SELECT size, stock_qty FROM product_stock WHERE product_id=' . $productId);
    while ($r && ($row = mysqli_fetch_assoc($r))) {
        $map[(string) $row['size']] = (int) $row['stock_qty'];
    }
    return $map;
}

function bm_stock_can_sell(mysqli $conn, int $productId, string $size, int $qty): bool
{
    if (!bm_shop_table_exists($conn, 'product_stock')) {
        return true;
    }
    $size = bm_shop_normalize_size($size);
    $r = mysqli_query(
        $conn,
        'SELECT stock_qty FROM product_stock WHERE product_id=' . $productId . " AND size='" . mysqli_real_escape_string($conn, $size) . "' LIMIT 1"
    );
    if (!$r || mysqli_num_rows($r) === 0) {
        return true;
    }
    $row = mysqli_fetch_assoc($r);
    return (int) $row['stock_qty'] >= $qty;
}

function bm_stock_decrement(mysqli $conn, int $productId, string $size, int $qty): void
{
    if (!bm_shop_table_exists($conn, 'product_stock') || $qty <= 0) {
        return;
    }
    $size = bm_shop_normalize_size($size);
    $e = mysqli_real_escape_string($conn, $size);
    $chk = mysqli_query($conn, 'SELECT id, stock_qty FROM product_stock WHERE product_id=' . $productId . " AND size='$e' LIMIT 1");
    if ($chk && mysqli_num_rows($chk) > 0) {
        mysqli_query($conn, 'UPDATE product_stock SET stock_qty=GREATEST(0, stock_qty-' . (int) $qty . ') WHERE product_id=' . $productId . " AND size='$e' LIMIT 1");
    }
}

function bm_shop_normalize_size(string $s): string
{
    $s = strtoupper(trim($s));
    if ($s === '') {
        return 'M';
    }
    return preg_match('/^[A-Z0-9]{1,8}$/', $s) ? $s : 'M';
}

/**
 * @param array<string,mixed> $row из promocodes
 */
function bm_promo_calc_discount(array $row, int $subtotalRub): int
{
    if (empty($row['active'])) {
        return 0;
    }
    $min = (int) ($row['min_order_rub'] ?? 0);
    if ($subtotalRub < $min) {
        return 0;
    }
    if (!empty($row['valid_until']) && $row['valid_until'] !== '0000-00-00') {
        $u = strtotime((string) $row['valid_until'] . ' 23:59:59');
        if ($u && time() > $u) {
            return 0;
        }
    }
    $type = (string) ($row['discount_type'] ?? '');
    $val = (int) ($row['discount_value'] ?? 0);
    if ($type === 'percent') {
        $val = max(0, min(100, $val));
        return (int) round($subtotalRub * $val / 100);
    }
    if ($type === 'fixed') {
        return min($subtotalRub, max(0, $val));
    }
    return 0;
}

/** @return array{ok:bool,row:?array,discount:int,error:string} */
function bm_promo_lookup(mysqli $conn, string $code, int $subtotalRub): array
{
    if (!bm_shop_table_exists($conn, 'promocodes') || $code === '') {
        return ['ok' => true, 'row' => null, 'discount' => 0, 'error' => ''];
    }
    $c = strtoupper(trim($code));
    $e = mysqli_real_escape_string($conn, $c);
    $r = mysqli_query($conn, "SELECT * FROM promocodes WHERE code='$e' LIMIT 1");
    if (!$r || mysqli_num_rows($r) === 0) {
        return ['ok' => false, 'row' => null, 'discount' => 0, 'error' => 'Промокод не найден'];
    }
    $row = mysqli_fetch_assoc($r);
    $d = bm_promo_calc_discount($row, $subtotalRub);
    if ($d <= 0 && $c !== '') {
        return ['ok' => false, 'row' => $row, 'discount' => 0, 'error' => 'Промокод не применим к этой корзине'];
    }
    return ['ok' => true, 'row' => $row, 'discount' => $d, 'error' => ''];
}

function bm_shop_send_mail(string $to, string $subject, string $htmlBody): bool
{
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, $headers);
}

/** @return array{from:string,name:string} */
function bm_shop_mail_from(mysqli $conn): array
{
    $from = bm_shop_settings_get($conn, 'mail_from', 'noreply@basemood.ru');
    $name = bm_shop_settings_get($conn, 'mail_from_name', 'BASEMOOD');
    return ['from' => $from, 'name' => $name];
}

function bm_shop_notify_admin_email(mysqli $conn): string
{
    return bm_shop_settings_get($conn, 'admin_notify_email', '');
}

/** @return ?array{title:string,body_html:string,meta_description:string} */
function bm_cms_get(mysqli $conn, string $slug): ?array
{
    if (!bm_shop_table_exists($conn, 'cms_pages')) {
        return null;
    }
    $s = mysqli_real_escape_string($conn, $slug);
    $r = mysqli_query($conn, "SELECT title, body_html, meta_description FROM cms_pages WHERE slug='$s' AND published=1 LIMIT 1");
    if ($r && ($row = mysqli_fetch_assoc($r))) {
        return [
            'title' => (string) $row['title'],
            'body_html' => (string) $row['body_html'],
            'meta_description' => (string) ($row['meta_description'] ?? ''),
        ];
    }
    return null;
}

function bm_stock_save_for_product(mysqli $conn, int $productId, array $sizesQty): void
{
    if (!bm_shop_table_exists($conn, 'product_stock') || $productId <= 0) {
        return;
    }
    mysqli_query($conn, 'DELETE FROM product_stock WHERE product_id=' . $productId);
    foreach ($sizesQty as $size => $qty) {
        if ($qty === '' || $qty === null) {
            continue;
        }
        $sz = bm_shop_normalize_size((string) $size);
        $q = max(0, (int) $qty);
        $e = mysqli_real_escape_string($conn, $sz);
        mysqli_query(
            $conn,
            'INSERT INTO product_stock (product_id, size, stock_qty) VALUES (' . $productId . ", '$e', $q)"
        );
    }
}
