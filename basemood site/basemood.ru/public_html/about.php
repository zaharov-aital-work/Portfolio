<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
require_once __DIR__ . '/config.php';
$bm_css_ver = is_file(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time();
$bm_js_ver = is_file(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="description" content="BASEMOOD - бренд из Якутии. Движение за качественную одежду для молодежи крайнего севера">
    <title>О бренде BASEMOOD</title>
    
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
        /* Дополнительные стили для страницы "О нас" */
        .about-page {
            padding: 60px 40px 0;
            max-width: var(--container-width);
            margin: 0 auto;
            min-height: calc(100vh - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .about-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .about-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-family: 'Montserrat', sans-serif;
        }

        .about-subtitle {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
            max-width: 700px;
            margin: 0 auto;
        }

        /* Секция с изображением бренда */
        .brand-image-section {
            margin: 80px 0;
            text-align: center;
        }

        .brand-svg {
            max-width: 1000px;
            height: auto;
            margin: 0 auto;
            display: block;
        }

        /* Секция с текстом */
        .about-content {
            max-width: 900px;
            width: 100%;
            text-align: center;
        }

        .about-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 60px;
        }

        .about-logo img {
            width: 220px;
            height: auto;
        }

        .about-section {
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-family: 'Montserrat', sans-serif;
        }

        .section-text {
            font-size: 1.1rem;
            line-height: 1.7;
            color: var(--text-color);
            margin-bottom: 15px;
            font-family: 'Montserrat', sans-serif;
        }

        /* Ценности бренда */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .value-card {
            background: #f8f8f8;
            padding: 30px;
            border-radius: var(--border-radius);
            text-align: center;
        }

        .value-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .value-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            font-family: 'Montserrat', sans-serif;
        }

        .value-text {
            font-size: 0.95rem;
            line-height: 1.5;
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
        }

        /* Адаптивность */
        @media (min-width: 769px) and (max-width: 1024px) {
            .about-page {
                min-height: auto;
                align-items: flex-start;
                padding: 104px 100px 76px;
                text-align: left;
            }

            .about-logo {
                margin-bottom: 32px;
            }

            .about-logo img {
                width: 170px;
            }

            .about-title {
                text-align: left;
            }

            .about-subtitle {
                text-align: left;
                margin: 0;
            }

            .section-title {
                text-align: left;
            }

            .section-text {
                text-align: left;
            }
        }

        @media (max-width: 768px) {
            .about-page {
                min-height: auto;
                align-items: flex-start;
                padding: 108px 70px 40px;
                text-align: left;
            }

            .about-logo {
                margin-bottom: 26px;
            }

            .about-logo img {
                width: 110px;
            }

            .about-title {
                font-size: 2rem;
                text-align: left;
            }

            .about-subtitle {
                font-size: 1.1rem;
                text-align: left;
                margin: 0;
            }

            .brand-svg {
                max-width: 100%;
                padding: 0 20px;
            }

            .section-title {
                font-size: 1.3rem;
                text-align: left;
            }

            .section-text {
                font-size: 1rem;
                text-align: left;
            }

            .values-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .value-card {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .about-page {
                min-height: auto;
                align-items: flex-start;
                padding: 76px 50px 28px;
                text-align: left;
            }

            .about-title {
                font-size: 1.8rem;
                text-align: left;
            }

            .about-subtitle {
                font-size: 1rem;
                text-align: left;
                margin: 0;
            }

            .section-title {
                font-size: 1.2rem;
                text-align: left;
            }

            .section-text {
                font-size: 0.95rem;
                line-height: 1.6;
                text-align: left;
            }
        }
    </style>
    <?php include __DIR__ . '/yandex-metrika.php'; ?>
</head>
<body>
    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner"></div>
    </div>

    <!-- Секция 1: Шапка сайта -->
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
            <input type="text" class="search-input" placeholder="Поиск товаров...">
            <button class="search-close" id="searchClose">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </header>

    <!-- Основное содержимое страницы "О нас" -->
    <main class="main">
        <div class="about-page">
            <div class="about-content">
                <div class="about-logo">
                    <img src="img/logo.svg" alt="BASEMOOD">
                </div>
                <div class="about-section">
                    <p class="section-text">
                        BASEMOOD — это свобода выражать себя через то, что ты носишь. Мы родились из желания создать что-то свое, настоящее. Наша эстетика — это визуально приятные, атмосферные вещи, в которых есть настроение. Мы создаем качественную, доступную и универсальную одежду для повседневной жизни. Такую, которую хочется носить снова и снова.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Футер -->
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

    <!-- Подключение JavaScript -->
    <script src="script.js?v=<?= (int) $bm_js_ver ?>"></script>
    <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
    
    <script>
        // Дополнительный скрипт для страницы "О нас"
        document.addEventListener('DOMContentLoaded', function() {
            // Добавляем класс active к текущей странице
            const currentPage = window.location.pathname;
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === 'about.php' || 
                   (link.getAttribute('href') === '#' && currentPage.includes('about'))) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });

            // Скрытие спиннера загрузки
            const loadingSpinner = document.getElementById('loadingSpinner');
            if (loadingSpinner) {
                setTimeout(() => {
                    loadingSpinner.classList.add('hidden');
                    setTimeout(() => {
                        if (loadingSpinner.parentNode) {
                            loadingSpinner.parentNode.removeChild(loadingSpinner);
                        }
                    }, 300);
                }, 1000);
            }
        });

        // ДОПОЛНИТЕЛЬНЫЙ ФИКС - перехватываем все клики по логотипу на уровне документа
        document.addEventListener('click', function(e) {
            if (e.target.closest('.logo-link') || e.target.closest('.logo-img')) {
                const logoLink = e.target.closest('.logo-link');
                if (logoLink && logoLink.href.includes('index.php')) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.location.href = 'index.php';
                    return false;
                }
            }
        }, true);
    </script>
</body>
</html>
