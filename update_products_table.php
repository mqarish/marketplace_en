<?php
// Script para actualizar la tabla de productos
require_once 'includes/config.php';

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificar si la columna category_id ya existe
$check_column = "SHOW COLUMNS FROM products LIKE 'category_id'";
$column_exists = $conn->query($check_column);

if ($column_exists && $column_exists->num_rows > 0) {
    echo "La columna category_id ya existe en la tabla products.";
} else {
    // Agregar la columna category_id
    $alter_table = "ALTER TABLE products ADD COLUMN category_id INT DEFAULT NULL";
    
    if ($conn->query($alter_table)) {
        echo "Columna category_id agregada correctamente a la tabla products.";
    } else {
        echo "Error al agregar la columna: " . $conn->error;
    }
}
?>
