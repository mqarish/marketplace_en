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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_POST['rating'])) {
    
    $product_id = (int)$_POST['product_id'];
    $rating = (float)$_POST['rating'];
    
    // التحقق من صحة القيم
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['status' => 'error', 'message' => 'قيمة التقييم يجب أن تكون بين 1 و 5']);
        exit;
    }
    
    // التحقق من وجود المنتج
    $check_product = $conn->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
    $check_product->bind_param("i", $product_id);
    $check_product->execute();
    $product_result = $check_product->get_result();
    
    if ($product_result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'المنتج غير موجود']);
        exit;
    }
    
    // التحقق من وجود تقييم سابق
    $check_rating = $conn->prepare("SELECT id FROM reviews WHERE product_id = ? AND customer_id = ?");
    $check_rating->bind_param("ii", $product_id, $customer_id);
    $check_rating->execute();
    $rating_result = $check_rating->get_result();
    
    if ($rating_result->num_rows > 0) {
        // تحديث التقييم الموجود
        $update_rating = $conn->prepare("UPDATE reviews SET rating = ?, updated_at = NOW() WHERE product_id = ? AND customer_id = ?");
        $update_rating->bind_param("dii", $rating, $product_id, $customer_id);
        
        if ($update_rating->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'تم تحديث التقييم بنجاح']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء تحديث التقييم']);
        }
    } else {
        // إضافة تقييم جديد
        $add_rating = $conn->prepare("INSERT INTO reviews (product_id, customer_id, rating, created_at) VALUES (?, ?, ?, NOW())");
        $add_rating->bind_param("iid", $product_id, $customer_id, $rating);
        
        if ($add_rating->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'تم إضافة التقييم بنجاح']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء إضافة التقييم']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'بيانات غير صحيحة']);
}
?>
