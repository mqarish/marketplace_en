-- إنشاء جدول الإعجابات إذا لم يكن موجوداً
CREATE TABLE IF NOT EXISTS `product_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_customer` (`product_id`,`customer_id`),
  KEY `product_id` (`product_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة بعض الإعجابات التجريبية
INSERT INTO `product_likes` (`product_id`, `customer_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 1),
(3, 2),
(50, 1),
(50, 2),
(50, 3),
(50, 4),
(50, 5);
