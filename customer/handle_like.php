<?php
session_start();
require_once '../includes/init.php';

// التحقق من وجود المستخدم
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'يرجى تسجيل الدخول أولاً']);
    exit;
}

$customer_id = $_SESSION['customer_id'];

// التحقق من البيانات المرسلة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    
    $product_id = (int)$_POST['product_id'];
    
    // التحقق من وجود المنتج
    $check_product = $conn->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
    $check_product->bind_param("i", $product_id);
    $check_product->execute();
    $product_result = $check_product->get_result();
    
    if ($product_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'المنتج غير موجود']);
        exit;
    }
    
    // التحقق من وجود إعجاب سابق
    $check_like = $conn->prepare("SELECT id FROM product_likes WHERE product_id = ? AND customer_id = ?");
    $check_like->bind_param("ii", $product_id, $customer_id);
    $check_like->execute();
    $like_result = $check_like->get_result();
    
    if ($like_result->num_rows > 0) {
        // إلغاء الإعجاب
        $like_row = $like_result->fetch_assoc();
        $delete_like = $conn->prepare("DELETE FROM product_likes WHERE id = ?");
        $delete_like->bind_param("i", $like_row['id']);
        
        if ($delete_like->execute()) {
            echo json_encode(['status' => 'success', 'action' => 'unlike', 'message' => 'تم إلغاء الإعجاب بنجاح']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء إلغاء الإعجاب']);
        }
    } else {
        // إضافة إعجاب جديد
        $add_like = $conn->prepare("INSERT INTO product_likes (product_id, customer_id, created_at) VALUES (?, ?, NOW())");
        $add_like->bind_param("ii", $product_id, $customer_id);
        
        if ($add_like->execute()) {
            echo json_encode(['status' => 'success', 'action' => 'like', 'message' => 'تم الإعجاب بنجاح']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء إضافة الإعجاب']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'بيانات غير صحيحة']);
}
?>
