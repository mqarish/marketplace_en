<?php
require_once '../includes/init.php';
// بدء الجلسة فقط إذا لم تكن موجودة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التحقق مما إذا كان المستخدم مسجل الدخول بالفعل
if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit();
}

// معالجة التسجيل
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $errors = [];

    // التحقق من البيانات
    if (empty($name)) {
        $errors[] = "يرجى إدخال الاسم";
    }
    if (empty($email)) {
        $errors[] = "يرجى إدخال البريد الإلكتروني";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح";
    }
    if (empty($password)) {
        $errors[] = "يرجى إدخال كلمة المرور";
    } elseif (strlen($password) < 6) {
        $errors[] = "يجب أن تكون كلمة المرور 6 أحرف على الأقل";
    }
    if ($password !== $confirm_password) {
        $errors[] = "كلمتا المرور غير متطابقتين";
    }

    // التحقق من وجود البريد الإلكتروني
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "البريد الإلكتروني مستخدم بالفعل";
        }
        $stmt->close();
    }

    // إذا لم تكن هناك أخطاء، قم بإنشاء الحساب
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // التحقق من وجود جدول customers
            $check_table = $conn->query("SHOW TABLES LIKE 'customers'");
            if ($check_table->num_rows == 0) {
                // إنشاء جدول customers إذا لم يكن موجوداً
                $create_table = "CREATE TABLE customers (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    status ENUM('pending', 'active', 'blocked') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $conn->query($create_table);
            }
            
            $stmt = $conn->prepare("INSERT INTO customers (name, email, password, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
            if (!$stmt) {
                throw new Exception("خطأ في إعداد الاستعلام: " . $conn->error);
            }
            
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if (!$stmt->execute()) {
                throw new Exception("خطأ في تنفيذ الاستعلام: " . $stmt->error);
            }
            
            // عرض رسالة نجاح
            $_SESSION['success_message'] = "تم إنشاء حسابك بنجاح. يرجى الانتظار حتى تتم الموافقة على حسابك من قبل الإدارة.";
            header('Location: login.php');
            exit();
            
        } catch (Exception $e) {
            $errors[] = "حدث خطأ أثناء إنشاء الحساب: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل حساب جديد - السوق الإلكتروني</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.6)), url('../assets/images/customer-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-color: #000;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            z-index: 2;
        }

        .brand-logo {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 3;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 15px 25px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .register-container {
            width: 100%;
            max-width: 560px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 2.8rem;
            border-radius: 24px;
            box-shadow: 
                0 8px 32px 0 rgba(31, 38, 135, 0.37),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeIn 0.6s ease-out;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h2 {
            color: #fff;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-floating > .form-control {
            padding: 1rem 0.75rem;
            height: calc(3.5rem + 2px);
            line-height: 1.25;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
        }

        .btn-primary {
            background-color: #0056b3;
            border-color: #0056b3;
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #004494;
            border-color: #004494;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            body {
                background-size: cover;
                background-position: center;
            }
            
            .brand-logo {
                position: relative;
                top: 0;
                right: 0;
                margin: 1rem auto;
                width: fit-content;
            }

            .main-content {
                padding: 1rem;
            }

            .register-container {
                padding: 2rem;
                margin: 0 1rem;
            }

            .register-header h2 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="brand-logo">
        <i class="bi bi-shop"></i>
        <span style="color: #fff; font-size: 1.5rem;">السوق الإلكتروني</span>
    </div>

    <div class="main-content">
        <div class="register-container">
            <div class="register-header">
                <h2>تسجيل حساب جديد</h2>
                <p>أنشئ حسابك للوصول إلى جميع المميزات</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="register.php" method="post" class="needs-validation" novalidate>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="name" name="name" placeholder="الاسم" required>
                    <label for="name">الاسم</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="البريد الإلكتروني" required>
                    <label for="email">البريد الإلكتروني</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="كلمة المرور" required>
                    <label for="password">كلمة المرور</label>
                </div>

                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="تأكيد كلمة المرور" required>
                    <label for="confirm_password">تأكيد كلمة المرور</label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">تسجيل</button>
                </div>

                <div class="text-center mt-4">
                    <p style="color: #fff;">
                        لديك حساب بالفعل؟
                        <a href="login.php" class="text-white text-decoration-underline">تسجيل الدخول</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>