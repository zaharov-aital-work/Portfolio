<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die('Forbidden');
}

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/adm_cp3k9j2x5f/orders_helpers.php';

if (!$conn || !bm_orders_tables_exist($conn)) {
    http_response_code(500);
    die('Orders table missing');
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'wb');
if ($out === false) {
    exit;
}

fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, ['id', 'created_at', 'status', 'payment_status', 'customer_name', 'customer_email', 'customer_phone', 'total_amount', 'delivery_method'], ';');

$res = mysqli_query($conn, 'SELECT id, created_at, status, payment_status, customer_name, customer_email, customer_phone, total_amount, delivery_method FROM orders ORDER BY id DESC LIMIT 5000');

while ($res && ($row = mysqli_fetch_assoc($res))) {
    $payload = [
        $row['id'],
        $row['created_at'],
        $row['status'],
        $row['payment_status'],
        $row['customer_name'],
        $row['customer_email'],
        $row['customer_phone'],
        $row['total_amount'],
        $row['delivery_method'],
    ];
    fputcsv($out, $payload, ';');
}

fclose($out);
mysqli_close($conn);
exit;
