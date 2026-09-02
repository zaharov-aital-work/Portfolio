<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: account.php');
    exit;
}

require_once __DIR__ . '/config.php';

$bm_css_ver = is_file(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time();
$bm_js_ver = is_file(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time();

$errors = [];
$registered      = isset($_GET['registered']);
$verify_sent     = isset($_GET['verify_sent']);
$verify_error    = isset($_GET['verify_error']);
$unverified_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email';
    } elseif (empty($password)) {
        $errors[] = 'Введите пароль';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, first_name, last_name, email, password, email_verified FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['email_verified']) {
                $unverified_email = $email;
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name']  = $user['last_name'];
                $_SESSION['email']      = $user['email'];

                if (!empty($_POST['ajax'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true]);
                    exit;
                }

                header('Location: account.php');
                exit;
            }
        } else {
            $errors[] = 'Неверный email или пароль';
        }
    }

    if (!empty($_POST['ajax'])) {
        header('Content-Type: application/json');
        if ($unverified_email) {
            echo json_encode(['success' => false, 'error' => 'Подтвердите email перед входом. Проверьте вашу почту.']);
        } else {
            echo json_encode(['success' => false, 'error' => $errors[0] ?? 'Ошибка']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="description" content="Вход в личный кабинет BASEMOOD">
    <title>Авторизация — BASEMOOD</title>
    
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
        /* Стили для страницы авторизации */
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 120px 20px 80px;
            background: linear-gradient(135deg, #f5f5f5 0%, #ffffff 100%);
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .auth-header {
            background: var(--primary-color);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .auth-logo {
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-logo img {
            width: 82px;
            height: auto;
        }

        .auth-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            font-family: 'Montserrat', sans-serif;
        }

        .auth-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-family: 'Montserrat', sans-serif;
        }

        .auth-body {
            padding: 40px 30px;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-color);
            font-family: 'Montserrat', sans-serif;
        }

        .form-input {
            padding: 12px 16px;
            border: 1px solid #d5d5d5;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            transition: border-color 0.2s;
            width: 100%;
            box-sizing: border-box;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            box-shadow: none;
            background: #fafafa;
        }

        .form-input:focus,
        .form-input:focus-visible,
        .form-input:active {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: none;
            background: #fff;
        }

        .password-input {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 1rem;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 5px 0 20px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-family: 'Montserrat', sans-serif;
        }

        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #333;
            text-decoration: underline;
        }

        .auth-button {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 16px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .auth-button:hover {
            background: #333;
            transform: translateY(-2px);
        }

        .auth-divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
        }

        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .auth-divider span {
            padding: 0 15px;
            font-size: 0.9rem;
        }

        .social-auth {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }

        .social-button {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: white;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .social-button:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .social-button.yandex {
            color: #FC3F1D;
        }

        .social-button.vk {
            color: #4C75A3;
        }

        .auth-footer {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
        }

        .auth-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
            transition: color 0.3s ease;
        }

        .auth-link:hover {
            color: #333;
            text-decoration: underline;
        }

        .error-message {
            color: var(--error-color);
            font-size: 0.85rem;
            margin-top: 5px;
            font-family: 'Montserrat', sans-serif;
            display: none;
        }

        /* Адаптивность */
        @media (min-width: 769px) and (max-width: 1024px) {
            .auth-page {
                padding: calc(var(--header-height) + 80px) 14px 96px;
                min-height: auto;
                align-items: flex-start;
                justify-content: flex-start;
            }

            .footer {
                margin-top: 0 !important;
            }

            .form-label {
                font-weight: 500;
            }

            .auth-container {
                max-width: 520px;
                margin: 0 auto;
                background: transparent;
                border-radius: 0;
                box-shadow: none;
                overflow: visible;
            }

            .auth-header {
                background: transparent;
                color: var(--primary-color);
                padding: 2px 28px 0;
            }

            .auth-body {
                padding: 4px 18px 14px;
                margin-top: 6px;
            }

            .auth-title {
                display: block;
                font-size: 1.55rem;
                margin-bottom: 0;
            }
        }

        @media (max-width: 768px) {
            .auth-page {
                padding: 92px 15px 60px;
            }

            .form-label {
                font-weight: 500;
            }

            .auth-container {
                max-width: 100%;
                margin: 0 10px;
                background: transparent;
                border-radius: 0;
                box-shadow: none;
                overflow: visible;
            }

            .auth-header {
                background: transparent;
                color: var(--primary-color);
                padding: 6px 20px 0;
            }

            .auth-body {
                padding: 4px 20px 24px;
                margin-top: 20px;
            }

            .social-auth {
                flex-direction: column;
            }

            .remember-forgot {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        @media (max-width: 480px) {
            .auth-title {
                font-size: 1.25rem;
            }

            .auth-subtitle {
                font-size: 0.9rem;
            }

            .form-input {
                padding: 10px 12px;
                font-size: 16px;
                min-height: 35px;
            }

            .auth-button {
                padding: 14px;
            }
        }

        @media (max-width: 375px) {
            .form-input {
                padding: 8px 10px;
            }

            .auth-body {
                padding: 2px 15px 20px;
                margin-top: -8px;
            }

            .auth-header {
                padding: 4px 15px 0;
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

    <!-- Шапка сайта -->
    <nav class="nav-container">
        <div class="nav-inner">
            <div class="logo">
                <a href="index.php" class="logo-link" aria-label="На главную страницу BASEMOOD">
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
    <main class="auth-page">
        <div class="auth-container">
            <!-- Заголовок -->
            <div class="auth-header">
                <h1 class="auth-title">Авторизация</h1>
            </div>

            <!-- Форма -->
            <div class="auth-body">
                <?php if ($registered): ?>
                    <div class="px-4 py-3 mb-3" style="background:#d4edda; color:#155724; border:1px solid #c3e6cb; border-radius:10px; font-family:'Montserrat',sans-serif; font-size:0.9rem;">
                        ✅ Регистрация прошла успешно! Войдите в свой аккаунт.
                    </div>
                <?php endif; ?>
                <?php if ($verify_sent): ?>
                    <div class="px-4 py-3 mb-3" style="background:#d4edda; color:#155724; border:1px solid #c3e6cb; border-radius:10px; font-family:'Montserrat',sans-serif; font-size:0.9rem;">
                        ✉️ Письмо с подтверждением отправлено. Проверьте вашу почту.
                    </div>
                <?php endif; ?>
                <?php if ($verify_error): ?>
                    <div class="px-4 py-3 mb-3" style="background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:10px; font-family:'Montserrat',sans-serif; font-size:0.9rem;">
                        ⚠️ Ссылка для подтверждения недействительна или уже была использована.
                    </div>
                <?php endif; ?>
                <?php if ($unverified_email): ?>
                    <div class="px-4 py-3 mb-3" style="background:#fff3cd; color:#856404; border:1px solid #ffc107; border-radius:10px; font-family:'Montserrat',sans-serif; font-size:0.9rem;">
                        ✉️ Сначала подтвердите email. <a href="verify.php?resend=<?php echo urlencode($unverified_email); ?>" style="color:#856404; font-weight:600;">Отправить письмо повторно</a>
                    </div>
                <?php endif; ?>
                <?php if (!empty($errors)): ?>
                    <div class="px-4 py-3 mb-3" style="background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:10px; font-family:'Montserrat',sans-serif; font-size:0.9rem;">
                        <?php foreach ($errors as $e): ?>
                            <p style="margin:3px 0;">⚠️ <?php echo htmlspecialchars($e); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form class="auth-form" id="loginForm" method="POST" action="login.php">
                    <div class="form-group mb-0">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" 
                               id="email" 
                               name="email"
                               class="form-input"
                               required>
                        <div class="error-message" id="emailError">
                            Введите корректный email
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label" for="password">Пароль</label>
                        <div class="password-input">
                            <input type="password" 
                                   id="password" 
                                   name="password"
                                   class="form-input"
                                   required>
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                        <div class="error-message" id="passwordError">
                            Минимум 6 символов
                        </div>
                    </div>

                    <div class="remember-forgot flex-wrap gap-2">
                        <label class="remember-me">
                            <input type="checkbox" id="rememberMe">
                            <span>Запомнить меня</span>
                        </label>
                        <a href="#" class="forgot-password">Забыли пароль?</a>
                    </div>

                    <button type="submit" class="auth-button mt-1" id="loginButton">
                        Войти
                    </button>
                </form>

                <!-- Социальная авторизация -->
                <div class="auth-divider">
                    <span>Или войдите через</span>
                </div>

                <div class="social-auth flex-column flex-sm-row">
                    <button type="button" class="social-button yandex w-100">
                        <i class="fab fa-yandex"></i>
                        Яндекс
                    </button>
                    <button type="button" class="social-button vk w-100">
                        <i class="fab fa-vk"></i>
                        ВКонтакте
                    </button>
                </div>

                <!-- Ссылка на регистрацию -->
                <div class="auth-footer">
                    <p>Еще нет аккаунта? <a href="register.php" class="auth-link">Зарегистрироваться</a></p>
                </div>
            </div>
        </div>
    </main>

    <!-- Футер -->
    <?php include __DIR__ . '/footer.php'; ?>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Скрытие спиннера
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

            // Объявляем переменные ОДИН РАЗ
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');
            
            // Переключение видимости пароля
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="far fa-eye"></i>' : '<i class="far fa-eye-slash"></i>';
                });
            }

            // Функция валидации email
            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            // Валидация формы
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    let isValid = true;
                    
                    // Валидация email
                    if (!emailInput.value || !validateEmail(emailInput.value)) {
                        emailError.style.display = 'block';
                        emailInput.style.borderColor = 'var(--error-color)';
                        isValid = false;
                    } else {
                        emailError.style.display = 'none';
                        emailInput.style.borderColor = 'var(--border-color)';
                    }
                    
                    // Валидация пароля
                    if (!passwordInput.value || passwordInput.value.length < 6) {
                        passwordError.style.display = 'block';
                        passwordInput.style.borderColor = 'var(--error-color)';
                        isValid = false;
                    } else {
                        passwordError.style.display = 'none';
                        passwordInput.style.borderColor = 'var(--border-color)';
                    }
                    
                    if (isValid) {
                        const loginButton = document.getElementById('loginButton');
                        loginButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Вход...';
                        loginButton.disabled = true;
                        loginForm.submit();
                    }
                });
            }

            // Навигация
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                if (link.getAttribute('href') === 'login.php') {
                    link.classList.add('active');
                }
            });

            // Добавляем обработчик для кнопки пользователя (личный кабинет)
            const userButton = document.getElementById('userButton');
            if (userButton) {
                userButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (isAuthenticated) {
                        window.location.href = 'account.php';
                    } else {
                        window.location.href = 'login.php';
                    }
                });
            }
        });
    </script>
    <script src="script.js?v=<?= (int) $bm_js_ver ?>"></script>
</body>
</html>
