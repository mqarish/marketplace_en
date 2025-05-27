<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// إذا كان المستخدم مسجل الدخول، قم بتوجيهه للصفحة المناسبة
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } elseif (isStore()) {
        header("Location: store/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
    } else {
        $sql = "SELECT * FROM users WHERE (username = ? OR email = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                if ($user['status'] == 'active') {
                    // تخزين معلومات المستخدم في الجلسة
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // توجيه المستخدم للصفحة المناسبة
                    if ($user['role'] == 'admin') {
                        header("Location: admin/dashboard.php");
                    } elseif ($user['role'] == 'store') {
                        // التحقق من حالة المتجر
                        $sql = "SELECT status FROM stores WHERE user_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $user['id']);
                        $stmt->execute();
                        $store_result = $stmt->get_result();
                        $store = $store_result->fetch_assoc();
                        
                        if ($store && $store['status'] == 'active') {
                            header("Location: store/dashboard.php");
                        } else {
                            $_SESSION['alert'] = [
                                'type' => 'warning',
                                'message' => 'متجرك قيد المراجعة. يرجى الانتظار حتى يتم التفعيل.'
                            ];
                            header("Location: store/pending.php");
                        }
                    } else {
                        header("Location: index.php");
                    }
                    exit;
                } else {
                    $error = 'هذا الحساب غير مفعل';
                }
            } else {
                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            }
        } else {
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - السوق الإلكتروني</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">تسجيل الدخول</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger text-center">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error']) && $_GET['error'] == 'unauthorized'): ?>
                <div class="alert alert-danger text-center">
                    يجب تسجيل الدخول للوصول إلى هذه الصفحة
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success text-center">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">اسم المستخدم أو البريد الإلكتروني</label>
                    <input type="text" class="form-control" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">تسجيل الدخول</button>
                
                <div class="text-center">
                    <p class="mb-0">ليس لديك حساب؟</p>
                    <div class="btn-group mt-2" role="group">
                        <a href="register.php?type=customer" class="btn btn-outline-primary">تسجيل كعميل</a>
                        <a href="register.php?type=store" class="btn btn-outline-success">تسجيل كمتجر</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
