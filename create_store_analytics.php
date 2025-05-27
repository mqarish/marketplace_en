<?php
// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/init.php';

// إنشاء جدول زيارات المتجر
$create_store_visits_sql = "
CREATE TABLE IF NOT EXISTS `store_visits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `visitor_ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visitor_user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visited_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  CONSTRAINT `store_visits_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// إنشاء جدول بحث المتجر
$create_store_searches_sql = "
CREATE TABLE IF NOT EXISTS `store_searches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `search_term` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `searched_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `store_searches_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// إنشاء جدول إحصائيات المتجر
$create_store_stats_sql = "
CREATE TABLE IF NOT EXISTS `store_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `views_count` int(11) NOT NULL DEFAULT '0',
  `likes_count` int(11) NOT NULL DEFAULT '0',
  `products_count` int(11) NOT NULL DEFAULT '0',
  `orders_count` int(11) NOT NULL DEFAULT '0',
  `avg_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_id` (`store_id`),
  CONSTRAINT `store_stats_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// تنفيذ استعلامات إنشاء الجداول
if ($conn->query($create_store_visits_sql) === TRUE) {
    echo "تم إنشاء جدول زيارات المتجر بنجاح<br>";
} else {
    echo "خطأ في إنشاء جدول زيارات المتجر: " . $conn->error . "<br>";
}

if ($conn->query($create_store_searches_sql) === TRUE) {
    echo "تم إنشاء جدول بحث المتجر بنجاح<br>";
} else {
    echo "خطأ في إنشاء جدول بحث المتجر: " . $conn->error . "<br>";
}

if ($conn->query($create_store_stats_sql) === TRUE) {
    echo "تم إنشاء جدول إحصائيات المتجر بنجاح<br>";
} else {
    echo "خطأ في إنشاء جدول إحصائيات المتجر: " . $conn->error . "<br>";
}

// إنشاء إجراء محفوظ لتحديث إحصائيات المتجر
$create_update_store_stats_procedure = "
CREATE PROCEDURE IF NOT EXISTS update_store_stats(IN store_id_param INT)
BEGIN
    DECLARE store_views INT;
    DECLARE store_likes INT;
    DECLARE store_products INT;
    DECLARE store_orders INT;
    DECLARE store_rating DECIMAL(3,2);
    
    -- حساب عدد الزيارات
    SELECT COUNT(DISTINCT visitor_ip) INTO store_views 
    FROM store_visits 
    WHERE store_id = store_id_param;
    
    -- حساب عدد الإعجابات (من منتجات المتجر)
    SELECT COUNT(DISTINCT pl.customer_id) INTO store_likes 
    FROM product_likes pl
    JOIN products p ON pl.product_id = p.id
    WHERE p.store_id = store_id_param;
    
    -- حساب عدد المنتجات
    SELECT COUNT(*) INTO store_products 
    FROM products 
    WHERE store_id = store_id_param;
    
    -- حساب متوسط التقييم (من منتجات المتجر)
    SELECT IFNULL(AVG(r.rating), 0) INTO store_rating 
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    WHERE p.store_id = store_id_param;
    
    -- حساب عدد الطلبات (إذا كان هناك جدول للطلبات)
    SET store_orders = 0;
    
    -- تحديث أو إدراج في جدول الإحصائيات
    INSERT INTO store_stats (store_id, views_count, likes_count, products_count, orders_count, avg_rating)
    VALUES (store_id_param, store_views, store_likes, store_products, store_orders, store_rating)
    ON DUPLICATE KEY UPDATE
        views_count = store_views,
        likes_count = store_likes,
        products_count = store_products,
        orders_count = store_orders,
        avg_rating = store_rating;
END;
";

// تنفيذ استعلام إنشاء الإجراء المحفوظ
if ($conn->multi_query($create_update_store_stats_procedure)) {
    echo "تم إنشاء الإجراء المحفوظ لتحديث إحصائيات المتجر بنجاح<br>";
} else {
    echo "خطأ في إنشاء الإجراء المحفوظ: " . $conn->error . "<br>";
}

// إغلاق الاتصال
$conn->close();

echo "<br>تم إنشاء جميع الجداول والإجراءات اللازمة لتتبع إحصائيات المتجر بنجاح.";
echo "<br><a href='index.php'>العودة للصفحة الرئيسية</a>";
?>
