<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
require_once __DIR__ . '/config.php';

if (!isset($_GET['id'])) {
    die('ID товара не указан.');
}

$id = intval($_GET['id']);

$query = "SELECT * FROM products WHERE id = $id";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) == 0) {
    die('Товар не найден.');
}

$product = mysqli_fetch_assoc($result);
require_once __DIR__ . '/product_images_helpers.php';
require_once __DIR__ . '/catalog_helpers.php';
require_once __DIR__ . '/shop_helpers.php';
$gallery_urls = bm_product_full_gallery_urls($product);
$bm_stock_map = bm_shop_table_exists($conn, 'product_stock') ? bm_stock_map_for_product($conn, $id) : [];

// Подбираем товары для блока "Смотрите также", исключая текущий товар.
$related_query = "
    SELECT id, name, price, image_url
    FROM products
    WHERE id <> $id";
if (bm_products_has_column($conn, 'show_in_catalog')) {
    $related_query .= ' AND show_in_catalog = 1';
}
if (bm_products_has_column($conn, 'sort_order')) {
    $related_query .= ' ORDER BY sort_order ASC, id ASC';
} else {
    $related_query .= ' ORDER BY id DESC';
}
$related_query .= '
    LIMIT 4
';
$related_result = mysqli_query($conn, $related_query);
$related_products = [];

if ($related_result) {
    while ($row = mysqli_fetch_assoc($related_result)) {
        $related_products[] = $row;
    }
}

mysqli_close($conn);

