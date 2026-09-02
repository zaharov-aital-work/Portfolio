<?php
$password = "Admin@2024.SecurePass#9x7k";
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Пароль: " . $password . "<br>";
echo "Хеш для вставки в код:<br>";
echo "<code>" . $hash . "</code><br><br>";

// Проверка
$verify = password_verify($password, $hash);
echo "Проверка: " . ($verify ? "✅ УСПЕШНО" : "❌ ОШИБКА") . "<br>";
?>
