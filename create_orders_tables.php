<?php
require_once 'includes/init.php';

// إنشاء جدول الطلبات
$orders_table_query = "
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    store_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash_on_delivery', 'credit_card', 'bank_transfer') DEFAULT 'cash_on_delivery',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    phone VARCHAR(20) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id),
    INDEX (customer_id),
    INDEX (store_id),
    INDEX (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// إنشاء جدول تفاصيل الطلبات
$order_items_table_query = "
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX (order_id),
    INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// إنشاء جدول سجل حالة الطلبات
$order_history_table_query = "
CREATE TABLE IF NOT EXISTS order_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// تنفيذ الاستعلامات
if ($conn->query($orders_table_query)) {
    echo "تم إنشاء جدول الطلبات بنجاح<br>";
} else {
    echo "خطأ في إنشاء جدول الطلبات: " . $conn->error . "<br>";
}

if ($conn->query($order_items_table_query)) {
    echo "تم إنشاء جدول تفاصيل الطلبات بنجاح<br>";
} else {
    echo "خطأ في إنشاء جدول تفاصيل الطلبات: " . $conn->error . "<br>";
}

if ($conn->query($order_history_table_query)) {
    echo "تم إنشاء جدول سجل حالة الطلبات بنجاح<br>";
} else {
    echo "خطأ في إنشاء جدول سجل حالة الطلبات: " . $conn->error . "<br>";
}

// إضافة بيانات تجريبية للطلبات (اختياري)
$check_orders_query = "SELECT COUNT(*) as count FROM orders";
$result = $conn->query($check_orders_query);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // إضافة بيانات تجريبية فقط إذا كان الجدول فارغاً
    $sample_orders_query = "
    INSERT INTO orders (customer_id, store_id, total_amount, status, payment_method, payment_status, shipping_address, phone, notes)
    VALUES 
    (1, 1, 250.00, 'pending', 'cash_on_delivery', 'pending', 'شارع الملك فهد، الرياض', '0501234567', 'الرجاء الاتصال قبل التوصيل'),
    (2, 1, 120.50, 'processing', 'credit_card', 'paid', 'حي النزهة، جدة', '0559876543', ''),
    (3, 1, 75.25, 'shipped', 'bank_transfer', 'paid', 'شارع الأمير محمد، الدمام', '0561234567', 'توصيل إلى المنزل فقط'),
    (1, 1, 320.00, 'delivered', 'cash_on_delivery', 'paid', 'شارع الملك فهد، الرياض', '0501234567', ''),
    (4, 1, 95.75, 'cancelled', 'credit_card', 'failed', 'حي الروضة، مكة', '0555555555', 'تم إلغاء الطلب بناءً على طلب العميل')
    ";
    
    if ($conn->query($sample_orders_query)) {
        echo "تم إضافة بيانات تجريبية للطلبات بنجاح<br>";
        
        // إضافة تفاصيل الطلبات
        $sample_order_items_query = "
        INSERT INTO order_items (order_id, product_id, quantity, price)
        VALUES 
        (1, 1, 2, 100.00),
        (1, 2, 1, 50.00),
        (2, 3, 1, 120.50),
        (3, 1, 1, 75.25),
        (4, 2, 2, 160.00),
        (5, 3, 1, 95.75)
        ";
        
        if ($conn->query($sample_order_items_query)) {
            echo "تم إضافة بيانات تجريبية لتفاصيل الطلبات بنجاح<br>";
        } else {
            echo "خطأ في إضافة بيانات تجريبية لتفاصيل الطلبات: " . $conn->error . "<br>";
        }
        
        // إضافة سجل حالة الطلبات
        $sample_order_history_query = "
        INSERT INTO order_history (order_id, status, notes)
        VALUES 
        (1, 'pending', 'تم استلام الطلب'),
        (2, 'pending', 'تم استلام الطلب'),
        (2, 'processing', 'جاري تجهيز الطلب'),
        (3, 'pending', 'تم استلام الطلب'),
        (3, 'processing', 'جاري تجهيز الطلب'),
        (3, 'shipped', 'تم شحن الطلب'),
        (4, 'pending', 'تم استلام الطلب'),
        (4, 'processing', 'جاري تجهيز الطلب'),
        (4, 'shipped', 'تم شحن الطلب'),
        (4, 'delivered', 'تم توصيل الطلب'),
        (5, 'pending', 'تم استلام الطلب'),
        (5, 'cancelled', 'تم إلغاء الطلب بناءً على طلب العميل')
        ";
        
        if ($conn->query($sample_order_history_query)) {
            echo "تم إضافة بيانات تجريبية لسجل حالة الطلبات بنجاح<br>";
        } else {
            echo "خطأ في إضافة بيانات تجريبية لسجل حالة الطلبات: " . $conn->error . "<br>";
        }
    } else {
        echo "خطأ في إضافة بيانات تجريبية للطلبات: " . $conn->error . "<br>";
    }
}

echo "<br>تم الانتهاء من إعداد جداول الطلبات بنجاح!";
