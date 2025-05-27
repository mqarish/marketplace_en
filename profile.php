<?php
session_start();
require_once '../includes/init.php';
require_once '../includes/functions.php';
require_once '../includes/customer_auth.php';

// التحقق من تسجيل دخول العميل
check_customer_auth();
check_customer_status($conn, $_SESSION['customer_id']);

// جلب معلومات المستخدم
$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
if (!$stmt) {
    die("خطأ في إعداد الاستعلام: " . $conn->error);
}

$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: login.php');
    exit;
}

// معالجة تحديث الملف الشخصي
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $errors = [];

    // التحقق من البيانات
    if (empty($name)) {
        $errors[] = "الاسم مطلوب";
    }
    if (empty($email)) {
        $errors[] = "البريد الإلكتروني مطلوب";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح";
    }

    // التحقق من عدم وجود بريد إلكتروني مكرر
    if (!empty($email) && $email !== $user['email']) {
        $check_email = $conn->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
        if (!$check_email) {
            die("خطأ في إعداد الاستعلام: " . $conn->error);
        }
        $check_email->bind_param("si", $email, $customer_id);
        $check_email->execute();
        $email_result = $check_email->get_result();
        if ($email_result->num_rows > 0) {
            $errors[] = "البريد الإلكتروني مستخدم بالفعل";
        }
        $check_email->close();
    }

    // التحقق من عدم وجود أخطاء
    if (empty($errors)) {
        $update_stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        if (!$update_stmt) {
            die("خطأ في إعداد الاستعلام: " . $conn->error);
        }
        
        $update_stmt->bind_param("ssssi", $name, $email, $phone, $address, $customer_id);
        
        if ($update_stmt->execute()) {
            $success_message = "تم تحديث الملف الشخصي بنجاح";
            // تحديث بيانات المستخدم في المتغير
            $user['name'] = $name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['address'] = $address;
        } else {
            $errors[] = "حدث خطأ أثناء تحديث البيانات: " . $update_stmt->error;
        }
        
        $update_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - <?php echo htmlspecialchars($user['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
            border: 3px solid rgba(255, 255, 255, 0.5);
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #344767;
        }
        .btn-primary {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
        }
        .member-since {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .profile-header {
                padding: 2rem 0;
            }
            .profile-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/customer_navbar.php'; ?>

    <div class="profile-header text-center">
        <div class="container">
            <div class="profile-avatar">
                <i class="bi bi-person"></i>
            </div>
            <h2 class="mb-2"><?php echo htmlspecialchars($user['name']); ?></h2>
            <span class="member-since">
                <i class="bi bi-calendar3 me-2"></i>
                عضو منذ <?php echo date('Y/m/d', strtotime($user['created_at'])); ?>
            </span>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="profile-card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">الاسم</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">رقم الهاتف</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   placeholder="05xxxxxxxx" dir="ltr">
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label">العنوان</label>
                            <textarea class="form-control" id="address" name="address" 
                                      rows="3" placeholder="أدخل عنوانك الكامل"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>
                                حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
