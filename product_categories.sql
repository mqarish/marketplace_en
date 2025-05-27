-- إنشاء جدول تصنيفات المنتجات
CREATE TABLE IF NOT EXISTS product_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    store_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (store_id) REFERENCES stores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة حقل category_id إلى جدول المنتجات إذا لم يكن موجوداً
ALTER TABLE products ADD COLUMN IF NOT EXISTS category_id INT DEFAULT NULL;

-- إضافة مفتاح أجنبي يربط المنتجات بتصنيفات المنتجات
ALTER TABLE products ADD CONSTRAINT IF NOT EXISTS fk_product_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL;
