<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/product_images_helpers.php';
require_once __DIR__ . '/catalog_helpers.php';

if (!$conn) {
    die('Ошибка подключения: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

$result = mysqli_query($conn, bm_sql_products_for_catalog($conn));
if (!$result) {
    die('Ошибка запроса: ' . mysqli_error($conn));
}

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

mysqli_close($conn);

$bm_css_ver = is_file(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time();
$categories = bm_product_categories();
$catalog_prefill_cat = isset($_GET['cat']) ? bm_normalize_category((string) $_GET['cat']) : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="description" content="Каталог BASEMOOD — одежда">
    <title>Каталог — BASEMOOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">
    <link rel="stylesheet" href="style.css?v=<?= (int) $bm_css_ver ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-card { cursor: pointer; }
        .product-card a { text-decoration: none; color: inherit; display: block; }
        .products .product-image-container,
        .products .product-image-wrapper,
        .products .image-slider,
        .products .product-image,
        .product-image-container,
        .product-image-wrapper,
        .image-slider,
        .product-image {
            background: transparent !important;
            background-color: transparent !important;
        }
        :root {
            --cat-accent: #0f0f0f;
            --cat-muted: #888;
            --cat-border: #e8e8e8;
            --cat-bg: #f5f5f5;
        }
        .catalog-page .catalog-topbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px 28px;
            justify-content: flex-start;
            margin-bottom: 1.75rem;
        }
        .catalog-page .catalog-page-heading {
            margin: 0;
            font-size: 1.9rem;
            font-weight: 500;
            text-align: left;
            flex: 0 0 auto;
        }
        .catalog-page .catalog-toolbar {
            margin-bottom: 0;
            flex: 1 1 auto;
            min-width: 0;
        }
        .catalog-page .lk-filters {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 0;
        }
        .catalog-page .lk-filter { position: relative; min-width: 220px; }
        .catalog-page .lk-filter-input { display: none; }
        .catalog-page .lk-filter-trigger {
            width: 100%;
            min-height: 48px;
            padding: 12px 18px;
            border: 1px solid #bdbdbd;
            border-radius: 12px;
            font-size: 13px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            color: #1a1a1a;
            -webkit-text-fill-color: #1a1a1a;
            background: #fff;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            transition: border-color .2s, box-shadow .2s;
        }
        .catalog-page .lk-filter-trigger:hover { border-color: #8d8d8d; }
        .catalog-page .lk-filter.open .lk-filter-trigger {
            border-color: var(--cat-accent);
            box-shadow: 0 6px 18px rgba(15,15,15,.06);
        }
        .catalog-page .lk-filter-trigger i {
            color: var(--cat-muted);
            transition: transform .24s ease, color .2s;
            flex-shrink: 0;
        }
        .catalog-page .lk-filter.open .lk-filter-trigger i {
            transform: rotate(180deg);
            color: var(--cat-accent);
        }
        .catalog-page .lk-filter-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #d9d9d9;
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(0,0,0,.14);
            padding: 10px;
            opacity: 0;
            transform: translateY(-6px) scale(.98);
            pointer-events: none;
            transition: opacity .22s ease, transform .22s ease;
            z-index: 50;
        }
        .catalog-page .lk-filter.open .lk-filter-menu {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
        .catalog-page .catalog-price-filter .lk-filter-menu {
            left: 0;
            right: 0;
            min-width: 0;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            padding: 14px 16px 16px;
        }
        .catalog-page .catalog-price-menu-body {
            padding-top: 4px;
            max-width: 100%;
            overflow: hidden;
        }
        .catalog-page .catalog-price-menu-values {
            font-size: 13px;
            font-weight: 600;
            color: var(--cat-accent);
            margin-bottom: 12px;
            text-align: center;
        }
        .catalog-page .lk-filter-option {
            width: 100%;
            border: none;
            background: transparent;
            text-align: left;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            color: #1a1a1a;
            -webkit-text-fill-color: #1a1a1a;
            cursor: pointer;
            transition: background .18s, color .18s;
        }
        .catalog-page .lk-filter-option:hover { background: #f4f4f4; }
        .catalog-page .lk-filter-option.active {
            background: #ececec;
            color: var(--cat-accent);
            font-weight: 600;
        }
        .catalog-page .catalog-price-trigger-text {
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .catalog-dual-range {
            position: relative;
            height: 22px;
            margin: 0 2px;
            width: calc(100% - 4px);
            max-width: 100%;
            box-sizing: border-box;
            --range-min: 2500;
            --range-max: 30000;
        }
        .catalog-dual-range::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 3px;
            border-radius: 2px;
            background: #e4e4e4;
            z-index: 0;
        }
        .catalog-dual-range-fill {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            height: 3px;
            border-radius: 2px;
            background: var(--cat-accent);
            z-index: 1;
            pointer-events: none;
            left: 0;
            width: 100%;
        }
        .catalog-dual-range input[type="range"] {
            position: absolute;
            left: 0;
            width: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin: 0;
            padding: 0;
            background: transparent;
            pointer-events: none;
            -webkit-appearance: none;
            appearance: none;
            height: 18px;
            z-index: 2;
        }
        .catalog-dual-range input[type="range"]::-webkit-slider-runnable-track {
            height: 3px;
            background: transparent;
            border: none;
        }
        .catalog-dual-range input[type="range"]::-webkit-slider-thumb {
            pointer-events: auto;
            -webkit-appearance: none;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid var(--cat-accent);
            margin-top: -5px;
            box-shadow: 0 1px 3px rgba(0,0,0,.12);
            cursor: grab;
        }
        .catalog-dual-range input[type="range"]::-moz-range-track {
            height: 3px;
            background: transparent;
            border: none;
        }
        .catalog-dual-range input[type="range"]::-moz-range-thumb {
            pointer-events: auto;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid var(--cat-accent);
            box-shadow: 0 1px 3px rgba(0,0,0,.12);
            cursor: grab;
        }
        .catalog-dual-range input.catalog-range-max { z-index: 3; }
        .catalog-empty-hint {
            display: none;
            text-align: center;
            padding: 2rem;
            color: var(--text-light, #666);
        }
        .catalog-empty-hint.visible { display: block; }
        @media (max-width: 1024px) {
            .catalog-page .catalog-topbar { gap: 12px 16px; }
            .catalog-page .catalog-page-heading {
                flex: 1 1 100%;
                font-size: 1.55rem;
            }
            .catalog-page .lk-filters { flex-wrap: wrap; gap: 10px; }
            .catalog-page .lk-filter {
                flex: 1 1 calc(50% - 6px);
                min-width: min(160px, 100%);
                max-width: 100%;
            }
            .catalog-page .catalog-price-filter {
                flex: 1 1 100%;
                min-width: 0;
            }
            .catalog-page .lk-filter-trigger {
                min-height: 44px;
                padding: 10px 12px;
                font-size: 12px;
            }
        }
        @media (max-width: 520px) {
            .catalog-page .lk-filters {
                flex-direction: column;
                flex-wrap: nowrap;
                gap: 10px;
            }
            .catalog-page .lk-filter,
            .catalog-page .catalog-price-filter {
                flex: none;
                width: 100%;
                min-width: 0;
            }
            .catalog-page .lk-filter-menu {
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                box-sizing: border-box;
            }
            .catalog-page .catalog-price-filter .lk-filter-menu {
                position: absolute;
                width: 100% !important;
            }
            .catalog-page .lk-filter-option {
                font-size: 13px;
                padding: 10px 12px;
            }
            .catalog-page .catalog-price-menu-values {
                font-size: 12px;
            }
            .catalog-page .catalog-dual-range {
                margin: 0 6px;
                width: calc(100% - 12px);
            }
        }
    </style>
    <?php include __DIR__ . '/yandex-metrika.php'; ?>
</head>
<body class="catalog-page">
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
            <input type="text" class="search-input" placeholder="Поиск товаров..." autocomplete="off">
            <button class="search-close" id="searchClose"><i class="fas fa-times"></i></button>
            <div class="search-autocomplete" id="searchAutocomplete" aria-live="polite"></div>
        </div>
    </header>

    <main class="main">
        <section class="products" id="collections">
            <div class="container">
                <div class="catalog-topbar">
                <h2 class="section-title catalog-page-heading">Каталог</h2>

                <div class="catalog-toolbar">
                    <div class="lk-filters">
                        <div class="lk-filter" data-filter="catalogSort">
                            <input type="hidden" class="lk-filter-input" id="catalogSort" value="default">
                            <button type="button" class="lk-filter-trigger">
                                <span>По умолчанию</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="lk-filter-menu">
                                <button type="button" class="lk-filter-option active" data-value="default">По умолчанию</button>
                                <button type="button" class="lk-filter-option" data-value="price_asc">Цена: по возрастанию</button>
                                <button type="button" class="lk-filter-option" data-value="price_desc">Цена: по убыванию</button>
                                <button type="button" class="lk-filter-option" data-value="popularity">По популярности</button>
                            </div>
                        </div>
                        <div class="lk-filter" data-filter="catalogCategory">
                            <input type="hidden" class="lk-filter-input" id="catalogCategory" value="">
                            <button type="button" class="lk-filter-trigger">
                                <span>Все категории</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="lk-filter-menu">
                                <button type="button" class="lk-filter-option active" data-value="">Все категории</button>
                                <?php foreach ($categories as $slug => $label): ?>
                                <button type="button" class="lk-filter-option" data-value="<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($label) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="lk-filter catalog-price-filter" data-filter="catalogPrice">
                            <button type="button" class="lk-filter-trigger" aria-expanded="false" aria-haspopup="true" id="catalogPriceTrigger">
                                <span class="catalog-price-trigger-text">Цена: <span id="catalogPriceTriggerRange">2 500 — 30 000</span> ₽</span>
                                <i class="fas fa-chevron-down" aria-hidden="true"></i>
                            </button>
                            <div class="lk-filter-menu catalog-price-dropdown" role="dialog" aria-label="Диапазон цены">
                                <div class="catalog-price-menu-body">
                                    <div class="catalog-price-menu-values">
                                        <span id="catalogPriceMinLbl">2 500</span> — <span id="catalogPriceMaxLbl">30 000</span> ₽
                                    </div>
                                    <div class="catalog-dual-range" id="catalogDualRange">
                                        <div class="catalog-dual-range-fill" id="catalogRangeFill"></div>
                                        <input type="range" id="catalogPriceMin" class="catalog-range-min" min="2500" max="30000" step="100" value="2500" aria-label="Минимальная цена">
                                        <input type="range" id="catalogPriceMax" class="catalog-range-max" min="2500" max="30000" step="100" value="30000" aria-label="Максимальная цена">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <?php if (count($products) === 0): ?>
                <p style="color:#666;">В каталоге пока нет товаров.</p>
                <?php else: ?>
                <div class="product-grid" id="productGrid">
                    <?php foreach ($products as $product): ?>
                    <?php include __DIR__ . '/partials/product_card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <p class="catalog-empty-hint" id="catalogNoResults">Нет товаров по выбранным фильтрам.</p>
                <?php endif; ?>
            </div>
        </section>
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

    <script src="script.js?v=<?= (int) (is_file(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time()) ?>"></script>
    <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
    <script>window.catalogPrefillCategory = <?= json_encode($catalog_prefill_cat, JSON_UNESCAPED_UNICODE) ?>;</script>
    <script>
    (function () {
        var grid = document.getElementById('productGrid');

        var priceMinEl = document.getElementById('catalogPriceMin');
        var priceMaxEl = document.getElementById('catalogPriceMax');
        var rangeFill = document.getElementById('catalogRangeFill');
        var minLbl = document.getElementById('catalogPriceMinLbl');
        var maxLbl = document.getElementById('catalogPriceMaxLbl');
        var triggerRangeEl = document.getElementById('catalogPriceTriggerRange');
        var PRICE_R_MIN = 2500;
        var PRICE_R_MAX = 30000;
        var sortInput = document.getElementById('catalogSort');
        var catInput = document.getElementById('catalogCategory');
        var noResults = document.getElementById('catalogNoResults');

        var preCat = typeof window.catalogPrefillCategory === 'string' ? window.catalogPrefillCategory : '';
        if (preCat && catInput) {
            catInput.value = preCat;
            var catFilter = document.querySelector('.catalog-page [data-filter="catalogCategory"]');
            if (catFilter) {
                var clabel = catFilter.querySelector('.lk-filter-trigger span');
                var copts = catFilter.querySelectorAll('.lk-filter-option');
                copts.forEach(function (o) {
                    o.classList.toggle('active', (o.dataset.value || '') === preCat);
                });
                var activeOpt = catFilter.querySelector('.lk-filter-option.active');
                if (clabel && activeOpt) {
                    clabel.textContent = activeOpt.textContent.trim();
                }
            }
        }

        function fmt(n) {
            return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        }

        function getPriceBounds() {
            var a = parseInt(priceMinEl.value, 10);
            var b = parseInt(priceMaxEl.value, 10);
            if (a > b) { var t = a; a = b; b = t; }
            return { min: a, max: b };
        }

        function priceToPercent(val) {
            return ((val - PRICE_R_MIN) / (PRICE_R_MAX - PRICE_R_MIN)) * 100;
        }

        function updateRangeVisual() {
            var a = parseInt(priceMinEl.value, 10);
            var b = parseInt(priceMaxEl.value, 10);
            var lo = Math.min(a, b);
            var hi = Math.max(a, b);
            if (rangeFill) {
                var p0 = priceToPercent(lo);
                var p1 = priceToPercent(hi);
                rangeFill.style.left = p0 + '%';
                rangeFill.style.width = Math.max(0, p1 - p0) + '%';
            }
            if (triggerRangeEl) triggerRangeEl.textContent = fmt(lo) + ' — ' + fmt(hi);
            if (minLbl) minLbl.textContent = fmt(lo);
            if (maxLbl) maxLbl.textContent = fmt(hi);
        }

        function onRangeInput() {
            var a = parseInt(priceMinEl.value, 10);
            var b = parseInt(priceMaxEl.value, 10);
            if (a > b) {
                if (this === priceMinEl) priceMaxEl.value = a;
                else priceMinEl.value = b;
            }
            updateRangeVisual();
            applyFilters();
        }

        if (priceMinEl && priceMaxEl) {
            function zMinActive() {
                priceMinEl.style.zIndex = '5';
                priceMaxEl.style.zIndex = '3';
            }
            function zMaxActive() {
                priceMaxEl.style.zIndex = '5';
                priceMinEl.style.zIndex = '3';
            }
            priceMinEl.addEventListener('mousedown', zMinActive);
            priceMinEl.addEventListener('touchstart', zMinActive, { passive: true });
            priceMaxEl.addEventListener('mousedown', zMaxActive);
            priceMaxEl.addEventListener('touchstart', zMaxActive, { passive: true });
            priceMinEl.addEventListener('input', onRangeInput);
            priceMaxEl.addEventListener('input', onRangeInput);
            priceMinEl.style.zIndex = '3';
            priceMaxEl.style.zIndex = '4';
            updateRangeVisual();
        }

        document.querySelectorAll('.catalog-page .lk-filter').forEach(function (filter) {
            if (filter.classList.contains('catalog-price-filter')) {
                var ptrigger = filter.querySelector('.lk-filter-trigger');
                if (!ptrigger) return;
                ptrigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    document.querySelectorAll('.catalog-page .lk-filter.open').forEach(function (openFilter) {
                        if (openFilter !== filter) {
                            openFilter.classList.remove('open');
                            var ot = openFilter.querySelector('.lk-filter-trigger[aria-expanded]');
                            if (ot) ot.setAttribute('aria-expanded', 'false');
                        }
                    });
                    var willOpen = !filter.classList.contains('open');
                    filter.classList.toggle('open', willOpen);
                    ptrigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });
                return;
            }

            var trigger = filter.querySelector('.lk-filter-trigger');
            var label = trigger ? trigger.querySelector('span') : null;
            var input = filter.querySelector('.lk-filter-input');
            var options = filter.querySelectorAll('.lk-filter-option');
            if (!trigger || !label || !input || !options.length) return;

            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.catalog-page .lk-filter.open').forEach(function (openFilter) {
                    if (openFilter !== filter) {
                        openFilter.classList.remove('open');
                        var ot = openFilter.querySelector('.lk-filter-trigger[aria-expanded]');
                        if (ot) ot.setAttribute('aria-expanded', 'false');
                    }
                });
                filter.classList.toggle('open');
            });

            options.forEach(function (option) {
                option.addEventListener('click', function () {
                    options.forEach(function (item) { item.classList.remove('active'); });
                    option.classList.add('active');
                    input.value = option.dataset.value || '';
                    label.textContent = option.textContent.trim();
                    filter.classList.remove('open');
                    applyFilters();
                });
            });
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.catalog-page .lk-filter')) {
                document.querySelectorAll('.catalog-page .lk-filter.open').forEach(function (f) {
                    f.classList.remove('open');
                    var t = f.querySelector('.lk-filter-trigger[aria-expanded]');
                    if (t) t.setAttribute('aria-expanded', 'false');
                });
            }
        });

        function applyFilters() {
            if (!grid) return;
            var bounds = getPriceBounds();
            var cat = catInput ? catInput.value : '';
            var searchQ = '';
            var si = document.querySelector('.search-input');
            if (si && si.value.trim()) {
                searchQ = si.value.trim().toLowerCase();
            }
            var cards = grid.querySelectorAll('.product-card');
            var visible = 0;
            cards.forEach(function (card) {
                var price = parseInt(card.getAttribute('data-price'), 10) || 0;
                var c = card.getAttribute('data-category') || '';
                var okPrice = price >= bounds.min && price <= bounds.max;
                var okCat = !cat || c === cat;
                var titleEl = card.querySelector('.product-title');
                var nameOk = !searchQ || (titleEl && titleEl.textContent.toLowerCase().indexOf(searchQ) !== -1);
                var show = okPrice && okCat && nameOk;
                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });
            if (noResults) {
                noResults.classList.toggle('visible', visible === 0);
            }
            sortVisible();
        }

        function sortVisible() {
            if (!grid) return;
            var mode = sortInput ? sortInput.value : 'default';
            var cards = Array.prototype.slice.call(grid.querySelectorAll('.product-card'));
            var visible = cards.filter(function (c) { return c.style.display !== 'none'; });
            var hidden = cards.filter(function (c) { return c.style.display === 'none'; });

            function num(attr, el) {
                return parseInt(el.getAttribute(attr), 10) || 0;
            }

            visible.sort(function (a, b) {
                if (mode === 'price_asc') return num('data-price', a) - num('data-price', b);
                if (mode === 'price_desc') return num('data-price', b) - num('data-price', a);
                if (mode === 'popularity') return num('data-popularity', b) - num('data-popularity', a);
                var so = num('data-sort-order', a) - num('data-sort-order', b);
                if (so !== 0) return so;
                return num('data-product-id', a) - num('data-product-id', b);
            });

            visible.concat(hidden).forEach(function (c) {
                grid.appendChild(c);
            });
        }

        window.catalogApplyFilters = applyFilters;

        if (grid) applyFilters();
    })();
    </script>
</body>
</html>
