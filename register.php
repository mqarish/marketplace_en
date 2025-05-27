<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// التأكد من وجود جدول المستخدمين
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'store', 'customer') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("خطأ في إنشاء جدول المستخدمين: " . $conn->error);
}

// التأكد من وجود جدول التصنيفات
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (!$conn->query($sql)) {
    die("خطأ في إنشاء جدول التصنيفات: " . $conn->error);
}

// التأكد من وجود جدول المتاجر
$sql = "CREATE TABLE IF NOT EXISTS stores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    email VARCHAR(100),
    phone VARCHAR(20),
    logo VARCHAR(255),
    cover VARCHAR(255),
    address TEXT,
    user_id INT,
    category_id INT,
    status ENUM('pending', 'active', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)";

if (!$conn->query($sql)) {
    die("خطأ في إنشاء جدول المتاجر: " . $conn->error);
}

// التأكد من وجود عمود user_id في جدول المتاجر
$sql = "SHOW COLUMNS FROM stores LIKE 'user_id'";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    $sql = "ALTER TABLE stores ADD COLUMN user_id INT,
            ADD FOREIGN KEY (user_id) REFERENCES users(id)";
    if (!$conn->query($sql)) {
        die("خطأ في تحديث جدول المتاجر: " . $conn->error);
    }
}

// التحقق من نوع التسجيل
$type = isset($_GET['type']) && in_array($_GET['type'], ['store', 'customer']) ? $_GET['type'] : 'customer';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $errors = [];

    // التحقق من البيانات
    if (empty($username)) $errors[] = "اسم المستخدم مطلوب";
    if (empty($email)) $errors[] = "البريد الإلكتروني مطلوب";
    if (empty($password)) $errors[] = "كلمة المرور مطلوبة";
    if ($password !== $confirm_password) $errors[] = "كلمتا المرور غير متطابقتين";

    // التحقق من عدم تكرار اسم المستخدم والبريد الإلكتروني
    $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($check_sql);
    if ($stmt === false) {
        die("خطأ في إعداد الاستعلام: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // إضافة المستخدم
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die("خطأ في إعداد الاستعلام: " . $conn->error);
        }
        
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $type);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // إذا كان التسجيل لمتجر، قم بإنشاء سجل المتجر
            if ($type == 'store') {
                $store_name = sanitize($_POST['store_name']);
                $phone = sanitize($_POST['phone']);
                $category_id = (int)$_POST['category_id'];
                
                $sql = "INSERT INTO stores (name, email, phone, user_id, category_id, status) 
                        VALUES (?, ?, ?, ?, ?, 'pending')";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    die("خطأ في إعداد الاستعلام: " . $conn->error);
                }
                
                $stmt->bind_param("sssii", $store_name, $email, $phone, $user_id, $category_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "تم التسجيل بنجاح. يرجى انتظار موافقة الإدارة على متجرك.";
                } else {
                    $errors[] = "حدث خطأ أثناء إنشاء المتجر";
                }
            } else {
                $_SESSION['success_message'] = "تم التسجيل بنجاح. يمكنك الآن تسجيل الدخول.";
            }
            
            if (empty($errors)) {
                header("Location: login.php");
                exit;
            }
        } else {
            $errors[] = "حدث خطأ أثناء التسجيل";
        }
    }
}

// جلب التصنيفات للمتاجر
$categories = [];
if ($type == 'store') {
    $sql = "SELECT id, name FROM categories ORDER BY name";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل <?php echo $type == 'store' ? 'متجر' : 'عميل'; ?> جديد - السوق الإلكتروني</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            background-color: #fff;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4">
                تسجيل <?php echo $type == 'store' ? 'متجر' : 'عميل'; ?> جديد
            </h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">اسم المستخدم</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                
                <?php if ($type == 'store'): ?>
                    <div class="mb-3">
                        <label for="store_name" class="form-label">اسم المتجر</label>
                        <input type="text" class="form-control" id="store_name" name="store_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">رقم الهاتف</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">تصنيف المتجر</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">اختر التصنيف</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">تسجيل</button>
                
                <div class="text-center">
                    <p class="mb-0">لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
