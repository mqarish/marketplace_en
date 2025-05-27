<?php
require_once '../includes/init.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conn->begin_transaction();

    // إنشاء جدول المستخدمين إذا لم يكن موجوداً
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(191) NOT NULL,
        email VARCHAR(191) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'store', 'customer') NOT NULL,
        status ENUM('pending', 'active', 'blocked') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_email (email),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // إنشاء جدول العملاء إذا لم يكن موجوداً
    $conn->query("CREATE TABLE IF NOT EXISTS customers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(191) NOT NULL,
        email VARCHAR(191) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        address TEXT,
        status ENUM('pending', 'active', 'blocked') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_email (email),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // إنشاء جدول المتاجر إذا لم يكن موجوداً
    $conn->query("CREATE TABLE IF NOT EXISTS stores (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(191) NOT NULL,
        email VARCHAR(191) UNIQUE NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        address TEXT,
        description TEXT,
        logo VARCHAR(255) DEFAULT NULL,
        status ENUM('pending', 'active', 'blocked') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_email (email),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // إنشاء جدول الفئات إذا لم يكن موجوداً
    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(191) NOT NULL,
        description TEXT,
        image VARCHAR(255) DEFAULT NULL,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // إنشاء جدول المنتجات إذا لم يكن موجوداً
    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        store_id INT NOT NULL,
        category_id INT NOT NULL,
        name VARCHAR(191) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->commit();
    echo "تم إنشاء جميع الجداول بنجاح!";

} catch (Exception $e) {
    $conn->rollback();
    echo "حدث خطأ: " . $e->getMessage();
}

// عرض هيكل الجداول
$tables = ['users', 'customers', 'stores', 'categories', 'products'];
echo "<h2>هيكل الجداول:</h2>";

foreach ($tables as $table) {
    echo "<h3>جدول $table:</h3>";
    $result = $conn->query("SHOW CREATE TABLE $table");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
    }
}
?>
