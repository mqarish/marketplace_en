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
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .store-logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 1rem;
        }
        .stats-card {
            text-align: center;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .stats-card h4 {
            color: #4e73df;
            margin-bottom: 0.5rem;
        }
        .stats-card p {
            color: #858796;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
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

        <div class="row">
            <!-- معلومات المتجر -->
            <div class="col-xl-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">معلومات المتجر</h6>
                        <span class="badge <?php echo $store['status'] == 'active' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $store['status'] == 'active' ? 'نشط' : 'معلق'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($store['logo'])): ?>
                            <div class="text-center mb-3">
                                <img src="<?php echo htmlspecialchars($store['logo']); ?>" alt="شعار المتجر" class="store-logo">
                            </div>
                        <?php endif; ?>
                        
                        <h5 class="card-title text-center mb-3"><?php echo htmlspecialchars($store['name']); ?></h5>
                        
                        <div class="list-group list-group-flush">
                            <div class="list-group-item">
                                <strong>البريد الإلكتروني:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($store['email']); ?></p>
                            </div>
                            <div class="list-group-item">
                                <strong>رقم الهاتف:</strong>
                                <p class="mb-0"><?php echo htmlspecialchars($store['phone']); ?></p>
                            </div>
                            <?php if (!empty($store['address'])): ?>
                                <div class="list-group-item">
                                    <strong>العنوان:</strong>
                                    <p class="mb-0"><?php echo htmlspecialchars($store['address']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3">
                            <button class="btn btn-primary btn-block mb-2" data-bs-toggle="modal" data-bs-target="#editStoreModal">
                                <i class="fas fa-edit me-1"></i> تعديل المتجر
                            </button>
                            <button class="btn btn-<?php echo $store['status'] == 'active' ? 'warning' : 'success'; ?> btn-block mb-2" onclick="toggleStoreStatus()">
                                <i class="fas <?php echo $store['status'] == 'active' ? 'fa-ban' : 'fa-check'; ?> me-1"></i>
                                <?php echo $store['status'] == 'active' ? 'تعليق المتجر' : 'تفعيل المتجر'; ?>
                            </button>
                            <button class="btn btn-danger btn-block" data-bs-toggle="modal" data-bs-target="#deleteStoreModal">
                                <i class="fas fa-trash me-1"></i> حذف المتجر
                            </button>
                        </div>
                    </div>
                </div>

                <!-- إحصائيات المتجر -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">إحصائيات المتجر</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <h4><?php echo number_format($store['product_count']); ?></h4>
                                    <p>المنتجات</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <h4><?php echo number_format($store['order_count']); ?></h4>
                                    <p>الطلبات</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <h4><?php echo number_format($store['total_sales'], 2); ?></h4>
                                    <p>المبيعات</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المنتجات والطلبات -->
            <div class="col-xl-8">
                <!-- قائمة المنتجات -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">منتجات المتجر</h6>
                        <a href="products.php?store_id=<?php echo $store_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> إضافة منتج جديد
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
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
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($product['image'])): ?>
                                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="" class="me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php endif; ?>
                                                <div>
                                                    <div class="font-weight-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($product['category_name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo $product['stock']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $product['status'] == 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $product['status'] == 'active' ? 'نشط' : 'معطل'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- قائمة الطلبات -->
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">طلبات المتجر</h6>
                        <a href="orders.php?store_id=<?php echo $store_id; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-list me-1"></i> عرض كل الطلبات
                        </a>
                    </div>
                    <div class="card-body">
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
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $order['status'] == 'completed' ? 'bg-success' : 
                                                    ($order['status'] == 'processing' ? 'bg-primary' : 
                                                        ($order['status'] == 'cancelled' ? 'bg-danger' : 'bg-warning')); 
                                            ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y/m/d', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
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
                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($store['address']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="logo" class="form-label">شعار المتجر</label>
                            <input type="file" class="form-control" id="logo" name="logo">
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

    <!-- Modal حذف المتجر -->
    <div class="modal fade" id="deleteStoreModal" tabindex="-1" aria-labelledby="deleteStoreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteStoreModalLabel">حذف المتجر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف المتجر "<?php echo htmlspecialchars($store['name']); ?>"؟</p>
                    <p class="text-danger">تحذير: هذا الإجراء لا يمكن التراجع عنه.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <form action="" method="post" style="display: inline;">
                        <button type="submit" name="delete_store" class="btn btn-danger">حذف المتجر</button>
                    </form>
                </div>
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
                form.innerHTML = '<input type="hidden" name="toggle_status" value="1">';
                document.body.appendChild(form);
                form.submit();
            }
        }

        // حذف منتج
        function deleteProduct(productId) {
            if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_product" value="${productId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
