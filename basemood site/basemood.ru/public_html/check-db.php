<?php
require_once __DIR__ . '/config.php';

if (!$conn) {
    die('Ошибка подключения: ' . mysqli_connect_error());
}

$result = mysqli_query($conn, 'DESCRIBE products');

echo 'Текущая структура таблицы products:<br>';
while ($row = mysqli_fetch_assoc($result)) {
    echo '  - ' . $row['Field'] . ' (' . $row['Type'] . ')<br>';
}

echo '<br>Нужно добавить следующие колонки:<br>';
echo 'ALTER TABLE products ADD COLUMN material VARCHAR(100) DEFAULT NULL;<br>';
echo 'ALTER TABLE products ADD COLUMN composition VARCHAR(100) DEFAULT NULL;<br>';
echo 'ALTER TABLE products ADD COLUMN printing VARCHAR(100) DEFAULT NULL;<br>';

mysqli_close($conn);
?>