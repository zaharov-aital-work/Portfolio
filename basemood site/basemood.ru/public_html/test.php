<?php
require_once 'config.php';

$result = mysqli_query($conn, "SELECT * FROM products");

echo '<h1>Товары из базы данных:</h1>';

while ($row = mysqli_fetch_assoc($result)) {
    echo '<div>';
    echo '<h3>' . htmlspecialchars($row['name']) . '</h3>';
    echo '<p>Цена: ' . $row['price'] . ' ₽</p>';
    echo '<img src="' . htmlspecialchars($row['image_url']) . '" width="150">';
    echo '</div><hr>';
}

mysqli_close($conn);
?>