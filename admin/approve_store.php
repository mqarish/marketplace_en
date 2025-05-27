<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $store_id = (int)$_POST['store_id'];
    $action = $_POST['action'];
    
    if ($action == 'approve') {
        $sql = "UPDATE stores SET status = 'active' WHERE id = ?";
    } else if ($action == 'reject') {
        $sql = "UPDATE stores SET status = 'rejected' WHERE id = ?";
    } else {
        echo json_encode(['success' => false, 'message' => 'إجراء غير صالح']);
        exit;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $store_id);
    
    if ($stmt->execute()) {
        // إذا تم قبول المتجر، قم بتفعيل حساب المستخدم المرتبط به
        if ($action == 'approve') {
            $sql = "UPDATE users u 
                   JOIN stores s ON u.id = s.user_id 
                   SET u.status = 'active' 
                   WHERE s.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $store_id);
            $stmt->execute();
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث حالة المتجر']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'طلب غير صالح']);
}
