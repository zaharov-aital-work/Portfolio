<?php
// config.php - подключение к базе данных (Beget)
//
// Если сайт открыт из подпапки (редко для Beget), задайте префикс: define('BM_PUBLIC_BASE', '/подпапка');
// Пустая строка — пути к картинкам вида «uploads/…» становятся «/uploads/…» (корень домена).

if (!defined('BM_PUBLIC_BASE')) {
    define('BM_PUBLIC_BASE', '');
}

$host     = 'localhost';
$user     = 'basemosy_db';
$password = 'U%o3w5iQpR47';
$dbname   = 'basemosy_db';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = @mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    die('Ошибка подключения к БД: ' . mysqli_connect_error());
}

// Установка кодировки
mysqli_set_charset($conn, 'utf8mb4');

/** API-ключ подсказок DaData (https://dadata.ru) для поля адреса на checkout; пустая строка — подсказки отключены. */
if (!defined('BM_DADATA_TOKEN')) {
    define('BM_DADATA_TOKEN', '57c5dcddd32508dcaff45645cdc92490cc50dd65');
}
?>