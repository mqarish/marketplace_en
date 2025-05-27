<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

header('Content-Type: application/json');

try {
    // التحقق من البيانات المطلوبة
    $required_fields = ['subscriber_type', 'subscriber_id', 'package_id', 'start_date', 'end_date'];
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            throw new Exception("الحقل {$field} مطلوب");
        }
    }

    // تنظيف وتحضير البيانات
    $subscriber_type = $data['subscriber_type'];
    $subscriber_id = (int)$data['subscriber_id'];
    $package_id = (int)$data['package_id'];
    $start_date = $data['start_date'];
    $end_date = $data['end_date'];
    $status = 'active';

    // التحقق من صحة نوع المشترك
    if (!in_array($subscriber_type, ['store', 'customer'])) {
        throw new Exception("نوع المشترك غير صالح");
    }

    // التحقق من وجود الباقة
    $stmt = $conn->prepare("SELECT id FROM subscription_packages WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $package_result = $stmt->get_result();
    if ($package_result->num_rows === 0) {
        throw new Exception("الباقة غير موجودة أو غير نشطة");
    }

    // التحقق من وجود المشترك
    if ($subscriber_type === 'store') {
        $stmt = $conn->prepare("SELECT id FROM stores WHERE id = ? AND status = 'active'");
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND status = 'active'");
    }
    $stmt->bind_param("i", $subscriber_id);
    $stmt->execute();
    $subscriber_result = $stmt->get_result();
    if ($subscriber_result->num_rows === 0) {
        throw new Exception("المشترك غير موجود أو غير نشط");
    }

    // التحقق من عدم وجود اشتراك نشط للمشترك
    $stmt = $conn->prepare("SELECT id FROM subscriptions WHERE subscriber_type = ? AND subscriber_id = ? AND status = 'active'");
    $stmt->bind_param("si", $subscriber_type, $subscriber_id);
    $stmt->execute();
    $existing_subscription = $stmt->get_result();
    if ($existing_subscription->num_rows > 0) {
        throw new Exception("يوجد اشتراك نشط بالفعل لهذا المشترك");
    }

    // بدء المعاملة
    $conn->begin_transaction();

    // إضافة الاشتراك
    $stmt = $conn->prepare("INSERT INTO subscriptions (subscriber_type, subscriber_id, package_id, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisss", $subscriber_type, $subscriber_id, $package_id, $start_date, $end_date, $status);
    
    if (!$stmt->execute()) {
        throw new Exception("خطأ في إضافة الاشتراك: " . $stmt->error);
    }

    $subscription_id = $conn->insert_id;

    // تأكيد المعاملة
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'تم إضافة الاشتراك بنجاح',
        'data' => [
            'id' => $subscription_id
        ]
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
