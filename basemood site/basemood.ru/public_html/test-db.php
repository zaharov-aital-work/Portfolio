<?php
require_once __DIR__ . '/config.php';

if (!$conn) {
    die('Ошибка: ' . mysqli_connect_error());
}

$result = mysqli_query($conn, "SELECT * FROM products");

echo '<h1>Товары из базы данных basemood</h1>';

while ($row = mysqli_fetch_assoc($result)) {
    echo '<div style="border:1px solid #ccc; margin:10px; padding:10px;">';
    echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
    echo '<p>Цена: ' . $row['price'] . ' ₽</p>';
    echo '<img src="' . htmlspecialchars($row['image_url']) . '" width="150">';
    echo '</div>';
}

mysqli_close($conn);
?>
