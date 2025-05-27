-- Crear tabla de reviews si no existe
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` decimal(3,1) NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Añadir algunos ejemplos de reviews
INSERT INTO `reviews` (`product_id`, `customer_id`, `rating`, `comment`) VALUES
(1, 1, 4.5, 'منتج رائع وجودة عالية'),
(1, 2, 5, 'أفضل منتج اشتريته هذا العام'),
(2, 1, 3.5, 'جيد ولكن السعر مرتفع نسبياً'),
(3, 3, 4, 'خدمة ممتازة وجودة عالية'),
(50, 1, 5, 'ممتاز جداً'),
(50, 2, 4.5, 'سعر مناسب وجودة عالية'),
(50, 3, 4, 'راضي عن المنتج ولكن التوصيل كان متأخر');
