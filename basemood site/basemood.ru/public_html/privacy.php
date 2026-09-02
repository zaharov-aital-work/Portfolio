<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$bm_css_ver = is_file(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time();
$bm_js_ver = is_file(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="description" content="Согласие на обработку персональных данных — BASEMOOD">
    <title>Согласие на обработку персональных данных — BASEMOOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">
    <link rel="stylesheet" href="style.css?v=<?= (int) $bm_css_ver ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .legal-doc {
            max-width: 48rem;
            margin: 0 auto;
            padding: calc(var(--header-height) + 2.5rem) 1.5rem 4rem;
            font-family: 'Montserrat', var(--font-main), sans-serif;
            line-height: 1.65;
            color: var(--text-color);
        }
        @media (min-width: 1024px) {
            .legal-doc {
                max-width: 68rem;
            }
        }
        .legal-doc h1 {
            font-size: clamp(1.35rem, 3vw, 1.85rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.3;
        }
        .legal-doc p { margin-bottom: 1rem; }
        .legal-doc ul {
            margin: 0 0 1.25rem 1.25rem;
            padding: 0;
        }
        .legal-doc li { margin-bottom: 0.4rem; }
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

    <main class="main">
        <article class="legal-doc">
            <h1>Согласие на обработку персональных данных</h1>

            <p>Настоящим в соответствии с Федеральным законом № 152-ФЗ «О персональных данных» от 27.07.2006 года свободно, своей волей и в своем интересе выражаю свое безусловное согласие на обработку моих персональных данных Индивидуальным предпринимателем Алексеевой Кристиной Викторовной (ОГРНИП 323140000005390, ИНН 143534401059), осуществляющим деятельность по адресу: basemood.ru (далее - Интернет-магазин).</p>

            <p>Под персональными данными понимается любая информация, относящаяся к определенному или определяемому на основании такой информации физическому лицу.</p>

            <p>Настоящее Согласие выдано мною на обработку следующих персональных данных:</p>
            <ul>
                <li>Имя, включая фамилию, имя и отчество (при наличии);</li>
                <li>Номер телефона;</li>
                <li>Адрес;</li>
                <li>Сведения о владельце паспорта и иные паспортные данные;</li>
                <li>Ссылки на социальные сети и мессенджеры;</li>
                <li>Адрес электронной почты.</li>
            </ul>

            <p>Согласие дано Интернет-магазину для совершения следующих действий с моими персональными данными с использованием средств автоматизации и/или без использования таких средств: сбор, систематизация, накопление, хранение, уточнение (обновление, изменение), использование, передача (предоставление, доступ), обезличивание, блокирование, удаление, уничтожение, а также осуществление любых иных действий, предусмотренных действующим законодательством Российской Федерации как неавтоматизированными, так и автоматизированными способами.</p>

            <p>Я уведомлен(а) и согласен(на) с тем, что Интернет-магазин вправе передавать мои персональные данные третьим лицам исключительно в объеме, необходимом для достижения указанных ниже целей обработки. Передача данных осуществляется следующим категориям третьих лиц:</p>
            <ul>
                <li>Курьерским службам, транспортным компаниям и почтовым операторам — для осуществления доставки заказов;</li>
                <li>Платежным системам и банкам — для обработки платежей и осуществления возвратов денежных средств;</li>
                <li>Партнерам и контрагентам Интернет-магазина — в случае необходимости привлечения их услуг для исполнения обязательств передо мной;</li>
                <li>Государственным органам и должностным лицам — исключительно в случаях, предусмотренных действующим законодательством Российской Федерации.</li>
            </ul>

            <p>При передаче персональных данных третьим лицам Интернет-магазин обязуется соблюдать конфиденциальность и обеспечивать безопасность передаваемых данных в соответствии с требованиями Федерального закона № 152-ФЗ.</p>

            <p>Данное согласие дается Интернет-магазину для обработки моих персональных данных в следующих целях:</p>
            <ul>
                <li>предоставление мне услуг;</li>
                <li>исполнение обязательств по заключенному со мной договору, включая организацию доставки товаров и обработку платежей (в том числе с привлечением третьих лиц);</li>
                <li>направление в мой адрес уведомлений, касающихся предоставляемых услуг;</li>
                <li>подготовка и направление ответов на мои запросы;</li>
                <li>направление в мой адрес информации, в том числе рекламной, о мероприятиях / товарах / услугах / предложениях Интернет-магазина.</li>
            </ul>

            <p>Настоящее согласие действует до момента его отзыва путем направления соответствующего уведомления на адрес электронной почты shop@vsrap.com. Отзыв согласия не препятствует обработке персональных данных, осуществляемой в целях исполнения заключенного договора или требований законодательства Российской Федерации. В иных случаях, при получении отзыва согласия, Интернет-магазин прекращает обработку данных и уничтожает их в срок, не превышающий 30 дней с даты поступления отзыва, за исключением данных, обработка которых необходима для достижения целей, предусмотренных законодательством Российской Федерации.</p>
        </article>
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
    <script>window.isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
    <script>
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
