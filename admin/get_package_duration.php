<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('معرف الباقة مطلوب');
    }

    $package_id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("SELECT duration FROM subscription_packages WHERE id = ?");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('الباقة غير موجودة');
    }
    
    $package = $result->fetch_assoc();
    
    echo json_encode([
        'status' => 'success',
        'duration' => (int)$package['duration']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
