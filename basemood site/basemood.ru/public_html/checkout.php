<?php
session_start();

$is_logged_in = isset($_SESSION['user_id']);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/shop_helpers.php';
require_once __DIR__ . '/adm_cp3k9j2x5f/orders_helpers.php';

if (!$conn || !bm_orders_tables_exist($conn)) {
    die('<p>Сначала выполните миграцию <code>migrate_orders.php</code> и при необходимости <code>migrate_shop_full.php</code>.</p>');
}

$err = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);

$_SESSION['checkout_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['checkout_csrf'];

mysqli_close($conn);

$dadata_on = defined('BM_DADATA_TOKEN') && BM_DADATA_TOKEN !== '';

$bm_css_ver = is_file(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time();
$bm_js_ver = is_file(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title>Оформление заказа — BASEMOOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">
    <link rel="stylesheet" href="style.css?v=<?= (int) $bm_css_ver ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .co-page {
            --co-border: #e8e8e8;
            --co-muted: #757575;
            --co-green: #1a9f5c;
            --co-radius: 10px;
            font-family: 'Inter', var(--font-main), system-ui, sans-serif;
            background: #fff;
            padding: calc(var(--header-height) + 1.5rem) 0 4rem;
            min-height: 60vh;
        }
        .co-page h1 {
            font-family: 'Inter', var(--font-main), sans-serif;
            font-size: clamp(1.5rem, 2.5vw, 1.85rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 1.5rem;
            color: #0a0a0a;
        }
        .co-back {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            color: var(--co-muted);
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        .co-back:hover { color: #000; }
        .co-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            align-items: start;
        }
        @media (min-width: 992px) {
            .co-layout { grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); gap: 1.75rem; }
        }
        .co-card {
            background: #fff;
            border: 1px solid var(--co-border);
            border-radius: var(--co-radius);
            padding: 1.35rem 1.4rem 1.45rem;
            margin-bottom: 1rem;
        }
        .co-card:last-child { margin-bottom: 0; }
        .co-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .co-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #0a0a0a;
            margin: 0;
        }
        .co-btn-ghost {
            background: #f3f3f3;
            border: none;
            color: #222;
            font-size: 0.8125rem;
            font-weight: 500;
            padding: 0.4rem 0.85rem;
            border-radius: 8px;
            cursor: pointer;
            font-family: inherit;
        }
        .co-btn-ghost:hover { background: #e9e9e9; }
        .co-field { margin-bottom: 0.95rem; }
        .co-field-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #1a1a1a;
            margin-bottom: 0.4rem;
        }
        .co-input,
        .co-textarea,
        .co-select {
            width: 100%;
            padding: 0.75rem 0.9rem;
            border: 1px solid var(--co-border);
            border-radius: 8px;
            font-size: 0.9375rem;
            color: #111;
            background: #fff;
            font-family: inherit;
        }
        .co-input::placeholder,
        .co-textarea::placeholder { color: #a0a0a0; }
        .co-textarea { min-height: 5.5rem; resize: none; }
        .co-field--full { grid-column: 1 / -1; }
        .co-address-wrap { position: relative; }
        .co-suggest-list {
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            z-index: 25;
            margin: 4px 0 0;
            padding: 0;
            list-style: none;
            background: #fff;
            border: 1px solid var(--co-border);
            border-radius: 8px;
            max-height: 240px;
            overflow-y: auto;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        .co-suggest-list li {
            padding: 10px 12px;
            font-size: 0.875rem;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            line-height: 1.35;
        }
        .co-suggest-list li:last-child { border-bottom: none; }
        .co-suggest-list li:hover,
        .co-suggest-list li[aria-selected="true"] { background: #f6f6f6; }
        .co-suggest-empty { font-size: 0.75rem; color: var(--co-muted); margin-top: 0.35rem; }
        .co-hint {
            font-size: 0.75rem;
            color: var(--co-muted);
            margin-top: 0.35rem;
            line-height: 1.4;
        }
        .co-grid-2 {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.25rem 1.25rem;
        }
        @media (min-width: 640px) {
            .co-grid-2 { grid-template-columns: 1fr 1fr; }
        }
        .co-btn-black {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #0a0a0a;
            color: #fff;
            border: none;
            padding: 0.7rem 1.35rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            margin-top: 0.25rem;
        }
        .co-btn-black:hover { background: #222; }
        .co-btn-black.w-100 { width: 100%; }
        .co-btn-black.lg { padding: 0.95rem 1.25rem; font-size: 0.9375rem; }
        .co-delivery-body,
        .co-payment-body { font-size: 0.9375rem; color: #333; line-height: 1.55; }
        .co-delivery-body p,
        .co-payment-body p { margin: 0 0 0.35rem; }
        .co-delivery-body p:last-child,
        .co-payment-body p:last-child { margin-bottom: 0; }
        .co-edit-panel {
            display: none;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #eee;
        }
        .co-edit-panel.is-open { display: block; }
        .co-err {
            background: #fdeaea;
            border: 1px solid #f5c2c2;
            color: #a42828;
            padding: 0.85rem 1rem;
            border-radius: var(--co-radius);
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }
        .co-sidebar-sticky {
            position: relative;
        }
        @media (min-width: 992px) {
            .co-sidebar-sticky { position: sticky; top: calc(var(--header-height) + 1rem); }
        }
        .co-total-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #eee;
        }
        .co-total-head span { font-size: 0.95rem; font-weight: 500; }
        .co-total-sum {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .co-rows { font-size: 0.875rem; margin-bottom: 1rem; }
        .co-row-line {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 0.5rem;
            margin-bottom: 0.45rem;
            color: #333;
        }
        .co-row-line--muted { color: var(--co-muted); font-size: 0.8125rem; }
        .co-dots {
            flex: 1;
            border-bottom: 1px dotted #ccc;
            min-width: 0.5rem;
            margin: 0 0.35rem;
            position: relative;
            top: -0.15rem;
        }
        .co-save { color: var(--co-green); font-weight: 600; }
        .co-promo-wrap {
            position: relative;
            margin-bottom: 0.85rem;
        }
        .co-promo-wrap .co-input { padding-right: 2.5rem; }
        .co-promo-go {
            position: absolute;
            right: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            padding: 0.25rem;
            font-size: 1rem;
        }
        .co-promo-go:hover { color: #000; }
        .co-mini {
            font-size: 0.75rem;
            color: var(--co-muted);
            line-height: 1.45;
            margin-bottom: 1rem;
        }
        .co-mini p { margin: 0 0 0.2rem; }
        .co-legal {
            font-size: 0.6875rem;
            color: var(--co-muted);
            line-height: 1.45;
            margin: 0.75rem 0 0;
            text-align: center;
        }
        .co-legal a { color: var(--co-muted); text-decoration: underline; }
        .co-empty-cart {
            padding: 2rem;
            text-align: center;
            color: var(--co-muted);
        }
    </style>
    <?php include __DIR__ . '/yandex-metrika.php'; ?>
</head>
<body>
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner"></div>
    </div>

    <header class="header">
        <div class="header-svg-container">
            <img src="img/header.svg" alt="BASEMOOD Header" class="header-svg">
        </div>
        <nav class="nav-container">
            <div class="nav-inner">
                <div class="logo">
                    <a href="index.php" class="logo-link" aria-label="На главную страницу BASEMOOD">
                        <img src="img/logo.svg" alt="BASEMOOD" class="logo-img">
                        <span class="logo-text-hidden">BASEMOOD</span>
                    </a>
                </div>
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Открыть меню">
                    <span></span><span></span><span></span>
                </button>
                <ul class="nav-menu" id="navMenu">
                    <?php include __DIR__ . '/partials/nav_menu_inner.php'; ?>
                </ul>
                <div class="nav-actions">
                    <button class="nav-icon" id="searchButton" aria-label="Поиск"><i class="fas fa-search"></i></button>
                    <button class="nav-icon" id="userButton" aria-label="Личный кабинет"><i class="fas fa-user"></i></button>
                    <button class="nav-icon" id="wishlistButton" aria-label="Избранное"><i class="far fa-heart"></i></button>
                    <button class="nav-icon cart-icon" id="cartButton" aria-label="Корзина">
                        <i class="fas fa-basket-shopping"></i><span class="cart-count">0</span>
                    </button>
                </div>
            </div>
        </nav>
        <div class="search-container" id="searchContainer">
            <input type="text" class="search-input" placeholder="Поиск товаров...">
            <button class="search-close" id="searchClose"><i class="fas fa-times"></i></button>
        </div>
    </header>

    <main class="co-page">
        <div class="container">
            <a class="co-back" href="account.php#cart"><i class="fas fa-arrow-left" aria-hidden="true"></i> В корзину</a>
            <h1>Оформление заказа</h1>

            <?php if ($err !== ''): ?>
                <div class="co-err"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div id="coEmpty" class="co-empty-cart" style="display:none;">
                <p>Корзина пуста. Перейдите в <a href="catalog.php">каталог</a>, чтобы добавить товары.</p>
            </div>

            <form id="coForm" method="post" action="checkout_process.php" class="co-layout" style="display:none;">
                <input type="hidden" name="checkout_csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="cart_json" id="cart_json" value="[]">

                <div class="co-main-col">
                    <div class="co-card">
                        <h2 class="co-card-title" style="margin-bottom:1rem;">Покупатель</h2>
                        <div class="co-grid-2">
                            <div class="co-field co-field--full">
                                <label class="co-field-label" for="customer_name">Имя и фамилия *</label>
                                <input class="co-input" type="text" id="customer_name" name="customer_name" required maxlength="255" autocomplete="name" placeholder="Например: Иван Иванов">
                                <p class="co-hint">Используется для получения заказа, указывайте корректно</p>
                            </div>
                            <div class="co-field">
                                <label class="co-field-label" for="customer_email">Почта *</label>
                                <input class="co-input" type="email" id="customer_email" name="customer_email" required maxlength="255" autocomplete="email" placeholder="Например: shop@example.com">
                            </div>
                            <div class="co-field">
                                <label class="co-field-label" for="customer_phone">Номер телефона *</label>
                                <input class="co-input" type="tel" id="customer_phone" name="customer_phone" required maxlength="64" autocomplete="tel">
                            </div>
                        </div>
                        <div class="co-field" style="margin-bottom:0;">
                            <label class="co-field-label" for="delivery_address">Адрес доставки *</label>
                            <div class="co-address-wrap">
                                <textarea class="co-textarea" id="delivery_address" name="delivery_address" required rows="3" autocomplete="street-address" placeholder="Начните вводить город или улицу…"></textarea>
                                <ul class="co-suggest-list" id="coAddressSuggest" hidden role="listbox" aria-label="Подсказки адреса"></ul>
                            </div>
                            <?php if (!$dadata_on): ?>
                                <p class="co-suggest-empty">Подсказки по адресу сейчас отключены. После настройки сервиса подсказок они появятся здесь автоматически.</p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="co-btn-black" id="coContinue">Продолжить</button>
                    </div>

                    <div class="co-card" id="coDeliveryCard">
                        <h2 class="co-card-title" style="margin-bottom:1rem;">Способ доставки</h2>
                        <div class="co-field" style="margin-bottom:0;">
                            <label class="co-field-label" for="delivery_id">Выберите способ</label>
                            <select class="co-select" id="delivery_id" name="delivery_id" required>
                                <?php foreach (bm_checkout_fixed_delivery_options() as $d): ?>
                                    <option value="<?php echo (int) $d['id']; ?>" data-price="<?php echo (int) $d['price_rub']; ?>" data-summary="<?php echo htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($d['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="co-card" id="coPaymentCard">
                        <div class="co-card-head">
                            <h2 class="co-card-title">Способ оплаты</h2>
                            <button type="button" class="co-btn-ghost co-toggle-edit" data-target="coPaymentEdit" aria-expanded="false">Изменить</button>
                        </div>
                        <div class="co-payment-body" id="coPaymentSummary"></div>
                        <div class="co-edit-panel" id="coPaymentEdit">
                            <label class="co-field-label" for="payment_method">Способ оплаты</label>
                            <select class="co-select" id="payment_method" name="payment_method" required>
                                <option value="СБП" selected>СБП</option>
                                <option value="Банковская карта (ЮКасса)">Банковская карта (ЮКасса)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="co-sidebar-sticky">
                    <div class="co-card">
                        <div class="co-total-head">
                            <span>Итого:</span>
                            <strong class="co-total-sum" id="coGrand">0 ₽</strong>
                        </div>
                        <div class="co-rows">
                            <div class="co-row-line">
                                <span>Товаров на</span><span class="co-dots" aria-hidden="true"></span>
                                <span id="coSub">0 ₽</span>
                            </div>
                            <div class="co-row-line co-row-line--muted">
                                <span>НДС (7%, включён в цену)</span><span class="co-dots" aria-hidden="true"></span>
                                <span id="coVat">0 ₽</span>
                            </div>
                            <div class="co-row-line">
                                <span>Доставка</span><span class="co-dots" aria-hidden="true"></span>
                                <span id="coShip">0 ₽</span>
                            </div>
                            <div class="co-row-line" id="coSaveRow" style="display:none;">
                                <span class="co-save">Экономия</span>
                                <span class="co-dots" aria-hidden="true"></span>
                                <span class="co-save" id="coSave">0 ₽</span>
                            </div>
                        </div>
                        <div class="co-promo-wrap">
                            <label class="visually-hidden" for="promo_code">Промокод</label>
                            <input class="co-input" type="text" id="promo_code" name="promo_code" maxlength="32" placeholder="Есть промокод или сертификат?" autocomplete="off">
                            <button type="button" class="co-promo-go" id="coPromoDummy" aria-label="Применить промокод"><i class="fas fa-arrow-right"></i></button>
                        </div>
                        <div class="co-mini" id="coMiniDp"></div>
                        <button type="submit" class="co-btn-black w-100 lg">Оформить заказ</button>
                        <p class="co-legal">Нажимая на кнопку, вы соглашаетесь на обработку персональных данных и с <a href="offer.php" target="_blank" rel="noopener">публичной офертой</a></p>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>

    <div class="auth-modal-overlay" id="authModal">
        <div class="auth-modal-box">
            <button class="auth-modal-close" id="authModalClose" aria-label="Закрыть"><i class="fas fa-times"></i></button>
            <h2 class="auth-modal-title">Войти в аккаунт</h2>
            <div class="auth-modal-msg" id="authModalMsg" style="display:none;"></div>
            <form id="authModalForm" novalidate>
                <div class="auth-modal-field">
                    <input type="email" id="authEmail" class="auth-modal-input" placeholder="Email" required autocomplete="email">
                </div>
                <div class="auth-modal-field">
                    <input type="password" id="authPassword" class="auth-modal-input" placeholder="Пароль" required autocomplete="current-password">
                </div>
                <button type="submit" class="auth-modal-btn" id="authModalSubmit">Войти</button>
            </form>
            <div class="auth-modal-footer">
                <p>Нет аккаунта? <a href="register.php" class="auth-modal-link">Зарегистрироваться</a></p>
            </div>
        </div>
    </div>

    <script src="script.js?v=<?= (int) $bm_js_ver ?>"></script>
    <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>; window.coAddressSuggestOn = <?= $dadata_on ? 'true' : 'false' ?>;</script>
    <script>
    (function () {
        function money(n) {
            return Math.round(Number(n) || 0).toLocaleString('ru-RU') + ' ₽';
        }
        function moneyDec(n) {
            return (Math.round((Number(n) || 0) * 100) / 100).toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₽';
        }
        function vatFromSubtotal(sub) {
            return sub * 7 / 107;
        }

        var form = document.getElementById('coForm');
        var emptyEl = document.getElementById('coEmpty');
        var raw = localStorage.getItem('basemood-cart') || '[]';
        document.getElementById('cart_json').value = raw;
        var cart = [];
        try { cart = JSON.parse(raw); } catch (e) {}

        if (!cart.length) {
            emptyEl.style.display = '';
        } else {
            form.style.display = 'grid';
            emptyEl.style.display = 'none';
        }

        var subtotal = 0;
        var savings = 0;
        cart.forEach(function (item) {
            if (!item || typeof item !== 'object') return;
            var q = Math.max(1, parseInt(item.quantity, 10) || 1);
            var p = parseInt(item.price, 10) || 0;
            var oldP = item.oldPrice != null ? parseInt(item.oldPrice, 10) : NaN;
            subtotal += p * q;
            if (!isNaN(oldP) && oldP > p) savings += (oldP - p) * q;
        });

        var coSub = document.getElementById('coSub');
        var coVat = document.getElementById('coVat');
        var coShip = document.getElementById('coShip');
        var coGrand = document.getElementById('coGrand');
        var coSaveRow = document.getElementById('coSaveRow');
        var coSave = document.getElementById('coSave');
        var deliverySelect = document.getElementById('delivery_id');
        var paymentSelect = document.getElementById('payment_method');
        var coPaymentSummary = document.getElementById('coPaymentSummary');
        var coMiniDp = document.getElementById('coMiniDp');

        function deliveryFee() {
            if (!deliverySelect) return 0;
            var opt = deliverySelect.options[deliverySelect.selectedIndex];
            if (!opt) return 0;
            var pr = parseInt(opt.getAttribute('data-price'), 10);
            return isNaN(pr) ? 0 : pr;
        }

        function deliveryTitleText() {
            if (!deliverySelect) return '';
            var opt = deliverySelect.options[deliverySelect.selectedIndex];
            if (!opt) return '';
            var s = opt.getAttribute('data-summary');
            return (s && s.trim()) ? s.trim() : (opt.textContent || '').trim();
        }

        function escapeHtml(s) {
            if (s == null) return '';
            var d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        function refreshPaymentSummary() {
            if (!coPaymentSummary || !paymentSelect) return;
            var opt = paymentSelect.options[paymentSelect.selectedIndex];
            var label = opt ? opt.textContent.trim() : '';
            coPaymentSummary.innerHTML = '<p>' + escapeHtml(label) + '</p>';
        }

        function refreshMini() {
            if (!coMiniDp) return;
            var d = deliveryTitleText();
            var p = paymentSelect && paymentSelect.options[paymentSelect.selectedIndex] ? paymentSelect.options[paymentSelect.selectedIndex].textContent.trim() : '';
            coMiniDp.innerHTML = '<p>Доставка: ' + escapeHtml(d) + '</p><p>Оплата: ' + escapeHtml(p) + '</p>';
        }

        function refreshTotals() {
            var vat = vatFromSubtotal(subtotal);
            var ship = deliveryFee();
            var grand = Math.max(0, subtotal + ship);
            coSub.textContent = money(subtotal);
            coVat.textContent = moneyDec(vat);
            coShip.textContent = money(ship);
            coGrand.textContent = money(grand);
            if (savings > 0) {
                coSaveRow.style.display = '';
                coSave.textContent = money(savings);
            } else {
                coSaveRow.style.display = 'none';
            }
        }

        function refreshAll() {
            refreshTotals();
            refreshPaymentSummary();
            refreshMini();
        }

        if (cart.length) {
            refreshAll();
            if (deliverySelect) {
                deliverySelect.addEventListener('change', refreshAll);
            }
            if (paymentSelect) {
                paymentSelect.addEventListener('change', refreshAll);
            }
        }

        document.querySelectorAll('.co-toggle-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-target');
                var panel = document.getElementById(id);
                if (!panel) return;
                var open = panel.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        var coContinue = document.getElementById('coContinue');
        if (coContinue) {
            coContinue.addEventListener('click', function () {
                var el = document.getElementById('coDeliveryCard');
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }

        (function addressSuggest() {
            if (!window.coAddressSuggestOn) return;
            var addr = document.getElementById('delivery_address');
            var list = document.getElementById('coAddressSuggest');
            if (!addr || !list) return;
            var t = null;
            var selIdx = -1;

            function hideList() {
                list.hidden = true;
                list.innerHTML = '';
                selIdx = -1;
            }

            function render(items) {
                list.innerHTML = '';
                if (!items.length) {
                    hideList();
                    return;
                }
                items.forEach(function (text, i) {
                    var li = document.createElement('li');
                    li.setAttribute('role', 'option');
                    li.textContent = text;
                    li.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        addr.value = text;
                        hideList();
                    });
                    list.appendChild(li);
                });
                list.hidden = false;
            }

            function scheduleFetch() {
                clearTimeout(t);
                t = setTimeout(function () {
                    var q = addr.value.trim();
                    if (q.length < 2) {
                        hideList();
                        return;
                    }
                    fetch('checkout_address_suggest.php?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            if (data && Array.isArray(data.suggestions)) {
                                render(data.suggestions);
                            } else hideList();
                        })
                        .catch(function () { hideList(); });
                }, 280);
            }

            addr.addEventListener('input', function () {
                scheduleFetch();
            });
            addr.addEventListener('focus', function () {
                if (addr.value.trim().length >= 2 && list.children.length) list.hidden = false;
            });
            addr.addEventListener('blur', function () {
                setTimeout(hideList, 200);
            });
            addr.addEventListener('keydown', function (e) {
                if (list.hidden || !list.children.length) return;
                var items = list.querySelectorAll('li');
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selIdx = Math.min(selIdx + 1, items.length - 1);
                    items.forEach(function (el, i) { el.setAttribute('aria-selected', i === selIdx ? 'true' : 'false'); });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selIdx = Math.max(selIdx - 1, 0);
                    items.forEach(function (el, i) { el.setAttribute('aria-selected', i === selIdx ? 'true' : 'false'); });
                } else if (e.key === 'Enter' && selIdx >= 0 && items[selIdx]) {
                    e.preventDefault();
                    items[selIdx].dispatchEvent(new MouseEvent('mousedown'));
                } else if (e.key === 'Escape') {
                    hideList();
                }
            });
        })();

        var coPromoDummy = document.getElementById('coPromoDummy');
        if (coPromoDummy) {
            coPromoDummy.addEventListener('click', function () {
                var inp = document.getElementById('promo_code');
                if (inp) inp.focus();
            });
        }
    })();

    document.addEventListener('DOMContentLoaded', function () {
        var sp = document.getElementById('loadingSpinner');
        if (sp) {
            setTimeout(function () {
                sp.classList.add('hidden');
                setTimeout(function () { if (sp.parentNode) sp.parentNode.removeChild(sp); }, 300);
            }, 400);
        }
    });
    </script>
</body>
</html>
