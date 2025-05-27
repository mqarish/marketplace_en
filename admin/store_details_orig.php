<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'تفاصيل المتجر';
$page_icon = 'fa-store';

// تفعيل عرض الأخطاء للتصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// التحقق من وجود معرف المتجر
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'معرف المتجر غير صالح';
    header('Location: stores.php');
    exit;
}

$store_id = (int)$_GET['id'];

// استرجاع بيانات المتجر - استعلام بسيط
$query = "SELECT * FROM stores WHERE id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("خطأ في استعلام بيانات المتجر: " . $conn->error);
}

$stmt->bind_param('i', $store_id);
$execute_result = $stmt->execute();
if (!$execute_result) {
    die("خطأ في تنفيذ استعلام بيانات المتجر: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['error'] = 'المتجر غير موجود';
    header('Location: stores.php');
    exit;
}

$store = $result->fetch_assoc();

// استرجاع بيانات المالك
if (!empty($store['user_id'])) {
    $owner_query = "SELECT name as owner_name, email as owner_email, phone as owner_phone FROM users WHERE id = ?";
    $owner_stmt = $conn->prepare($owner_query);
    if ($owner_stmt) {
        $owner_stmt->bind_param('i', $store['user_id']);
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
    $product_count_stmt->bind_param('i', $store_id);
    $product_count_stmt->execute();
    $product_count_result = $product_count_stmt->get_result();
    $product_count_data = $product_count_result->fetch_assoc();
    $store['product_count'] = $product_count_data['product_count'];
} else {
    $store['product_count'] = 0;
}

// استرجاع إحصائيات الطلبات - إذا كانت الجداول موجودة
$check_orders_table = $conn->query("SHOW TABLES LIKE 'orders'");
$check_order_items_table = $conn->query("SHOW TABLES LIKE 'order_items'");

if ($check_orders_table->num_rows > 0 && $check_order_items_table->num_rows > 0) {
    // عدد الطلبات
    $order_count_query = "SELECT COUNT(DISTINCT o.id) as order_count 
                         FROM orders o 
                         JOIN order_items oi ON o.id = oi.order_id 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE p.store_id = ?";
    $order_count_stmt = $conn->prepare($order_count_query);
    if ($order_count_stmt) {
        $order_count_stmt->bind_param('i', $store_id);
        $order_count_stmt->execute();
        $order_count_result = $order_count_stmt->get_result();
        $order_count_data = $order_count_result->fetch_assoc();
        $store['order_count'] = $order_count_data['order_count'];
    } else {
        $store['order_count'] = 0;
    }
    
    // إجمالي المبيعات
    $sales_query = "SELECT SUM(oi.price * oi.quantity) as total_sales 
                   FROM orders o 
                   JOIN order_items oi ON o.id = oi.order_id 
                   JOIN products p ON oi.product_id = p.id 
                   WHERE p.store_id = ?";
    $sales_stmt = $conn->prepare($sales_query);
    if ($sales_stmt) {
        $sales_stmt->bind_param('i', $store_id);
        $sales_stmt->execute();
        $sales_result = $sales_stmt->get_result();
        $sales_data = $sales_result->fetch_assoc();
        $store['total_sales'] = $sales_data['total_sales'] ?: 0;
    } else {
        $store['total_sales'] = 0;
    }
} else {
    $store['order_count'] = 0;
    $store['total_sales'] = 0;
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
    $products_stmt->bind_param('i', $store_id);
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
        $orders_stmt->bind_param('i', $store_id);
        $orders_stmt->execute();
        $orders_result = $orders_stmt->get_result();
        while ($row = $orders_result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
}

// تحديث بيانات المتجر
if (isset($_POST['update_store'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // التحقق من البيانات
    if (empty($name) || empty($email) || empty($phone)) {
        $_SESSION['error'] = 'جميع الحقول المطلوبة يجب ملؤها';
    } else {
        // معالجة تحميل الشعار إذا تم تقديمه
        $logo_path = $store['logo'] ?? ''; // الاحتفاظ بالشعار الحالي افتراضيًا
        
        if (!empty($_FILES['logo']['name'])) {
            $upload_dir = '../uploads/stores/';
            
            // التأكد من وجود المجلد
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $new_filename = 'store_' . $store_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;
            
            // التحقق من نوع الملف
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($file_extension), $allowed_types)) {
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                    $logo_path = $target_file;
                } else {
                    $_SESSION['error'] = 'حدث خطأ أثناء تحميل الشعار';
                }
            } else {
                $_SESSION['error'] = 'نوع الملف غير مسموح به. الأنواع المسموح بها: ' . implode(', ', $allowed_types);
            }
        }
        
        // تحديث بيانات المتجر في قاعدة البيانات
        if (!isset($_SESSION['error'])) {
            $update_query = "UPDATE stores SET 
                            name = ?, 
                            email = ?, 
                            phone = ?, 
                            address = ?, 
                            city = ?, 
                            description = ?";
            
            $params = [$name, $email, $phone, $address, $city, $description];
            $types = 'ssssss';
            
            // إضافة الشعار إلى الاستعلام إذا تم تحديثه
            if (!empty($logo_path)) {
                $update_query .= ", logo = ?";
                $params[] = $logo_path;
                $types .= 's';
            }
            
            $update_query .= " WHERE id = ?";
            $params[] = $store_id;
            $types .= 'i';
            
            $stmt = $conn->prepare($update_query);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $_SESSION['success'] = 'تم تحديث بيانات المتجر بنجاح';
                    
                    // تحديث بيانات المتجر في المتغير
                    $store['name'] = $name;
                    $store['email'] = $email;
                    $store['phone'] = $phone;
                    $store['address'] = $address;
                    $store['city'] = $city;
                    $store['description'] = $description;
                    if (!empty($logo_path)) {
                        $store['logo'] = $logo_path;
                    }
                } else {
                    $_SESSION['error'] = 'حدث خطأ أثناء تحديث بيانات المتجر: ' . $stmt->error;
                }
            } else {
                $_SESSION['error'] = 'حدث خطأ في استعلام تحديث المتجر: ' . $conn->error;
            }
        }
    }
}

