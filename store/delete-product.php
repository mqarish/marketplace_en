<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['store_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit;
}

$store_id = $_SESSION['store_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    
    // التحقق من أن المنتج ينتمي للمتجر
    $check_sql = "SELECT id, image_url FROM products WHERE id = ? AND store_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $product_id, $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product) {
        // حذف صورة المنتج إذا كانت موجودة
        if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])) {
            unlink('../' . $product['image_url']);
        }
        
        // حذف المنتج من قاعدة البيانات
        $delete_sql = "DELETE FROM products WHERE id = ? AND store_id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("ii", $product_id, $store_id);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'تم حذف المنتج بنجاح';
        } else {
            $response['message'] = 'حدث خطأ أثناء حذف المنتج';
        }
    } else {
        $response['message'] = 'المنتج غير موجود أو غير مصرح لك بحذفه';
    }
} else {
    $response['message'] = 'طلب غير صالح';
}

header('Content-Type: application/json');
echo json_encode($response);
