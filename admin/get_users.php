<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['type'])) {
        throw new Exception('نوع المستخدم مطلوب');
    }

    $type = $_GET['type'];
    if (!in_array($type, ['store', 'customer'])) {
        throw new Exception('نوع المستخدم غير صالح');
    }

    $users = [];
    
    if ($type === 'store') {
        // جلب المتاجر النشطة
        $query = "SELECT id, name, email FROM stores WHERE status = 'active' ORDER BY name";
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception($conn->error);
        }
        
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => $row['email']
            ];
        }
    } else {
        // جلب العملاء النشطين
        $query = "SELECT id, username as name, email FROM users WHERE status = 'active' ORDER BY username";
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception($conn->error);
        }
        
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'email' => $row['email']
            ];
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $users
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
