<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'تفاصيل المتجر';
$page_icon = 'fa-store';

// التحقق من وجود معرف المتجر
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'معرف المتجر غير صالح';
    header('Location: stores.php');
    exit;
}

$store_id = (int)$_GET['id'];

// التحقق من وجود الجداول وإنشائها إذا لم تكن موجودة
$check_orders_table = $conn->query("SHOW TABLES LIKE 'orders'");
$check_order_items_table = $conn->query("SHOW TABLES LIKE 'order_items'");

if ($check_orders_table->num_rows == 0) {
    $create_orders_table = "CREATE TABLE orders (
        id INT(11) NOT NULL AUTO_INCREMENT,
        order_number VARCHAR(50) NOT NULL,
        user_id INT(11) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY (order_number),
        KEY (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($create_orders_table);
}

if ($check_order_items_table->num_rows == 0) {
    $create_order_items_table = "CREATE TABLE order_items (
        id INT(11) NOT NULL AUTO_INCREMENT,
        order_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY (order_id),
        KEY (product_id),
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->query($create_order_items_table);
}

// استرجاع بيانات المتجر
$query = "SELECT *, 
    CASE 
        WHEN status = 'pending' THEN 'قيد المراجعة'
        WHEN status = 'active' THEN 'نشط'
        WHEN status = 'suspended' THEN 'معلق'
        ELSE status 
    END as status_text
    FROM stores WHERE id = ?";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('i', $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $store = $result->fetch_assoc();
    
    if (!$store) {
        $_SESSION['error'] = 'المتجر غير موجود';
        header('Location: stores.php');
        exit;
    }
} else {
    $_SESSION['error'] = 'حدث خطأ في استرجاع بيانات المتجر';
    header('Location: stores.php');
    exit;
}

// استرجاع عدد المنتجات
$products_count_query = "SELECT COUNT(*) as count FROM products WHERE store_id = ?";
$products_count_stmt = $conn->prepare($products_count_query);
if ($products_count_stmt) {
    $products_count_stmt->bind_param('i', $store_id);
    $products_count_stmt->execute();
    $products_count_result = $products_count_stmt->get_result();
    $products_count_data = $products_count_result->fetch_assoc();
    $store['product_count'] = $products_count_data['count'];
} else {
    $store['product_count'] = 0;
}

// استرجاع إحصائيات الطلبات
$store['order_count'] = 0;
$store['total_sales'] = 0;

if ($check_orders_table->num_rows > 0 && $check_order_items_table->num_rows > 0) {
    // عدد الطلبات
    $order_count_query = "SELECT COUNT(DISTINCT o.id) as order_count 
                         FROM orders o 
                         JOIN order_items oi ON o.id = oi.order_id 
                         JOIN products p ON oi.product_id = p.id 
                         WHERE p.store_id = ?";
    
    try {
        $order_count_stmt = $conn->prepare($order_count_query);
        if ($order_count_stmt) {
            $order_count_stmt->bind_param('i', $store_id);
            $order_count_stmt->execute();
            $order_count_result = $order_count_stmt->get_result();
            $order_count_data = $order_count_result->fetch_assoc();
            $store['order_count'] = $order_count_data['order_count'];
        }
    } catch (Exception $e) {
        error_log('Error fetching order count: ' . $e->getMessage());
    }
    
    // إجمالي المبيعات
    $sales_query = "SELECT SUM(oi.price * oi.quantity) as total_sales 
                   FROM orders o 
                   JOIN order_items oi ON o.id = oi.order_id 
                   JOIN products p ON oi.product_id = p.id 
                   WHERE p.store_id = ? AND o.status != 'cancelled'";
    
    try {
        $sales_stmt = $conn->prepare($sales_query);
        if ($sales_stmt) {
            $sales_stmt->bind_param('i', $store_id);
            $sales_stmt->execute();
            $sales_result = $sales_stmt->get_result();
            $sales_data = $sales_result->fetch_assoc();
            $store['total_sales'] = $sales_data['total_sales'] ?: 0;
        }
    } catch (Exception $e) {
        error_log('Error fetching total sales: ' . $e->getMessage());
    }
}

