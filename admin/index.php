<?php
session_start();
require_once '../includes/init.php';

// إذا كان المشرف مسجل الدخول بالفعل، قم بتحويله إلى لوحة التحكم
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        try {
            // التحقق من بيانات المشرف
            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND status = 'active'");
            if ($stmt === false) {
                throw new Exception('خطأ في الاتصال بقاعدة البيانات');
            }
            
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($admin = $result->fetch_assoc()) {
                if (md5($password) === $admin['password']) {
                    // تسجيل الدخول بنجاح
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['name'];
                    
                    // تحديث آخر تسجيل دخول
                    $update = $conn->prepare("UPDATE admins SET created_at = NOW() WHERE id = ?");
                    $update->bind_param("i", $admin['id']);
                    $update->execute();
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'كلمة المرور غير صحيحة';
                    error_log('Password verification failed for admin: ' . $username);
                }
            } else {
                $error = 'اسم المستخدم غير موجود أو الحساب غير نشط';
            }
        } catch (Exception $e) {
            $error = 'حدث خطأ أثناء تسجيل الدخول: ' . $e->getMessage();
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول المشرف - السوق الإلكتروني</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
        }
        
        .login-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .login-icon {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-4">
                <i class="fas fa-user-shield login-icon"></i>
                <h2 class="mb-3">تسجيل دخول المشرف</h2>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">اسم المستخدم</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">تسجيل الدخول</button>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
