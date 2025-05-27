<?php
require_once 'includes/init.php';

// التحقق من وجود الأعمدة في جدول المنتجات
$check_columns_query = "SHOW COLUMNS FROM products LIKE 'currency'";
$column_exists = $conn->query($check_columns_query);
$currency_exists = ($column_exists && $column_exists->num_rows > 0);

// إذا لم تكن الأعمدة موجودة، قم بإضافتها
$results = [];

if (!$currency_exists) {
    // إضافة حقل العملة
    $add_currency_query = "ALTER TABLE products ADD COLUMN currency ENUM('SAR', 'YER', 'USD') DEFAULT 'SAR' AFTER price";
    if ($conn->query($add_currency_query)) {
        $results[] = "تم إضافة حقل العملة إلى جدول المنتجات بنجاح";
    } else {
        $results[] = "خطأ في إضافة حقل العملة: " . $conn->error;
    }
    
    // إضافة حقل إخفاء المبلغ
    $add_hide_price_query = "ALTER TABLE products ADD COLUMN hide_price TINYINT(1) NOT NULL DEFAULT 0 AFTER currency";
    if ($conn->query($add_hide_price_query)) {
        $results[] = "تم إضافة حقل إخفاء المبلغ إلى جدول المنتجات بنجاح";
    } else {
        $results[] = "خطأ في إضافة حقل إخفاء المبلغ: " . $conn->error;
    }
    
    // تحديث المنتجات الحالية لتعيين قيم افتراضية
    $update_products_query = "UPDATE products SET currency = 'SAR', hide_price = 0 WHERE currency IS NULL";
    if ($conn->query($update_products_query)) {
        $results[] = "تم تحديث المنتجات الحالية بقيم افتراضية للعملة وإخفاء المبلغ";
    } else {
        $results[] = "خطأ في تحديث المنتجات الحالية: " . $conn->error;
    }
} else {
    $results[] = "حقول العملة وإخفاء المبلغ موجودة بالفعل في جدول المنتجات";
}

// التحقق من وجود جدول العروض
$check_offers_table = "SHOW TABLES LIKE 'offers'";
$offers_exists = $conn->query($check_offers_table);

if ($offers_exists && $offers_exists->num_rows > 0) {
    // التحقق من وجود الأعمدة في جدول العروض
    $check_offers_columns_query = "SHOW COLUMNS FROM offers LIKE 'currency'";
    $offers_column_exists = $conn->query($check_offers_columns_query);
    $offers_currency_exists = ($offers_column_exists && $offers_column_exists->num_rows > 0);
    
    if (!$offers_currency_exists) {
        // إضافة حقل العملة إلى جدول العروض
        $add_offers_currency_query = "ALTER TABLE offers ADD COLUMN currency ENUM('SAR', 'YER', 'USD') DEFAULT 'SAR' AFTER price";
        if ($conn->query($add_offers_currency_query)) {
            $results[] = "تم إضافة حقل العملة إلى جدول العروض بنجاح";
        } else {
            $results[] = "خطأ في إضافة حقل العملة إلى جدول العروض: " . $conn->error;
        }
        
        // إضافة حقل إخفاء المبلغ إلى جدول العروض
        $add_offers_hide_price_query = "ALTER TABLE offers ADD COLUMN hide_price TINYINT(1) NOT NULL DEFAULT 0 AFTER currency";
        if ($conn->query($add_offers_hide_price_query)) {
            $results[] = "تم إضافة حقل إخفاء المبلغ إلى جدول العروض بنجاح";
        } else {
            $results[] = "خطأ في إضافة حقل إخفاء المبلغ إلى جدول العروض: " . $conn->error;
        }
        
        // تحديث العروض الحالية لتعيين قيم افتراضية
        $update_offers_query = "UPDATE offers SET currency = 'SAR', hide_price = 0 WHERE currency IS NULL";
        if ($conn->query($update_offers_query)) {
            $results[] = "تم تحديث العروض الحالية بقيم افتراضية للعملة وإخفاء المبلغ";
        } else {
            $results[] = "خطأ في تحديث العروض الحالية: " . $conn->error;
        }
    } else {
        $results[] = "حقول العملة وإخفاء المبلغ موجودة بالفعل في جدول العروض";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة حقول العملة وإخفاء المبلغ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
</head>
<body>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">إضافة حقول العملة وإخفاء المبلغ</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h4>نتائج العملية:</h4>
                            <ul class="mb-0">
                                <?php foreach ($results as $result): ?>
                                    <li><?php echo $result; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <a href="store/products.php" class="btn btn-primary">العودة إلى صفحة المنتجات</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
