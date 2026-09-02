<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/shop_helpers.php';
require_once __DIR__ . '/adm_cp3k9j2x5f/orders_helpers.php';

if (!$conn || !bm_orders_tables_exist($conn)) {
    $_SESSION['checkout_error'] = 'Оформление временно недоступно. Выполните миграцию migrate_orders.php.';
    header('Location: checkout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

$csrf = $_POST['checkout_csrf'] ?? '';
if (empty($_SESSION['checkout_csrf']) || !hash_equals($_SESSION['checkout_csrf'], $csrf)) {
    $_SESSION['checkout_error'] = 'Сессия устарела. Обновите страницу и попробуйте снова.';
    header('Location: checkout.php');
    exit;
}

$cartJson = $_POST['cart_json'] ?? '[]';
$cart = json_decode($cartJson, true);
if (!is_array($cart) || count($cart) === 0) {
    $_SESSION['checkout_error'] = 'Корзина пуста.';
    header('Location: checkout.php');
    exit;
}

$name = trim(strip_tags($_POST['customer_name'] ?? ''));
$email = trim($_POST['customer_email'] ?? '');
$phone = trim(strip_tags($_POST['customer_phone'] ?? ''));
$address = trim(strip_tags($_POST['delivery_address'] ?? ''));
$paymentMethod = trim(strip_tags($_POST['payment_method'] ?? ''));
$deliveryId = (int) ($_POST['delivery_id'] ?? 0);
$promoInput = trim((string) ($_POST['promo_code'] ?? ''));
$allowedPayments = ['СБП', 'Банковская карта (ЮКасса)'];
if (!in_array($paymentMethod, $allowedPayments, true)) {
    $_SESSION['checkout_error'] = 'Выберите способ оплаты: СБП или банковская карта (ЮКасса).';
    header('Location: checkout.php');
    exit;
}

if ($name === '' || strlen($name) > 255) {
    $_SESSION['checkout_error'] = 'Укажите имя.';
    header('Location: checkout.php');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['checkout_error'] = 'Укажите корректный email.';
    header('Location: checkout.php');
    exit;
}
if ($phone === '' || strlen($phone) > 64) {
    $_SESSION['checkout_error'] = 'Укажите телефон.';
    header('Location: checkout.php');
    exit;
}
if ($address === '') {
    $_SESSION['checkout_error'] = 'Укажите адрес доставки.';
    header('Location: checkout.php');
    exit;
}

$addrLines = array_values(array_filter([
    $address,
    'Оплата: ' . $paymentMethod,
], static function ($x) {
    return $x !== '';
}));
$address = implode("\n", $addrLines);

$deliveryTitle = 'Доставка';
$deliveryFee = 0;
if ($deliveryId <= 0) {
    $_SESSION['checkout_error'] = 'Выберите способ доставки.';
    header('Location: checkout.php');
    exit;
}
$resolved = bm_checkout_resolve_delivery($conn, $deliveryId);
if ($resolved === null) {
    $_SESSION['checkout_error'] = 'Выберите корректный способ доставки.';
    header('Location: checkout.php');
    exit;
}
$deliveryTitle = $resolved['title'];
$deliveryFee = $resolved['price_rub'];

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$userIdSql = $userId > 0 ? (string) $userId : 'NULL';

$lines = [];
$subtotal = 0;
foreach ($cart as $row) {
    if (!is_array($row)) {
        continue;
    }
    $pid = (int) ($row['id'] ?? 0);
    $qty = max(1, (int) ($row['quantity'] ?? 1));
    $size = bm_shop_normalize_size((string) ($row['size'] ?? 'M'));
    if ($pid <= 0) {
        continue;
    }
    $pr = mysqli_query($conn, 'SELECT id, name, price, image_url FROM products WHERE id=' . $pid . ' LIMIT 1');
    if (!$pr || mysqli_num_rows($pr) === 0) {
        $_SESSION['checkout_error'] = 'Товар #' . $pid . ' недоступен.';
        header('Location: checkout.php');
        exit;
    }
    $p = mysqli_fetch_assoc($pr);
    $dbPrice = (int) $p['price'];
    if (!bm_stock_can_sell($conn, $pid, $size, $qty)) {
        $_SESSION['checkout_error'] = 'Недостаточно остатка для: ' . htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') . ', размер ' . $size;
        header('Location: checkout.php');
        exit;
    }
    $lineTotal = $dbPrice * $qty;
    $subtotal += $lineTotal;
    $lines[] = [
        'product_id' => $pid,
        'name' => (string) $p['name'],
        'size' => $size,
        'qty' => $qty,
        'unit_price' => $dbPrice,
        'image' => (string) ($p['image_url'] ?? ''),
    ];
}

if (count($lines) === 0) {
    $_SESSION['checkout_error'] = 'Не удалось разобрать корзину.';
    header('Location: checkout.php');
    exit;
}

$discount = 0;
$promoStored = '';
if ($promoInput !== '' && bm_shop_table_exists($conn, 'promocodes')) {
    $pr = bm_promo_lookup($conn, $promoInput, $subtotal);
    if (!$pr['ok']) {
        $_SESSION['checkout_error'] = $pr['error'] !== '' ? $pr['error'] : 'Промокод не принят.';
        header('Location: checkout.php');
        exit;
    }
    $discount = $pr['discount'];
    $promoStored = strtoupper(trim($promoInput));
}

$grandTotal = max(0, $subtotal - $discount + $deliveryFee);

$nameEsc = mysqli_real_escape_string($conn, $name);
$emailEsc = mysqli_real_escape_string($conn, $email);
$phoneEsc = mysqli_real_escape_string($conn, $phone);
$addrEsc = mysqli_real_escape_string($conn, $address);
$dmethodEsc = mysqli_real_escape_string($conn, $deliveryTitle);
$promoEsc = mysqli_real_escape_string($conn, $promoStored);

$dmidSql = ($deliveryId > 0) ? (string) $deliveryId : 'NULL';

mysqli_begin_transaction($conn);

try {
    $hasExtra = bm_orders_column_exists($conn, 'subtotal_amount');
    if ($hasExtra) {
        $sql = "INSERT INTO orders (status, payment_status, customer_name, customer_email, customer_phone, delivery_address, delivery_method, delivery_method_id, total_amount, subtotal_amount, discount_amount, delivery_fee, promo_code, user_id)
            VALUES ('new', 'pending', '$nameEsc', '$emailEsc', '$phoneEsc', '$addrEsc', '$dmethodEsc', $dmidSql, $grandTotal, $subtotal, $discount, $deliveryFee, '$promoEsc', $userIdSql)";
    } else {
        $sql = "INSERT INTO orders (status, payment_status, customer_name, customer_email, customer_phone, delivery_address, delivery_method, total_amount, user_id)
            VALUES ('new', 'pending', '$nameEsc', '$emailEsc', '$phoneEsc', '$addrEsc', '$dmethodEsc', $grandTotal, $userIdSql)";
    }

    if (!mysqli_query($conn, $sql)) {
        throw new RuntimeException(mysqli_error($conn));
    }

    $orderId = (int) mysqli_insert_id($conn);

    foreach ($lines as $ln) {
        $pid = (int) $ln['product_id'];
        $pn = mysqli_real_escape_string($conn, $ln['name']);
        $sz = mysqli_real_escape_string($conn, $ln['size']);
        $q = (int) $ln['qty'];
        $up = (int) $ln['unit_price'];
        $im = mysqli_real_escape_string($conn, $ln['image']);
        $iq = "INSERT INTO order_items (order_id, product_id, product_name, size, quantity, unit_price, image_url) VALUES ($orderId, $pid, '$pn', '$sz', $q, $up, '$im')";
        if (!mysqli_query($conn, $iq)) {
            throw new RuntimeException(mysqli_error($conn));
        }
        bm_stock_decrement($conn, $pid, $ln['size'], $q);
    }

    mysqli_commit($conn);
} catch (Throwable $e) {
    mysqli_rollback($conn);
    $_SESSION['checkout_error'] = 'Не удалось сохранить заказ. Попробуйте позже.';
    header('Location: checkout.php');
    exit;
}

$_SESSION['checkout_done_id'] = $orderId;
$_SESSION['checkout_done_payment'] = $paymentMethod;
unset($_SESSION['checkout_csrf']);

$adminMail = bm_shop_notify_admin_email($conn);
$body = '<p>Новый заказ #' . $orderId . '</p><p>Сумма: ' . $grandTotal . " ₽</p><p>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '<br>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>';
if ($adminMail !== '') {
    bm_shop_send_mail($adminMail, 'BASEMOOD: заказ #' . $orderId, $body);
}
bm_shop_send_mail($email, 'BASEMOOD — заказ №' . $orderId . ' принят', '<p>Спасибо! Заказ №' . $orderId . ' на сумму ' . $grandTotal . " ₽ принят в обработку.</p>");

header('Location: checkout_success.php?oid=' . $orderId);
exit;
