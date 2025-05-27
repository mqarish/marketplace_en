<?php
require_once '../includes/config.php';

// إنشاء جدول المنتجات إذا لم يكن موجوداً
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id)
)";

if ($conn->query($sql)) {
    echo "<div dir='rtl'>";
    echo "<h2>تم إنشاء جدول المنتجات بنجاح!</h2>";
    echo "</div>";
} else {
    echo "<div dir='rtl'>";
    echo "<h2>حدث خطأ في إنشاء جدول المنتجات:</h2>";
    echo "<p>" . $conn->error . "</p>";
    echo "</div>";
}
?>
