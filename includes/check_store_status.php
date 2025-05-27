<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__FILE__) . '/init.php';

// التحقق من تسجيل دخول المتجر
if (!isset($_SESSION['store_id'])) {
    header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '') . 'login.php');
    exit();
}

// التحقق من حالة المتجر
$store_id = $_SESSION['store_id'];
$stmt = $conn->prepare("SELECT status FROM stores WHERE id = ?");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $store = $result->fetch_assoc();
    
    // إذا كان المتجر معطل، قم بتسجيل الخروج وإعادة التوجيه
    if ($store['status'] === 'suspended' || $store['status'] === 'pending') {
        // تسجيل الخروج
        session_unset();
        session_destroy();
        
        // إنشاء جلسة جديدة لعرض رسالة الخطأ
        session_start();
        $_SESSION['error'] = "تم تعطيل حسابك. يرجى التواصل مع إدارة الموقع للمزيد من المعلومات.";
        
        // إعادة التوجيه إلى صفحة تسجيل الدخول
        header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '') . 'login.php');
        exit();
    }
}
?>
