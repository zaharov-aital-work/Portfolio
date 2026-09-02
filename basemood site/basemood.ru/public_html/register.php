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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim(strip_tags($_POST['first_name'] ?? ''));
    $last_name  = trim(strip_tags($_POST['last_name'] ?? ''));
    $email      = trim($_POST['email'] ?? '');
    $phone      = trim(strip_tags($_POST['phone'] ?? ''));
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    if (empty($first_name)) $errors[] = 'Введите имя';
    if (empty($last_name))  $errors[] = 'Введите фамилию';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Введите корректный email';
    if (strlen($password) < 8) $errors[] = 'Пароль должен содержать минимум 8 символов';
    if ($password !== $confirm) $errors[] = 'Пароли не совпадают';

    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = 'Аккаунт с таким email уже существует';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt2 = mysqli_prepare($conn, "INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, "sssss", $first_name, $last_name, $email, $phone, $hashed);
            if (mysqli_stmt_execute($stmt2)) {
                $new_id = mysqli_insert_id($conn);

                // Генерируем токен подтверждения
                $token = bin2hex(random_bytes(32));
                $stmt3 = mysqli_prepare($conn, "UPDATE users SET verification_token = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt3, "si", $token, $new_id);
                mysqli_stmt_execute($stmt3);

                // Отправляем письмо
                $protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $verify_link = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/verify.php?token=' . $token;
                $subject     = '=?UTF-8?B?' . base64_encode('Подтвердите ваш email — BASEMOOD') . '?=';
                $body        = "Здравствуйте, {$first_name}!\n\n"
                             . "Для завершения регистрации перейдите по ссылке:\n{$verify_link}\n\n"
                             . "Если вы не регистрировались на нашем сайте, просто проигнорируйте это письмо.";
                $headers     = "From: noreply@" . $_SERVER['HTTP_HOST'] . "\r\n"
                             . "Content-Type: text/plain; charset=UTF-8\r\n"
                             . "Content-Transfer-Encoding: 8bit";
                mail($email, $subject, $body, $headers);

                header('Location: login.php?verify_sent=1');
                exit;
            } else {
                $errors[] = 'Ошибка при регистрации. Попробуйте ещё раз.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="description" content="Регистрация в BASEMOOD">
    <title>Регистрация в BASEMOOD</title>
    
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
        /* Стили для страницы регистрации (аналогичны login) */
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
            max-width: 500px;
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
            margin: 0 auto 4px;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-color);
            font-family: 'Montserrat', sans-serif;
        }

        .form-label::after {
            content: ' *';
            color: #d62828;
            font-weight: 600;
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

        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin: 15px 0 25px;
        }

        .terms-checkbox input {
            margin-top: 3px;
        }

        .terms-text {
            font-size: calc(0.85rem - 1px);
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
            line-height: 1.4;
            flex: 1;
        }

        .terms-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-family: 'Montserrat', sans-serif;
        }

        .terms-link:hover {
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
                padding: calc(var(--header-height) + 80px) 16px 96px;
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
                max-width: 640px;
                margin: 0 auto;
                background: transparent;
                border-radius: 0;
                box-shadow: none;
                overflow: visible;
            }

            .auth-header {
                background: transparent;
                color: var(--primary-color);
                padding: 8px 28px 0;
            }

            .auth-body {
                padding: 4px 20px 16px;
                margin-top: 8px;
            }

            .auth-title {
                display: block;
                font-size: 1.55rem;
                margin-bottom: 0;
            }
        }

        @media (max-width: 768px) {
            .auth-page {
                padding: 100px 15px 60px;
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

            .form-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .social-auth {
                flex-direction: column;
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
                font-size: 16px; /* Предотвращает увеличение масштаба на iOS */
                min-height: 35px; /* Увеличено примерно на 60% */
            }

            .auth-button {
                padding: 14px;
            }
            
            .form-row {
                gap: 15px;
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
                <h1 class="auth-title">Регистрация</h1>
            </div>

            <!-- Форма -->
            <div class="auth-body">
                <?php if (!empty($errors)): ?>
                    <div class="px-4 py-3 mb-3" style="background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; border-radius:10px; font-family:'Montserrat',sans-serif; font-size:0.9rem;">
                        <?php foreach ($errors as $e): ?>
                            <p style="margin:3px 0;">⚠️ <?php echo htmlspecialchars($e); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form class="auth-form" id="registerForm" method="POST" action="register.php">
                    <div class="form-row">
                        <div class="form-group mb-0">
                            <label class="form-label" for="firstName">Имя</label>
                            <input type="text" 
                                   id="firstName" 
                                   name="first_name"
                                   class="form-input"
                                   required>
                            <div class="error-message" id="firstNameError">
                                Введите ваше имя
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label" for="lastName">Фамилия</label>
                            <input type="text" 
                                   id="lastName" 
                                   name="last_name"
                                   class="form-input"
                                   required>
                            <div class="error-message" id="lastNameError">
                                Введите вашу фамилию
                            </div>
                        </div>
                    </div>

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
                        <label class="form-label" for="phone">Телефон</label>
                        <input type="tel" 
                               id="phone" 
                               name="phone"
                               class="form-input"
                               required>
                        <div class="error-message" id="phoneError">
                            Введите корректный номер телефона
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
                            Минимум 8 символов (буквы и цифры)
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label" for="confirmPassword">Подтверждение пароля</label>
                        <div class="password-input">
                            <input type="password" 
                                   id="confirmPassword" 
                                   name="confirm_password"
                                   class="form-input"
                                   required>
                            <button type="button" class="toggle-password" id="toggleConfirmPassword">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                        <div class="error-message" id="confirmPasswordError">
                            Пароли не совпадают
                        </div>
                    </div>

                    <div class="terms-checkbox flex-wrap">
                        <input type="checkbox" id="terms" required>
                        <label for="terms" class="terms-text">
                            Я соглашаюсь с <a href="offer.php" class="terms-link">Условиями использования</a> 
                            и <a href="privacy.php" class="terms-link">Политикой конфиденциальности</a>
                        </label>
                    </div>

                    <button type="submit" class="auth-button mt-1" id="registerButton">
                        Зарегистрироваться
                    </button>
                </form>

                <!-- Социальная регистрация -->
                <div class="auth-divider">
                    <span>Или зарегистрируйтесь через</span>
                </div>

                <div class="social-auth flex-column flex-sm-row">
                    <button type="button" class="social-button yandex w-100">
                        <span style="font-weight:800; font-size:17px; line-height:1; font-family:serif;">Я</span>
                        Яндекс
                    </button>
                    <button type="button" class="social-button vk w-100">
                        <i class="fab fa-vk"></i>
                        ВКонтакте
                    </button>
                </div>

                <!-- Ссылка на вход -->
                <div class="auth-footer">
                    <p>Уже есть аккаунт? <a href="login.php" class="auth-link">Войти</a></p>
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

            // Маска для телефона
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace') {
                        const pos = this.selectionStart;
                        const val = this.value;
                        // Пропускаем символы-разделители при удалении
                        if (pos > 0 && /[\s\(\)\-\+]/.test(val[pos - 1])) {
                            e.preventDefault();
                            const newPos = pos - 1;
                            this.value = val.slice(0, newPos) + val.slice(pos);
                            this.setSelectionRange(newPos, newPos);
                        }
                    }
                });

                phoneInput.addEventListener('input', function(e) {
                    const cursorPos = this.selectionStart;
                    const oldLen = this.value.length;

                    let digits = this.value.replace(/\D/g, '');
                    // Заменяем 8 на 7 в начале
                    if (digits.startsWith('8')) digits = '7' + digits.slice(1);
                    if (!digits.startsWith('7')) digits = '7' + digits;
                    digits = digits.slice(0, 11);

                    let formatted = '+7';
                    if (digits.length > 1) formatted += ' (' + digits.slice(1, 4);
                    if (digits.length >= 4) formatted += ') ' + digits.slice(4, 7);
                    if (digits.length >= 7) formatted += '-' + digits.slice(7, 9);
                    if (digits.length >= 9) formatted += '-' + digits.slice(9, 11);

                    this.value = formatted;
                    // Сохраняем позицию курсора
                    const newLen = this.value.length;
                    const newPos = cursorPos + (newLen - oldLen);
                    this.setSelectionRange(Math.max(newPos, 3), Math.max(newPos, 3));
                });

                phoneInput.addEventListener('focus', function() {
                    if (!this.value) this.value = '+7 (';
                });

                phoneInput.addEventListener('blur', function() {
                    if (this.value === '+7 (') this.value = '';
                });
            }

            // Переключение видимости паролей
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            
            function setupPasswordToggle(button, input) {
                if (button && input) {
                    button.addEventListener('click', function() {
                        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                        input.setAttribute('type', type);
                        this.innerHTML = type === 'password' ? '<i class="far fa-eye"></i>' : '<i class="far fa-eye-slash"></i>';
                    });
                }
            }
            
            setupPasswordToggle(togglePassword, passwordInput);
            setupPasswordToggle(toggleConfirmPassword, confirmPasswordInput);

            // Валидация формы
            const registerForm = document.getElementById('registerForm');
            
            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            
            function validatePhone(phone) {
                const re = /^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/;
                return re.test(phone);
            }
            
            function validatePassword(password) {
                return password.length >= 8 && /\d/.test(password) && /[a-zA-Z]/.test(password);
            }

            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    let isValid = true;
                    
                    // Валидация имени
                    const firstName = document.getElementById('firstName');
                    const firstNameError = document.getElementById('firstNameError');
                    if (!firstName.value.trim()) {
                        firstNameError.style.display = 'block';
                        firstName.style.borderColor = 'var(--error-color)';
                        isValid = false;
                    } else {
                        firstNameError.style.display = 'none';
                        firstName.style.borderColor = 'var(--border-color)';
                    }
                    
                    // Валидация фамилии
                    const lastName = document.getElementById('lastName');
                    const lastNameError = document.getElementById('lastNameError');
                    if (!lastName.value.trim()) {
                        lastNameError.style.display = 'block';
                        lastName.style.borderColor = 'var(--error-color)';
                        isValid = false;
                    } else {
                        lastNameError.style.display = 'none';
                        lastName.style.borderColor = 'var(--border-color)';
                    }
                    
                    // Валидация email
                    const email = document.getElementById('email');
                    const emailError = document.getElementById('emailError');
                    if (!email.value || !validateEmail(email.value)) {
                        emailError.style.display = 'block';
                        email.style.borderColor = 'var(--error-color)';
                        isValid = false;
                    } else {
                        emailError.style.display = 'none';
                        email.style.borderColor = 'var(--border-color)';
                    }
                    
                    // Валидация телефона
                    const phone = document.getElementById('phone');
                    const phoneError = document.getElementById('phoneError');
                    if (!phone.value || !validatePhone(phone.value)) {
                        phoneError.style.display = 'block';
                        phone.style.borderColor = 'var(--error-color)';
                        isValid = false;
                    } else {
                        phoneError.style.display = 'none';
                        phone.style.borderColor = 'var(--border-color)';
                    }
                    
                    // Валидация пароля
                    const password = document.getElementById('password');
                    const passwordError = document.getElementById('passwordError');
                    if (!password.value || !validatePassword(password.value)) {
                        passwordError.style.display = 'block';
                        password.style.borderColor = 'var(--error-color)';
                        isValid = false;
                    } else {
                        passwordError.style.display = 'none';
                        password.style.borderColor = 'var(--border-color)';
                    }
                    
                    // Подтверждение пароля
                    const confirmPassword = document.getElementById('confirmPassword');
                    const confirmPasswordError = document.getElementById('confirmPasswordError');
                    if (password.value !== confirmPassword.value) {
                        confirmPasswordError.style.display = 'block';
                        confirmPassword.style.borderColor = 'var(--error-color)';
                        isValid = false;
                    } else {
                        confirmPasswordError.style.display = 'none';
                        confirmPassword.style.borderColor = 'var(--border-color)';
                    }
                    
                    // Проверка согласия с условиями
                    const terms = document.getElementById('terms');
                    if (!terms.checked) {
                        alert('Пожалуйста, согласитесь с условиями использования');
                        isValid = false;
                    }
                    
                    if (isValid) {
                        const registerButton = document.getElementById('registerButton');
                        registerButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Регистрация...';
                        registerButton.disabled = true;
                        registerForm.submit();
                    }
                });
            }

            // Навигация
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                if (link.getAttribute('href') === 'register.php') {
                    link.classList.add('active');
                }
            });
        });

        // Добавляем обработчик для кнопки пользователя (личный кабинет)
        const userButton = document.getElementById('userButton');
        if (userButton) {
            userButton.addEventListener('click', function(e) {
                e.preventDefault();
                const isAuthenticated = localStorage.getItem('basemood-auth') === 'true';
                if (isAuthenticated) {
                    window.location.href = 'account.php';
                } else {
                    window.location.href = 'login.php';
                }
            });
        }
    </script>
    <script src="script.js?v=<?= (int) $bm_js_ver ?>"></script>
</body>
</html>
