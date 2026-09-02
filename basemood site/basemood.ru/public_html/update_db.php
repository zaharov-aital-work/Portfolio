<?php
include 'config.php';

$sql = "ALTER TABLE products 
        ADD COLUMN description TEXT,
        ADD COLUMN material VARCHAR(255),
        ADD COLUMN composition VARCHAR(255),
        ADD COLUMN printing VARCHAR(255)";

if (mysqli_query($conn, $sql)) {
    echo "Столбцы добавлены успешно.";
} else {
    echo "Ошибка: " . mysqli_error($conn);
}

mysqli_close($conn);
?>