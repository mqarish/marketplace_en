<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'تعديل بيانات العميل';
$page_icon = 'fa-user-edit';

// التحقق من وجود معرف العميل
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'معرف العميل غير صحيح';
    header('Location: customers.php');
    exit();
}

$customer_id = intval($_GET['id']);

// جلب بيانات العميل
$query = "SELECT * FROM customers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'العميل غير موجود';
    header('Location: customers.php');
    exit();
}

$customer = $result->fetch_assoc();

// معالجة تعديل بيانات العميل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استلام البيانات
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? 'pending';
    
    // التحقق من البيانات
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'يرجى إدخال اسم العميل';
    }
    
    if (empty($email)) {
        $errors[] = 'يرجى إدخال البريد الإلكتروني';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني غير صالح';
    }
    
    // التحقق من وجود البريد الإلكتروني لعميل آخر
    if (!empty($email) && $email !== $customer['email']) {
        $check_stmt = $conn->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $customer_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $errors[] = 'البريد الإلكتروني مستخدم بالفعل من قبل عميل آخر';
        }
        $check_stmt->close();
    }
    
    // التحقق من رقم الهاتف
    if (!empty($phone) && !preg_match('/^[0-9]{8,15}$/', $phone)) {
        $errors[] = 'رقم الهاتف غير صالح، يجب أن يتكون من 8-15 رقم';
    }
    
    // إذا تم تغيير كلمة المرور
    $password_updated = false;
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $errors[] = 'يجب أن تكون كلمة المرور 6 أحرف على الأقل';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'كلمتا المرور غير متطابقتين';
        } else {
            $password_updated = true;
        }
    }
    
    // إذا لم تكن هناك أخطاء، قم بتحديث بيانات العميل
    if (empty($errors)) {
        try {
            if ($password_updated) {
                // تحديث البيانات مع كلمة المرور
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, status = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $name, $email, $phone, $status, $hashed_password, $customer_id);
            } else {
                // تحديث البيانات بدون كلمة المرور
                $stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, status = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $email, $phone, $status, $customer_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'تم تحديث بيانات العميل بنجاح';
                header('Location: customers.php');
                exit();
            } else {
                $errors[] = 'حدث خطأ أثناء تحديث بيانات العميل: ' . $stmt->error;
            }
        } catch (Exception $e) {
            $errors[] = 'حدث خطأ أثناء تحديث بيانات العميل: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .form-label {
            font-weight: 500;
        }
        .required::after {
            content: " *";
            color: red;
        }
        .card-header {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas <?php echo $page_icon; ?>"></i> <?php echo $page_title; ?></h2>
            <a href="customers.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i>
                العودة إلى قائمة العملاء
            </a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">تعديل بيانات العميل: <?php echo htmlspecialchars($customer['name']); ?></h4>
                    </div>
                    <div class="card-body">
                        <form action="edit_customer.php?id=<?php echo $customer_id; ?>" method="POST">
                            <div class="mb-3">
                                <label for="name" class="form-label required">الاسم</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label required">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">رقم الهاتف</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                                <small class="text-muted">يجب أن يتكون من 8-15 رقم</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">الحالة</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="pending" <?php echo $customer['status'] === 'pending' ? 'selected' : ''; ?>>في انتظار الموافقة</option>
                                    <option value="active" <?php echo $customer['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                                    <option value="blocked" <?php echo $customer['status'] === 'blocked' ? 'selected' : ''; ?>>محظور</option>
                                </select>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <small class="text-muted">اترك هذا الحقل فارغًا إذا كنت لا ترغب في تغيير كلمة المرور</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-save me-2"></i>
                                    حفظ التغييرات
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-muted">
                        <small>تاريخ التسجيل: <?php echo date('Y-m-d', strtotime($customer['created_at'])); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
