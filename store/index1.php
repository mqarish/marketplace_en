<?php
// التأكد من عدم وجود مخرجات قبل بدء الجلسة
require_once '../includes/init.php';

// التحقق من تسجيل دخول المتجر
if (!isset($_SESSION['store_id'])) {
    header('Location: login.php');
    exit();
}

// جلب بيانات المتجر
$store_id = $_SESSION['store_id'];
$stmt = $conn->prepare("SELECT * FROM stores WHERE id = ?");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$store = $stmt->get_result()->fetch_assoc();

// جلب المنتجات
$products_stmt = $conn->prepare("SELECT * FROM products WHERE store_id = ? ORDER BY created_at DESC");
$products_stmt->bind_param("i", $store_id);
$products_stmt->execute();
$products = $products_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// جلب الطلبات
$orders_stmt = $conn->prepare("
    SELECT o.*, c.name as customer_name 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE o.store_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$orders_stmt->bind_param("i", $store_id);
$orders_stmt->execute();
$recent_orders = $orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?php echo htmlspecialchars($store['name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .sidebar .list-group-item.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>
    
    <div class="container-fluid px-4 py-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- القائمة الجانبية -->
            <div class="col-md-3 sidebar">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">القائمة الرئيسية</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="index.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-speedometer2 me-2"></i>لوحة التحكم
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-person me-2"></i>الملف الشخصي
                        </a>
                        <a href="products.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-box me-2"></i>المنتجات
                        </a>
                        <a href="add-product.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-plus-circle me-2"></i>إضافة منتج
                        </a>
                        <a href="offers.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-tag me-2"></i>العروض
                        </a>
                        <a href="add-offer.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-plus-circle me-2"></i>إضافة عرض
                        </a>
                        <a href="orders.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-cart me-2"></i>الطلبات
                        </a>
                    </div>
                </div>
            </div>

            <!-- المحتوى الرئيسي -->
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <img src="<?php echo !empty($store['logo']) ? '../uploads/stores/' . $store['logo'] : '../assets/images/store-placeholder.jpg'; ?>" 
                                         alt="شعار المتجر" 
                                         class="img-fluid rounded-circle mb-3" 
                                         style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #0d6efd;">
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateLogoModal">
                                            <i class="bi bi-camera"></i> تغيير الشعار
                                        </button>
                                    </div>
                                </div>
                                <h5 class="card-title">معلومات المتجر</h5>
                                <p class="card-text">
                                    <strong>الاسم:</strong> <?php echo htmlspecialchars($store['name']); ?><br>
                                    <strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($store['email']); ?><br>
                                    <strong>العنوان:</strong> <?php echo htmlspecialchars($store['city']); ?><br>
                                    <strong>الحالة:</strong> <?php echo $store['status'] === 'active' ? 'نشط' : 'معلق'; ?><br>
                                    <strong>رابط المتجر:</strong>
                                    <div class="input-group mb-2">
                                        <input type="text" class="form-control" id="storeUrl" value="<?php echo htmlspecialchars($store['slug'] ?? ''); ?>" readonly>
                                        <button class="btn btn-outline-primary" type="button" onclick="copyStoreUrl()">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                    <button class="btn btn-outline-secondary btn-sm w-100" type="button" data-bs-toggle="modal" data-bs-target="#editUrlModal">
                                        <i class="bi bi-pencil"></i> تعديل الرابط
                                    </button>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title">المنتجات الأخيرة</h5>
                                    <a href="products.php" class="btn btn-primary">إدارة المنتجات</a>
                                </div>
                                <?php if (!empty($products)): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>المنتج</th>
                                                    <th>السعر</th>
                                                    <th>الحالة</th>
                                                    <th>تاريخ الإضافة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($products, 0, 5) as $product): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                        <td><?php echo number_format($product['price'], 2); ?> ريال</td>
                                                        <td>
                                                            <?php if ($product['status'] === 'active'): ?>
                                                                <span class="badge bg-success">نشط</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">غير نشط</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">لا توجد منتجات حالياً</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title">الطلبات الأخيرة</h5>
                                    <a href="orders.php" class="btn btn-primary">عرض جميع الطلبات</a>
                                </div>
                                <?php if (!empty($recent_orders)): ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>رقم الطلب</th>
                                                    <th>العميل</th>
                                                    <th>الحالة</th>
                                                    <th>التاريخ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_orders as $order): ?>
                                                    <tr>
                                                        <td>#<?php echo $order['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                        <td>
                                                            <?php
                                                            $status_class = '';
                                                            $status_text = '';
                                                            switch ($order['status']) {
                                                                case 'pending':
                                                                    $status_class = 'bg-warning';
                                                                    $status_text = 'قيد الانتظار';
                                                                    break;
                                                                case 'processing':
                                                                    $status_class = 'bg-info';
                                                                    $status_text = 'قيد المعالجة';
                                                                    break;
                                                                case 'completed':
                                                                    $status_class = 'bg-success';
                                                                    $status_text = 'مكتمل';
                                                                    break;
                                                                case 'cancelled':
                                                                    $status_class = 'bg-danger';
                                                                    $status_text = 'ملغي';
                                                                    break;
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                        </td>
                                                        <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">لا توجد طلبات حالياً</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for editing store URL -->
    <div class="modal fade" id="editUrlModal" tabindex="-1" aria-labelledby="editUrlModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUrlModalLabel">تعديل رابط المتجر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newSlug" class="form-label">الرابط الجديد</label>
                        <div class="input-group">
                            <span class="input-group-text" dir="ltr">/store/</span>
                            <input type="text" class="form-control" id="newSlug" value="<?php echo htmlspecialchars($store['slug'] ?? ''); ?>" dir="ltr">
                        </div>
                        <div class="form-text">يجب أن يحتوي الرابط على أحرف إنجليزية وأرقام وشرطات فقط</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="updateStoreUrl()">حفظ التغييرات</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for updating store logo -->
    <div class="modal fade" id="updateLogoModal" tabindex="-1" aria-labelledby="updateLogoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateLogoModalLabel">تغيير شعار المتجر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateLogoForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="storeLogo" class="form-label">اختر صورة الشعار الجديدة</label>
                            <input type="file" class="form-control" id="storeLogo" name="logo" accept="image/*" required>
                            <div class="form-text">يجب أن تكون الصورة بصيغة JPG أو PNG أو GIF وحجم لا يتجاوز 5 ميجابايت</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="updateStoreLogo()">حفظ التغييرات</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function copyStoreUrl() {
        var urlInput = document.getElementById('storeUrl');
        urlInput.select();
        document.execCommand('copy');
        alert('تم نسخ الرابط بنجاح');
    }

    function updateStoreUrl() {
        var newSlug = document.getElementById('newSlug').value;
        
        // Validate slug format
        if (!/^[a-zA-Z0-9-]+$/.test(newSlug)) {
            alert('الرجاء استخدام أحرف إنجليزية وأرقام وشرطات فقط');
            return;
        }

        $.ajax({
            url: 'update_store_url.php',
            method: 'POST',
            data: {
                slug: newSlug
            },
            success: function(response) {
                try {
                    response = typeof response === 'string' ? JSON.parse(response) : response;
                    if (response.success) {
                        document.getElementById('storeUrl').value = newSlug;
                        $('#editUrlModal').modal('hide');
                        alert('تم تحديث رابط المتجر بنجاح');
                        location.reload(); // Refresh the page to update all instances of the URL
                    } else {
                        alert(response.message || 'حدث خطأ أثناء تحديث الرابط');
                    }
                } catch (e) {
                    alert('حدث خطأ غير متوقع');
                }
            },
            error: function() {
                alert('حدث خطأ أثناء الاتصال بالخادم');
            }
        });
    }

    function updateStoreLogo() {
        var formData = new FormData();
        var fileInput = document.getElementById('storeLogo');
        
        if (fileInput.files.length === 0) {
            alert('الرجاء اختيار صورة');
            return;
        }

        formData.append('logo', fileInput.files[0]);

        $.ajax({
            url: 'update_store_logo.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    response = typeof response === 'string' ? JSON.parse(response) : response;
                    if (response.success) {
                        $('#updateLogoModal').modal('hide');
                        alert('تم تحديث شعار المتجر بنجاح');
                        location.reload();
                    } else {
                        alert(response.message || 'حدث خطأ أثناء تحديث الشعار');
                    }
                } catch (e) {
                    alert('حدث خطأ غير متوقع');
                }
            },
            error: function() {
                alert('حدث خطأ أثناء الاتصال بالخادم');
            }
        });
    }
    </script>
</body>
</html>