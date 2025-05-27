<?php
session_start();
require_once '../includes/init.php';

// التحقق من تسجيل دخول المستخدم
if (!isset($_SESSION['store_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $store_id = $_SESSION['store_id'];
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $category_id = $_POST['category_id'] ?? '';

    // معالجة تحميل الصورة
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            // إنشاء اسم فريد للملف
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = '../uploads/stores/';
            
            // التأكد من وجود المجلد
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            $destination = $upload_path . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // تحديث اسم الصورة في قاعدة البيانات
                $sql = "UPDATE stores SET logo = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_filename, $store_id);
                $stmt->execute();
            }
        }
    }

    {{ ... }}
}
?>