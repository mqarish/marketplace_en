<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

header('Content-Type: application/json');

try {
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    
    if (!in_array($type, ['store', 'customer'])) {
        throw new Exception('نوع المشترك غير صالح');
    }
    
    if ($type === 'store') {
        $sql = "SELECT id, name FROM stores WHERE status = 'active' ORDER BY name";
    } else {
        $sql = "SELECT id, name FROM customers WHERE status = 'active' ORDER BY name";
    }
    
    $result = $conn->query($sql);
    if ($result === false) {
        throw new Exception($conn->error);
    }
    
    $subscribers = array();
    while ($row = $result->fetch_assoc()) {
        $subscribers[] = array(
            'id' => $row['id'],
            'name' => $row['name']
        );
    }
    
    echo json_encode([
        'data' => $subscribers
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'حدث خطأ أثناء جلب البيانات: ' . $e->getMessage()
    ]);
}
