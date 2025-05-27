<?php
session_start();
require_once '../includes/init.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'store') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك بالوصول']);
    exit();
}

$store_id = $_SESSION['store_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offer_id'])) {
    $offer_id = (int)$_POST['offer_id'];
    
    // التحقق من أن العرض ينتمي للمتجر وجلب مسار الصورة
    $check_sql = "SELECT id, image_path FROM offers WHERE id = ? AND store_id = ?";
    $stmt = $conn->prepare($check_sql);
    
    if ($stmt) {
        $stmt->bind_param("ii", $offer_id, $store_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $offer = $result->fetch_assoc();
            
            // حذف المنتجات المرتبطة بالعرض
            $delete_products_sql = "DELETE FROM offer_products WHERE offer_id = ?";
            $delete_products_stmt = $conn->prepare($delete_products_sql);
            if ($delete_products_stmt) {
                $delete_products_stmt->bind_param("i", $offer_id);
                $delete_products_stmt->execute();
                $delete_products_stmt->close();
            }
            
            // حذف العرض
            $delete_sql = "DELETE FROM offers WHERE id = ? AND store_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            
            if ($delete_stmt) {
                $delete_stmt->bind_param("ii", $offer_id, $store_id);
                if ($delete_stmt->execute()) {
                    // حذف الصورة إذا كانت موجودة
                    if (!empty($offer['image_path'])) {
                        $image_path = "../" . $offer['image_path'];
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'تم حذف العرض بنجاح';
                } else {
                    $response['message'] = 'حدث خطأ أثناء حذف العرض';
                }
                $delete_stmt->close();
            } else {
                $response['message'] = 'حدث خطأ في إعداد الاستعلام';
            }
        } else {
            $response['message'] = 'العرض غير موجود أو لا يمكنك حذفه';
        }
        $stmt->close();
    } else {
        $response['message'] = 'حدث خطأ في إعداد الاستعلام';
    }
} else {
    $response['message'] = 'طلب غير صالح';
}

echo json_encode($response);
?>
