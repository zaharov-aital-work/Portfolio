<?php
require_once __DIR__ . '/config.php';

if (!$conn) {
    die('❌ Ошибка подключения: ' . mysqli_connect_error());
}

$result = mysqli_query($conn, 'DESCRIBE products');

echo '<h2>📋 Что нужно добавить в базу данных</h2>';
echo '<h3>Текущие колонки в таблице products:</h3>';
echo '<ul>';
while ($row = mysqli_fetch_assoc($result)) {
    echo '<li><strong>' . $row['Field'] . '</strong> (' . $row['Type'] . ')</li>';
}
echo '</ul>';

echo '<h3 style="color: red;">❌ Нужно добавить эти 3 колонки:</h3>';
echo '<ol>';
echo '<li><strong>material</strong> - VARCHAR(100) - для материала товара</li>';
echo '<li><strong>composition</strong> - VARCHAR(100) - для состава ткани</li>';
echo '<li><strong>printing</strong> - VARCHAR(100) - для типа нанесения</li>';
echo '</ol>';

echo '<h3>📝 SQL запросы для добавления:</h3>';
echo '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; font-family: monospace;">';
echo 'ALTER TABLE products ADD COLUMN material VARCHAR(100) DEFAULT NULL;<br>';
echo 'ALTER TABLE products ADD COLUMN composition VARCHAR(100) DEFAULT NULL;<br>';
echo 'ALTER TABLE products ADD COLUMN printing VARCHAR(100) DEFAULT NULL;';
echo '</div>';

echo '<h3>🔧 Как добавить:</h3>';
echo '<ol>';
echo '<li>Откройте <a href="/phpmyadmin/" target="_blank">phpMyAdmin</a></li>';
echo '<li>Выберите базу данных <strong>basemood</strong></li>';
echo '<li>Выберите таблицу <strong>products</strong></li>';
echo '<li>Перейдите во вкладку <strong>"SQL"</strong></li>';
echo '<li>Вставьте и выполните запросы выше</li>';
echo '</ol>';

mysqli_close($conn);
?>