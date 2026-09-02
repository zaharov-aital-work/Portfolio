<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);

// Подключение к базе данных
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/product_images_helpers.php';
require_once __DIR__ . '/catalog_helpers.php';

if (!$conn) {
    die('Ошибка подключения: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Товары для главной (после миграции — только show_on_home; без колонок — все товары).
$result = mysqli_query($conn, bm_sql_products_for_home($conn));
if (!$result) {
    die('Ошибка запроса: ' . mysqli_error($conn));
}

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

// Отладка
error_log('Количество товаров: ' . count($products));

mysqli_close($conn);

$bm_css_ver = is_file(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time();
$bm_js_ver = is_file(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="description" content="BASEMOOD - интернет-магазин современной одежды">
    <title>BASEMOOD - Интернет-магазин одежды</title>
    
    <!-- Подключение шрифта Montserrat -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Подключение стилей -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">
    <link rel="stylesheet" href="style.css?v=<?= (int) $bm_css_ver ?>">
    
    <!-- Иконочный шрифт -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Дополнительные стили для динамической загрузки */
        .product-card {
            cursor: pointer;
        }
        .product-card a {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        /* Подложка под фото: прозрачная (в HTML — чтобы не залипал кэш style.css) */
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
    </style>
    <?php include __DIR__ . '/yandex-metrika.php'; ?>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner"></div>
    </div>

    <!-- Секция 1: Шапка сайта с SVG хедером -->
    <header class="header">
        <!-- SVG хедер -->
        <div class="header-svg-container">
            <img src="img/header.svg" alt="BASEMOOD Header" class="header-svg">
        </div>
        
        <nav class="nav-container">
            <div class="nav-inner">
                <!-- Логотип -->
                <div class="logo">
                    <a href="index.php" class="logo-link" aria-label="На главную страницу BASEMOOD">
                        <img src="img/logo.svg" alt="BASEMOOD" class="logo-img">
                        <span class="logo-text-hidden">BASEMOOD</span>
                    </a>
                </div>
                
                <!-- Мобильное меню бургер -->
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Открыть меню">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <!-- Основная навигация -->
                <ul class="nav-menu" id="navMenu">
                    <?php include __DIR__ . '/partials/nav_menu_inner.php'; ?>
                </ul>
                
                <!-- Иконки действий -->
                <div class="nav-actions">
                    <button class="nav-icon" id="searchButton" aria-label="Поиск">
                        <i class="fas fa-search"></i>
                    </button>
                    <button class="nav-icon" id="userButton" aria-label="Личный кабинет">
                        <i class="fas fa-user"></i>
                    </button>
                    <button class="nav-icon" id="wishlistButton" aria-label="Избранное">
                        <i class="far fa-heart"></i>
                    </button>
                    <button class="nav-icon cart-icon" id="cartButton" aria-label="Корзина">
                        <i class="fas fa-basket-shopping"></i>
                        <span class="cart-count">0</span>
                    </button>
                </div>
            </div>
        </nav>
        
        <!-- Поисковая строка -->
        <div class="search-container" id="searchContainer">
            <input type="text" class="search-input" placeholder="Поиск товаров..." autocomplete="off">
            <button class="search-close" id="searchClose">
                <i class="fas fa-times"></i>
            </button>
            <div class="search-autocomplete" id="searchAutocomplete" aria-live="polite"></div>
        </div>
    </header>

    <!-- Секция 2: Основное содержимое -->
    <main class="main">
        <!-- Секция товаров -->
        <section class="products" id="collections">
            <div class="container">
                <h2 class="section-title">Последние новинки</h2>
                
                <!-- DEBUG: Проверка загрузки товаров -->
                <?php if (count($products) == 0): ?>
                    <div style="background: #ffcccc; padding: 20px; margin: 20px 0; border-radius: 5px; color: #c00;">
                        <strong>⚠️ Ошибка:</strong> Товары не загружены из базы данных.<br>
                        Количество товаров: <?= count($products) ?><br>
                        <a href="debug.php">Проверить подключение к БД</a>
                    </div>
                <?php endif; ?>
                
                <!-- Сетка товаров - данные из базы данных -->
                <div class="product-grid" id="productGrid">
                    <?php foreach ($products as $product): ?>
                    <?php include __DIR__ . '/partials/product_card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Секция 3: Подвал сайта -->
    <?php include __DIR__ . '/footer.php'; ?>

    <!-- Auth Modal -->
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
    <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
</body>
</html>
