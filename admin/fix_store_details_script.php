<?php
// Script to fix the header structure in store_details.php

// Read the current file content
$file_path = __DIR__ . '/store_details.php';
$content = file_get_contents($file_path);

if ($content === false) {
    die("Error: Could not read the store_details.php file.");
}

// Create a backup of the original file
$backup_path = __DIR__ . '/store_details_backup_' . date('Y-m-d_H-i-s') . '.php';
if (!file_put_contents($backup_path, $content)) {
    die("Error: Could not create a backup file.");
}

// Replace the header part
$header_pattern = '/^<\?php.*?include \'admin_navbar\.php\';.*?\?>/s';
$header_replacement = '<?php
require_once \'../includes/init.php\';
require_once \'check_admin.php\';

// Define BASEPATH for included files
define(\'BASEPATH\', true);

$page_title = \'تفاصيل المتجر\';
$page_icon = \'fa-store\';

// تفعيل عرض الأخطاء للتصحيح
ini_set(\'display_errors\', 1);
ini_set(\'display_startup_errors\', 1);
error_reporting(E_ALL);

// التحقق من وجود معرف المتجر
if (!isset($_GET[\'id\']) || !is_numeric($_GET[\'id\'])) {
    $_SESSION[\'error\'] = \'معرف المتجر غير صالح\';
    header(\'Location: stores.php\');
    exit;
}

$store_id = (int)$_GET[\'id\'];

// استرجاع بيانات المتجر - استعلام بسيط
$query = "SELECT * FROM stores WHERE id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("خطأ في استعلام بيانات المتجر: " . $conn->error);
}

$stmt->bind_param(\'i\', $store_id);
$execute_result = $stmt->execute();
if (!$execute_result) {
    die("خطأ في تنفيذ استعلام بيانات المتجر: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION[\'error\'] = \'المتجر غير موجود\';
    header(\'Location: stores.php\');
    exit;
}

$store = $result->fetch_assoc();

// استرجاع بيانات المالك
if (!empty($store[\'user_id\'])) {
    $owner_query = "SELECT name as owner_name, email as owner_email, phone as owner_phone FROM users WHERE id = ?";
    $owner_stmt = $conn->prepare($owner_query);
    if ($owner_stmt) {
        $owner_stmt->bind_param(\'i\', $store[\'user_id\']);
        $owner_stmt->execute();
        $owner_result = $owner_stmt->get_result();
        if ($owner_result->num_rows > 0) {
            $owner_data = $owner_result->fetch_assoc();
            $store = array_merge($store, $owner_data);
        }
    }
}

// استرجاع إحصائيات المتجر - عدد المنتجات
$product_count_query = "SELECT COUNT(*) as product_count FROM products WHERE store_id = ?";
$product_count_stmt = $conn->prepare($product_count_query);
if ($product_count_stmt) {
    $product_count_stmt->bind_param(\'i\', $store_id);
    $product_count_stmt->execute();
    $product_count_result = $product_count_stmt->get_result();
    $product_count_data = $product_count_result->fetch_assoc();
    $store[\'product_count\'] = $product_count_data[\'product_count\'];
} else {
    $store[\'product_count\'] = 0;
}

// استرجاع إحصائيات الطلبات - إذا كانت الجداول موجودة
$check_orders_table = $conn->query("SHOW TABLES LIKE \'orders\'");
$check_order_items_table = $conn->query("SHOW TABLES LIKE \'order_items\'");

if ($check_orders_table->num_rows > 0 && $check_order_items_table->num_rows > 0) {
    // عدد الطلبات
    $order_count_query = "SELECT COUNT(DISTINCT o.id) as order_count 
                         FROM orders o 
                         JOIN order_items oi ON o.id = oi.order_id 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE p.store_id = ?";
    $order_count_stmt = $conn->prepare($order_count_query);
    if ($order_count_stmt) {
        $order_count_stmt->bind_param(\'i\', $store_id);
        $order_count_stmt->execute();
        $order_count_result = $order_count_stmt->get_result();
        $order_count_data = $order_count_result->fetch_assoc();
        $store[\'order_count\'] = $order_count_data[\'order_count\'];
    } else {
        $store[\'order_count\'] = 0;
    }
    
    // إجمالي المبيعات
    $sales_query = "SELECT SUM(oi.price * oi.quantity) as total_sales 
                   FROM orders o 
                   JOIN order_items oi ON o.id = oi.order_id 
                   JOIN products p ON oi.product_id = p.id 
                   WHERE p.store_id = ?";
    $sales_stmt = $conn->prepare($sales_query);
    if ($sales_stmt) {
        $sales_stmt->bind_param(\'i\', $store_id);
        $sales_stmt->execute();
        $sales_result = $sales_stmt->get_result();
        $sales_data = $sales_result->fetch_assoc();
        $store[\'total_sales\'] = $sales_data[\'total_sales\'] ?: 0;
    } else {
        $store[\'total_sales\'] = 0;
    }
} else {
    $store[\'order_count\'] = 0;
    $store[\'total_sales\'] = 0;
}

// استرجاع منتجات المتجر
$products_query = "SELECT p.*, c.name as category_name
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE p.store_id = ?
          ORDER BY p.created_at DESC
          LIMIT 10";

$products_stmt = $conn->prepare($products_query);
if ($products_stmt) {
    $products_stmt->bind_param(\'i\', $store_id);
    $products_stmt->execute();
    $products_result = $products_stmt->get_result();
    $products = [];
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    $products = [];
}

// استرجاع الطلبات المرتبطة بالمتجر
$orders = [];
if ($check_orders_table->num_rows > 0 && $check_order_items_table->num_rows > 0) {
    $orders_query = "SELECT DISTINCT o.id, o.order_number, o.total_amount, o.status, o.created_at, u.name as customer_name
              FROM orders o
              JOIN order_items oi ON o.id = oi.order_id
              JOIN products p ON oi.product_id = p.id
              LEFT JOIN users u ON o.user_id = u.id
              WHERE p.store_id = ?
              ORDER BY o.created_at DESC
              LIMIT 10";
    
    $orders_stmt = $conn->prepare($orders_query);
    if ($orders_stmt) {
        $orders_stmt->bind_param(\'i\', $store_id);
        $orders_stmt->execute();
        $orders_result = $orders_stmt->get_result();
        while ($row = $orders_result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
}

// تحديث بيانات المتجر
if (isset($_POST[\'update_store\'])) {
    $name = $_POST[\'name\'];
    $email = $_POST[\'email\'];
    $phone = $_POST[\'phone\'];
    $address = $_POST[\'address\'] ?? \'\';
    $city = $_POST[\'city\'] ?? \'\';
    $description = $_POST[\'description\'] ?? \'\';
    
    // التحقق من البيانات
    if (empty($name) || empty($email) || empty($phone)) {
        $_SESSION[\'error\'] = \'جميع الحقول المطلوبة يجب ملؤها\';
    } else {
        // معالجة تحميل الشعار إذا تم تقديمه
        $logo_path = $store[\'logo\'] ?? \'\'; // الاحتفاظ بالشعار الحالي افتراضيًا
        
        if (!empty($_FILES[\'logo\'][\'name\'])) {
            $upload_dir = \'../uploads/stores/\';
            
            // التأكد من وجود المجلد
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES[\'logo\'][\'name\'], PATHINFO_EXTENSION);
            $new_filename = \'store_\' . $store_id . \'_\' . time() . \'.\' . $file_extension;
            $target_file = $upload_dir . $new_filename;
            
            // التحقق من نوع الملف
            $allowed_types = [\'jpg\', \'jpeg\', \'png\', \'gif\'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES[\'logo\'][\'tmp_name\'], $target_file)) {
                    $logo_path = $target_file;
                } else {
                    $_SESSION[\'error\'] = \'حدث خطأ أثناء تحميل الشعار\';
                }
            } else {
                $_SESSION[\'error\'] = \'نوع الملف غير مسموح به. الأنواع المسموح بها: \' . implode(\', \', $allowed_types);
            }
        }
        
        // تحديث بيانات المتجر في قاعدة البيانات
        if (!isset($_SESSION[\'error\'])) {
            $update_query = "UPDATE stores SET 
                            name = ?, 
                            email = ?, 
                            phone = ?, 
                            address = ?, 
                            city = ?, 
                            description = ?";
            
            $params = [$name, $email, $phone, $address, $city, $description];
            $types = \'ssssss\';
            
            // إضافة الشعار إلى الاستعلام إذا تم تحديثه
            if (!empty($logo_path)) {
                $update_query .= ", logo = ?";
                $params[] = $logo_path;
                $types .= \'s\';
            }
            
            $update_query .= " WHERE id = ?";
            $params[] = $store_id;
            $types .= \'i\';
            
            $stmt = $conn->prepare($update_query);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $_SESSION[\'success\'] = \'تم تحديث بيانات المتجر بنجاح\';
                    
                    // تحديث بيانات المتجر في المتغير
                    $store[\'name\'] = $name;
                    $store[\'email\'] = $email;
                    $store[\'phone\'] = $phone;
                    $store[\'address\'] = $address;
                    $store[\'city\'] = $city;
                    $store[\'description\'] = $description;
                    if (!empty($logo_path)) {
                        $store[\'logo\'] = $logo_path;
                    }
                } else {
                    $_SESSION[\'error\'] = \'حدث خطأ أثناء تحديث بيانات المتجر: \' . $stmt->error;
                }
            } else {
                $_SESSION[\'error\'] = \'حدث خطأ في استعلام تحديث المتجر: \' . $conn->error;
            }
        }
    }
}

// تغيير حالة المتجر (تفعيل/تعطيل)
if (isset($_POST[\'toggle_status\'])) {
    $new_status = ($store[\'status\'] == \'active\') ? \'suspended\' : \'active\';
    $update_query = "UPDATE stores SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param(\'si\', $new_status, $store_id);
    
    if ($stmt->execute()) {
        $_SESSION[\'success\'] = ($new_status == \'active\') ? \'تم تفعيل المتجر بنجاح\' : \'تم تعليق المتجر بنجاح\';
        // تحديث حالة المتجر في المتغير
        $store[\'status\'] = $new_status;
    } else {
        $_SESSION[\'error\'] = \'حدث خطأ أثناء تحديث حالة المتجر\';
    }
}

// حذف المتجر
if (isset($_POST[\'delete_store\'])) {
    // التحقق من وجود منتجات مرتبطة بالمتجر
    $check_products = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE store_id = ?");
    $check_products->bind_param(\'i\', $store_id);
    $check_products->execute();
    $result = $check_products->get_result();
    $row = $result->fetch_assoc();
    
    if ($row[\'count\'] > 0) {
        $_SESSION[\'error\'] = \'لا يمكن حذف المتجر لأنه يحتوي على منتجات. قم بحذف المنتجات أولاً.\';
    } else {
        $delete_query = "DELETE FROM stores WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param(\'i\', $store_id);
        
        if ($stmt->execute()) {
            $_SESSION[\'success\'] = \'تم حذف المتجر بنجاح\';
            header(\'Location: stores.php\');
            exit;
        } else {
            $_SESSION[\'error\'] = \'حدث خطأ أثناء حذف المتجر\';
        }
    }
}

// الحصول على رمز العملة
$currency_symbol = \'ر.س\'; // افتراضي: ريال سعودي
$currency_query = "SELECT value FROM settings WHERE name = \'currency\'";
try {
    $currency_result = $conn->query($currency_query);
    if ($currency_result && $currency_result->num_rows > 0) {
        $currency = $currency_result->fetch_assoc()[\'value\'];
        switch ($currency) {
            case \'USD\':
                $currency_symbol = \'$\';
                break;
            case \'EUR\':
                $currency_symbol = \'€\';
                break;
            case \'GBP\':
                $currency_symbol = \'£\';
                break;
            case \'YER\':
                $currency_symbol = \'ر.ي\';
                break;
            default:
                $currency_symbol = \'ر.س\'; // ريال سعودي
        }
    }
} catch (Exception $e) {
    // استخدام القيمة الافتراضية في حالة حدوث خطأ
    error_log(\'خطأ في استرجاع رمز العملة: \' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php include \'admin_header.php\'; ?>
</head>
<body>
    <?php include \'admin_navbar.php\'; ?>
    
    <div class="container-fluid">';

// Replace the footer part
$footer_pattern = '/\<\?php include \'admin_footer\.php\'\; \?\>/';
$footer_replacement = '    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>';

// Apply the replacements
$content = preg_replace($header_pattern, $header_replacement, $content);
$content = preg_replace($footer_pattern, $footer_replacement, $content);

// Write the modified content back to the file
if (file_put_contents($file_path, $content) === false) {
    die("Error: Could not write to the store_details.php file.");
}

echo "Success! The store_details.php file has been updated to match the dashboard.php structure.";
echo "A backup of the original file has been saved to: " . $backup_path;
