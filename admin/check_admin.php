<?php
// تضمين ملفات الإعدادات والدوال
require_once '../includes/init.php';

// التحقق من تسجيل دخول المشرف
if (!isset($_SESSION['admin_id'])) {
    // تسجيل محاولة الوصول غير المصرح
    $ip = $_SERVER['REMOTE_ADDR'];
    $page = $_SERVER['REQUEST_URI'];
    
    // إعادة التوجيه إلى صفحة تسجيل الدخول
    header("Location: login.php?error=unauthorized");
    exit;
}

// التحقق من صلاحية المشرف في قاعدة البيانات
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ? AND status = 'active'");
if ($stmt === false) {
    session_destroy();
    header('Location: login.php?error=db');
    exit();
}

$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->fetch_assoc()) {
    // إذا لم يتم العثور على المشرف أو كان حسابه غير نشط
    session_destroy();
    header('Location: login.php?error=invalid');
    exit();
}

// تعيين متغيرات عامة للاستخدام في جميع صفحات المشرف
$admin_name = $_SESSION['admin_name'] ?? 'المشرف';
$page_title = $page_title ?? 'لوحة التحكم';
?>
