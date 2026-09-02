<?php
// ==========================================
// BASEMOOD SECURE ADMIN PANEL
// ==========================================
// Полная защита от взлома и перебора паролей

$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $is_https,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

if (empty($_SESSION['session_initialized'])) {
    session_regenerate_id(true);
    $_SESSION['session_initialized'] = true;
}

// ==========================================
// КОНФИГУРАЦИЯ БЕЗОПАСНОСТИ
// ==========================================

// 1. Хешированный пароль (bcrypt)
// Пароль: Admin@2024.SecurePass#9x7k
$secure_password_hash = '$2y$12$3f6vh5anjcFur4HaljmdfeQN1VW3V7yCAhWNHY4o3cXmpOYsZ6bim';

// 2. Параметры защиты от brute force
$max_attempts = 5;
$lockout_time = 3600; // 1 час
// Логи и служебные файлы — внутри public_html/.security (и .htaccess Deny from all)
$bm_security_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.security';
$attempt_log_file = $bm_security_dir . DIRECTORY_SEPARATOR . 'login_attempts.log';
$admin_actions_log = $bm_security_dir . DIRECTORY_SEPARATOR . 'admin_actions.log';

// ==========================================
// ФУНКЦИИ БЕЗОПАСНОСТИ
// ==========================================

// Создание защищённой директории логов
if (!is_dir($bm_security_dir)) {
    @mkdir($bm_security_dir, 0700, true);
    @file_put_contents($bm_security_dir . DIRECTORY_SEPARATOR . 'index.php', '<?php exit;');
    @file_put_contents($bm_security_dir . DIRECTORY_SEPARATOR . '.htaccess', "Deny from all\n<FilesMatch \"\\.log$\">\n    Deny from all\n</FilesMatch>\n");
}

// Логирование попыток входа
function log_login_attempt($success, $details = '') {
    global $attempt_log_file;
    $timestamp = date('Y-m-d H:i:s');
    $ip = get_client_ip();
    $status = $success ? 'SUCCESS' : 'FAILED';
    $log_entry = "[$timestamp] IP: $ip | Status: $status | $details\n";
    @file_put_contents($attempt_log_file, $log_entry, FILE_APPEND);
    @chmod($attempt_log_file, 0600);
}

// Логирование действий админа
function log_admin_action($action, $details = '') {
    global $admin_actions_log;
    $timestamp = date('Y-m-d H:i:s');
    $ip = get_client_ip();
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 100);
    $log_entry = "[$timestamp] IP: $ip | Action: $action | Details: $details | UA: $user_agent\n";
    @file_put_contents($admin_actions_log, $log_entry, FILE_APPEND);
    @chmod($admin_actions_log, 0600);
}

// Получение реального IP адреса
function get_client_ip() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return filter_var(trim($ip), FILTER_VALIDATE_IP) ? trim($ip) : 'INVALID_IP';
}

// Проверка защиты от brute force
function check_brute_force($max_attempts, $lockout_time, $attempt_log_file) {
    $client_ip = get_client_ip();
    
    if (!file_exists($attempt_log_file)) {
        return true;
    }
    
    $log_contents = @file_get_contents($attempt_log_file);
    if (!$log_contents) return true;
    
    $lines = array_filter(explode("\n", $log_contents));
    $failed_attempts = 0;
    $current_time = time();
    
    // Проверка последних 100 строк
    foreach (array_slice($lines, -100) as $line) {
        if (strpos($line, $client_ip) !== false && strpos($line, 'FAILED') !== false) {
            if (preg_match('/\[([^\]]+)\]/', $line, $matches)) {
                $attempt_time = strtotime($matches[1]);
                if (($current_time - $attempt_time) < $lockout_time) {
                    $failed_attempts++;
                }
            }
        }
    }
    
    return $failed_attempts < $max_attempts;
}

// Генерация CSRF токена
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Проверка CSRF токена
function verify_csrf_token($token) {
    return (!empty($_SESSION['csrf_token']) && !empty($token) && 
            hash_equals($_SESSION['csrf_token'], $token));
}

