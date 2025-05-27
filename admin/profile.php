<?php
require_once 'check_admin.php';
require_once '../includes/functions.php';

// تعيين عنوان الصفحة
$page_title = 'الملف الشخصي';

// جلب بيانات المشرف
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// معالجة تحديث البيانات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        
        // التحقق من البريد الإلكتروني
        $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $admin_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['error'] = "البريد الإلكتروني مستخدم بالفعل";
        } else {
            // تحديث البيانات
            $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $email, $admin_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "تم تحديث البيانات بنجاح";
                // تحديث بيانات المشرف
                $admin['username'] = $username;
                $admin['email'] = $email;
            } else {
                $_SESSION['error'] = "حدث خطأ أثناء تحديث البيانات";
            }
        }
        $stmt->close();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // التحقق من كلمة المرور الحالية باستخدام MD5
        if (md5($current_password) !== $admin['password']) {
            $_SESSION['error'] = "كلمة المرور الحالية غير صحيحة";
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error'] = "كلمة المرور الجديدة غير متطابقة";
        } else {
            // تحديث كلمة المرور باستخدام MD5
            $hashed_password = md5($new_password);
            $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $admin_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "تم تحديث كلمة المرور بنجاح";
            } else {
                $_SESSION['error'] = "حدث خطأ أثناء تحديث كلمة المرور";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - السوق الإلكتروني</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>الملف الشخصي</h2>
        </div>

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
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">تحديث البيانات الشخصية</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">اسم المستخدم</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> حفظ التغييرات
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">تغيير كلمة المرور</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key"></i> تغيير كلمة المرور
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