// استرجاع الطلبات المرتبطة بالمتجر
$orders = [];
if ($check_orders_table->num_rows > 0 && $check_order_items_table->num_rows > 0) {
    $orders_query = "SELECT DISTINCT o.*, u.name as customer_name,
                   GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as products_list
                   FROM orders o
                   JOIN order_items oi ON o.id = oi.order_id
                   JOIN products p ON oi.product_id = p.id
                   LEFT JOIN users u ON o.user_id = u.id
                   WHERE p.store_id = ?
                   GROUP BY o.id
                   ORDER BY o.created_at DESC
                   LIMIT 10";
    
    try {
        $orders_stmt = $conn->prepare($orders_query);
        if ($orders_stmt) {
            $orders_stmt->bind_param('i', $store_id);
            $orders_stmt->execute();
            $orders_result = $orders_stmt->get_result();
            while ($row = $orders_result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log('Error fetching orders: ' . $e->getMessage());
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
        }
    }
} catch (Exception $e) {
    error_log('Error fetching currency: ' . $e->getMessage());
}

// استرجاع بيانات المالك
if (!empty($store['user_id'])) {
    $owner_query = "SELECT name as owner_name, phone as owner_phone FROM users WHERE id = ?";
    try {
        $owner_stmt = $conn->prepare($owner_query);
        if ($owner_stmt) {
            $owner_stmt->bind_param('i', $store['user_id']);
            $owner_stmt->execute();
            $owner_result = $owner_stmt->get_result();
            if ($owner_data = $owner_result->fetch_assoc()) {
                $store['owner_name'] = $owner_data['owner_name'];
                $store['owner_phone'] = $owner_data['owner_phone'];
            }
        }
    } catch (Exception $e) {
        error_log('Error fetching owner details: ' . $e->getMessage());
    }
}

// استرجاع المنتجات
$products = [];
$products_query = "SELECT p.*, c.name as category_name
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.store_id = ?
                  ORDER BY p.created_at DESC";

try {
    $products_stmt = $conn->prepare($products_query);
    if ($products_stmt) {
        $products_stmt->bind_param('i', $store_id);
        $products_stmt->execute();
        $products_result = $products_stmt->get_result();
        while ($row = $products_result->fetch_assoc()) {
            $products[] = $row;
        }
    }
} catch (Exception $e) {
    error_log('Error fetching products: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .border-left-primary { border-left: 4px solid #4e73df !important; }
        .border-left-success { border-left: 4px solid #1cc88a !important; }
        .border-left-info { border-left: 4px solid #36b9cc !important; }
        .text-gray-300 { color: #dddfeb !important; }
        .text-gray-800 { color: #5a5c69 !important; }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <!-- عنوان الصفحة -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas <?php echo $page_icon; ?> me-1"></i> <?php echo $page_title; ?>
            </h1>
        </div>

        <?php include 'alert_messages.php'; ?>

        <div class="row">
            <!-- معلومات المتجر -->
            <div class="col-xl-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">معلومات المتجر</h6>
                        <div>
                            <button type="button" class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editStoreModal">
                                <i class="fas fa-edit"></i> تعديل
                            </button>
                            <button type="button" class="btn <?php echo $store['status'] == 'active' ? 'btn-danger' : 'btn-success'; ?> btn-sm" onclick="toggleStoreStatus()">
                                <i class="fas <?php echo $store['status'] == 'active' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                <?php echo $store['status'] == 'active' ? 'تعطيل' : 'تفعيل'; ?>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- بطاقات الإحصائيات -->
                        <div class="row mb-4">
                            <div class="col-xl-4 col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col me-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">المنتجات</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($store['product_count']); ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-box fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6 mb-4">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col me-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">الطلبات</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($store['order_count']); ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6 mb-4">
                                <div class="card border-left-info shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col me-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">المبيعات</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($store['total_sales'], 2) . ' ' . $currency_symbol; ?></div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- معلومات المتجر الأساسية -->
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>اسم المتجر:</strong> <?php echo htmlspecialchars($store['name']); ?></p>
                                <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($store['email']); ?></p>
                                <p><strong>رقم الهاتف:</strong> <?php echo htmlspecialchars($store['phone']); ?></p>
                                <p><strong>العنوان:</strong> <?php echo htmlspecialchars($store['address']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>الحالة:</strong> 
                                    <span class="badge <?php 
                                        echo match($store['status']) {
                                            'active' => 'bg-success',
                                            'pending' => 'bg-warning',
                                            'suspended' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                    ?>">
                                        <?php echo htmlspecialchars($store['status_text']); ?>
                                    </span>
                                </p>
                                <p><strong>تاريخ الإنشاء:</strong> <?php echo date('Y-m-d', strtotime($store['created_at'])); ?></p>
                                <?php if (!empty($store['owner_name'])): ?>
                                <p><strong>اسم المالك:</strong> <?php echo htmlspecialchars($store['owner_name']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($store['owner_phone'])): ?>
                                <p><strong>رقم هاتف المالك:</strong> <?php echo htmlspecialchars($store['owner_phone']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <!-- قائمة المنتجات -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">منتجات المتجر</h6>
                        <a href="products.php?store_id=<?php echo $store_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-list me-1"></i> عرض كل المنتجات
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle mb-2" style="font-size: 2rem;"></i>
                                <p class="mb-0">لا توجد منتجات لهذا المتجر حالياً</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>المنتج</th>
                                            <th>القسم</th>
                                            <th>السعر</th>
                                            <th>الحالة</th>
                                            <th>التاريخ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name'] ?? 'بدون قسم'); ?></td>
                                            <td><?php echo number_format($product['price'], 2) . ' ' . $currency_symbol; ?></td>
                                            <td>
                                                <span class="badge <?php echo $product['status'] == 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $product['status'] == 'active' ? 'نشط' : 'غير نشط'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- طلبات المتجر -->
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">طلبات المتجر</h6>
                        <a href="orders.php?store_id=<?php echo $store_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-list me-1"></i> عرض كل الطلبات
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle mb-2" style="font-size: 2rem;"></i>
                                <p class="mb-0">لا توجد طلبات لهذا المتجر حالياً</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>رقم الطلب</th>
                                            <th>العميل</th>
                                            <th>المنتجات</th>
                                            <th>المبلغ</th>
                                            <th>الحالة</th>
                                            <th>التاريخ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name'] ?? 'غير معروف'); ?></td>
                                            <td>
                                                <small><?php echo htmlspecialchars($order['products_list'] ?? 'لا توجد منتجات'); ?></small>
                                            </td>
                                            <td><?php echo number_format($order['total_amount'], 2) . ' ' . $currency_symbol; ?></td>
                                            <td>
                                                <span class="badge <?php 
                                                    echo match($order['status']) {
                                                        'pending' => 'bg-warning',
                                                        'processing' => 'bg-info',
                                                        'shipped' => 'bg-primary',
                                                        'delivered' => 'bg-success',
                                                        'cancelled' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                ?>">
                                                    <?php 
                                                    echo match($order['status']) {
                                                        'pending' => 'قيد الانتظار',
                                                        'processing' => 'قيد المعالجة',
                                                        'shipped' => 'تم الشحن',
                                                        'delivered' => 'تم التوصيل',
                                                        'cancelled' => 'ملغي',
                                                        default => 'غير معروف'
                                                    };
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
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
                    <h5 class="modal-title" id="editStoreModalLabel">تعديل بيانات المتجر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="store_id" value="<?php echo $store_id; ?>">
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
                            <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($store['address']); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="update_store" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // تفعيل/تعطيل المتجر
        function toggleStoreStatus() {
            if (confirm('هل أنت متأكد من تغيير حالة المتجر؟')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="store_id" value="<?php echo $store_id; ?>">
                    <input type="hidden" name="toggle_status" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
