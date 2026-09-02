<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config.php';

$bm_css_ver = is_file(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : time();
$bm_js_ver = is_file(__DIR__ . '/script.js') ? filemtime(__DIR__ . '/script.js') : time();

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$user_id     = $_SESSION['user_id'];
$success_msg = '';
$error_msg   = '';

// Получаем актуальные данные пользователя из БД
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/shop_helpers.php';
require_once __DIR__ . '/adm_cp3k9j2x5f/orders_helpers.php';
$my_orders_js = [];
if ($conn && bm_orders_tables_exist($conn)) {
    $uid = (int) $user_id;
    $oq = mysqli_query($conn, 'SELECT id, created_at, total_amount, status, payment_status FROM orders WHERE user_id=' . $uid . ' ORDER BY id DESC LIMIT 50');
    while ($oq && ($r = mysqli_fetch_assoc($oq))) {
        $my_orders_js[] = $r;
    }
}

// Обработка POST форм
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Обновление профиля
    if ($action === 'update_profile') {
        $first_name = trim(strip_tags($_POST['first_name'] ?? ''));
        $last_name  = trim(strip_tags($_POST['last_name'] ?? ''));
        $phone      = trim(strip_tags($_POST['phone'] ?? ''));

        if (empty($first_name) || empty($last_name)) {
            $error_msg = 'Заполните имя и фамилию';
        } else {
            $stmt2 = mysqli_prepare($conn, "UPDATE users SET first_name=?, last_name=?, phone=? WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "sssi", $first_name, $last_name, $phone, $user_id);
            if (mysqli_stmt_execute($stmt2)) {
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name']  = $last_name;
                $user['first_name'] = $first_name;
                $user['last_name']  = $last_name;
                $user['phone']      = $phone;
                $success_msg = 'Профиль успешно обновлён!';
            } else {
                $error_msg = 'Ошибка при сохранении данных';
            }
        }
    }

    // Смена пароля
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $error_msg = 'Неверный текущий пароль';
        } elseif (strlen($new) < 8) {
            $error_msg = 'Новый пароль должен содержать минимум 8 символов';
        } elseif ($new !== $confirm) {
            $error_msg = 'Пароли не совпадают';
        } else {
            $hashed = password_hash($new, PASSWORD_BCRYPT);
            $stmt3 = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($stmt3, "si", $hashed, $user_id);
            if (mysqli_stmt_execute($stmt3)) {
                $success_msg = 'Пароль успешно изменён!';
            } else {
                $error_msg = 'Ошибка при смене пароля';
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
    <title>Личный кабинет — BASEMOOD</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">
    <link rel="stylesheet" href="style.css?v=<?= (int) $bm_css_ver ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --accent: #0f0f0f; --accent2: #333; --border: #e8e8e8; --bg: #f5f5f5; --card: #fff; --text: #0f0f0f; --muted: #888; }
        * { box-sizing: border-box; }
        body { background: var(--bg); font-family: 'Montserrat', sans-serif; }

        /* PAGE */
        .lk-page { padding: 110px 0 80px; min-height: 100vh; }
        .lk-wrap { max-width: 1440px; margin: 0 auto; padding: 0 32px; }

        /* LAYOUT */
        .lk-layout { align-items: start; }
        .lk-layout.row { --bs-gutter-x: 24px; --bs-gutter-y: 24px; align-items: flex-start; }

        /* BREADCRUMBS */
        .lk-breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 18px;
            font-size: 13px;
            color: var(--muted);
            flex-wrap: wrap;
        }
        .lk-breadcrumb-link {
            border: none;
            background: none;
            padding: 0;
            margin: 0;
            font: inherit;
            color: inherit;
            cursor: pointer;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: color .18s, border-color .18s;
            outline: none;
            box-shadow: none;
        }
        .lk-breadcrumb-link:hover {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }
        .lk-breadcrumb-link:focus,
        .lk-breadcrumb-link:focus-visible,
        .lk-breadcrumb-link:active {
            outline: none;
            box-shadow: none;
        }
        .lk-breadcrumb-sep {
            color: #b6b6b6;
            user-select: none;
        }
        .lk-breadcrumb-current {
            color: var(--accent);
            font-weight: 700;
            border-bottom-color: transparent;
            cursor: default;
        }

        /* SIDEBAR */
        .lk-sidebar {
            background: var(--card);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(0,0,0,.06);
            position: sticky; top: 100px;
        }
        .lk-nav { list-style: none; padding: 12px 0; margin: 0; }
        .lk-nav li a {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 24px;
            color: var(--text); text-decoration: none;
            font-size: 14px; font-weight: 500;
            transition: background .18s, color .18s;
            border-left: 2px solid transparent;
            outline: none;
            box-shadow: none;
            -webkit-tap-highlight-color: transparent;
        }
        .lk-nav li a i { width: 18px; text-align: center; font-size: 15px; color: var(--muted); transition: color .18s; }
        .lk-nav li a:hover { background: var(--bg); }
        .lk-nav li a.active { background: var(--bg); border-left-color: var(--accent); font-weight: 700; color: var(--accent); }
        .lk-nav li a.active i { color: var(--accent); }
        .lk-nav li a:focus,
        .lk-nav li a:focus-visible,
        .lk-nav li a:active {
            outline: none;
            box-shadow: none;
        }
        .lk-nav-divider { border: none; border-top: 1px solid var(--border); margin: 8px 0; }
        .lk-nav li a.lk-logout { color: #c0392b; }
        .lk-nav li a.lk-logout i { color: #c0392b; }
        .lk-nav li a.lk-logout:hover { background: #fdf0f0; }

        /* MAIN */
        .lk-main { background: transparent !important; }
        .lk-section { display: none; }
        .lk-section.active { display: block; animation: fadeUp .3s ease; }
        @keyframes fadeUp { from { opacity:0; transform:translateY(12px); } to { opacity:1; transform:translateY(0); } }

        /* CARD */
        .lk-card {
            background: var(--card);
            border-radius: 20px;
            padding: 32px;
            margin-bottom: 20px;
            box-shadow: 0 2px 16px rgba(0,0,0,.06);
        }
        .lk-card-title {
            font-size: 17px; font-weight: 700;
            margin: 0 0 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 10px;
        }
        .lk-card-title i { color: var(--muted); font-size: 15px; }

        /* PROFILE INFO */
        .lk-profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .lk-profile-grid.row { display: flex; --bs-gutter-x: 20px; --bs-gutter-y: 20px; }
        .lk-info-item {}
        .lk-info-label { font-size: 12px; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
        .lk-info-value { font-size: 16px; font-weight: 600; }

        /* FORM */
        .lk-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .lk-form-group { display: flex; flex-direction: column; gap: 7px; }
        .lk-form-group.full { grid-column: span 2; }
        .lk-label { font-size: 13px; font-weight: 600; color: var(--accent2); }
        .lk-input {
            padding: 12px 16px;
            border: 1px solid #d5d5d5;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Montserrat', sans-serif;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            box-shadow: none;
            transition: border-color .2s;
            background: #fafafa;
            width: 100%;
        }
        .lk-input:focus,
        .lk-input:focus-visible,
        .lk-input:active {
            border-color: var(--accent);
            background: #fff;
            outline: none;
            box-shadow: none;
        }
        .lk-input[readonly] { background: #f0f0f0; color: var(--muted); cursor: not-allowed; }
        .lk-btn {
            padding: 13px 28px;
            background: var(--accent);
            color: #fff;
            border: none; border-radius: 12px;
            font-size: 14px; font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: background .2s, transform .15s;
        }
        .lk-btn:hover { background: var(--accent2); transform: translateY(-1px); }
        .lk-btn-outline {
            padding: 13px 28px;
            background: transparent;
            color: var(--accent); border: 1px solid var(--accent);
            border-radius: 12px;
            font-size: 14px; font-weight: 700;
            font-family: 'Montserrat', sans-serif;
            cursor: pointer;
            transition: all .2s;
        }
        .lk-btn-outline:hover { background: var(--accent); color: #fff; }

        /* SECTION SEPARATOR */
        .lk-section-sep { font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin: 28px 0 18px; display: flex; align-items: center; gap: 10px; }
        .lk-section-sep::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        /* SOCIAL */
        .lk-social-list { display: flex; flex-direction: column; gap: 12px; }
        .lk-social-item { display: flex; align-items: center; justify-content: flex-start; gap: 18px; padding: 14px 18px; border: 1px solid #dcdcdc; border-radius: 12px; }
        .lk-social-item-left { display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; min-width: 180px; }
        .lk-social-item-left i { font-size: 18px; }
        .lk-tag-btn { font-size: 12px; font-weight: 700; color: var(--muted); border: 1px solid var(--border); background: none; border-radius: 8px; padding: 6px 14px; cursor: pointer; font-family: 'Montserrat', sans-serif; transition: all .2s; }
        .lk-tag-btn:hover { border-color: var(--accent); color: var(--accent); }
        .fa-vk { color: #4C75A3; }
        .lk-yandex-icon { font-weight: 900; font-size: 16px; color: #FC3F1D; font-family: serif; line-height: 1; }

        /* ORDERS */
        .lk-filters { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
        .lk-filter {
            position: relative;
            min-width: 220px;
        }
        .lk-filter-input {
            display: none;
        }
        .lk-filter-trigger {
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
            box-shadow: none;
            transition: border-color .2s, box-shadow .2s, transform .18s;
        }
        .lk-filter-trigger:hover {
            border-color: #8d8d8d;
        }
        .lk-filter.open .lk-filter-trigger {
            border-color: var(--accent);
            box-shadow: 0 6px 18px rgba(15,15,15,.06);
        }
        .lk-filter-trigger:focus,
        .lk-filter-trigger:focus-visible {
            outline: none;
            box-shadow: none;
        }
        .lk-filter.open .lk-filter-trigger:focus,
        .lk-filter.open .lk-filter-trigger:focus-visible {
            box-shadow: 0 6px 18px rgba(15,15,15,.06);
        }
        .lk-filter-trigger i {
            color: var(--muted);
            transition: transform .24s ease, color .2s ease;
            flex-shrink: 0;
        }
        .lk-filter.open .lk-filter-trigger i {
            transform: rotate(180deg);
            color: var(--accent);
        }
        .lk-filter-menu {
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
            z-index: 25;
        }
        .lk-filter.open .lk-filter-menu {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
        .lk-filter-option {
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
            transition: background .18s ease, color .18s ease;
        }
        .lk-filter-option:hover {
            background: #f4f4f4;
        }
        .lk-filter-option.active {
            background: #ececec;
            color: var(--accent);
            font-weight: 600;
        }
        .lk-orders-list { display: flex; flex-direction: column; gap: 16px; }
        .lk-order-card { border: 1px solid #dcdcdc; border-radius: 14px; overflow: hidden; transition: border-color .2s; }
        .lk-order-card:hover { border-color: #aaa; }
        .lk-order-head { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: var(--bg); border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 10px; }
        .lk-order-id { font-weight: 700; font-size: 14px; }
        .lk-order-date { font-size: 13px; color: var(--muted); }
        .lk-order-body { padding: 18px 20px; display: flex; align-items: center; gap: 20px; flex-wrap: wrap; justify-content: space-between; }
        .lk-order-items-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .lk-order-thumb { width: 56px; height: 56px; background: var(--card); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--muted); font-size: 20px; }
        .lk-order-total { font-size: 16px; font-weight: 700; }
        .lk-order-foot { padding: 12px 20px; border-top: 1px solid var(--border); display: flex; gap: 10px; justify-content: flex-end; }
        .lk-status { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .lk-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .s-pending { background:#fff8e1; color:#f39c12; }
        .s-processing { background:#e3f2fd; color:#1565c0; }
        .s-assembly { background:#e8f5e9; color:#2e7d32; }
        .s-delivery { background:#ede7f6; color:#6a1b9a; }
        .s-waiting { background:#fce4ec; color:#c62828; }
        .s-delivered { background:#e0f2f1; color:#00695c; }
        .s-cancelled { background:#fbe9e7; color:#bf360c; }
        .s-payment { background:#fff3e0; color:#e65100; }
        .lk-pay-badge { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 6px; }
        .paid { background: #e8f5e9; color: #2e7d32; }
        .unpaid { background: #fbe9e7; color: #bf360c; }

        /* NODATA */
        .lk-nodata { text-align: center; padding: 60px 20px; color: var(--muted); }
        .lk-nodata i { font-size: 48px; margin-bottom: 16px; display: block; opacity: .3; }
        .lk-nodata h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; color: var(--text); }
        .lk-nodata p { font-size: 14px; }

        /* CART */
        .lk-cart-list { display: flex; flex-direction: column; gap: 14px; }
        .lk-cart-item {
            display: grid;
            grid-template-columns: 88px 1fr auto auto;
            gap: 14px;
            align-items: center;
            border: 1px solid #dcdcdc;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }
        .lk-cart-img {
            width: 88px;
            height: 88px;
            border-radius: 10px;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .lk-cart-img img { width: 100%; height: 100%; object-fit: contain; }
        .lk-cart-info { min-width: 0; }
        .lk-cart-name { font-size: 14px; font-weight: 700; margin-bottom: 6px; }
        .lk-cart-meta { font-size: 12px; color: var(--muted); }
        .lk-cart-qty {
            display: inline-flex;
            align-items: center;
            border: 1px solid #d5d5d5;
            border-radius: 8px;
            overflow: hidden;
        }
        .lk-cart-qty button {
            width: 30px;
            height: 30px;
            border: none;
            background: #f6f6f6;
            cursor: pointer;
            font-weight: 700;
        }
        .lk-cart-qty span {
            min-width: 34px;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }
        .lk-cart-price { font-size: 15px; font-weight: 700; white-space: nowrap; }
        .lk-cart-remove {
            border: 1px solid #d5d5d5;
            background: transparent;
            border-radius: 8px;
            padding: 8px 10px;
            cursor: pointer;
        }
        .lk-cart-remove:hover { border-color: #bf360c; color: #bf360c; }
        .lk-cart-summary {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #e3e3e3;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .lk-cart-total { font-size: 18px; font-weight: 800; }

        /* WISHLIST */
        .lk-wishlist-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap: 18px; }
        .lk-witem { border: 1px solid #dcdcdc; border-radius: 14px; overflow: hidden; transition: border-color .2s, transform .2s; }
        .lk-witem:hover { border-color: var(--accent); transform: translateY(-4px); }
        .lk-witem-img { height: 200px; background: transparent; display: flex; align-items: center; justify-content: center; }
        .lk-witem-img img { max-height: 100%; object-fit: contain; }
        .lk-witem-info { padding: 14px; }
        .lk-witem-name { font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .lk-witem-price { font-size: 15px; font-weight: 700; margin-bottom: 12px; }
        .lk-witem-actions { display: flex; gap: 8px; }
        .lk-witem-btn { flex:1; padding: 8px; border: 1px solid #d5d5d5; border-radius: 8px; background: none; font-size: 12px; font-weight: 600; font-family:'Montserrat',sans-serif; cursor: pointer; transition: all .2s; }
        .lk-witem-btn:hover { border-color: var(--accent); }
        .lk-witem-btn:focus,
        .lk-witem-btn:focus-visible,
        .lk-witem-btn:active {
            outline: none;
            box-shadow: none;
            border-color: #bcbcbc;
        }
        .lk-witem-btn.is-added {
            animation: lkAddToCartPulse .6s ease;
        }

        @keyframes lkAddToCartPulse {
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

        /* HELP */
        .lk-help-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .lk-help-grid.row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); --bs-gutter-x: 0; --bs-gutter-y: 0; }
        .lk-help-grid.row > .lk-help-item {
            width: 100%;
            max-width: none;
            flex: initial;
        }
        .lk-help-item { border: 1px solid #dcdcdc; border-radius: 14px; padding: 24px; transition: border-color .2s, transform .2s; cursor: pointer; }
        .lk-help-item:hover { border-color: var(--accent); transform: translateY(-3px); }
        .lk-help-item i { font-size: 28px; color: var(--muted); margin-bottom: 12px; display: block; }
        .lk-help-item h3 { font-size: 15px; font-weight: 700; margin-bottom: 6px; }
        .lk-help-item p { font-size: 13px; color: var(--muted); line-height: 1.5; }
        .lk-help-item.active {
            border-color: var(--accent);
            background: #f6f6f6;
            box-shadow: 0 10px 24px rgba(15,15,15,.08);
        }
        .lk-help-item.active i,
        .lk-help-item.active h3 {
            color: var(--accent);
        }
        .lk-help-selected {
            margin-top: 16px;
            padding: 12px 14px;
            border: 1px dashed #cfcfcf;
            border-radius: 10px;
            font-size: 13px;
            color: var(--muted);
            background: #fbfbfb;
            transition: all .2s ease;
        }
        .lk-help-selected.active {
            border-style: solid;
            border-color: #bcbcbc;
            color: var(--accent);
            background: #f3f3f3;
        }
        .lk-faq { margin-top: 8px; opacity: 0; transform: translateY(10px); }
        .lk-faq.visible {
            animation: helpFaqReveal .32s ease forwards;
        }
        @keyframes helpFaqReveal {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .lk-faq-item { border-bottom: 1px solid var(--border); }
        .lk-faq-q { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; cursor: pointer; font-weight: 600; font-size: 14px; }
        .lk-faq-q i { transition: transform .25s; color: var(--muted); }
        .lk-faq-item.open .lk-faq-q i { transform: rotate(180deg); }
        .lk-faq-a { font-size: 13px; color: var(--muted); line-height: 1.7; max-height: 0; overflow: hidden; transition: max-height .3s ease, padding .3s; }
        .lk-faq-item.open .lk-faq-a { max-height: 300px; padding-bottom: 14px; }

        /* ALERT */
        .lk-alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; }
        .lk-alert-ok { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .lk-alert-err { background:#fbe9e7; color:#bf360c; border:1px solid #ffccbc; }

        /* RESPONSIVE */
        @media(max-width:1024px) {
            .lk-layout.row { --bs-gutter-x: 18px; }
            .lk-profile-grid, .lk-form-grid { grid-template-columns: 1fr; }
            .lk-form-group.full { grid-column: span 1; }
            .lk-sidebar { position: static; }
            .lk-card-title { font-weight: 400; }
            .lk-cart-name { font-weight: 400; }
            .lk-cart-price { font-weight: 400; }
            .lk-cart-total { font-weight: 400; }
            .lk-witem-name { font-weight: 400; }
            .lk-witem-price { font-weight: 400; }
        }
        @media(max-width:768px) {
            .lk-layout.row { --bs-gutter-x: 0; --bs-gutter-y: 14px; }
            .lk-sidebar { position: static; }
            .lk-card {
                background: transparent;
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }
            .lk-card-title {
                padding-bottom: 12px;
                margin-bottom: 14px;
            }
            .lk-nav {
                display: flex;
                overflow-x: auto;
                padding: 8px 12px;
                gap: 8px;
                scrollbar-width: none;
            }
            .lk-nav::-webkit-scrollbar { display: none; }
            .lk-nav li a {
                padding: 10px 14px;
                white-space: nowrap;
                border: 1px solid #dddddd;
                border-left: 1px solid #dddddd;
                border-bottom: 1px solid #dddddd;
                border-radius: 999px;
                background: #ffffff;
                color: #545454;
                font-weight: 600;
            }
            .lk-nav li a i { color: #7a7a7a; }
            .lk-nav li a.active {
                border: 1px solid var(--accent);
                border-left: 1px solid var(--accent);
                border-bottom: 1px solid var(--accent);
                background: var(--accent);
                color: #ffffff;
                font-weight: 700;
            }
            .lk-nav li a.active i { color: #ffffff; }
            .lk-nav li a:hover { background: #f0f0f0; }
            .lk-nav li a.active:hover { background: var(--accent); }
            .lk-nav-divider { display: none; }
            .lk-help-grid { grid-template-columns: 1fr; }
            .lk-help-grid.row {
                display: grid;
                grid-template-columns: 1fr;
                --bs-gutter-x: 0;
                --bs-gutter-y: 0;
            }
            .lk-help-item {
                padding: 16px 14px;
                border-radius: 12px;
            }
            .lk-help-item i {
                font-size: 22px;
                margin-bottom: 8px;
            }
            .lk-help-item h3 {
                font-size: 14px;
                margin-bottom: 4px;
            }
            .lk-help-item p {
                font-size: 12px;
                line-height: 1.35;
            }
            .lk-social-item { justify-content: space-between; gap: 12px; }
            .lk-social-item-left { min-width: 0; }
            .lk-cart-item { grid-template-columns: 1fr; }
            .lk-cart-img { width: 100%; height: 180px; }
            .lk-cart-name { font-weight: 400; }
            .lk-cart-price { font-weight: 400; }
            .lk-cart-total { font-weight: 400; font-size: 15px; }
            .lk-witem-name { font-weight: 400; }
            .lk-witem-price { font-weight: 400; }
            .lk-filters {
                flex-direction: column;
                flex-wrap: nowrap;
                gap: 10px;
            }
            .lk-filter {
                min-width: 0;
                width: 100%;
                flex: none;
            }
            .lk-filter-trigger {
                min-height: 44px;
                height: auto;
                padding: 10px 12px;
                font-size: 12px;
                gap: 6px;
                border-radius: 10px;
            }
            .lk-filter-trigger span {
                white-space: normal;
                overflow: visible;
                text-overflow: unset;
            }
            .lk-filter-menu {
                left: 0;
                right: 0;
                width: 100%;
                min-width: 0;
                max-width: 100%;
                box-sizing: border-box;
            }
            .lk-filter-option { padding: 10px 12px; font-size: 13px; }

            /* Убрать черный блок снизу на мобильных */
            .footer-svg-container {
                display: none;
            }
            .footer {
                padding-bottom: 16px;
            }
        }
    </style>
    <?php include __DIR__ . '/yandex-metrika.php'; ?>
</head>
<body>
    <div class="loading-spinner" id="loadingSpinner"><div class="spinner"></div></div>

    <!-- Шапка -->
    <nav class="nav-container">
        <div class="nav-inner">
            <div class="logo">
                <a href="index.php" class="logo-link" aria-label="На главную страницу BASEMOOD">
                    <img src="img/logo.svg" alt="BASEMOOD" class="logo-img">
                </a>
            </div>
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Открыть меню"><span></span><span></span><span></span></button>
            <ul class="nav-menu" id="navMenu">
                <?php include __DIR__ . '/partials/nav_menu_inner.php'; ?>
            </ul>
            <div class="nav-actions">
                <button class="nav-icon" id="searchButton" aria-label="Поиск"><i class="fas fa-search"></i></button>
                <button class="nav-icon" id="userButton" aria-label="Личный кабинет"><i class="fas fa-user"></i></button>
                <button class="nav-icon" id="wishlistButton" aria-label="Избранное"><i class="far fa-heart"></i></button>
                <button class="nav-icon cart-icon" id="cartButton" aria-label="Корзина"><i class="fas fa-basket-shopping"></i><span class="cart-count">0</span></button>
            </div>
        </div>
        <div class="search-container" id="searchContainer">
            <input type="text" class="search-input" placeholder="Поиск товаров...">
            <button class="search-close" id="searchClose"><i class="fas fa-times"></i></button>
        </div>
    </nav>

    <main class="lk-page">
        <div class="lk-wrap">
            <nav class="lk-breadcrumb" aria-label="Хлебные крошки">
                <a href="index.php" class="lk-breadcrumb-link">Главная</a>
                <span class="lk-breadcrumb-sep">/</span>
                <button type="button" class="lk-breadcrumb-link" id="lkBreadcrumbCabinet">Личный кабинет</button>
                <span class="lk-breadcrumb-sep">/</span>
                <button type="button" class="lk-breadcrumb-link lk-breadcrumb-current" id="lkBreadcrumbCurrent">Профиль</button>
            </nav>
            <div class="lk-layout row">
                <!-- Боковое меню -->
                <aside class="lk-sidebar col-12 col-lg-4 col-xl-3">
                    <ul class="lk-nav">
                        <li><a href="#" class="active" data-section="profile"><i class="fas fa-user"></i> Профиль</a></li>
                        <li><a href="#" data-section="personal"><i class="fas fa-id-card"></i> Личные данные</a></li>
                        <li><a href="#" data-section="orders"><i class="fas fa-box"></i> Заказы</a></li>
                        <li><a href="#" data-section="cart"><i class="fas fa-basket-shopping"></i> Корзина</a></li>
                        <li><a href="#" data-section="wishlist"><i class="fas fa-heart"></i> Избранное</a></li>
                        <hr class="lk-nav-divider">
                        <li><a href="#" data-section="help"><i class="fas fa-life-ring"></i> Помощь</a></li>
                        <hr class="lk-nav-divider">
                        <li><a href="?logout=1" class="lk-logout" onclick="return confirm('Выйти из аккаунта?')"><i class="fas fa-sign-out-alt"></i> Выйти</a></li>
                    </ul>
                </aside>

                <!-- Основной контент -->
                <div class="col-12 col-lg-8 col-xl-9">

                    <!-- ПРОФИЛЬ -->
                    <div id="section-profile" class="lk-section active">
                        <div class="lk-card">
                            <div class="lk-card-title"><i class="fas fa-user"></i> Мой профиль</div>
                            <div class="lk-profile-grid row">
                                <div class="lk-info-item col-12 col-md-6">
                                    <div class="lk-info-label">Имя</div>
                                    <div class="lk-info-value"><?php echo htmlspecialchars($user['first_name']); ?></div>
                                </div>
                                <div class="lk-info-item col-12 col-md-6">
                                    <div class="lk-info-label">Фамилия</div>
                                    <div class="lk-info-value"><?php echo htmlspecialchars($user['last_name']); ?></div>
                                </div>
                                <div class="lk-info-item col-12 col-md-6">
                                    <div class="lk-info-label">Email</div>
                                    <div class="lk-info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                                </div>
                                <div class="lk-info-item col-12 col-md-6">
                                    <div class="lk-info-label">Телефон</div>
                                    <div class="lk-info-value"><?php echo htmlspecialchars($user['phone'] ?: 'Не указан'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ЛИЧНЫЕ ДАННЫЕ -->
                    <div id="section-personal" class="lk-section">
                        <!-- Контактные данные -->
                        <div class="lk-card">
                            <div class="lk-card-title"><i class="fas fa-id-card"></i> Контактные данные</div>
                            <?php if ($success_msg && ($_POST['action']??'')==='update_profile'): ?>
                                <div class="lk-alert lk-alert-ok">✅ <?php echo htmlspecialchars($success_msg); ?></div>
                            <?php elseif ($error_msg && ($_POST['action']??'')==='update_profile'): ?>
                                <div class="lk-alert lk-alert-err">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="lk-form-grid">
                                    <div class="lk-form-group">
                                        <label class="lk-label" for="fn">Имя</label>
                                        <input type="text" id="fn" name="first_name" class="lk-input" required value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                    </div>
                                    <div class="lk-form-group">
                                        <label class="lk-label" for="ln">Фамилия</label>
                                        <input type="text" id="ln" name="last_name" class="lk-input" required value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                    </div>
                                    <div class="lk-form-group">
                                        <label class="lk-label" for="em">Email</label>
                                        <input type="email" id="em" class="lk-input" readonly value="<?php echo htmlspecialchars($user['email']); ?>">
                                    </div>
                                    <div class="lk-form-group">
                                        <label class="lk-label" for="ph">Телефон</label>
                                        <input type="tel" id="ph" name="phone" class="lk-input" value="<?php echo htmlspecialchars($user['phone']??''); ?>">
                                    </div>
                                    <div class="lk-form-group full">
                                        <button type="submit" class="lk-btn" style="max-width:220px;">Сохранить изменения</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Изменить пароль -->
                        <div class="lk-card">
                            <div class="lk-card-title"><i class="fas fa-lock"></i> Изменить пароль</div>
                            <?php if ($success_msg && ($_POST['action']??'')==='change_password'): ?>
                                <div class="lk-alert lk-alert-ok">✅ <?php echo htmlspecialchars($success_msg); ?></div>
                            <?php elseif ($error_msg && ($_POST['action']??'')==='change_password'): ?>
                                <div class="lk-alert lk-alert-err">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                <div class="lk-form-grid">
                                    <div class="lk-form-group">
                                        <label class="lk-label">Текущий пароль</label>
                                        <input type="password" name="current_password" class="lk-input" required>
                                    </div>
                                    <div class="lk-form-group"></div>
                                    <div class="lk-form-group">
                                        <label class="lk-label">Новый пароль</label>
                                        <input type="password" name="new_password" class="lk-input" required>
                                    </div>
                                    <div class="lk-form-group">
                                        <label class="lk-label">Подтверждение</label>
                                        <input type="password" name="confirm_password" class="lk-input" required>
                                    </div>
                                    <div class="lk-form-group full">
                                        <button type="submit" class="lk-btn" style="max-width:220px;">Сменить пароль</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Социальные сети -->
                        <div class="lk-card">
                            <div class="lk-card-title"><i class="fas fa-link"></i> Социальные сети</div>
                            <div class="lk-social-list">
                                <div class="lk-social-item">
                                    <div class="lk-social-item-left"><i class="fab fa-vk fa-vk"></i> ВКонтакте</div>
                                    <button class="lk-tag-btn">Привязать</button>
                                </div>
                                <div class="lk-social-item">
                                    <div class="lk-social-item-left"><span class="lk-yandex-icon">Я</span> Яндекс</div>
                                    <button class="lk-tag-btn">Привязать</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ЗАКАЗЫ -->
                    <div id="section-orders" class="lk-section">
                        <div class="lk-card">
                            <div class="lk-card-title"><i class="fas fa-box"></i> Мои заказы</div>
                            <div class="lk-filters">
                                <div class="lk-filter" data-filter="filterStatus">
                                    <input type="hidden" class="lk-filter-input" id="filterStatus" value="">
                                    <button type="button" class="lk-filter-trigger">
                                        <span>Любой статус</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="lk-filter-menu">
                                        <button type="button" class="lk-filter-option active" data-value="">Любой статус</button>
                                        <button type="button" class="lk-filter-option" data-value="payment">Ожидаем оплату</button>
                                        <button type="button" class="lk-filter-option" data-value="processing">Обрабатываем</button>
                                        <button type="button" class="lk-filter-option" data-value="assembly">В сборке</button>
                                        <button type="button" class="lk-filter-option" data-value="delivery">В службе доставки</button>
                                        <button type="button" class="lk-filter-option" data-value="waiting">Ожидает получения</button>
                                        <button type="button" class="lk-filter-option" data-value="delivered">Получен</button>
                                        <button type="button" class="lk-filter-option" data-value="cancelled">Отменён</button>
                                    </div>
                                </div>
                                <div class="lk-filter" data-filter="filterPayment">
                                    <input type="hidden" class="lk-filter-input" id="filterPayment" value="">
                                    <button type="button" class="lk-filter-trigger">
                                        <span>Любой статус оплаты</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="lk-filter-menu">
                                        <button type="button" class="lk-filter-option active" data-value="">Любой статус оплаты</button>
                                        <button type="button" class="lk-filter-option" data-value="paid">Оплачен</button>
                                        <button type="button" class="lk-filter-option" data-value="unpaid">Не оплачен</button>
                                    </div>
                                </div>
                                <div class="lk-filter" data-filter="filterTime">
                                    <input type="hidden" class="lk-filter-input" id="filterTime" value="">
                                    <button type="button" class="lk-filter-trigger">
                                        <span>За всё время</span>
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    <div class="lk-filter-menu">
                                        <button type="button" class="lk-filter-option active" data-value="">За всё время</button>
                                        <button type="button" class="lk-filter-option" data-value="week">За неделю</button>
                                        <button type="button" class="lk-filter-option" data-value="month">За месяц</button>
                                        <button type="button" class="lk-filter-option" data-value="3month">За 3 месяца</button>
                                        <button type="button" class="lk-filter-option" data-value="year">За год</button>
                                    </div>
                                </div>
                            </div>
                            <div class="lk-orders-list" id="ordersList"></div>
                            <div class="lk-nodata" id="noOrders">
                                <i class="fas fa-box-open"></i>
                                <h3>Заказов пока нет</h3>
                                <p>Здесь будут отображаться только реальные заказы после оформления покупки</p>
                                <a href="catalog.php" class="lk-btn" style="display:inline-block; margin-top:20px; text-decoration:none;">В каталог</a>
                            </div>
                        </div>
                    </div>

                    <!-- КОРЗИНА -->
                    <div id="section-cart" class="lk-section">
                        <div class="lk-card">
                            <div class="lk-card-title"><i class="fas fa-basket-shopping"></i> Моя корзина</div>
                            <div class="lk-cart-list" id="cartList"></div>
                            <div class="lk-cart-summary" id="cartSummary" style="display:none;">
                                <div class="lk-cart-total" id="cartTotal">Итого: 0 ₽</div>
                                <a href="checkout.php" class="lk-btn" style="text-decoration:none;">Оформить заказ</a>
                            </div>
                            <div class="lk-nodata" id="noCart">
                                <i class="fas fa-basket-shopping"></i>
                                <h3>Корзина пуста</h3>
                                <p>Добавляйте товары, чтобы оформить заказ</p>
                                <a href="catalog.php" class="lk-btn" style="display:inline-block; margin-top:20px; text-decoration:none;">В каталог</a>
                            </div>
                        </div>
                    </div>

                    <!-- ИЗБРАННОЕ -->
                    <div id="section-wishlist" class="lk-section">
                        <div class="lk-card">
                            <div class="lk-card-title"><i class="fas fa-heart"></i> Избранные товары</div>
                            <div class="lk-wishlist-grid" id="wishlistGrid"></div>
                            <div class="lk-nodata" id="noWishlist">
                                <i class="fas fa-heart"></i>
                                <h3>В избранном пока ничего нет</h3>
                                <p>Добавляйте товары, чтобы не потерять их</p>
                            </div>
                        </div>
                    </div>

                    <!-- ПОМОЩЬ -->
                    <div id="section-help" class="lk-section">
                        <div class="lk-card">
                            <div class="lk-card-title"><i class="fas fa-life-ring"></i> Центр помощи</div>
                            <div class="lk-help-grid row">
                                <div class="lk-help-item col-12 col-md-6" data-topic="delivery"><i class="fas fa-truck"></i><h3>Вопросы о доставке</h3><p>Сроки, способы и стоимость доставки по России</p></div>
                                <div class="lk-help-item col-12 col-md-6" data-topic="payment"><i class="fas fa-credit-card"></i><h3>Вопросы по оплате</h3><p>Способы оплаты, безопасность платежей</p></div>
                                <div class="lk-help-item col-12 col-md-6" data-topic="return"><i class="fas fa-undo"></i><h3>Вопросы по возврату</h3><p>Условия и сроки возврата и обмена товара</p></div>
                                <div class="lk-help-item col-12 col-md-6" data-topic="size"><i class="fas fa-ruler"></i><h3>Размеры и посадка</h3><p>Таблица размеров, как выбрать правильный размер</p></div>
                                <div class="lk-help-item col-12 col-md-6" data-topic="care"><i class="fas fa-tshirt"></i><h3>Уход за изделием</h3><p>Рекомендации по стирке и хранению</p></div>
                                <div class="lk-help-item col-12 col-md-6" data-topic="contact"><i class="fas fa-headset"></i><h3>Связаться с нами</h3><p>Телефон: +7 914 227 02 20</p></div>
                            </div>
                            <div class="lk-help-selected" id="helpSelectedHint">Выберите категорию выше, чтобы увидеть соответствующие вопросы</div>

                            <!-- FAQ секции -->
                            <div id="faq-delivery" class="lk-faq" style="display:none; margin-top:24px;">
                                <div class="lk-section-sep">Вопросы о доставке</div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Сколько занимает доставка?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Стандартная доставка по России занимает от 3 до 14 рабочих дней в зависимости от региона.</div></div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Как отслеживать заказ?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">После отправки заказа вам придёт трек-номер на email, с помощью которого можно отследить посылку на сайте службы доставки.</div></div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Есть ли бесплатная доставка?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Бесплатная доставка доступна при заказе от 5 000 ₽.</div></div>
                            </div>
                            <div id="faq-payment" class="lk-faq" style="display:none; margin-top:24px;">
                                <div class="lk-section-sep">Вопросы по оплате</div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Какие способы оплаты доступны?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Мы принимаем оплату картами Visa, Mastercard, Мир, а также через СБП и электронные кошельки.</div></div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Безопасна ли оплата на сайте?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Да, все платежи защищены SSL-шифрованием. Мы не храним данные вашей карты.</div></div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Когда спишутся деньги?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Деньги списываются сразу после оформления заказа и подтверждения оплаты.</div></div>
                            </div>
                            <div id="faq-return" class="lk-faq" style="display:none; margin-top:24px;">
                                <div class="lk-section-sep">Вопросы по возврату</div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Как вернуть товар?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Вы можете вернуть товар в течение 14 дней с момента получения. Товар должен быть в оригинальной упаковке с бирками.</div></div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Когда вернут деньги?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">После получения возврата мы проверяем товар в течение 3-5 рабочих дней и возвращаем средства на карту.</div></div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Можно ли обменять товар?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Да, обмен возможен на другой размер или цвет при наличии товара на складе.</div></div>
                            </div>
                            <div id="faq-size" class="lk-faq" style="display:none; margin-top:24px;">
                                <div class="lk-section-sep">Размеры и посадка</div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Как определить свой размер?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Замерьте обхват груди и сверьтесь с таблицей: XS — до 84 см, S — 84–88, M — 88–92, L — 92–96, XL — 96–100, XXL — 100–104.</div></div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Какая посадка у футболок?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Наши футболки имеют оверсайз посадку, при сомнении рекомендуем выбирать меньший размер.</div></div>
                            </div>
                            <div id="faq-care" class="lk-faq" style="display:none; margin-top:24px;">
                                <div class="lk-section-sep">Уход за изделием</div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Как стирать?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Стирать при температуре не выше 30°C, деликатный режим, без отбеливателей. Гладить с изнаночной стороны.</div></div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Как хранить одежду?<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Храните в сухом месте, сложенной или на вешалке, избегайте прямых солнечных лучей.</div></div>
                            </div>
                            <div id="faq-contact" class="lk-faq" style="display:none; margin-top:24px;">
                                <div class="lk-section-sep">Связаться с нами</div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Телефон<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">+7 914 227 02 20 — звонки принимаем Пн–Пт с 10:00 до 18:00 (МСК).</div></div>
                                <div class="lk-faq-item"><div class="lk-faq-q">Email поддержки<i class="fas fa-chevron-down"></i></div><div class="lk-faq-a">Напишите нам на support@basemood.ru — ответим в течение 24 часов.</div></div>
                            </div>
                        </div>
                    </div>

                </div><!-- /lk-main -->
            </div><!-- /lk-layout -->
        </div><!-- /lk-wrap -->
    </main>

    <!-- Футер -->
    <?php include __DIR__ . '/footer.php'; ?>

    <script>
    window.basemoodOrdersFromServer = <?php echo json_encode($my_orders_js ?? [], JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="script.js?v=<?= (int) $bm_js_ver ?>"></script>
    <script>
    window.isLoggedIn = true;
    document.addEventListener('DOMContentLoaded', function () {
        // Скрываем спиннер
        const sp = document.getElementById('loadingSpinner');
        if (sp) { setTimeout(() => { sp.classList.add('hidden'); setTimeout(() => sp.remove(), 300); }, 600); }

        function resetHelpFaqExpandedState() {
            document.querySelectorAll('.lk-faq-item.open').forEach(item => {
                item.classList.remove('open');
            });
        }

        function getSectionLabel(section) {
            const sectionLabels = {
                profile: 'Профиль',
                personal: 'Личные данные',
                orders: 'Заказы',
                cart: 'Корзина',
                wishlist: 'Избранные товары',
                help: 'Помощь'
            };
            return sectionLabels[section] || 'Профиль';
        }

        function updateBreadcrumb(section) {
            const currentCrumb = document.getElementById('lkBreadcrumbCurrent');
            if (!currentCrumb) return;
            currentCrumb.textContent = getSectionLabel(section);
            currentCrumb.dataset.section = section;
        }

        const navLinks = document.querySelectorAll('.lk-nav a[data-section]');

        function activateSection(sectionName, syncHash = true) {
            const targetLink = document.querySelector(`.lk-nav a[data-section="${sectionName}"]`);
            const sectionKey = targetLink ? sectionName : 'profile';

            navLinks.forEach(l => l.classList.remove('active'));
            if (targetLink) targetLink.classList.add('active');
            else {
                const profileLink = document.querySelector('.lk-nav a[data-section="profile"]');
                if (profileLink) profileLink.classList.add('active');
            }

            const activeLink = document.querySelector(`.lk-nav a.active[data-section="${sectionKey}"]`);
            if (activeLink && typeof activeLink.scrollIntoView === 'function') {
                activeLink.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
            }

            document.querySelectorAll('.lk-section').forEach(s => s.classList.remove('active'));
            const sec = document.getElementById('section-' + sectionKey);
            if (sec) {
                if (sectionKey !== 'help') {
                    resetHelpFaqExpandedState();
                }
                sec.classList.add('active');
                if (sectionKey === 'orders') renderOrders();
                if (sectionKey === 'cart') renderCart();
                if (sectionKey === 'wishlist') renderWishlist();
                if (sectionKey === 'help') {
                    document.querySelectorAll('.lk-help-item').forEach(i => i.classList.remove('active'));
                    document.querySelectorAll('.lk-faq').forEach(f => {
                        f.classList.remove('visible');
                        f.style.display = 'none';
                    });
                    const helpHintEl = document.getElementById('helpSelectedHint');
                    if (helpHintEl) {
                        helpHintEl.textContent = 'Выберите категорию выше, чтобы увидеть соответствующие вопросы';
                        helpHintEl.classList.remove('active');
                    }
                }
            }

            updateBreadcrumb(sectionKey);
            if (syncHash) {
                window.location.hash = sectionKey;
            }
            return Boolean(sec);
        }

        function openSection(sectionName) {
            return activateSection(sectionName, true);
        }

        // Навигация по меню
        navLinks.forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                activateSection(this.dataset.section, true);
            });
        });

        const cabinetCrumb = document.getElementById('lkBreadcrumbCabinet');
        if (cabinetCrumb) {
            cabinetCrumb.addEventListener('click', function () {
                activateSection('profile', true);
            });
        }

        const currentCrumb = document.getElementById('lkBreadcrumbCurrent');
        if (currentCrumb) {
            currentCrumb.addEventListener('click', function () {
                const section = this.dataset.section || 'profile';
                activateSection(section, true);
            });
        }

        // Если после POST нужно открыть нужный раздел
        <?php if (($_POST['action'] ?? '') === 'update_profile' || ($_POST['action'] ?? '') === 'change_password'): ?>
        (function() {
            activateSection('personal', true);
        })();
        <?php endif; ?>

        // Открытие секции по hash (#cart, #wishlist, ...)
        const hashSection = (window.location.hash || '').replace('#', '').trim();
        if (hashSection) {
            activateSection(hashSection, false);
        } else {
            activateSection('profile', false);
        }

        window.addEventListener('hashchange', function () {
            const section = (window.location.hash || '').replace('#', '').trim() || 'profile';
            activateSection(section, false);
        });

        // --- ЗАКАЗЫ ---
        function renderOrders() {
            const list = document.getElementById('ordersList');
            const noMsg = document.getElementById('noOrders');
            if (!list || !noMsg) return;
            const rows = window.basemoodOrdersFromServer || [];
            list.innerHTML = '';
            if (!rows.length) {
                list.style.display = 'none';
                noMsg.style.display = '';
                return;
            }
            noMsg.style.display = 'none';
            list.style.display = '';
            const statusMap = { new: 'Новый', processing: 'В обработке', shipped: 'Отправлен', delivered: 'Доставлен', cancelled: 'Отменён' };
            const payMap = { pending: 'Ожидает оплаты', paid: 'Оплачен', failed: 'Ошибка оплаты', refunded: 'Возврат' };
            list.innerHTML = rows.map(function (o) {
                const st = statusMap[o.status] || o.status;
                const pt = payMap[o.payment_status] || o.payment_status;
                const dt = o.created_at ? new Date(String(o.created_at).replace(' ', 'T')) : null;
                const ds = dt && !isNaN(dt.getTime()) ? dt.toLocaleString('ru-RU') : (o.created_at || '');
                const sum = Number(o.total_amount || 0).toLocaleString('ru-RU');
                return '<div class="lk-order-card"><div class="lk-order-head"><span class="lk-order-id">Заказ №' + o.id + '</span><span class="lk-order-date">' + ds + '</span></div><div class="lk-order-body"><div><span class="lk-status">' + st + '</span> <span class="lk-pay-badge">' + pt + '</span></div><div class="lk-order-total">' + sum + ' ₽</div></div></div>';
            }).join('');
        }
        ['filterStatus','filterPayment','filterTime'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', renderOrders);
        });

        // Кастомные dropdown-фильтры
        document.querySelectorAll('.lk-filter').forEach(filter => {
            const trigger = filter.querySelector('.lk-filter-trigger');
            const label = trigger ? trigger.querySelector('span') : null;
            const input = filter.querySelector('.lk-filter-input');
            const options = filter.querySelectorAll('.lk-filter-option');

            if (!trigger || !label || !input || !options.length) return;

            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.lk-filter.open').forEach(openFilter => {
                    if (openFilter !== filter) openFilter.classList.remove('open');
                });
                filter.classList.toggle('open');
            });

            options.forEach(option => {
                option.addEventListener('click', function () {
                    options.forEach(item => item.classList.remove('active'));
                    this.classList.add('active');
                    input.value = this.dataset.value || '';
                    label.textContent = this.textContent.trim();
                    filter.classList.remove('open');
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.lk-filter')) {
                document.querySelectorAll('.lk-filter.open').forEach(filter => filter.classList.remove('open'));
            }
        });

        function normalizeFavoriteItems(raw) {
            if (!Array.isArray(raw)) return [];
            return raw.map(item => {
                if (typeof item === 'number') {
                    return {
                        id: item,
                        name: `Товар #${item}`,
                        price: 0,
                        image: '',
                        url: `product.php?id=${item}`
                    };
                }
                return {
                    id: parseInt(item.id, 10),
                    name: item.name || `Товар #${item.id}`,
                    price: parseInt(item.price, 10) || 0,
                    image: item.image || '',
                    url: item.url || `product.php?id=${item.id}`
                };
            }).filter(item => Number.isFinite(item.id));
        }

        function getFavorites() {
            return normalizeFavoriteItems(JSON.parse(localStorage.getItem('basemood-favorites') || '[]'));
        }

        function saveFavorites(items) {
            localStorage.setItem('basemood-favorites', JSON.stringify(items));
        }

        function getCart() {
            const raw = JSON.parse(localStorage.getItem('basemood-cart') || '[]');
            if (!Array.isArray(raw)) return [];
            return raw.map(item => ({
                id: parseInt(item.id, 10),
                name: item.name || `Товар #${item.id}`,
                price: parseInt(item.price, 10) || 0,
                image: item.image || '',
                url: item.url || `product.php?id=${item.id}`,
                size: item.size || 'M',
                quantity: Math.max(1, parseInt(item.quantity, 10) || 1)
            })).filter(item => Number.isFinite(item.id));
        }

        function saveCart(items) {
            localStorage.setItem('basemood-cart', JSON.stringify(items));
        }

        function syncHeaderCartCounter() {
            const countEl = document.querySelector('.cart-count');
            if (!countEl) return;
            const total = getCart().reduce((sum, item) => sum + item.quantity, 0);
            countEl.textContent = total;
        }

        // --- КОРЗИНА ---
        function renderCart() {
            const list = document.getElementById('cartList');
            const no = document.getElementById('noCart');
            const summary = document.getElementById('cartSummary');
            const totalEl = document.getElementById('cartTotal');
            if (!list || !no || !summary || !totalEl) return;

            const cart = getCart();
            if (!cart.length) {
                list.innerHTML = '';
                no.style.display = '';
                summary.style.display = 'none';
                syncHeaderCartCounter();
                return;
            }

            no.style.display = 'none';
            summary.style.display = 'flex';

            list.innerHTML = cart.map(item => {
                const lineTotal = item.price * item.quantity;
                const safeName = (item.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                const safeImage = item.image || '';
                return `
                <div class="lk-cart-item">
                    <a class="lk-cart-img" href="${item.url || '#'}"><img src="${safeImage}" alt="${safeName}"></a>
                    <div class="lk-cart-info">
                        <div class="lk-cart-name">${safeName}</div>
                        <div class="lk-cart-meta">Размер: ${item.size}</div>
                    </div>
                    <div class="lk-cart-qty">
                        <button type="button" onclick="cartQtyDown(${item.id}, '${item.size}')">-</button>
                        <span>${item.quantity}</span>
                        <button type="button" onclick="cartQtyUp(${item.id}, '${item.size}')">+</button>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div class="lk-cart-price">${lineTotal.toLocaleString('ru-RU')} ₽</div>
                        <button type="button" class="lk-cart-remove" onclick="removeCartItem(${item.id}, '${item.size}')"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
            }).join('');

            const totalSum = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
            totalEl.textContent = `Итого: ${totalSum.toLocaleString('ru-RU')} ₽`;
            syncHeaderCartCounter();
        }

        window.cartQtyUp = function(id, size) {
            const cart = getCart();
            const found = cart.find(item => item.id === id && item.size === size);
            if (!found) return;
            found.quantity += 1;
            saveCart(cart);
            renderCart();
        };

        window.cartQtyDown = function(id, size) {
            const cart = getCart();
            const found = cart.find(item => item.id === id && item.size === size);
            if (!found) return;
            found.quantity = Math.max(1, found.quantity - 1);
            saveCart(cart);
            renderCart();
        };

        window.removeCartItem = function(id, size) {
            const cart = getCart().filter(item => !(item.id === id && item.size === size));
            saveCart(cart);
            renderCart();
        };

        // --- ИЗБРАННОЕ ---
        function renderWishlist() {
            const favs = getFavorites();
            const grid = document.getElementById('wishlistGrid');
            const no = document.getElementById('noWishlist');
            if (!grid || !no) return;

            if (!favs.length) {
                grid.innerHTML = '';
                no.style.display = '';
                return;
            }

            no.style.display = 'none';
            grid.innerHTML = favs.map(item => {
                const safeName = (item.name || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                return `
                <div class="lk-witem">
                    <a class="lk-witem-img" href="${item.url || '#'}"><img src="${item.image || ''}" alt="${safeName}"></a>
                    <div class="lk-witem-info">
                        <div class="lk-witem-name">${safeName}</div>
                        <div class="lk-witem-price">${(item.price || 0).toLocaleString('ru-RU')} ₽</div>
                        <div class="lk-witem-actions">
                            <button class="lk-witem-btn" onclick="addWishlistToCart(${item.id}, this)"><i class="fas fa-shopping-cart"></i> В корзину</button>
                            <button class="lk-witem-btn" onclick="removeFav(${item.id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>`;
            }).join('');
        }

        window.removeFav = function(id) {
            const favs = getFavorites().filter(item => item.id !== id);
            saveFavorites(favs);
            renderWishlist();
        };

        window.addWishlistToCart = function(id, sourceButton = null) {
            const favs = getFavorites();
            const item = favs.find(f => f.id === id);
            if (!item) return;

            const cart = getCart();
            const existing = cart.find(c => c.id === id && c.size === 'M');
            if (existing) {
                existing.quantity += 1;
            } else {
                cart.push({
                    id: item.id,
                    name: item.name,
                    price: item.price,
                    image: item.image,
                    url: item.url,
                    size: 'M',
                    quantity: 1
                });
            }

            saveCart(cart);
            syncHeaderCartCounter();
            if (window.basemoodStore && typeof window.basemoodStore.animateAddToCartFeedback === 'function') {
                window.basemoodStore.animateAddToCartFeedback(sourceButton);
            }
            renderCart();
        };

        // --- ПОМОЩЬ ---
        const helpItems = document.querySelectorAll('.lk-help-item');
        const helpGrid = document.querySelector('.lk-help-grid');
        const helpHint = document.getElementById('helpSelectedHint');
        function activateHelpTopic(item) {
            if (!item) return;
            if (!item.dataset.topic) return;

            resetHelpFaqExpandedState();
            document.querySelectorAll('.lk-help-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');

            document.querySelectorAll('.lk-faq').forEach(f => {
                f.classList.remove('visible');
                f.style.display = 'none';
            });

            const faq = document.getElementById('faq-' + item.dataset.topic);
            if (faq) {
                faq.style.display = 'block';
                requestAnimationFrame(() => {
                    faq.classList.add('visible');
                });

                requestAnimationFrame(() => {
                    const nav = document.querySelector('.nav-container');
                    const navOffset = nav ? nav.offsetHeight : 0;
                    // Keep more space above FAQ on desktop so the page does not jump too far down.
                    const extraOffset = window.innerWidth <= 768 ? 110 : window.innerWidth <= 1024 ? 160 : 180;
                    const top = faq.getBoundingClientRect().top + window.pageYOffset - navOffset - extraOffset;
                    window.scrollTo({ top: Math.max(top, 0), behavior: 'smooth' });
                });
            }

            if (helpHint) {
                const title = item.querySelector('h3');
                helpHint.textContent = title ? ('Показаны: ' + title.textContent) : 'Категория выбрана';
                helpHint.classList.add('active');
            }
        }

        if (helpGrid) {
            helpGrid.addEventListener('click', function (e) {
                const item = e.target.closest('.lk-help-item');
                if (!item) return;
                activateHelpTopic(item);
            });
        }

        document.addEventListener('click', function (e) {
            const question = e.target.closest('.lk-faq-q');
            if (!question) return;
            const item = question.closest('.lk-faq-item');
            if (!item) return;
            item.classList.toggle('open');
        });
    });
    </script>
</body>
</html>
