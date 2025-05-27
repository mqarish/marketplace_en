<?php
require_once '../includes/init.php';
require_once '../includes/functions.php';

// التحقق من تسجيل دخول المشرف
if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة طلب غير صحيحة']);
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => ''];

switch ($action) {
    case 'add':
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'customer';

        if (empty($username) || empty($email) || empty($password)) {
            $response['message'] = 'جميع الحقول مطلوبة';
            break;
        }

        // التحقق من عدم وجود مستخدم بنفس اسم المستخدم أو البريد الإلكتروني
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $response['message'] = 'اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل';
            break;
        }
        $stmt->close();

        // إضافة المستخدم الجديد
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'تم إضافة المستخدم بنجاح';
        } else {
            $response['message'] = 'حدث خطأ أثناء إضافة المستخدم';
        }
        $stmt->close();
        break;

    case 'edit':
        $user_id = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $status = $_POST['status'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($user_id <= 0 || empty($username) || empty($email) || empty($role) || empty($status)) {
            $response['message'] = 'جميع الحقول مطلوبة';
            break;
        }

        // التحقق من عدم وجود مستخدم آخر بنفس اسم المستخدم أو البريد الإلكتروني
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $response['message'] = 'اسم المستخدم أو البريد الإلكتروني مستخدم بالفعل';
            break;
        }
        $stmt->close();

        // تحديث بيانات المستخدم
        if (!empty($password)) {
            // تحديث مع كلمة المرور
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $username, $email, $hashed_password, $role, $status, $user_id);
        } else {
            // تحديث بدون كلمة المرور
            $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, role = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $email, $role, $status, $user_id);
        }

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'تم تحديث بيانات المستخدم بنجاح';
        } else {
            $response['message'] = 'حدث خطأ أثناء تحديث بيانات المستخدم';
        }
        $stmt->close();
        break;

    case 'delete':
        $user_id = (int)($_POST['user_id'] ?? 0);

        if ($user_id <= 0) {
            $response['message'] = 'معرف المستخدم غير صحيح';
            break;
        }

        // التحقق من عدم حذف المستخدم لنفسه
        if ($user_id === (int)$_SESSION['user_id']) {
            $response['message'] = 'لا يمكنك حذف حسابك';
            break;
        }

        // حذف المستخدم
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'تم حذف المستخدم بنجاح';
        } else {
            $response['message'] = 'حدث خطأ أثناء حذف المستخدم';
        }
        $stmt->close();
        break;

    default:
        $response['message'] = 'إجراء غير صحيح';
        break;
}

echo json_encode($response);