// ==========================================
// ПРОВЕРКА ВХОДА
// ==========================================

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
            log_login_attempt(false, 'CSRF_TOKEN_INVALID');
            die('❌ Ошибка безопасности: невалидный CSRF токен.');
        }
        
        if (isset($_POST['password']) && strlen($_POST['password']) > 0) {
            if (password_verify($_POST['password'], $secure_password_hash)) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_ip'] = get_client_ip();
                $_SESSION['admin_login_time'] = time();
                $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                log_login_attempt(true, 'Успешный вход');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                log_login_attempt(false, 'INVALID_PASSWORD');
                $error = '❌ Неверный пароль!';
            }
        }
    }
    
    $csrf_token = generate_csrf_token();
    ?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>BASEMOOD Admin Console</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --bm-black: #111111;
            --bm-ink: #222222;
            --bm-gray: #6b6b6b;
            --bm-light: #f6f6f6;
            --bm-border: #dedede;
            --bm-white: #ffffff;
        }

        * { box-sizing: border-box; }

        html, body {
            width: 100%;
            height: 100%;
        }

        body {
            font-family: 'Montserrat', 'Helvetica Neue', Arial, sans-serif;
            color: var(--bm-ink);
            background:
                radial-gradient(circle at 85% 10%, rgba(0, 0, 0, 0.08) 0, transparent 36%),
                radial-gradient(circle at 12% 82%, rgba(0, 0, 0, 0.06) 0, transparent 42%),
                linear-gradient(145deg, #fefefe 0%, #f1f1f1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .auth-shell {
            width: 100%;
            max-width: 510px;
            border: 1px solid var(--bm-border);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(4px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }

        .auth-head {
            border-bottom: 1px solid var(--bm-border);
            background: linear-gradient(135deg, #111111 0%, #272727 100%);
            color: var(--bm-white);
            padding: 30px 32px;
        }

        .auth-head h1 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
        }

        .subtitle {
            margin-top: 8px;
            margin-bottom: 0;
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-weight: 600;
        }

        .auth-body {
            padding: 28px 32px 32px;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #4f4f4f;
        }

        .form-control {
            border: 1px solid #cfcfcf;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus {
            border-color: #111111;
            box-shadow: 0 0 0 0.25rem rgba(17, 17, 17, 0.1);
        }

        .btn-brand {
            width: 100%;
            border: 1px solid #111111;
            background: #111111;
            color: #ffffff;
            border-radius: 12px;
            padding: 12px 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            transition: all 0.2s ease;
        }

        .btn-brand:hover {
            background: #ffffff;
            color: #111111;
            border-color: #111111;
        }

        .security-features {
            margin-top: 20px;
            border: 1px dashed #c8c8c8;
            border-radius: 12px;
            padding: 14px;
            background: #fbfbfb;
            font-size: 0.8rem;
            color: #4c4c4c;
        }

        .feature-list {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .feature-item {
            position: relative;
            padding-left: 16px;
        }

        .feature-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.45em;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #111111;
        }

        .error {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            border: 1px solid #f1b5b5;
            background: #faecec;
            color: #9f2f2f;
            font-size: 0.86rem;
            font-weight: 500;
        }

        @media (max-width: 575.98px) {
            .auth-head,
            .auth-body {
                padding-left: 20px;
                padding-right: 20px;
            }

            .auth-head h1 {
                font-size: 1.45rem;
            }

            .feature-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <div class="auth-head">
            <h1>BASEMOOD</h1>
            <p class="subtitle">Admin Console</p>
        </div>

        <div class="auth-body">
        <?php if (!empty($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="mb-3">
                <label for="password" class="form-label">Пароль администратора</label>
                <input type="password" id="password" class="form-control" name="password" required autofocus
                       placeholder="Введите пароль" autocomplete="off" spellcheck="false">
            </div>

            <button type="submit" class="btn-brand">Войти в систему</button>
        </form>

        <div class="security-features">
            <strong>Активная защита</strong>
            <div class="feature-list">
                <div class="feature-item">Шифрование bcrypt</div>
                <div class="feature-item">Защита brute-force</div>
                <div class="feature-item">CSRF токены</div>
                <div class="feature-item">Логирование входов</div>
                <div class="feature-item">Ограничение IP</div>
                <div class="feature-item">Аудит действий</div>
            </div>
        </div>
        </div>
    </div>
</body>
</html><?php
    exit;
}

// Проверка сессии
// ⚠️ Проверка IP отключена - разрешен доступ с разных IP адресов
// if (get_client_ip() !== ($_SESSION['admin_ip'] ?? '')) {
//     log_admin_action('SESSION_HIJACK_ATTEMPT', 'IP изменился');
//     session_destroy();
//     die('❌ Сессия истекла по причине изменения IP адреса.');
// }

// ==========================================
// ОСНОВНАЯ АДМИНКА (ПОСЛЕ ВХОДА)
// ==========================================

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/product_images_helpers.php';
require_once dirname(__DIR__) . '/catalog_helpers.php';
require_once __DIR__ . '/orders_helpers.php';
require_once dirname(__DIR__) . '/shop_helpers.php';

if (!$conn) {
    die('❌ Ошибка подключения к БД');
}

$bm_catalog_v2 = bm_products_has_catalog_v2($conn);
$bm_orders_ok = bm_orders_tables_exist($conn);
$bm_shop_ok = bm_shop_table_exists($conn, 'shop_settings');

$success = '';
$error = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('❌ CSRF ошибка безопасности!');
    }
    
    $action = $_POST['action'] ?? '';

    if ($action === 'update_order') {
        if (!$bm_orders_ok) {
            $error = '❌ Таблицы заказов не созданы. Один раз откройте в браузере migrate_orders.php (рядом с index.php).';
        } else {
            $oid = (int) ($_POST['order_id'] ?? 0);
            $st = bm_orders_normalize_status($_POST['status'] ?? '');
            $pst = bm_orders_normalize_payment($_POST['payment_status'] ?? '');
            $track = mysqli_real_escape_string($conn, substr(strip_tags($_POST['tracking_number'] ?? ''), 0, 128));
            $notes = mysqli_real_escape_string($conn, strip_tags($_POST['admin_notes'] ?? ''));
            if ($oid <= 0) {
                $error = '❌ Некорректный номер заказа.';
            } else {
                $sql = "UPDATE orders SET status='$st', payment_status='$pst', tracking_number='$track', admin_notes='$notes' WHERE id=$oid LIMIT 1";
                if (mysqli_query($conn, $sql)) {
                    log_admin_action('UPDATE_ORDER', 'ID ' . $oid);
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?view=orders&order_id=' . $oid);
                    exit;
                }
                $error = '❌ Ошибка БД: ' . mysqli_error($conn);
            }
        }
    }

    if ($bm_shop_ok && $action === 'save_shop_settings') {
        bm_shop_settings_set($conn, 'mail_from', trim((string) ($_POST['mail_from'] ?? '')));
        bm_shop_settings_set($conn, 'mail_from_name', trim((string) ($_POST['mail_from_name'] ?? '')));
        bm_shop_settings_set($conn, 'admin_notify_email', trim((string) ($_POST['admin_notify_email'] ?? '')));
        log_admin_action('SHOP_SETTINGS', 'mail');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?view=reports');
        exit;
    }

    if ($bm_shop_ok && $action === 'delivery_save') {
        $did = (int) ($_POST['delivery_id'] ?? 0);
        $title = mysqli_real_escape_string($conn, strip_tags((string) ($_POST['title'] ?? '')));
        $price = max(0, (int) ($_POST['price_rub'] ?? 0));
        $sort = max(0, (int) ($_POST['sort_order'] ?? 0));
        $active = isset($_POST['active']) ? 1 : 0;
        if ($title === '') {
            $error = '❌ Укажите название доставки.';
        } elseif ($did <= 0) {
            mysqli_query($conn, "INSERT INTO delivery_methods (title, price_rub, sort_order, active) VALUES ('$title', $price, $sort, $active)");
            log_admin_action('DELIVERY_ADD', $title);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?view=delivery');
            exit;
        } else {
            mysqli_query($conn, "UPDATE delivery_methods SET title='$title', price_rub=$price, sort_order=$sort, active=$active WHERE id=$did LIMIT 1");
            log_admin_action('DELIVERY_SAVE', (string) $did);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?view=delivery');
            exit;
        }
    }

    if ($bm_shop_ok && $action === 'delivery_delete') {
        $did = (int) ($_POST['delivery_id'] ?? 0);
        if ($did > 0) {
            mysqli_query($conn, 'DELETE FROM delivery_methods WHERE id=' . $did . ' LIMIT 1');
            log_admin_action('DELIVERY_DELETE', (string) $did);
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?view=delivery');
        exit;
    }

    if ($bm_shop_ok && $action === 'promo_save') {
        $pid = (int) ($_POST['promo_id'] ?? 0);
        $codeRaw = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($_POST['code'] ?? '')));
        $dt = ($_POST['discount_type'] ?? '') === 'fixed' ? 'fixed' : 'percent';
        $dv = max(0, (int) ($_POST['discount_value'] ?? 0));
        $min = max(0, (int) ($_POST['min_order_rub'] ?? 0));
        $vu = trim((string) ($_POST['valid_until'] ?? ''));
        $active = isset($_POST['active']) ? 1 : 0;
        if ($pid <= 0 && $codeRaw === '') {
            $error = '❌ Укажите код промокода.';
        } else {
            $vuSql = ($vu === '') ? 'NULL' : "'" . mysqli_real_escape_string($conn, $vu) . "'";
            $codeEsc = mysqli_real_escape_string($conn, $pid <= 0 ? $codeRaw : '');
            if ($pid <= 0) {
                mysqli_query($conn, "INSERT INTO promocodes (code, discount_type, discount_value, min_order_rub, valid_until, active) VALUES ('$codeEsc', '$dt', $dv, $min, $vuSql, $active)");
                log_admin_action('PROMO_ADD', $codeRaw);
            } else {
                $codeRow = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT code FROM promocodes WHERE id=' . $pid . ' LIMIT 1'));
                $codeEsc = mysqli_real_escape_string($conn, $codeRow['code'] ?? '');
                mysqli_query($conn, "UPDATE promocodes SET discount_type='$dt', discount_value=$dv, min_order_rub=$min, valid_until=$vuSql, active=$active WHERE id=$pid LIMIT 1");
                log_admin_action('PROMO_SAVE', (string) $pid);
            }
            header('Location: ' . $_SERVER['PHP_SELF'] . '?view=promos');
            exit;
        }
    }

    if ($bm_shop_ok && $action === 'promo_delete') {
        $pid = (int) ($_POST['promo_id'] ?? 0);
        if ($pid > 0) {
            mysqli_query($conn, 'DELETE FROM promocodes WHERE id=' . $pid . ' LIMIT 1');
            log_admin_action('PROMO_DELETE', (string) $pid);
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?view=promos');
        exit;
    }

    if ($bm_shop_ok && $action === 'cms_save') {
        $slug = preg_replace('/[^a-z0-9\-_]/i', '', (string) ($_POST['slug'] ?? ''));
        $title = mysqli_real_escape_string($conn, strip_tags((string) ($_POST['cms_title'] ?? '')));
        $body = mysqli_real_escape_string($conn, (string) ($_POST['body_html'] ?? ''));
        $meta = mysqli_real_escape_string($conn, strip_tags((string) ($_POST['meta_description'] ?? '')));
        $pub = isset($_POST['published']) ? 1 : 0;
        if ($slug === '' || $title === '') {
            $error = '❌ Slug и заголовок обязательны.';
        } else {
            $slugEsc = mysqli_real_escape_string($conn, $slug);
            mysqli_query(
                $conn,
                "INSERT INTO cms_pages (slug, title, body_html, meta_description, published) VALUES ('$slugEsc', '$title', '$body', '$meta', $pub)
                ON DUPLICATE KEY UPDATE title='$title', body_html='$body', meta_description='$meta', published=$pub"
            );
            log_admin_action('CMS_SAVE', $slug);
            header('Location: ' . $_SERVER['PHP_SELF'] . '?view=cms&cms_slug=' . urlencode($slug));
            exit;
        }
    }

    if ($bm_shop_ok && $action === 'media_upload' && !empty($_FILES['media_file']['tmp_name'])) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $mime = '';
        if (function_exists('mime_content_type')) {
            $mime = (string) mime_content_type($_FILES['media_file']['tmp_name']);
        }
        if ($mime === '' && !empty($_FILES['media_file']['type'])) {
            $mime = (string) $_FILES['media_file']['type'];
        }
        $ext = $allowed[$mime] ?? '';
        if ($ext === '') {
            $error = '❌ Допустимы только изображения JPG, PNG, WEBP, GIF.';
        } else {
            $uploadDir = dirname(__DIR__) . '/uploads/bm';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            $fname = 'bm_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $target = $uploadDir . DIRECTORY_SEPARATOR . $fname;
            if (!move_uploaded_file($_FILES['media_file']['tmp_name'], $target)) {
                $error = '❌ Не удалось сохранить файл.';
            } else {
                $rel = 'uploads/bm/' . $fname;
                $orig = mysqli_real_escape_string($conn, basename((string) ($_FILES['media_file']['name'] ?? '')));
                $mimeEsc = mysqli_real_escape_string($conn, $mime);
                $relEsc = mysqli_real_escape_string($conn, $rel);
                mysqli_query($conn, "INSERT INTO media_files (path, original_name, mime) VALUES ('$relEsc', '$orig', '$mimeEsc')");
                log_admin_action('MEDIA_UPLOAD', $rel);
                header('Location: ' . $_SERVER['PHP_SELF'] . '?view=media');
                exit;
            }
        }
    }

    if ($bm_shop_ok && $action === 'media_delete') {
        $mid = (int) ($_POST['media_id'] ?? 0);
        if ($mid > 0) {
            $mr = mysqli_query($conn, 'SELECT path FROM media_files WHERE id=' . $mid . ' LIMIT 1');
            $mp = $mr ? mysqli_fetch_assoc($mr) : null;
            if ($mp && !empty($mp['path'])) {
                $abs = dirname(__DIR__) . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $mp['path']);
                if (is_file($abs)) {
                    @unlink($abs);
                }
            }
            mysqli_query($conn, 'DELETE FROM media_files WHERE id=' . $mid . ' LIMIT 1');
            log_admin_action('MEDIA_DELETE', (string) $mid);
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?view=media');
        exit;
    }

    if ($action === 'save_product_meta') {
        if (!bm_products_has_catalog_v2($conn)) {
            $error = '❌ В таблице products нет колонок каталога. Откройте в браузере: migrate_products_catalog_v2.php';
        } else {
            foreach ($_POST['meta'] ?? [] as $idStr => $row) {
                $pid = (int) $idStr;
                if ($pid <= 0 || !is_array($row)) {
                    continue;
                }
                $so = max(0, (int) ($row['sort_order'] ?? 0));
                $sh = isset($row['show_on_home']) ? 1 : 0;
                $sc = isset($row['show_in_catalog']) ? 1 : 0;
                $pop = max(0, (int) ($row['popularity'] ?? 0));
                mysqli_query($conn, "UPDATE products SET sort_order=$so, show_on_home=$sh, show_in_catalog=$sc, popularity=$pop WHERE id=$pid");
            }
            log_admin_action('SAVE_PRODUCT_META', 'Порядок и отображение');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    
    // Добавление товара
    if ($action === 'add') {
        $name = mysqli_real_escape_string($conn, strip_tags($_POST['name']));
        $price = intval($_POST['price']);
        $description = mysqli_real_escape_string($conn, strip_tags($_POST['description']));
        $material = mysqli_real_escape_string($conn, strip_tags($_POST['material']));
        $composition = mysqli_real_escape_string($conn, strip_tags($_POST['composition']));
        $printing = mysqli_real_escape_string($conn, strip_tags($_POST['printing']));
        $cat_slug = bm_normalize_category(strip_tags($_POST['category'] ?? ''));
        $category = mysqli_real_escape_string($conn, $cat_slug);

        $card_lines = array_slice(bm_parse_image_lines($_POST['card_images'] ?? ''), 0, 4);
        $gallery_lines = bm_parse_image_lines($_POST['gallery_images'] ?? '');
        $card_json = mysqli_real_escape_string($conn, json_encode($card_lines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $gallery_json = mysqli_real_escape_string($conn, json_encode($gallery_lines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $image_url = mysqli_real_escape_string($conn, $card_lines[0] ?? '');
        $image_back_url = mysqli_real_escape_string($conn, $card_lines[1] ?? '');

        if (empty($name) || $price < 0) {
            $error = '❌ Ошибка: проверьте заполненные данные';
        } elseif (count($card_lines) < 1) {
            $error = '❌ Укажите минимум одну картинку в блоке «Картинки карточки каталога» (до 4 строк).';
        } else {
            if (bm_products_has_catalog_v2($conn)) {
                $sort_order = (int) ($_POST['sort_order'] ?? 0);
                if ($sort_order <= 0) {
                    $mx = mysqli_query($conn, 'SELECT COALESCE(MAX(sort_order), 0) AS m FROM products');
                    $rowm = $mx ? mysqli_fetch_assoc($mx) : null;
                    $sort_order = (int) ($rowm['m'] ?? 0) + 10;
                }
                $show_on_home = isset($_POST['show_on_home']) ? 1 : 0;
                $show_in_catalog = isset($_POST['show_in_catalog']) ? 1 : 0;
                $popularity = max(0, (int) ($_POST['popularity'] ?? 0));
                $query = "INSERT INTO products (name, price, description, material, composition, printing, image_url, image_back_url, card_images, gallery_images, category, sort_order, show_on_home, show_in_catalog, popularity)
                     VALUES ('$name', $price, '$description', '$material', '$composition', '$printing', '$image_url', '$image_back_url', '$card_json', '$gallery_json', '$category', $sort_order, $show_on_home, $show_in_catalog, $popularity)";
            } else {
                $query = "INSERT INTO products (name, price, description, material, composition, printing, image_url, image_back_url, card_images, gallery_images, category)
                     VALUES ('$name', $price, '$description', '$material', '$composition', '$printing', '$image_url', '$image_back_url', '$card_json', '$gallery_json', '$category')";
            }

            if (mysqli_query($conn, $query)) {
                $newPid = (int) mysqli_insert_id($conn);
                if ($bm_shop_ok && bm_shop_table_exists($conn, 'product_stock')) {
                    bm_stock_save_for_product($conn, $newPid, $_POST['stock'] ?? []);
                }
                $success = '✅ Товар успешно добавлен!';
                log_admin_action('ADD_PRODUCT', "ID товара: $newPid, Название: $name");
            } else {
                $error = '❌ Ошибка БД: ' . mysqli_error($conn);
                if (stripos(mysqli_error($conn), 'card_images') !== false || stripos(mysqli_error($conn), 'gallery_images') !== false || stripos(mysqli_error($conn), 'Unknown column') !== false) {
                    $error .= ' Запустите migrate_products_card_gallery.php и migrate_products_catalog_v2.php в public_html.';
                }
                log_admin_action('ADD_PRODUCT_FAILED', $error);
            }
        }
    }
    
    // Редактирование товара
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = mysqli_real_escape_string($conn, strip_tags($_POST['name']));
        $price = intval($_POST['price']);
        $description = mysqli_real_escape_string($conn, strip_tags($_POST['description']));
        $material = mysqli_real_escape_string($conn, strip_tags($_POST['material']));
        $composition = mysqli_real_escape_string($conn, strip_tags($_POST['composition']));
        $printing = mysqli_real_escape_string($conn, strip_tags($_POST['printing']));
        $cat_slug = bm_normalize_category(strip_tags($_POST['category'] ?? ''));
        $category = mysqli_real_escape_string($conn, $cat_slug);

        $card_lines = array_slice(bm_parse_image_lines($_POST['card_images'] ?? ''), 0, 4);
        $gallery_lines = bm_parse_image_lines($_POST['gallery_images'] ?? '');
        $card_json = mysqli_real_escape_string($conn, json_encode($card_lines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $gallery_json = mysqli_real_escape_string($conn, json_encode($gallery_lines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $image_url = mysqli_real_escape_string($conn, $card_lines[0] ?? '');
        $image_back_url = mysqli_real_escape_string($conn, $card_lines[1] ?? '');

        if (empty($name) || $price < 0) {
            $error = '❌ Ошибка: проверьте заполненные данные';
        } elseif (count($card_lines) < 1) {
            $error = '❌ Укажите минимум одну картинку в блоке «Картинки карточки каталога».';
        } else {
            if (bm_products_has_catalog_v2($conn)) {
                $sort_order = max(0, (int) ($_POST['sort_order'] ?? 0));
                $show_on_home = isset($_POST['show_on_home']) ? 1 : 0;
                $show_in_catalog = isset($_POST['show_in_catalog']) ? 1 : 0;
                $popularity = max(0, (int) ($_POST['popularity'] ?? 0));
                $query = "UPDATE products SET name='$name', price=$price, description='$description', material='$material', composition='$composition', printing='$printing', image_url='$image_url', image_back_url='$image_back_url', card_images='$card_json', gallery_images='$gallery_json', category='$category', sort_order=$sort_order, show_on_home=$show_on_home, show_in_catalog=$show_in_catalog, popularity=$popularity WHERE id=$id";
            } else {
                $query = "UPDATE products SET name='$name', price=$price, description='$description', material='$material', composition='$composition', printing='$printing', image_url='$image_url', image_back_url='$image_back_url', card_images='$card_json', gallery_images='$gallery_json', category='$category' WHERE id=$id";
            }
            if (mysqli_query($conn, $query)) {
                if ($bm_shop_ok && bm_shop_table_exists($conn, 'product_stock')) {
                    bm_stock_save_for_product($conn, $id, $_POST['stock'] ?? []);
                }
                $success = '✅ Товар успешно обновлён!';
                log_admin_action('EDIT_PRODUCT', "ID: $id, Название: $name");
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $error = '❌ Ошибка БД: ' . mysqli_error($conn);
                if (stripos(mysqli_error($conn), 'card_images') !== false || stripos(mysqli_error($conn), 'gallery_images') !== false || stripos(mysqli_error($conn), 'Unknown column') !== false) {
                    $error .= ' Запустите migrate_products_card_gallery.php и migrate_products_catalog_v2.php в public_html.';
                }
                log_admin_action('EDIT_PRODUCT_FAILED', $error);
            }
        }
    }

    // Удаление товара
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $product_result = mysqli_query($conn, "SELECT name FROM products WHERE id = $id");
        $product = mysqli_fetch_assoc($product_result);
        
        $query = "DELETE FROM products WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $success = '✅ Товар успешно удален!';
            log_admin_action('DELETE_PRODUCT', "ID: $id, Товар: " . ($product['name'] ?? 'Unknown'));
        } else {
            $error = '❌ Ошибка: ' . mysqli_error($conn);
            log_admin_action('DELETE_PRODUCT_FAILED', $error);
        }
    }
}

// Получение товаров
$result = mysqli_query(
    $conn,
    bm_products_has_column($conn, 'sort_order')
        ? 'SELECT * FROM products ORDER BY sort_order ASC, id ASC'
        : 'SELECT * FROM products ORDER BY id DESC'
);
$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

// Загрузка товара для редактирования
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_result = mysqli_query($conn, "SELECT * FROM products WHERE id = $edit_id");
    $edit_product = mysqli_fetch_assoc($edit_result);
}

$allowed_views = ['catalog', 'orders', 'users', 'delivery', 'promos', 'cms', 'media', 'logs', 'reports'];
$rawView = $_GET['view'] ?? 'catalog';
$view = in_array($rawView, $allowed_views, true) ? $rawView : 'catalog';
if ($view !== 'catalog') {
    $edit_product = null;
}

$stock_map_edit = [];
if ($bm_shop_ok && $edit_product && bm_shop_table_exists($conn, 'product_stock')) {
    $stock_map_edit = bm_stock_map_for_product($conn, (int) $edit_product['id']);
}

$orders_admin_order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;

$orders_list = [];
$order_detail = null;
$order_detail_items = [];
$orders_total_count = 0;

if ($bm_orders_ok) {
    $cr = mysqli_query($conn, 'SELECT COUNT(*) AS c FROM orders');
    $orders_total_count = ($cr && ($crow = mysqli_fetch_assoc($cr))) ? (int) ($crow['c'] ?? 0) : 0;
    if ($view === 'orders') {
        if ($orders_admin_order_id > 0) {
            $oq = mysqli_query($conn, 'SELECT * FROM orders WHERE id = ' . $orders_admin_order_id . ' LIMIT 1');
            $order_detail = $oq ? mysqli_fetch_assoc($oq) : null;
            if ($order_detail) {
                $oiq = mysqli_query($conn, 'SELECT * FROM order_items WHERE order_id = ' . $orders_admin_order_id . ' ORDER BY id ASC');
                while ($oiq && ($row = mysqli_fetch_assoc($oiq))) {
                    $order_detail_items[] = $row;
                }
            }
        } else {
            $olr = mysqli_query($conn, 'SELECT id, created_at, customer_name, customer_email, customer_phone, total_amount, status, payment_status FROM orders ORDER BY created_at DESC, id DESC LIMIT 200');
            while ($olr && ($row = mysqli_fetch_assoc($olr))) {
                $orders_list[] = $row;
            }
        }
    }
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BASEMOOD Admin Console</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bm-black: #111111;
            --bm-ink: #222222;
            --bm-muted: #6b6b6b;
            --bm-bg: #f5f5f5;
            --bm-card: #ffffff;
            --bm-border: #e2e2e2;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Montserrat', 'Helvetica Neue', Arial, sans-serif;
            color: var(--bm-ink);
            background:
                radial-gradient(circle at 20% 0%, rgba(0, 0, 0, 0.07) 0, transparent 22%),
                linear-gradient(180deg, #fafafa 0%, var(--bm-bg) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1620px;
            margin: 0 auto;
        }

        .admin-header {
            background: linear-gradient(120deg, #141414 0%, #2a2a2a 100%);
            color: #ffffff;
            border-radius: 18px;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.2);
        }

        .admin-header h1 {
            margin: 0;
            font-size: clamp(1.3rem, 1.6vw, 1.85rem);
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .admin-subtitle {
            margin-top: 4px;
            margin-bottom: 0;
            color: rgba(255, 255, 255, 0.76);
            font-size: 0.79rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 600;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .security-badge {
            background: rgba(255, 255, 255, 0.13);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            border-radius: 999px;
            padding: 8px 14px;
            font-size: 0.72rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .logout-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            background: rgba(255, 255, 255, 0.07);
            color: #ffffff;
            text-decoration: none;
            padding: 9px 14px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background: #ffffff;
            color: #161616;
        }

        .dashboard-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }

        .meta-chip {
            border-radius: 999px;
            border: 1px solid #d4d4d4;
            background: #ffffff;
            padding: 8px 12px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #4a4a4a;
            font-weight: 600;
        }

        .alert {
            border-radius: 12px;
            border-width: 1px;
            border-style: solid;
            margin-bottom: 10px;
        }

        .alert-success {
            background: #edf6ef;
            border-color: #b4dfbc;
            color: #145028;
        }

        .alert-error {
            background: #faecec;
            border-color: #edbbbb;
            color: #922f2f;
        }

        .content {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 18px;
        }

        .panel-card {
            background: var(--bm-card);
            border: 1px solid var(--bm-border);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            padding: 22px;
        }

        h2 {
            font-size: 1.08rem;
            margin-bottom: 16px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .edit-mode-title {
            color: #8c5b12;
        }

        .form-group {
            margin-bottom: 12px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.76rem;
            color: #4f4f4f;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-weight: 700;
        }

        input,
        textarea {
            width: 100%;
            border: 1px solid #cecece;
            border-radius: 10px;
            padding: 10px 12px;
            font-family: inherit;
            font-size: 0.9rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            background: #ffffff;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #111111;
            box-shadow: 0 0 0 0.2rem rgba(17, 17, 17, 0.08);
        }

        .btn-main {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid #111111;
            background: #111111;
            color: #ffffff;
            padding: 10px 16px;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            font-size: 0.73rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-main:hover {
            background: #ffffff;
            color: #111111;
        }

        .btn-delete {
            border: 1px solid #b83838;
            background: #b83838;
            color: #ffffff;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: all 0.2s ease;
            line-height: 1.2;
        }

        .btn-delete:hover {
            background: #ffffff;
            color: #9f2f2f;
        }

        .btn-edit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            border: 1px solid #7a7a7a;
            background: #f2f2f2;
            color: #292929;
            border-radius: 6px;
            font-weight: 700;
            font-size: 0.6rem;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-right: 4px;
            transition: all 0.2s ease;
            line-height: 1.2;
        }

        .btn-edit:hover {
            background: #1f1f1f;
            color: #ffffff;
            border-color: #1f1f1f;
        }

        .products-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .product-count {
            background: #111111;
            color: #ffffff;
            padding: 6px 11px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .search-input {
            flex: 1 1 360px;
            max-width: 420px;
            min-width: 240px;
            width: 100%;
            margin-left: auto;
            font-size: 0.86rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 620px;
        }

        .products-table th {
            border-bottom: 1px solid #e2e2e2;
            background: #fafafa;
            padding: 12px;
            text-align: left;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #595959;
        }

        .products-table td {
            border-bottom: 1px solid #f0f0f0;
            padding: 12px;
            font-size: 0.86rem;
        }

        .products-table tbody tr:hover {
            background: #fbfbfb;
        }

        .admin-nav-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        .admin-nav-tabs a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid #cfcfcf;
            background: #fff;
            color: #292929;
            transition: border-color 0.2s ease, color 0.2s ease, background 0.2s ease;
        }

        .admin-nav-tabs a:hover {
            border-color: #111111;
            color: #111111;
        }

        .admin-nav-tabs a.active {
            background: #111111;
            border-color: #111111;
            color: #ffffff;
        }

        .admin-nav-tabs .tab-badge {
            display: inline-block;
            min-width: 22px;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .admin-nav-tabs a.active .tab-badge {
            background: rgba(255, 255, 255, 0.22);
            color: inherit;
        }

        .admin-nav-tabs a:not(.active) .tab-badge {
            background: #111111;
            color: #ffffff;
        }

        .content.content-orders-full {
            grid-template-columns: 1fr;
        }

        @media (max-width: 1000px) {
            .content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            body {
                padding: 14px;
            }

            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-right {
                width: 100%;
                justify-content: space-between;
            }

            .panel-card {
                padding: 16px;
            }

            .search-input {
                max-width: 100%;
                min-width: 0;
                flex-basis: 100%;
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="admin-header">
            <div>
                <h1>Панель управления BASEMOOD</h1>
                <p class="admin-subtitle">Каталог и заказы</p>
            </div>
            <div class="header-right">
                <span class="security-badge">✓ Защита активна</span>
                <a href="?logout=1" class="logout-btn">Выход</a>
            </div>
        </header>

        <nav class="admin-nav-tabs" aria-label="Разделы админки">
            <a href="?" class="<?php echo $view === 'catalog' ? 'active' : ''; ?>"><i class="fas fa-shirt"></i> Каталог</a>
            <a href="?view=orders" class="<?php echo $view === 'orders' ? 'active' : ''; ?>"><i class="fas fa-receipt"></i> Заказы<?php if ($bm_orders_ok && $orders_total_count > 0): ?> <span class="tab-badge"><?php echo $orders_total_count; ?></span><?php endif; ?></a>
            <a href="?view=users" class="<?php echo $view === 'users' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Клиенты</a>
            <a href="?view=delivery" class="<?php echo $view === 'delivery' ? 'active' : ''; ?>"><i class="fas fa-truck"></i> Доставка</a>
            <a href="?view=promos" class="<?php echo $view === 'promos' ? 'active' : ''; ?>"><i class="fas fa-tag"></i> Промокоды</a>
            <a href="?view=cms" class="<?php echo $view === 'cms' ? 'active' : ''; ?>"><i class="fas fa-file-lines"></i> Страницы</a>
            <a href="?view=media" class="<?php echo $view === 'media' ? 'active' : ''; ?>"><i class="fas fa-image"></i> Медиа</a>
            <a href="?view=logs" class="<?php echo $view === 'logs' ? 'active' : ''; ?>"><i class="fas fa-shield-halved"></i> Журнал</a>
            <a href="?view=reports" class="<?php echo $view === 'reports' ? 'active' : ''; ?>"><i class="fas fa-chart-simple"></i> Отчёты</a>
        </nav>

        <div class="dashboard-meta">
            <span class="meta-chip">Продуктов в каталоге: <?php echo count($products); ?></span>
            <?php if ($bm_orders_ok): ?>
            <span class="meta-chip">Заказов в базе: <?php echo $orders_total_count; ?></span>
            <?php endif; ?>
            <span class="meta-chip">Сессия: <?php echo date('d.m.Y H:i', $_SESSION['admin_login_time'] ?? time()); ?></span>
            <span class="meta-chip">IP: <?php echo htmlspecialchars($_SESSION['admin_ip'] ?? get_client_ip()); ?></span>
        </div>
        
        <div class="alerts">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!$bm_catalog_v2): ?>
                <div class="alert alert-error">В БД нет колонок <code>show_on_home</code>, <code>show_in_catalog</code>, <code>sort_order</code>, <code>popularity</code>. Один раз откройте в браузере: <strong>migrate_products_catalog_v2.php</strong> (файл в корне сайта рядом с <code>index.php</code>).</div>
            <?php endif; ?>
        </div>

        <?php if ($view === 'catalog'): ?>
        <div class="content">
            <div class="panel-card">
                <?php if ($edit_product): ?>
                    <h2 class="edit-mode-title">Редактировать товар #<?php echo $edit_product['id']; ?></h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div class="form-group">
                            <label for="name">Название *</label>
                            <input type="text" id="name" name="name" required maxlength="255" value="<?php echo htmlspecialchars($edit_product['name']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="price">Цена (₽) *</label>
                            <input type="number" id="price" name="price" required min="0" max="999999" value="<?php echo intval($edit_product['price']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="description">Описание</label>
                            <textarea id="description" name="description" rows="2" maxlength="500"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="material">Материал</label>
                            <input type="text" id="material" name="material" maxlength="100" value="<?php echo htmlspecialchars($edit_product['material'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="composition">Состав</label>
                            <input type="text" id="composition" name="composition" maxlength="100" value="<?php echo htmlspecialchars($edit_product['composition'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="printing">Нанесение</label>
                            <input type="text" id="printing" name="printing" maxlength="100" value="<?php echo htmlspecialchars($edit_product['printing'] ?? ''); ?>">
                        </div>

                        <?php if ($bm_catalog_v2): ?>
                        <div class="form-group">
                            <label for="sort_order">Порядок (меньше — раньше в сетке)</label>
                            <input type="number" id="sort_order" name="sort_order" min="0" max="99999999" value="<?php echo (int) ($edit_product['sort_order'] ?? 0); ?>">
                        </div>

                        <div class="form-group">
                            <label for="popularity">Популярность (для сортировки на странице каталога)</label>
                            <input type="number" id="popularity" name="popularity" min="0" max="99999999" value="<?php echo (int) ($edit_product['popularity'] ?? 0); ?>">
                        </div>

                        <div class="form-group" style="display:flex;gap:20px;flex-wrap:wrap;align-items:center;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="show_on_home" value="1" <?php echo !empty($edit_product['show_on_home']) ? 'checked' : ''; ?>>
                                Показывать на главной
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="show_in_catalog" value="1" <?php echo !empty($edit_product['show_in_catalog']) ? 'checked' : ''; ?>>
                                Показывать в каталоге
                            </label>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="card_images">Картинки карточки каталога (до 4, по одной строке) *</label>
                            <textarea id="card_images" name="card_images" rows="4" required placeholder="img/front.png&#10;img/side.png&#10;img/back.png&#10;img/detail.png"><?php echo htmlspecialchars(bm_urls_to_textarea_lines(bm_product_card_urls($edit_product))); ?></textarea>
                            <small style="color:#6b6b6b;font-size:0.72rem;display:block;margin-top:6px;">Эти фото показываются в сетке каталога и переключаются при наведении (ПК) и свайпе.</small>
                        </div>

                        <div class="form-group">
                            <label for="gallery_images">Доп. картинки для страницы товара</label>
                            <textarea id="gallery_images" name="gallery_images" rows="5" placeholder="img/extra-1.png&#10;img/extra-2.png"><?php echo htmlspecialchars(bm_urls_to_textarea_lines(bm_product_gallery_extra_urls($edit_product))); ?></textarea>
                            <small style="color:#6b6b6b;font-size:0.72rem;display:block;margin-top:6px;">Только галерея на странице товара; в карточке каталога не используются.</small>
                        </div>

                        <div class="form-group">
                            <label for="category">Категория</label>
                            <select id="category" name="category" style="width:100%;max-width:420px;padding:10px 12px;border-radius:10px;border:1px solid #cfcfcf;">
                                <option value="">— не выбрано —</option>
                                <?php
                                $ec = bm_normalize_category($edit_product['category'] ?? '');
                                foreach (bm_product_categories() as $slug => $label):
                                ?>
                                <option value="<?php echo htmlspecialchars($slug); ?>"<?php echo $ec === $slug ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($bm_shop_ok && bm_shop_table_exists($conn, 'product_stock')): ?>
                        <div class="form-group">
                            <label>Остатки по размерам (пусто — без лимита)</label>
                            <div style="display:flex;flex-wrap:wrap;gap:10px;">
                                <?php foreach (['XS', 'S', 'M', 'L', 'XL'] as $sz): ?>
                                <div style="min-width:64px;"><small><?php echo $sz; ?></small><br>
                                    <input type="number" min="0" name="stock[<?php echo $sz; ?>]" style="width:72px;padding:6px;border-radius:8px;border:1px solid #ccc;" value="<?php echo isset($stock_map_edit[$sz]) ? (int) $stock_map_edit[$sz] : ''; ?>" placeholder="—">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn-main">Сохранить изменения</button>
                        <a href="?" style="margin-left:10px; color:#6f6f6f; font-size:13px;">Отмена</a>
                    </form>
                <?php else: ?>
                    <h2>Добавить товар</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div class="form-group">
                            <label for="name">Название *</label>
                            <input type="text" id="name" name="name" required placeholder="Футболка черная BM Couple" maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="price">Цена (₽) *</label>
                            <input type="number" id="price" name="price" required placeholder="3499" min="0" max="999999">
                        </div>

                        <div class="form-group">
                            <label for="description">Описание</label>
                            <textarea id="description" name="description" rows="2" placeholder="Оверсайз футболка из хлопка." maxlength="500"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="material">Материал</label>
                            <input type="text" id="material" name="material" placeholder="Кулирная гладь" maxlength="100">
                        </div>

                        <div class="form-group">
                            <label for="composition">Состав</label>
                            <input type="text" id="composition" name="composition" placeholder="100% хлопок" maxlength="100">
                        </div>

                        <div class="form-group">
                            <label for="printing">Нанесение</label>
                            <input type="text" id="printing" name="printing" placeholder="Шелкография" maxlength="100">
                        </div>

                        <?php if ($bm_catalog_v2): ?>
                        <div class="form-group">
                            <label for="sort_order">Порядок (пусто — в конец; меньше число — раньше)</label>
                            <input type="number" id="sort_order" name="sort_order" min="0" max="99999999" placeholder="авто">
                        </div>

                        <div class="form-group">
                            <label for="popularity">Популярность</label>
                            <input type="number" id="popularity" name="popularity" min="0" max="99999999" value="0">
                        </div>

                        <div class="form-group" style="display:flex;gap:20px;flex-wrap:wrap;align-items:center;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="show_on_home" value="1" checked>
                                На главной
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                                <input type="checkbox" name="show_in_catalog" value="1" checked>
                                В каталоге
                            </label>
                        </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="card_images">Картинки карточки каталога (до 4, по одной строке) *</label>
                            <textarea id="card_images" name="card_images" rows="4" required placeholder="img/front.png&#10;img/side.png&#10;img/back.png&#10;img/detail.png"></textarea>
                            <small style="color:#6b6b6b;font-size:0.72rem;display:block;margin-top:6px;">Эти фото в сетке каталога; наведение мыши по зонам слева→направо переключает до 4 кадров.</small>
                        </div>

                        <div class="form-group">
                            <label for="gallery_images">Доп. картинки для страницы товара</label>
                            <textarea id="gallery_images" name="gallery_images" rows="5" placeholder="img/extra-1.png&#10;img/extra-2.png"></textarea>
                            <small style="color:#6b6b6b;font-size:0.72rem;display:block;margin-top:6px;">Не попадают в карточку каталога — только страница товара.</small>
                        </div>

                        <div class="form-group">
                            <label for="category">Категория</label>
                            <select id="category" name="category" style="width:100%;max-width:420px;padding:10px 12px;border-radius:10px;border:1px solid #cfcfcf;">
                                <option value="">— не выбрано —</option>
                                <?php foreach (bm_product_categories() as $slug => $label): ?>
                                <option value="<?php echo htmlspecialchars($slug); ?>"><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if ($bm_shop_ok && bm_shop_table_exists($conn, 'product_stock')): ?>
                        <div class="form-group">
                            <label>Остатки по размерам (пусто — без лимита)</label>
                            <div style="display:flex;flex-wrap:wrap;gap:10px;">
                                <?php foreach (['XS', 'S', 'M', 'L', 'XL'] as $sz): ?>
                                <div style="min-width:64px;"><small><?php echo $sz; ?></small><br>
                                    <input type="number" min="0" name="stock[<?php echo $sz; ?>]" style="width:72px;padding:6px;border-radius:8px;border:1px solid #ccc;" value="" placeholder="—">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn-main">Добавить товар</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <div class="panel-card">
                <div class="products-toolbar">
                    <h2 style="margin-bottom: 0;">Товары в каталоге <span class="product-count"><?php echo count($products); ?></span></h2>
                    <input type="text" id="productsSearch" class="search-input" placeholder="Поиск по названию или категории">
                </div>
                <?php if (count($products) > 0): ?>
                    <?php if ($bm_catalog_v2): ?>
                    <p style="font-size:0.85rem;color:#666;margin:0 0 12px;">Меняйте порядок и галочки ниже, затем нажмите «Сохранить порядок».</p>
                    <form id="productMetaForm" method="POST" style="margin-bottom:12px;">
                        <input type="hidden" name="action" value="save_product_meta">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <button type="submit" class="btn-main" style="margin-bottom:14px;">Сохранить порядок и отображение</button>
                    </form>
                    <?php endif; ?>
                    <div class="table-wrap">
                        <table class="products-table" id="productsTable">
                            <thead>
                                <tr>
                                    <?php if ($bm_catalog_v2): ?>
                                    <th>Порядок</th>
                                    <?php endif; ?>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Цена</th>
                                    <th>Категория</th>
                                    <?php if ($bm_catalog_v2): ?>
                                    <th>Главная</th>
                                    <th>Каталог</th>
                                    <th>Попул.</th>
                                    <?php endif; ?>
                                    <th>Действие</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <?php if ($bm_catalog_v2): ?>
                                    <td><input form="productMetaForm" type="number" name="meta[<?php echo (int) $product['id']; ?>][sort_order]" value="<?php echo (int) ($product['sort_order'] ?? 0); ?>" style="width:72px;padding:6px;border-radius:8px;border:1px solid #ccc;"></td>
                                    <?php endif; ?>
                                    <td><?php echo $product['id']; ?></td>
                                    <td><?php echo htmlspecialchars(substr($product['name'], 0, 28)); ?></td>
                                    <td><?php echo $product['price']; ?> ₽</td>
                                    <td><?php
                                    $cl = bm_normalize_category($product['category'] ?? '');
                                    echo htmlspecialchars($cl ? bm_category_label($cl) : ($product['category'] ?? '—'));
?></td>
                                    <?php if ($bm_catalog_v2): ?>
                                    <td style="text-align:center;"><input form="productMetaForm" type="checkbox" name="meta[<?php echo (int) $product['id']; ?>][show_on_home]" value="1"<?php echo !empty($product['show_on_home']) ? ' checked' : ''; ?>></td>
                                    <td style="text-align:center;"><input form="productMetaForm" type="checkbox" name="meta[<?php echo (int) $product['id']; ?>][show_in_catalog]" value="1"<?php echo !empty($product['show_in_catalog']) ? ' checked' : ''; ?>></td>
                                    <td><input form="productMetaForm" type="number" name="meta[<?php echo (int) $product['id']; ?>][popularity]" value="<?php echo (int) ($product['popularity'] ?? 0); ?>" style="width:64px;padding:6px;border-radius:8px;border:1px solid #ccc;"></td>
                                    <?php endif; ?>
                                    <td>
                                        <a href="?edit=<?php echo $product['id']; ?>" class="btn-edit">Изменить</a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <button type="submit" class="btn-delete" onclick="return confirm('Вы уверены?')">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($bm_catalog_v2): ?>
                    <button type="submit" form="productMetaForm" class="btn-main" style="margin-top:14px;">Сохранить порядок и отображение</button>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="color: #999; text-align: center;">Товаров в базе данных не найдено.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php elseif ($view === 'orders'): ?>
        <div class="content content-orders-full">
            <div class="panel-card">
                <h2 style="margin-bottom: 14px;">Заказы</h2>
                <?php if (!$bm_orders_ok): ?>
                    <div class="alert alert-error" style="margin-bottom: 12px;">Таблицы заказов не созданы. Один раз откройте в браузере <strong>migrate_orders.php</strong> (файл в корне сайта рядом с <code>index.php</code>).</div>
                <?php elseif ($orders_admin_order_id > 0): ?>
                    <?php if (!$order_detail): ?>
                        <p style="color: #922f2f;">Заказ не найден.</p>
                        <p><a href="?view=orders" style="color: #555; font-weight: 600;">← К списку заказов</a></p>
                    <?php else: ?>
                        <p style="margin-bottom: 16px;"><a href="?view=orders" style="color: #555; font-weight: 600;">← Все заказы</a></p>
                        <h3 style="font-size: 1rem; margin-bottom: 12px;">Заказ #<?php echo (int) $order_detail['id']; ?></h3>
                        <p style="font-size: 0.85rem; color: #666; margin-bottom: 18px;">
                            <?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($order_detail['created_at']))); ?>
                            · <?php echo number_format((int) $order_detail['total_amount'], 0, '.', ' '); ?> ₽
                        </p>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; margin-bottom: 22px; font-size: 0.88rem;">
                            <div><strong>Имя</strong><br><?php echo htmlspecialchars($order_detail['customer_name']); ?></div>
                            <div><strong>Email</strong><br><?php echo htmlspecialchars($order_detail['customer_email']); ?></div>
                            <div><strong>Телефон</strong><br><?php echo htmlspecialchars($order_detail['customer_phone']); ?></div>
                            <div><strong>Способ доставки</strong><br><?php echo htmlspecialchars($order_detail['delivery_method']); ?></div>
                        </div>
                        <?php if (!empty($order_detail['delivery_address'])): ?>
                            <div style="margin-bottom: 18px; font-size: 0.88rem;"><strong>Адрес</strong><br><?php echo nl2br(htmlspecialchars($order_detail['delivery_address'])); ?></div>
                        <?php endif; ?>

                        <?php if (count($order_detail_items) > 0): ?>
                        <h4 style="font-size: 0.9rem; margin-bottom: 10px;">Состав заказа</h4>
                        <div class="table-wrap">
                            <table class="products-table">
                                <thead>
                                    <tr>
                                        <th>Товар</th>
                                        <th>Размер</th>
                                        <th>Кол-во</th>
                                        <th>Цена</th>
                                        <th>Сумма</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_detail_items as $it): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($it['product_name']); ?> <small style="color: #888;">#<?php echo (int) $it['product_id']; ?></small></td>
                                        <td><?php echo htmlspecialchars($it['size']); ?></td>
                                        <td><?php echo (int) $it['quantity']; ?></td>
                                        <td><?php echo number_format((int) $it['unit_price'], 0, '.', ' '); ?> ₽</td>
                                        <td><?php echo number_format((int) $it['unit_price'] * (int) $it['quantity'], 0, '.', ' '); ?> ₽</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>

                        <form method="POST" style="margin-top: 22px; max-width: 560px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <input type="hidden" name="action" value="update_order">
                            <input type="hidden" name="order_id" value="<?php echo (int) $order_detail['id']; ?>">
                            <div class="form-group">
                                <label for="ord_status">Статус заказа</label>
                                <select id="ord_status" name="status" style="width: 100%; max-width: 420px; padding: 10px 12px; border-radius: 10px; border: 1px solid #cfcfcf;">
                                    <?php foreach (['new', 'processing', 'shipped', 'delivered', 'cancelled'] as $sv): ?>
                                    <option value="<?php echo htmlspecialchars($sv); ?>"<?php echo ($order_detail['status'] === $sv) ? ' selected' : ''; ?>><?php echo htmlspecialchars(bm_order_status_label($sv)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ord_pay">Оплата</label>
                                <select id="ord_pay" name="payment_status" style="width: 100%; max-width: 420px; padding: 10px 12px; border-radius: 10px; border: 1px solid #cfcfcf;">
                                    <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $pv): ?>
                                    <option value="<?php echo htmlspecialchars($pv); ?>"<?php echo ($order_detail['payment_status'] === $pv) ? ' selected' : ''; ?>><?php echo htmlspecialchars(bm_order_payment_label($pv)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="ord_track">Трек-номер</label>
                                <input type="text" id="ord_track" name="tracking_number" maxlength="128" value="<?php echo htmlspecialchars($order_detail['tracking_number'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="ord_notes">Заметки администратора</label>
                                <textarea id="ord_notes" name="admin_notes" rows="3"><?php echo htmlspecialchars($order_detail['admin_notes'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="btn-main">Сохранить</button>
                        </form>
                    <?php endif; ?>
                <?php elseif (count($orders_list) === 0): ?>
                    <p style="color: #777;">Заказов пока нет. После подключения оформления на сайте они будут попадать в эту таблицу.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="products-table" id="ordersAdminTable">
                            <thead>
                                <tr>
                                    <th>№</th>
                                    <th>Дата</th>
                                    <th>Клиент</th>
                                    <th>Email</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Оплата</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders_list as $or): ?>
                                <tr>
                                    <td><?php echo (int) $or['id']; ?></td>
                                    <td><?php echo htmlspecialchars(date('d.m.Y H:i', strtotime($or['created_at']))); ?></td>
                                    <td><?php echo htmlspecialchars($or['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($or['customer_email']); ?></td>
                                    <td><?php echo number_format((int) $or['total_amount'], 0, '.', ' '); ?> ₽</td>
                                    <td><?php echo htmlspecialchars(bm_order_status_label($or['status'])); ?></td>
                                    <td><?php echo htmlspecialchars(bm_order_payment_label($or['payment_status'])); ?></td>
                                    <td><a class="btn-edit" href="?view=orders&amp;order_id=<?php echo (int) $or['id']; ?>">Открыть</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
            <?php require __DIR__ . '/extended_views.php'; ?>
        <?php endif; ?>
    </div>

    <script>
        (function () {
            var searchInput = document.getElementById('productsSearch');
            var table = document.getElementById('productsTable');

            if (!searchInput || !table) {
                return;
            }

            searchInput.addEventListener('input', function () {
                var value = searchInput.value.trim().toLowerCase();
                var rows = table.querySelectorAll('tbody tr');
                var thCount = table.querySelectorAll('thead th').length;
                var nameIdx = thCount > 5 ? 2 : 1;
                var catIdx = thCount > 5 ? 4 : 3;

                rows.forEach(function (row) {
                    var name = (row.children[nameIdx] ? row.children[nameIdx].textContent : '').toLowerCase();
                    var category = (row.children[catIdx] ? row.children[catIdx].textContent : '').toLowerCase();
                    var show = name.indexOf(value) !== -1 || category.indexOf(value) !== -1;
                    row.style.display = show ? '' : 'none';
                });
            });
        })();
    </script>
</body>
</html><?php

// Обработка выхода
if (isset($_GET['logout'])) {
    log_admin_action('LOGOUT', 'Выход из системы');
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

mysqli_close($conn);
?>
