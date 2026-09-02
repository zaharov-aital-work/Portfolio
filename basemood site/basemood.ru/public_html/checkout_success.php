<?php
session_start();

$oid = isset($_GET['oid']) ? (int) $_GET['oid'] : 0;
$done = isset($_SESSION['checkout_done_id']) ? (int) $_SESSION['checkout_done_id'] : 0;

if ($oid <= 0 || $done !== $oid) {
    header('Location: catalog.php');
    exit;
}

unset($_SESSION['checkout_done_id']);

$donePayment = $_SESSION['checkout_done_payment'] ?? '';
unset($_SESSION['checkout_done_payment']);

$isSbp = ($donePayment === 'СБП');
$isYooKassa = ($donePayment === 'Банковская карта (ЮКасса)');

$orderTotalRub = null;
require_once __DIR__ . '/config.php';
if ($conn && $oid > 0) {
    $oidEsc = (int) $oid;
    $res = mysqli_query($conn, "SELECT total_amount FROM orders WHERE id=$oidEsc LIMIT 1");
    if ($res && ($row = mysqli_fetch_assoc($res))) {
        $orderTotalRub = (int) ($row['total_amount'] ?? 0);
    }
    mysqli_close($conn);
}

$qrUrl = null;
$qrAlt = 'QR-код СБП для оплаты';
if ($isSbp) {
    foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
        $qrFile = 'sbp-qr.' . $ext;
        $qrPath = __DIR__ . '/img/' . $qrFile;
        if (is_file($qrPath)) {
            $qrUrl = 'img/' . $qrFile . '?v=' . (int) filemtime($qrPath);
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title>Заказ оформлен — BASEMOOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', 'Montserrat', system-ui, sans-serif;
            background: #fff;
            min-height: 100vh;
            padding: 32px 20px 48px;
            color: #1a1a1a;
        }
        .wrap {
            max-width: 420px;
            margin: 0 auto;
        }
        h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.35rem;
            font-weight: 800;
            margin: 0 0 8px;
            text-align: center;
        }
        .num {
            font-size: 1.5rem;
            font-weight: 800;
            text-align: center;
            margin: 0 0 6px;
        }
        .lead {
            text-align: center;
            color: #555;
            line-height: 1.55;
            font-size: 0.9rem;
            margin: 0 0 28px;
        }
        .pay-card {
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            padding: 22px 20px;
            text-align: center;
            margin-bottom: 24px;
            background: #fafafa;
        }
        .pay-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 6px;
        }
        .pay-sum {
            font-size: 0.95rem;
            color: #333;
            margin: 0 0 16px;
        }
        .pay-sum strong {
            font-size: 1.15rem;
            font-weight: 700;
        }
        .qr-img {
            display: block;
            max-width: 220px;
            width: 100%;
            height: auto;
            margin: 0 auto 14px;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 1px 8px rgba(0,0,0,0.06);
        }
        .pay-hint {
            font-size: 0.8rem;
            color: #757575;
            line-height: 1.45;
            margin: 0;
        }
        .pay-fallback {
            font-size: 0.88rem;
            color: #555;
            line-height: 1.5;
            margin: 0;
        }
        .btn {
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 8px;
            padding: 14px 20px;
            background: #0a0a0a;
            color: #fff !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .btn:hover { background: #222; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Спасибо за заказ!</h1>
        <div class="num">№ <?php echo (int) $oid; ?></div>
        <p class="lead">Подтверждение отправлено на email. Корзина очищена.</p>

        <?php if ($isSbp): ?>
        <div class="pay-card">
            <h2 class="pay-title">Оплата по QR-коду (СБП)</h2>
            <?php if ($orderTotalRub !== null && $orderTotalRub > 0): ?>
                <p class="pay-sum">Сумма к оплате: <strong><?php echo number_format($orderTotalRub, 0, '.', ' '); ?> ₽</strong></p>
            <?php endif; ?>
            <?php if ($qrUrl !== null): ?>
                <img src="<?php echo htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($qrAlt, ENT_QUOTES, 'UTF-8'); ?>" class="qr-img" width="220" height="220">
                <p class="pay-hint">Отсканируйте код в приложении банка. При переводе по желанию укажите номер заказа в комментарии к платежу.</p>
            <?php else: ?>
                <p class="pay-fallback">Реквизиты для оплаты также отправлены на почту. Если QR не отображается, откройте письмо или свяжитесь с нами.</p>
            <?php endif; ?>
        </div>
        <?php elseif ($isYooKassa): ?>
        <div class="pay-card">
            <h2 class="pay-title">Оплата картой (ЮКасса)</h2>
            <?php if ($orderTotalRub !== null && $orderTotalRub > 0): ?>
                <p class="pay-sum">Сумма заказа: <strong><?php echo number_format($orderTotalRub, 0, '.', ' '); ?> ₽</strong></p>
            <?php endif; ?>
            <p class="pay-fallback">Ссылка на защищённую оплату картой через ЮКассу будет отправлена на вашу почту или продублирована менеджером. После списания статус заказа обновится в личном кабинете.</p>
        </div>
        <?php endif; ?>

        <a class="btn" href="account.php#orders">Мои заказы</a>
    </div>
    <script>
        try { localStorage.removeItem('basemood-cart'); } catch (e) {}
        try {
            document.querySelectorAll('.cart-count').forEach(function (el) { el.textContent = '0'; });
        } catch (e) {}
    </script>
</body>
</html>
