<?php
require_once '../includes/init.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['store_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit();
}

$store_id = $_SESSION['store_id'];

// التحقق من وجود الملف
if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'لم يتم تحديد ملف أو حدث خطأ أثناء الرفع']);
    exit();
}

$file = $_FILES['logo'];

// التحقق من نوع الملف
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم. يرجى اختيار صورة بصيغة JPG أو PNG أو GIF']);
    exit();
}

// التحقق من حجم الملف (5 ميجابايت كحد أقصى)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'حجم الملف كبير جداً. الحد الأقصى هو 5 ميجابايت']);
    exit();
}

// إنشاء اسم فريد للملف
$file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$new_filename = uniqid() . '.' . $file_extension;
$upload_path = '../uploads/stores/';

// التأكد من وجود المجلد
if (!file_exists($upload_path)) {
    mkdir($upload_path, 0777, true);
}

// حذف الشعار القديم إذا وجد
$stmt = $conn->prepare("SELECT logo FROM stores WHERE id = ?");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$result = $stmt->get_result();
$store = $result->fetch_assoc();

if ($store && !empty($store['logo'])) {
    $old_logo_path = $upload_path . $store['logo'];
    if (file_exists($old_logo_path)) {
        unlink($old_logo_path);
    }
}

// رفع الملف الجديد
if (move_uploaded_file($file['tmp_name'], $upload_path . $new_filename)) {
    // تحديث قاعدة البيانات
    $update_stmt = $conn->prepare("UPDATE stores SET logo = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_filename, $store_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        // حذف الملف المرفوع إذا فشل التحديث في قاعدة البيانات
        unlink($upload_path . $new_filename);
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث قاعدة البيانات']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'فشل في رفع الملف']);
}