$bm_css_ver = is_file(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time();
$bm_js_ver = is_file(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time();

// Данные берём из базы данных
$description = !empty($product['description']) ? $product['description'] : 'Описание товара.';
$material    = !empty($product['material'])    ? $product['material']    : '—';
$composition = !empty($product['composition']) ? $product['composition'] : '—';
$printing    = !empty($product['printing'])    ? $product['printing']    : '—';
if (count($gallery_urls) === 0 && !empty($product['image_url'])) {
    $solo = bm_normalize_public_url(trim((string) $product['image_url']));
    $gallery_urls = $solo !== '' ? [$solo] : [];
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <title><?php echo htmlspecialchars($product['name']); ?> - BASEMOOD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">
    <link rel="stylesheet" href="style.css?v=<?php echo (int) $bm_css_ver; ?>">
    <style>
        /* Галерея и миниатюры: прозрачная подложка (в HTML — поверх кэша) */
        .product-gallery .gallery-swipe,
        .product-gallery .gallery-slide,
        .thumbnail-gallery .thumbnail,
        .thumbnail-gallery .thumbnail img {
            background: transparent !important;
            background-color: transparent !important;
        }
    </style>
    <style>
        /* Дополнительные стили для страницы товара */
        .main {
            padding: 180px 0;
        }

        .product-info-card {
            background: #ececec;
            border-radius: 13px;
            padding: 36px;
            height: fit-content;
            max-width: 325px;
            margin: 0 auto;
            font-family: 'Montserrat', sans-serif;
        }

        .product-main-title {
            font-size: 1.3rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 13px;
            color: var(--text-color);
            font-family: 'Montserrat', sans-serif;
        }

        .divider {
            height: 1px;
            background: #ddd;
            margin: 10px 0;
            width: 100%;
        }

        .price-section {
            text-align: center;
            margin-bottom: 16px;
        }

        .product-price-large {
            font-size: 1.625rem;
            font-weight: 700;
            color: var(--primary-color);
            font-family: 'Montserrat', sans-serif;
        }

        .size-section {
            margin-bottom: 16px;
        }

        .size-title {
            font-size: 0.715rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-align: center;
            color: var(--text-color);
            font-family: 'Montserrat', sans-serif;
        }

        .size-options-simple {
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .size-option-simple {
            padding: 4px 8px;
            border: 1.3px solid #ccc;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            background: white;
            min-width: 39px;
            text-align: center;
            font-size: 0.91rem;
            font-family: 'Montserrat', sans-serif;
        }

        .size-option-simple.size-unavailable {
            opacity: 0.35;
            cursor: not-allowed;
            pointer-events: none;
            text-decoration: line-through;
        }

        .size-option-simple:hover {
            border-color: var(--primary-color);
        }

        .size-option-simple.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .add-to-cart-large {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 24px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 16px;
            font-family: 'Montserrat', sans-serif;
        }

        .add-to-cart-large:hover {
            background: #333;
            transform: translateY(-1px);
        }

        .add-to-cart-large:focus,
        .add-to-cart-large:focus-visible,
        .add-to-cart-large:active {
            outline: none;
            box-shadow: none;
            background: var(--primary-color);
        }

        .add-to-cart-large.is-added {
            animation: addToCartPulse .6s ease;
        }

        @keyframes addToCartPulse {
            0% {
                transform: scale(1);
            }
            35% {
                transform: scale(0.97);
            }
            70% {
                transform: scale(1.03);
            }
            100% {
                transform: scale(1);
            }
        }

        .info-section {
            margin-bottom: 13px;
        }

        .info-title {
            font-size: 0.915rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-color);
            font-family: 'Montserrat', sans-serif;
        }

        .info-content {
            color: var(--text-light);
            line-height: 1.3;
            font-size: 0.8rem;
            font-family: 'Montserrat', sans-serif;
        }

        .model-info {
            background: #dddddd;
            padding: 10px;
            border-radius: 14px;
            margin-top: 7px;
            font-family: 'Montserrat', sans-serif;
        }

        .model-info p {
            margin: 4px 0;
            font-size: 0.785rem;
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
        }

        /* Стили для секции "Смотрите также" */
        .also-like-section {
            max-width: var(--container-width);
            margin: 100px auto 0;
            padding: 0 40px;
        }

        .also-like-title {
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 40px;
            color: var(--text-color);
            font-family: 'Montserrat', sans-serif;
        }

        .also-like-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 260px));
            justify-content: center;
            gap: 20px;
            margin-bottom: 60px;
        }

        .also-like-card {
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .also-like-card:hover {
            transform: translateY(-5px);
        }

        .also-like-image {
            width: 100%;
            height: 200px;
            background: transparent;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .also-like-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .also-like-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 8px;
            font-family: 'Montserrat', sans-serif;
        }

        .also-like-price {
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary-color);
            font-family: 'Montserrat', sans-serif;
        }

        /* === Swipe Gallery === */
        .gallery-swipe {
            width: 100%;
            height: 600px;
            background: transparent;
            border-radius: var(--border-radius-lg);
            margin-bottom: 1rem;
            display: flex;
            overflow-x: scroll;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            cursor: pointer;
        }

        .gallery-swipe::-webkit-scrollbar {
            display: none;
        }

        .gallery-slide {
            min-width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
            scroll-snap-align: start;
        }

        .gallery-slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        @media (min-width: 1025px) {
            .gallery-slide img {
                max-width: 86%;
                max-height: 86%;
            }
        }

        /* Lightbox */
        .product-lightbox {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.92);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .product-lightbox.active {
            display: flex;
        }

        .lightbox-img {
            max-width: 92vw;
            max-height: 88vh;
            object-fit: contain;
            border-radius: 4px;
        }

        .lightbox-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: white;
            font-size: 1.6rem;
            cursor: pointer;
            line-height: 1;
            padding: 4px 8px;
        }

        /* Dots для мобильной галереи */
        .gallery-dots {
            display: none;
        }

        .gallery-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #ccc;
            cursor: pointer;
            transition: background 0.3s;
        }

        .gallery-dot.active {
            background: var(--primary-color);
        }

        /* ПЛАНШЕТ */
        @media (min-width: 769px) and (max-width: 1024px) {
            .main {
                padding: 100px 0 40px;
            }

            .product-page {
                padding: 0;
            }

            .product-layout {
                gap: 0;
            }

            .product-gallery {
                position: static;
            }

            .gallery-swipe {
                height: min(68vw, 560px);
                border-radius: 0;
            }

            .thumbnail-gallery {
                padding: 0 30px;
                margin-top: 12px;
            }

            .product-info-card {
                background: transparent;
                max-width: 100%;
                padding: 20px 30px;
                border-radius: 0;
            }

            .product-main-title {
                font-size: 1.25rem;
                font-weight: 500;
                text-align: left;
            }

            .product-price-large {
                font-size: 1.3rem;
                font-weight: 500;
            }

            .price-section { text-align: left; }
            .size-title { text-align: left; }
            .size-options-simple { justify-content: flex-start; }

            .also-like-section {
                margin: 60px auto 0;
                padding: 0;
            }

            .also-like-title {
                text-align: left;
                font-weight: 500;
                font-size: 1.3rem;
                margin-bottom: 20px;
                padding: 0 44px 0 34px;
            }

            .also-like-grid {
                display: flex;
                flex-wrap: nowrap;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                -webkit-overflow-scrolling: touch;
                gap: 16px;
                padding: 0 30px 12px 34px;
                max-width: none;
            }

            .also-like-grid::-webkit-scrollbar { height: 4px; }
            .also-like-grid::-webkit-scrollbar-track { background: #f0f0f0; }
            .also-like-grid::-webkit-scrollbar-thumb { background: #bbb; border-radius: 2px; }

            .also-like-card {
                min-width: 210px;
                max-width: 210px;
                scroll-snap-align: start;
                flex-shrink: 0;
            }

            .also-like-image {
                height: 220px;
            }

            .also-like-name { font-weight: 400; }
            .also-like-price { font-weight: 500; }

            .gallery-dots {
                display: flex;
                justify-content: center;
                gap: 7px;
                padding: 12px 0;
            }
        }

        /* МОБИЛЬ */
        @media (max-width: 768px) {
            .main {
                padding: 70px 0 40px;
            }

            .product-page {
                padding: 0;
            }

            .product-layout {
                gap: 0;
            }

            .product-gallery {
                position: static;
            }

            .gallery-swipe {
                height: min(90vw, 450px);
                border-radius: 0;
            }

            .thumbnail-gallery {
                display: none;
            }

            .product-info-card {
                background: transparent;
                max-width: 92vw;
                padding: 16px 18px;
                border-radius: 0;
                margin: 0 auto;
            }

            .product-main-title {
                font-size: 1.1rem;
                font-weight: 500;
                text-align: left;
            }

            .product-price-large {
                font-size: 1.1rem;
                font-weight: 500;
            }

            .price-section { text-align: left; }
            .size-title { text-align: left; }
            .size-options-simple { justify-content: flex-start; }

            .size-option-simple {
                padding: 7px 13px;
                min-width: 33px;
                font-size: 0.78rem;
            }

            .gallery-dots {
                display: flex;
                justify-content: center;
                gap: 7px;
                padding: 10px 0 2px;
            }

            .also-like-section {
                margin: 40px auto 0;
                padding: 0;
            }

            .also-like-title {
                text-align: left;
                font-size: 1.2rem;
                font-weight: 500;
                margin-bottom: 16px;
                padding: 0 20px;
            }

            .also-like-grid {
                display: flex;
                flex-wrap: nowrap;
                overflow-x: auto;
                scroll-snap-type: x mandatory;
                -webkit-overflow-scrolling: touch;
                gap: 12px;
                padding: 0 20px 10px;
                scroll-padding-left: 20px;
                max-width: none;
                margin: 0;
            }

            .also-like-grid::before {
                content: '';
                flex: 0 0 0;
            }

            .also-like-grid .also-like-card:first-child {
                margin-left: 64px;
                scroll-margin-left: 64px;
            }

            .also-like-grid::-webkit-scrollbar { display: none; }

            .also-like-card {
                min-width: 152px;
                max-width: 152px;
                scroll-snap-align: start;
                flex-shrink: 0;
            }

            .also-like-image { height: 165px; }
            .also-like-name { font-weight: 400; font-size: 0.82rem; }
            .also-like-price { font-weight: 500; font-size: 0.88rem; }
        }

        @media (max-width: 480px) {
            .main {
                padding: 60px 0 30px;
            }

            .product-info-card {
                max-width: 90vw;
                padding: 13px 14px;
            }

            .product-main-title {
                font-size: 1rem;
            }

            .product-price-large {
                font-size: 1rem;
            }

            .size-options-simple {
                gap: 5px;
            }

            .size-option-simple {
                padding: 5px 10px;
                min-width: 29px;
                font-size: 0.715rem;
            }

            .add-to-cart-large {
                padding: 9px;
                font-size: 0.715rem;
            }

            .info-title {
                font-size: 0.65rem;
            }

            .info-content {
                font-size: 0.585rem;
            }

            .also-like-section {
                margin: 30px auto 0;
                padding: 0;
            }

            .also-like-title {
                font-size: 1.1rem;
                padding: 0 16px;
            }

            .also-like-grid {
                padding: 0 16px 10px;
                scroll-padding-left: 16px;
            }

            .also-like-grid::before {
                content: '';
                flex: 0 0 0;
            }

            .also-like-grid .also-like-card:first-child {
                margin-left: 56px;
                scroll-margin-left: 56px;
            }

            .also-like-card {
                min-width: 136px;
                max-width: 136px;
            }

            .also-like-image {
                height: 145px;
            }
        }
    </style>
    <?php include __DIR__ . '/yandex-metrika.php'; ?>
</head>
<body>
    <!-- Навигация -->
    <nav class="nav-container">
        <div class="nav-inner">
            <div class="logo">
                <a href="index.php" class="logo-link">
                    <img src="img/logo.svg" alt="BASEMOOD" class="logo-img">
                </a>
            </div>
            
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Открыть меню">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <ul class="nav-menu" id="navMenu">
                <?php include __DIR__ . '/partials/nav_menu_inner.php'; ?>
            </ul>
            
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
        
        <div class="search-container" id="searchContainer">
            <input type="text" class="search-input" placeholder="Поиск товаров...">
            <button class="search-close" id="searchClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </nav>

    <!-- Основное содержимое -->
    <main class="main">
        <div class="product-page">
            <div class="product-layout">
                <!-- Галерея -->
                <div class="product-gallery">
                    <div class="gallery-swipe" id="gallerySwipe">
                        <?php foreach ($gallery_urls as $gi => $gurl): ?>
                        <div class="gallery-slide">
                            <img src="<?php echo htmlspecialchars($gurl); ?>" alt="<?php echo htmlspecialchars($product['name']); ?><?php echo $gi > 0 ? ' — фото ' . ($gi + 1) : ''; ?>"<?php echo $gi === 0 ? ' id="mainImage"' : ''; ?>>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="thumbnail-gallery">
                        <?php foreach ($gallery_urls as $gi => $gurl): ?>
                        <div class="thumbnail<?php echo $gi === 0 ? ' active' : ''; ?>" data-index="<?php echo (int) $gi; ?>">
                            <img src="<?php echo htmlspecialchars($gurl); ?>" alt="">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Dots -->
                    <div class="gallery-dots" id="galleryDots"></div>
                </div>
                
                <!-- Информация о товаре в серой карточке -->
                <div class="product-info-card">
                    <h1 class="product-main-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="divider"></div>
                    
                    <div class="price-section">
                        <p class="product-price-large"><?php echo number_format($product['price'], 0, ',', ' '); ?> ₽</p>
                    </div>
                    
                    <div class="size-section">
                        <h3 class="size-title">Размер</h3>
                        <div class="size-options-simple">
                            <?php
                            $sizesUi = ['S', 'M', 'L'];
                            $firstAvail = 'M';
                            foreach ($sizesUi as $sz) {
                                $avail = !isset($bm_stock_map[$sz]) || (int) $bm_stock_map[$sz] > 0;
                                if ($avail) {
                                    $firstAvail = $sz;
                                    break;
                                }
                            }
                            foreach ($sizesUi as $sz) {
                                $avail = !isset($bm_stock_map[$sz]) || (int) $bm_stock_map[$sz] > 0;
                                $cls = 'size-option-simple' . ($sz === $firstAvail ? ' active' : '') . ($avail ? '' : ' size-unavailable');
                                echo '<div class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '" data-size="' . htmlspecialchars($sz, ENT_QUOTES, 'UTF-8') . '"' . ($avail ? '' : ' title="Нет в наличии"') . '>' . htmlspecialchars($sz, ENT_QUOTES, 'UTF-8') . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <button class="add-to-cart-large" id="addToCart">В корзину</button>
                    
                    <div class="divider"></div>
                    
                    <div class="info-section">
                        <h4 class="info-title">Описание</h4>
                        <p class="info-content"><?php echo htmlspecialchars($description); ?></p>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="info-section">
                        <h4 class="info-title">Материал</h4>
                        <p class="info-content"><?php echo htmlspecialchars($material); ?></p>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="info-section">
                        <h4 class="info-title">Состав</h4>
                        <p class="info-content"><?php echo htmlspecialchars($composition); ?></p>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="info-section">
                        <h4 class="info-title">Нанесение</h4>
                        <p class="info-content"><?php echo htmlspecialchars($printing); ?></p>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="info-section">
                        <h4 class="info-title">Машинная стирка</h4>
                        <p class="info-content">Рекомендуется стирка при 30°C</p>
                    </div>
                    
                    <div class="info-section">
                        <div class="model-info">
                            <p><strong>Размеры на моделях</strong></p>
                            <p>Рост моделей: 170/185</p>
                            <p>Размер на моделях: М/XL</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Секция "Смотрите также" -->
            <section class="also-like-section">
                <h2 class="also-like-title">Смотрите также</h2>
                
                <div class="also-like-grid">
                    <?php foreach ($related_products as $related): ?>
                    <div class="also-like-card" onclick="window.location.href='product.php?id=<?= (int) $related['id'] ?>'">
                        <div class="also-like-image">
                            <img src="<?= htmlspecialchars(bm_normalize_public_url(trim((string) ($related['image_url'] ?? '')))) ?>" alt="<?= htmlspecialchars($related['name']) ?>">
                        </div>
                        <h3 class="also-like-name"><?= htmlspecialchars($related['name']) ?></h3>
                        <p class="also-like-price"><?= number_format((float) $related['price'], 0, '', ' ') ?> ₽</p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Подвал -->
    <?php include __DIR__ . '/footer.php'; ?>

    <!-- Lightbox -->
    <div class="product-lightbox" id="productLightbox">
        <button class="lightbox-close" id="lightboxClose" aria-label="Закрыть"><i class="fas fa-times"></i></button>
        <img src="" alt="" id="lightboxImg" class="lightbox-img">
    </div>

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
                <button type="submit" class="auth-modal-btn" id="authModalSubmit">войти</button>
            </form>
            <div class="auth-modal-footer">
                <p>Нет аккаунта? <a href="register.php" class="auth-modal-link">Зарегистрироваться</a></p>
            </div>
        </div>
    </div>

    <script src="script.js?v=<?php echo (int) $bm_js_ver; ?>"></script>
    <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
    <script>
    (function () {
        var gallerySwipe = document.getElementById('gallerySwipe');
        if (!gallerySwipe) return;

        var slides = gallerySwipe.querySelectorAll('.gallery-slide');
        var dotsContainer = document.getElementById('galleryDots');
        var thumbnails = document.querySelectorAll('.thumbnail');
        var lightbox = document.getElementById('productLightbox');
        var lightboxImg = document.getElementById('lightboxImg');
        var lightboxClose = document.getElementById('lightboxClose');

        var count = slides.length;
        var currentIndex = 0;
        var touchStartX = 0;
        var touchMoved = false;

        for (var di = 0; di < count; di++) {
            (function (idx) {
                var dot = document.createElement('div');
                dot.className = 'gallery-dot' + (idx === 0 ? ' active' : '');
                dot.addEventListener('click', function () { scrollToSlide(idx); });
                dotsContainer.appendChild(dot);
            })(di);
        }

        function updateDots(idx) {
            dotsContainer.querySelectorAll('.gallery-dot').forEach(function (d, i) {
                d.classList.toggle('active', i === idx);
            });
        }

        function updateThumbs(idx) {
            thumbnails.forEach(function (t, i) {
                t.classList.toggle('active', i === idx);
            });
        }

        function scrollToSlide(idx) {
            gallerySwipe.scrollTo({ left: idx * gallerySwipe.offsetWidth, behavior: 'smooth' });
        }

        gallerySwipe.addEventListener('scroll', function () {
            var idx = Math.round(gallerySwipe.scrollLeft / gallerySwipe.offsetWidth);
            if (idx !== currentIndex) {
                currentIndex = idx;
                updateDots(idx);
                updateThumbs(idx);
            }
        }, { passive: true });

        thumbnails.forEach(function (t, i) {
            t.addEventListener('click', function () { scrollToSlide(i); });
        });

        gallerySwipe.addEventListener('touchstart', function (e) {
            touchStartX = e.touches[0].clientX;
            touchMoved = false;
        }, { passive: true });

        gallerySwipe.addEventListener('touchmove', function (e) {
            if (Math.abs(e.touches[0].clientX - touchStartX) > 8) touchMoved = true;
        }, { passive: true });

        gallerySwipe.addEventListener('touchend', function () {
            if (!touchMoved) openLightbox(currentIndex);
        }, { passive: true });

        gallerySwipe.addEventListener('click', function () {
            if (!('ontouchstart' in window)) openLightbox(currentIndex);
        });

        function openLightbox(idx) {
            lightboxImg.src = slides[idx].querySelector('img').src;
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.classList.remove('active');
            lightboxImg.src = '';
            document.body.style.overflow = '';
        }

        lightboxClose.addEventListener('click', closeLightbox);
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) closeLightbox();
        });

        var lbX = 0;
        lightbox.addEventListener('touchstart', function (e) { lbX = e.touches[0].clientX; }, { passive: true });
        lightbox.addEventListener('touchend', function (e) {
            var dx = e.changedTouches[0].clientX - lbX;
            if (Math.abs(dx) > 40) {
                currentIndex = (currentIndex + (dx < 0 ? 1 : -1) + count) % count;
                lightboxImg.src = slides[currentIndex].querySelector('img').src;
                gallerySwipe.scrollTo({ left: currentIndex * gallerySwipe.offsetWidth, behavior: 'smooth' });
                updateDots(currentIndex);
                updateThumbs(currentIndex);
            }
        }, { passive: true });

        document.addEventListener('keydown', function (e) {
            if (!lightbox.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') {
                currentIndex = (currentIndex - 1 + count) % count;
                lightboxImg.src = slides[currentIndex].querySelector('img').src;
                updateDots(currentIndex);
            }
            if (e.key === 'ArrowRight') {
                currentIndex = (currentIndex + 1) % count;
                lightboxImg.src = slides[currentIndex].querySelector('img').src;
                updateDots(currentIndex);
            }
        });
    })();
    </script>
</body>
</html>