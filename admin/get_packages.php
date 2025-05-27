<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['type'])) {
        throw new Exception('نوع الباقة مطلوب');
    }

    $type = $_GET['type'];
    if (!in_array($type, ['store', 'customer'])) {
        throw new Exception('نوع الباقة غير صالح');
    }

    // جلب الباقات حسب النوع
    $query = "SELECT id, name, description, duration, price, features 
             FROM subscription_packages 
             WHERE type = ? AND is_active = 1 
             ORDER BY price";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $type);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        throw new Exception($conn->error);
    }

    $packages = [];
    while ($row = $result->fetch_assoc()) {
        $packages[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'duration' => $row['duration'],
            'price' => $row['price'],
            'features' => $row['features']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $packages
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
