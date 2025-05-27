<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/init.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'store') {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

try {
    // جلب بيانات المتجر
    $store_sql = "SELECT * FROM stores WHERE user_id = ? LIMIT 1";
    $stmt = $conn->prepare($store_sql);
    if (!$stmt) {
        throw new Exception("خطأ في إعداد الاستعلام: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("خطأ في تنفيذ الاستعلام: " . $stmt->error);
    }

    $store = $stmt->get_result()->fetch_assoc();
    if (!$store) {
        throw new Exception("لم يتم العثور على المتجر المرتبط بحسابك");
    }

    $store_id = (int)$store['id'];
    $_SESSION['store_id'] = $store_id;
    $_SESSION['store_name'] = htmlspecialchars($store['name']);

    // جلب إحصائيات المنتجات
    $stats = [
        'total_products' => 0,
        'new_products' => 0,
        'low_stock' => 0,
        'avg_price' => 0
    ];

    // إجمالي المنتجات
    $total_sql = "SELECT COUNT(*) as count FROM products WHERE store_id = ? AND status = 'active'";
    $stmt = $conn->prepare($total_sql);
    if ($stmt) {
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stats['total_products'] = (int)$result['count'];
    }

    // المنتجات الجديدة
    $new_sql = "SELECT COUNT(*) as count FROM products WHERE store_id = ? AND status = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    $stmt = $conn->prepare($new_sql);
    if ($stmt) {
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stats['new_products'] = (int)$result['count'];
    }

    // متوسط السعر
    $avg_sql = "SELECT AVG(price) as avg_price FROM products WHERE store_id = ? AND status = 'active'";
    $stmt = $conn->prepare($avg_sql);
    if ($stmt) {
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stats['avg_price'] = round($result['avg_price'] ?? 0, 2);
    }

    // جلب آخر المنتجات
    $products = [];
    $products_sql = "SELECT * FROM products WHERE store_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->prepare($products_sql);
    if ($stmt) {
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($product = $result->fetch_assoc()) {
            $products[] = $product;
        }
    }

} catch (Exception $e) {
    die($e->getMessage());
}

// تنسيق السعر
function formatPrice($price) {
    return number_format($price, 2) . ' ريال';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?php echo htmlspecialchars($_SESSION['store_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">القائمة</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action active">لوحة التحكم</a>
                        <a href="products.php" class="list-group-item list-group-item-action">المنتجات</a>
                        <a href="offers.php" class="list-group-item list-group-item-action">العروض</a>
                        <a href="add-product.php" class="list-group-item list-group-item-action">إضافة منتج</a>
                        <a href="profile.php" class="list-group-item list-group-item-action">الملف الشخصي</a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Statistics Cards -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">إحصائيات المتجر</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="border rounded p-3 text-center">
                                    <i class="fas fa-box-open fa-2x mb-2 text-primary"></i>
                                    <h5>إجمالي المنتجات</h5>
                                    <h3 class="mb-0"><?php echo $stats['total_products']; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="border rounded p-3 text-center">
                                    <i class="fas fa-star fa-2x mb-2 text-warning"></i>
                                    <h5>المنتجات الجديدة</h5>
                                    <h3 class="mb-0"><?php echo $stats['new_products']; ?></h3>
                                    <small class="text-muted">خلال 7 أيام</small>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="border rounded p-3 text-center">
                                    <i class="fas fa-tag fa-2x mb-2 text-success"></i>
                                    <h5>متوسط السعر</h5>
                                    <h3 class="mb-0"><?php echo formatPrice($stats['avg_price']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Latest Products -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">آخر المنتجات</h5>
                        <a href="products.php" class="btn btn-primary btn-sm">عرض كل المنتجات</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <div class="alert alert-info text-center">
                                لا توجد منتجات حالياً
                                <br>
                                <a href="add-product.php" class="btn btn-primary btn-sm mt-2">إضافة منتج جديد</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>الصورة</th>
                                            <th>المنتج</th>
                                            <th>السعر</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الإضافة</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])): ?>
                                                        <img src="../<?php echo $product['image_url']; ?>" alt="صورة المنتج" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <img src="../assets/images/product-placeholder.jpg" alt="صورة افتراضية" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...</small>
                                                </td>
                                                <td><?php echo formatPrice($product['price']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $product['status'] == 'active' ? 'success' : 'warning'; ?>">
                                                        <?php echo $product['status'] == 'active' ? 'نشط' : 'غير نشط'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">تعديل</a>
                                                        <button onclick="deleteProduct(<?php echo $product['id']; ?>)" class="btn btn-sm btn-danger">حذف</button>
                                                    </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function deleteProduct(productId) {
        if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
            fetch('delete-product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم حذف المنتج بنجاح');
                    location.reload();
                } else {
                    alert('حدث خطأ: ' + data.message);
                }
            })
            .catch(error => {
                alert('حدث خطأ أثناء الحذف');
            });
        }
    }
    </script>
</body>
</html>
