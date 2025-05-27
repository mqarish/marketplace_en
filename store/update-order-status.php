<?php
session_start();
require_once '../includes/init.php';

// التحقق من تسجيل الدخول كمتجر
if (!isset($_SESSION['store_id'])) {
    header("Location: login.php");
    exit();
}

$store_id = $_SESSION['store_id'];

// التحقق من أن الطلب تم إرساله بطريقة POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: orders.php");
    exit();
}

// التحقق من وجود البيانات المطلوبة
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    $_SESSION['error'] = "البيانات المطلوبة غير مكتملة";
    header("Location: orders.php");
    exit();
}

$order_id = (int)$_POST['order_id'];
$status = $_POST['status'];
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

// التحقق من صحة حالة الطلب
$valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    $_SESSION['error'] = "حالة الطلب غير صالحة";
    header("Location: orders.php");
    exit();
}

// التحقق من أن الطلب ينتمي للمتجر
$check_query = "SELECT id FROM orders WHERE id = ? AND store_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $order_id, $store_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    $_SESSION['error'] = "الطلب غير موجود أو لا ينتمي لهذا المتجر";
    header("Location: orders.php");
    exit();
}

// بدء المعاملة
$conn->begin_transaction();

try {
    // تحديث حالة الطلب
    $update_query = "UPDATE orders SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $status, $order_id);
    $update_result = $update_stmt->execute();
    
    if (!$update_result) {
        throw new Exception("فشل في تحديث حالة الطلب");
    }
    
    // إضافة سجل لحالة الطلب الجديدة
    $history_query = "INSERT INTO order_history (order_id, status, notes) VALUES (?, ?, ?)";
    $history_stmt = $conn->prepare($history_query);
    $history_stmt->bind_param("iss", $order_id, $status, $notes);
    $history_result = $history_stmt->execute();
    
    if (!$history_result) {
        throw new Exception("فشل في إضافة سجل حالة الطلب");
    }
    
    // إذا كانت الحالة "تم التوصيل"، تحديث حالة الدفع إلى "تم الدفع" إذا كانت طريقة الدفع "الدفع عند الاستلام"
    if ($status === 'delivered') {
        $payment_query = "UPDATE orders SET payment_status = 'paid' WHERE id = ? AND payment_method = 'cash_on_delivery' AND payment_status = 'pending'";
        $payment_stmt = $conn->prepare($payment_query);
        $payment_stmt->bind_param("i", $order_id);
        $payment_stmt->execute();
    }
    
    // تأكيد المعاملة
    $conn->commit();
    
    $_SESSION['success'] = "تم تحديث حالة الطلب بنجاح";
    header("Location: order-details.php?id=" . $order_id);
    exit();
} catch (Exception $e) {
    // التراجع عن المعاملة في حالة حدوث خطأ
    $conn->rollback();
    
    $_SESSION['error'] = "حدث خطأ أثناء تحديث حالة الطلب: " . $e->getMessage();
    header("Location: order-details.php?id=" . $order_id);
    exit();
}
?>
