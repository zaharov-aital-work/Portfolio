<?php
require_once __DIR__ . '/config.php';

if (!$conn) {
    die('❌ Ошибка подключения: ' . mysqli_connect_error());
}

echo '✅ Подключение к БД успешно!<br><br>';

// Проверяем таблицы
$result = mysqli_query($conn, "SHOW TABLES");
echo 'Таблицы в БД:<br>';
while ($row = mysqli_fetch_row($result)) {
    echo '  - ' . $row[0] . '<br>';
}

echo '<br>';

// Пытаемся получить товары
$result = mysqli_query($conn, "SELECT * FROM products");
if (!$result) {
    die('❌ Ошибка запроса: ' . mysqli_error($conn));
}

$count = mysqli_num_rows($result);
echo "✅ Товаров найдено: $count<br><br>";

if ($count > 0) {
    echo 'Данные товаров:<br>';
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<pre>';
        print_r($row);
        echo '</pre>';
    }
} else {
    echo '⚠️ Таблица products пуста или не существует<br>';
}

mysqli_close($conn);
?>