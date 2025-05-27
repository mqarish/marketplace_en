<?php
// Archivo para crear las tablas necesarias para el sistema de categorías de productos
require_once 'includes/config.php';

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Crear tabla de categorías de productos
$create_categories_table = "
CREATE TABLE IF NOT EXISTS product_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    store_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// Verificar si la columna category_id ya existe en la tabla products
$check_column = "SHOW COLUMNS FROM products LIKE 'category_id'";
$column_exists = $conn->query($check_column)->num_rows > 0;

// Agregar columna category_id a la tabla de productos si no existe
$alter_products_table = "";
if (!$column_exists) {
    $alter_products_table = "ALTER TABLE products ADD COLUMN category_id INT DEFAULT NULL";
}

// Ejecutar consultas
if ($conn->query($create_categories_table)) {
    echo "Tabla product_categories creada correctamente.<br>";
} else {
    echo "Error al crear la tabla product_categories: " . $conn->error . "<br>";
}

if (!empty($alter_products_table)) {
    if ($conn->query($alter_products_table)) {
        echo "Columna category_id agregada a la tabla products correctamente.<br>";
    } else {
        echo "Error al agregar la columna category_id a la tabla products: " . $conn->error . "<br>";
    }
} else {
    echo "La columna category_id ya existe en la tabla products.<br>";
}

// Crear directorio para imágenes de categorías si no existe
$categories_dir = 'uploads/categories';
if (!file_exists($categories_dir)) {
    if (mkdir($categories_dir, 0777, true)) {
        echo "Directorio para imágenes de categorías creado correctamente.<br>";
    } else {
        echo "Error al crear el directorio para imágenes de categorías.<br>";
    }
}

echo "Proceso completado.";
?>