// تغيير حالة المتجر (تفعيل/تعطيل)
if (isset($_POST['toggle_status'])) {
    $new_status = ($store['status'] == 'active') ? 'suspended' : 'active';
    $update_query = "UPDATE stores SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('si', $new_status, $store_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = ($new_status == 'active') ? 'تم تفعيل المتجر بنجاح' : 'تم تعليق المتجر بنجاح';
        // تحديث حالة المتجر في المتغير
        $store['status'] = $new_status;
    } else {
        $_SESSION['error'] = 'حدث خطأ أثناء تحديث حالة المتجر';
    }
}

// حذف المتجر
if (isset($_POST['delete_store'])) {
    // التحقق من وجود منتجات مرتبطة بالمتجر
    $check_products = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE store_id = ?");
    $check_products->bind_param('i', $store_id);
    $check_products->execute();
    $result = $check_products->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        $_SESSION['error'] = 'لا يمكن حذف المتجر لأنه يحتوي على منتجات. قم بحذف المنتجات أولاً.';
    } else {
        $delete_query = "DELETE FROM stores WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('i', $store_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'تم حذف المتجر بنجاح';
            header('Location: stores.php');
            exit;
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء حذف المتجر';
        }
    }
}

// الحصول على رمز العملة
$currency_symbol = 'ر.س'; // افتراضي: ريال سعودي
$currency_query = "SELECT value FROM settings WHERE name = 'currency'";
try {
    $currency_result = $conn->query($currency_query);
    if ($currency_result && $currency_result->num_rows > 0) {
        $currency = $currency_result->fetch_assoc()['value'];
        switch ($currency) {
            case 'USD':
                $currency_symbol = '$';
                break;
            case 'EUR':
                $currency_symbol = '€';
                break;
            case 'GBP':
                $currency_symbol = '£';
                break;
            case 'YER':
                $currency_symbol = 'ر.ي';
                break;
            default:
                $currency_symbol = 'ر.س'; // ريال سعودي
        }
    }
} catch (Exception $e) {
    // استخدام القيمة الافتراضية في حالة حدوث خطأ
    error_log('خطأ في استرجاع رمز العملة: ' . $e->getMessage());
}

// بدء محتوى الصفحة
include 'admin_header.php';
include 'admin_navbar.php';
?>

