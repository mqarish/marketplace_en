<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

$page_title = 'إدارة باقات الاشتراكات';

// معالجة إضافة أو تحديث الباقة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_package']) || isset($_POST['update_package'])) {
        $name = $_POST['name'];
        $type = $_POST['type'];
        $price = $_POST['price'];
        $duration = $_POST['duration'];
        $features = $_POST['features'];
        $status = $_POST['status'] ?? 'active';
        $package_id = $_POST['package_id'] ?? null;
        
        try {
            if (isset($_POST['add_package'])) {
                $stmt = $conn->prepare("INSERT INTO subscription_packages (name, type, price, duration, features, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdiis", $name, $type, $price, $duration, $features, $status);
                $success_message = "تم إضافة الباقة بنجاح";
            } else {
                $stmt = $conn->prepare("UPDATE subscription_packages SET name = ?, type = ?, price = ?, duration = ?, features = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssdissi", $name, $type, $price, $duration, $features, $status, $package_id);
                $success_message = "تم تحديث الباقة بنجاح";
            }
            
            if ($stmt->execute()) {
                $_SESSION['success'] = $success_message;
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "حدث خطأ: " . $e->getMessage();
        }
        
        header("Location: subscription_packages.php");
        exit();
    }
}

// جلب قائمة الباقات
$packages_query = "SELECT * FROM subscription_packages ORDER BY type, price";
$packages_result = $conn->query($packages_query);
if ($packages_result === false) {
    die("خطأ في استعلام الباقات: " . $conn->error);
}
$packages = $packages_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - لوحة التحكم</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .package-card {
            transition: transform 0.2s;
        }
        .package-card:hover {
            transform: translateY(-5px);
        }
        .features-list {
            list-style: none;
            padding: 0;
        }
        .features-list li {
            margin-bottom: 0.5rem;
        }
        .features-list li i {
            color: #28a745;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
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

        <!-- نموذج إضافة باقة جديدة -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus-circle"></i> إضافة باقة جديدة
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم الباقة</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">نوع الباقة</label>
                        <select name="type" class="form-select" required>
                            <option value="store">متجر</option>
                            <option value="customer">مستخدم</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">السعر</label>
                        <div class="input-group">
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                            <span class="input-group-text">ريال</span>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">مدة الاشتراك (بالأيام)</label>
                        <input type="number" name="duration" class="form-control" min="1" value="30" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">المميزات</label>
                        <textarea name="features" class="form-control" rows="3" placeholder="أدخل كل ميزة في سطر جديد"></textarea>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select">
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
                        </select>
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" name="add_package" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إضافة الباقة
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- عرض الباقات -->
        <div class="row">
            <div class="col-12 mb-3">
                <h3>باقات المتاجر</h3>
            </div>
            <?php foreach ($packages as $package): ?>
                <?php if ($package['type'] === 'store'): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 package-card">
                            <div class="card-header <?php echo $package['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?> text-white">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($package['name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <h3 class="text-center mb-3">
                                    <?php echo number_format($package['price'], 2); ?> ريال
                                    <small class="d-block text-muted"><?php echo $package['duration']; ?> يوم</small>
                                </h3>
                                
                                <?php if (!empty($package['features'])): ?>
                                    <ul class="features-list">
                                        <?php foreach (explode("\n", $package['features']) as $feature): ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i>
                                                <?php echo htmlspecialchars($feature); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                
                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            onclick="editPackage(<?php echo htmlspecialchars(json_encode($package)); ?>)">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    <button type="submit" name="toggle_status" 
                                            class="btn <?php echo $package['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                                        <i class="fas <?php echo $package['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                        <?php echo $package['status'] === 'active' ? 'تعطيل' : 'تفعيل'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <div class="col-12 mb-3 mt-4">
                <h3>باقات المستخدمين</h3>
            </div>
            <?php foreach ($packages as $package): ?>
                <?php if ($package['type'] === 'customer'): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 package-card">
                            <div class="card-header <?php echo $package['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?> text-white">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($package['name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <h3 class="text-center mb-3">
                                    <?php echo number_format($package['price'], 2); ?> ريال
                                    <small class="d-block text-muted"><?php echo $package['duration']; ?> يوم</small>
                                </h3>
                                
                                <?php if (!empty($package['features'])): ?>
                                    <ul class="features-list">
                                        <?php foreach (explode("\n", $package['features']) as $feature): ?>
                                            <li>
                                                <i class="fas fa-check-circle"></i>
                                                <?php echo htmlspecialchars($feature); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                
                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                    <button type="button" class="btn btn-primary btn-sm" 
                                            onclick="editPackage(<?php echo htmlspecialchars(json_encode($package)); ?>)">
                                        <i class="fas fa-edit"></i> تعديل
                                    </button>
                                    <button type="submit" name="toggle_status" 
                                            class="btn <?php echo $package['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                                        <i class="fas <?php echo $package['status'] === 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                        <?php echo $package['status'] === 'active' ? 'تعطيل' : 'تفعيل'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal تعديل الباقة -->
    <div class="modal fade" id="editPackageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل الباقة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editPackageForm">
                        <input type="hidden" name="package_id" id="edit_package_id">
                        
                        <div class="mb-3">
                            <label class="form-label">اسم الباقة</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">نوع الباقة</label>
                            <select name="type" id="edit_type" class="form-select" required>
                                <option value="store">متجر</option>
                                <option value="customer">مستخدم</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">السعر</label>
                            <div class="input-group">
                                <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                                <span class="input-group-text">ريال</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">مدة الاشتراك (بالأيام)</label>
                            <input type="number" name="duration" id="edit_duration" class="form-control" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">المميزات</label>
                            <textarea name="features" id="edit_features" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الحالة</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">نشط</option>
                                <option value="inactive">غير نشط</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="update_package" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ التغييرات
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function editPackage(package) {
        document.getElementById('edit_package_id').value = package.id;
        document.getElementById('edit_name').value = package.name;
        document.getElementById('edit_type').value = package.type;
        document.getElementById('edit_price').value = package.price;
        document.getElementById('edit_duration').value = package.duration;
        document.getElementById('edit_features').value = package.features;
        document.getElementById('edit_status').value = package.status;
        
        new bootstrap.Modal(document.getElementById('editPackageModal')).show();
    }
    </script>
</body>
</html>
