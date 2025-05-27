<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// التحقق من وجود معرف المتجر
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "معرف المتجر غير صالح";
    header("Location: stores.php");
    exit();
}

$store_id = (int)$_GET['id'];

// جلب بيانات المتجر
$stmt = $conn->prepare("
    SELECT 
        s.id,
        s.name,
        s.phone,
        s.address,
        s.city,
        s.description,
        s.logo,
        s.status,
        s.user_id,
        u.email 
    FROM stores s 
    LEFT JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$store = $stmt->get_result()->fetch_assoc();

if (!$store) {
    $_SESSION['error'] = "المتجر غير موجود";
    header("Location: stores.php");
    exit();
}

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $description = trim($_POST['description']);
    
    // التحقق من البيانات
    $errors = [];
    if (empty($name)) {
        $errors[] = "اسم المتجر مطلوب";
    }
    if (empty($email)) {
        $errors[] = "البريد الإلكتروني مطلوب";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح";
    }
    
    if (empty($errors)) {
        try {
            // تحديث بيانات المتجر
            $stmt = $conn->prepare("UPDATE stores SET name = ?, phone = ?, address = ?, city = ?, description = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $name, $phone, $address, $city, $description, $store_id);
            $stmt->execute();
            
            // تحديث البريد الإلكتروني في جدول المستخدمين
            $stmt = $conn->prepare("UPDATE users u JOIN stores s ON u.id = s.user_id SET u.email = ? WHERE s.id = ?");
            $stmt->bind_param("si", $email, $store_id);
            $stmt->execute();
            
            // معالجة تحميل الشعار الجديد
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['logo']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $new_filename = uniqid() . '.' . $ext;
                    $upload_path = '../uploads/stores/' . $new_filename;
                    
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                        // حذف الشعار القديم
                        if (!empty($store['logo'])) {
                            $old_logo = '../uploads/stores/' . $store['logo'];
                            if (file_exists($old_logo)) {
                                unlink($old_logo);
                            }
                        }
                        
                        // تحديث اسم الشعار في قاعدة البيانات
                        $stmt = $conn->prepare("UPDATE stores SET logo = ? WHERE id = ?");
                        $stmt->bind_param("si", $new_filename, $store_id);
                        $stmt->execute();
                    }
                }
            }
            
            $_SESSION['success'] = "تم تحديث بيانات المتجر بنجاح";
            header("Location: stores.php");
            exit();
        } catch (Exception $e) {
            $errors[] = "حدث خطأ أثناء تحديث البيانات: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .store-logo {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">تعديل بيانات المتجر</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">اسم المتجر *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($store['name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($store['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">رقم الهاتف</label>
                                <input type="text" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($store['phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="address" class="form-label">العنوان</label>
                                <input type="text" class="form-control" id="address" name="address" 
                                       value="<?php echo htmlspecialchars($store['address'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="city" class="form-label">المدينة</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?php echo htmlspecialchars($store['city'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">وصف المتجر</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($store['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="logo" class="form-label">شعار المتجر</label>
                                <?php if (!empty($store['logo'])): ?>
                                    <div class="mb-2">
                                        <img src="../uploads/stores/<?php echo htmlspecialchars($store['logo']); ?>" 
                                             alt="شعار المتجر" class="store-logo">
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                <div class="form-text">اترك هذا الحقل فارغاً إذا كنت لا تريد تغيير الشعار</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                        <a href="stores.php" class="btn btn-secondary">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
