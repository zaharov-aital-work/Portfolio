<?php

declare(strict_types=1);

function bm_orders_tables_exist(mysqli $conn): bool
{
    $r = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
    return $r && mysqli_num_rows($r) > 0;
}

function bm_orders_normalize_status(string $s): string
{
    $allowed = ['new', 'processing', 'shipped', 'delivered', 'cancelled'];
    return in_array($s, $allowed, true) ? $s : 'new';
}

function bm_orders_normalize_payment(string $s): string
{
    $allowed = ['pending', 'paid', 'failed', 'refunded'];
    return in_array($s, $allowed, true) ? $s : 'pending';
}

function bm_order_status_label(string $s): string
{
    $m = [
        'new' => 'Новый',
        'processing' => 'В обработке',
        'shipped' => 'Отправлен',
        'delivered' => 'Доставлен',
        'cancelled' => 'Отменён',
    ];
    return $m[$s] ?? $s;
}

function bm_order_payment_label(string $s): string
{
    $m = [
        'pending' => 'Ожидает оплаты',
        'paid' => 'Оплачен',
        'failed' => 'Ошибка оплаты',
        'refunded' => 'Возврат',
    ];
    return $m[$s] ?? $s;
}