<div class="container-fluid">
    <!-- عنوان الصفحة -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas <?php echo $page_icon; ?> me-1"></i> <?php echo $page_title; ?>
        </h1>
        <a href="stores.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-right me-1"></i> العودة إلى قائمة المتاجر
        </a>
    </div>

    <?php include 'alert_messages.php'; ?>

    <!-- بطاقة معلومات المتجر -->
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">معلومات المتجر</h6>
                    <span class="badge <?php echo $store['status'] == 'active' ? 'bg-success' : ($store['status'] == 'pending' ? 'bg-warning' : 'bg-danger'); ?>">
                        <?php 
                        switch($store['status']) {
                            case 'active':
                                echo 'مفعل';
                                break;
                            case 'pending':
                                echo 'قيد المراجعة';
                                break;
                            case 'suspended':
                                echo 'معلق';
                                break;
                            default:
                                echo $store['status'];
                        }
                        ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <?php if (!empty($store['logo'])): ?>
                            <img src="<?php echo htmlspecialchars($store['logo']); ?>" alt="<?php echo htmlspecialchars($store['name']); ?>" class="img-fluid store-logo mb-2" style="max-height: 100px;">
                        <?php else: ?>
                            <div class="store-logo-placeholder mb-2">
                                <i class="fas fa-store fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        <h5><?php echo htmlspecialchars($store['name']); ?></h5>
                    </div>

                    <div class="store-info">
                        <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($store['email']); ?></p>
                        <p><strong>الهاتف:</strong> <?php echo htmlspecialchars($store['phone']); ?></p>
                        <p><strong>العنوان:</strong> <?php echo htmlspecialchars($store['address'] ?? 'غير محدد'); ?></p>
                        <p><strong>المدينة:</strong> <?php echo htmlspecialchars($store['city'] ?? 'غير محدد'); ?></p>
                        
                        <?php if (isset($store['owner_name'])): ?>
                        <hr>
                        <h6 class="font-weight-bold">معلومات المالك</h6>
                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($store['owner_name']); ?></p>
                        <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($store['owner_email']); ?></p>
                        <p><strong>الهاتف:</strong> <?php echo htmlspecialchars($store['owner_phone']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <form method="post" class="d-inline">
                            <button type="submit" name="toggle_status" class="btn <?php echo $store['status'] == 'active' ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                                <i class="fas <?php echo $store['status'] == 'active' ? 'fa-ban' : 'fa-check'; ?> me-1"></i>
                                <?php echo $store['status'] == 'active' ? 'تعليق المتجر' : 'تفعيل المتجر'; ?>
                            </button>
                        </form>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editStoreModal">
                            <i class="fas fa-edit me-1"></i> تعديل
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteStoreModal">
                            <i class="fas fa-trash me-1"></i> حذف
                        </button>
                    </div>
                </div>
            </div>

            <!-- بطاقة إحصائيات المتجر -->
            <div class="card shadow mt-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">إحصائيات المتجر</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($store['product_count']); ?></div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">المنتجات</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($store['order_count']); ?></div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">الطلبات</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($store['total_sales'], 2) . ' ' . $currency_symbol; ?></div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">المبيعات</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- بطاقة منتجات المتجر -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">منتجات المتجر</h6>
                    <a href="products.php?store_id=<?php echo $store_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-list me-1"></i> عرض كل المنتجات
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($products)): ?>
                        <div class="alert alert-info">لا توجد منتجات لهذا المتجر.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>الصورة</th>
                                        <th>الاسم</th>
                                        <th>التصنيف</th>
                                        <th>السعر</th>
                                        <th>المخزون</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($product['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-thumbnail" style="max-width: 50px;">
                                            <?php else: ?>
                                                <div class="product-image-placeholder">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'بدون تصنيف'); ?></td>
                                        <td><?php echo number_format($product['price'], 2) . ' ' . $currency_symbol; ?></td>
                                        <td><?php echo isset($product['stock_quantity']) ? number_format($product['stock_quantity']) : 'غير متوفر'; ?></td>
                                        <td>
                                            <span class="badge <?php echo $product['status'] == 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $product['status'] == 'active' ? 'مفعل' : 'معطل'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- بطاقة طلبات المتجر -->
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">طلبات المتجر</h6>
                    <a href="orders.php?store_id=<?php echo $store_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-list me-1"></i> عرض كل الطلبات
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info">لا توجد طلبات لهذا المتجر.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>رقم الطلب</th>
                                        <th>العميل</th>
                                        <th>المبلغ</th>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name'] ?? 'غير معروف'); ?></td>
                                        <td><?php echo number_format($order['total_amount'], 2) . ' ' . $currency_symbol; ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                switch($order['status']) {
                                                    case 'pending': echo 'bg-warning'; break;
                                                    case 'processing': echo 'bg-info'; break;
                                                    case 'shipped': echo 'bg-primary'; break;
                                                    case 'delivered': echo 'bg-success'; break;
                                                    case 'cancelled': echo 'bg-danger'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                                ?>">
                                                <?php 
                                                switch($order['status']) {
                                                    case 'pending': echo 'قيد الانتظار'; break;
                                                    case 'processing': echo 'قيد المعالجة'; break;
                                                    case 'shipped': echo 'تم الشحن'; break;
                                                    case 'delivered': echo 'تم التسليم'; break;
                                                    case 'cancelled': echo 'ملغي'; break;
                                                    default: echo $order['status'];
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal تعديل المتجر -->
<div class="modal fade" id="editStoreModal" tabindex="-1" aria-labelledby="editStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStoreModalLabel">تعديل المتجر</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">اسم المتجر</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($store['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($store['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">رقم الهاتف</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($store['phone']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">العنوان</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($store['address'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="city" class="form-label">المدينة</label>
                        <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($store['city'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="logo" class="form-label">شعار المتجر</label>
                        <input type="file" class="form-control" id="logo" name="logo">
                        <small class="form-text text-muted">اترك هذا الحقل فارغًا إذا كنت لا ترغب في تغيير الشعار الحالي.</small>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">وصف المتجر</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($store['description'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_store" class="btn btn-primary">حفظ التغييرات</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal حذف المتجر -->
<div class="modal fade" id="deleteStoreModal" tabindex="-1" aria-labelledby="deleteStoreModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteStoreModalLabel">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>هل أنت متأكد من رغبتك في حذف المتجر "<?php echo htmlspecialchars($store['name']); ?>"؟</p>
                <p class="text-danger">تحذير: هذا الإجراء لا يمكن التراجع عنه. سيتم حذف المتجر نهائيًا.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <form method="post">
                    <button type="submit" name="delete_store" class="btn btn-danger">حذف المتجر</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
