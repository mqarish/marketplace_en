<?php
require_once 'config.php';

// حذف الجداول إذا كانت موجودة
$tables = ['favorites', 'products', 'stores', 'categories', 'users'];
foreach ($tables as $table) {
    $conn->query("DROP TABLE IF EXISTS $table");
}

// إنشاء جدول المستخدمين
$sql = "CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'store', 'customer') NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($sql);

// إنشاء جدول التصنيفات
$sql = "CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($sql);

// إنشاء جدول المتاجر
$sql = "CREATE TABLE stores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    logo VARCHAR(255),
    status ENUM('pending', 'active', 'rejected', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($sql);

// إضافة المستخدمين التجار
$store_users = [
    [
        'username' => 'بقالة ابو الرجل',
        'email' => 'mqarish@gmail.com',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'phone' => '734111154',
        'role' => 'store'
    ],
    [
        'username' => 'سوبر مركة النجار',
        'email' => 'mqarish@yahoo.com',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'phone' => '737000557',
        'role' => 'store'
    ]
];

foreach ($store_users as $user) {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $user['username'], $user['email'], $user['password'], $user['phone'], $user['role']);
    $stmt->execute();
    
    $user_id = $conn->insert_id;
    
    // إضافة المتجر
    $stmt = $conn->prepare("INSERT INTO stores (user_id, name, description, status) VALUES (?, ?, ?, 'active')");
    $store_name = $user['username'];
    $description = "متجر " . $user['username'];
    $stmt->bind_param("iss", $user_id, $store_name, $description);
    $stmt->execute();
}

// إنشاء جدول المنتجات
$sql = "CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    store_id INT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($sql);

// إنشاء جدول المفضلة
$sql = "CREATE TABLE favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($sql);

// إضافة حساب المسؤول الافتراضي
$admin_username = 'admin';
$admin_email = 'admin@example.com';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_role = 'admin';

// التحقق من وجود المسؤول
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $admin_username, $admin_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $admin_username, $admin_email, $admin_password, $admin_role);
    $stmt->execute();
    echo "تم إنشاء حساب المسؤول بنجاح<br>";
}

// إضافة تصنيفات افتراضية
$categories = [
    'إلكترونيات',
    'ملابس',
    'أثاث',
    'مستلزمات منزلية',
    'كتب',
    'رياضة',
    'مستحضرات تجميل',
    'ألعاب',
    'طعام ومشروبات',
    'أخرى'
];

foreach ($categories as $category) {
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param("s", $category);
    $stmt->execute();
}

echo "تم تحديث قاعدة البيانات بنجاح";
