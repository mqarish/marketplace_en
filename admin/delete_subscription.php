<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('معرف الاشتراك غير صالح');
    }
    
    $id = intval($_POST['id']);
    
    // التحقق من وجود الاشتراك
    $stmt = $conn->prepare("SELECT id FROM subscriptions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('الاشتراك غير موجود');
    }
    
    // حذف الاشتراك
    $stmt = $conn->prepare("DELETE FROM subscriptions WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في حذف الاشتراك');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم حذف الاشتراك بنجاح'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
