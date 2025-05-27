<?php
require_once '../includes/init.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['store_id'])) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
    exit();
}

// التحقق من وجود البيانات المطلوبة
if (!isset($_POST['slug'])) {
    echo json_encode(['success' => false, 'message' => 'البيانات غير مكتملة']);
    exit();
}

$store_id = $_SESSION['store_id'];
$new_slug = trim($_POST['slug']);

// التحقق من تنسيق الرابط
if (!preg_match('/^[a-zA-Z0-9-]+$/', $new_slug)) {
    echo json_encode(['success' => false, 'message' => 'تنسيق الرابط غير صحيح']);
    exit();
}

// التحقق من عدم وجود متجر آخر بنفس الرابط
$check_stmt = $conn->prepare("SELECT id FROM stores WHERE slug = ? AND id != ?");
$check_stmt->bind_param("si", $new_slug, $store_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'هذا الرابط مستخدم بالفعل']);
    exit();
}

// تحديث الرابط
$update_stmt = $conn->prepare("UPDATE stores SET slug = ? WHERE id = ?");
$update_stmt->bind_param("si", $new_slug, $store_id);

if ($update_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث الرابط']);
}
