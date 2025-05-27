<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

header('Content-Type: application/json');

try {
    // التحقق من البيانات المطلوبة
    $required_fields = ['subscriber_type', 'subscriber_id', 'package_id', 'start_date', 'end_date', 'status'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("الحقل {$field} مطلوب");
        }
    }
    
    // تنظيف وتحضير البيانات
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $subscriber_type = $_POST['subscriber_type'];
    $subscriber_id = intval($_POST['subscriber_id']);
    $package_id = intval($_POST['package_id']);
    $start_date = date('Y-m-d', strtotime($_POST['start_date']));
    $end_date = date('Y-m-d', strtotime($_POST['end_date']));
    $status = $_POST['status'];
    
    // التحقق من صحة البيانات
    if (!in_array($subscriber_type, ['store', 'customer'])) {
        throw new Exception('نوع المشترك غير صالح');
    }
    
    if (!in_array($status, ['active', 'expired', 'cancelled'])) {
        throw new Exception('حالة الاشتراك غير صالحة');
    }
    
    if (strtotime($end_date) < strtotime($start_date)) {
        throw new Exception('تاريخ الانتهاء يجب أن يكون بعد تاريخ البداية');
    }
    
    // التحقق من وجود الباقة
    $stmt = $conn->prepare("SELECT id FROM subscription_packages WHERE id = ?");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('الباقة غير موجودة');
    }
    
    // التحقق من وجود المشترك
    if ($subscriber_type === 'store') {
        $table = 'stores';
    } else {
        $table = 'customers';
    }
    
    $stmt = $conn->prepare("SELECT id FROM {$table} WHERE id = ?");
    $stmt->bind_param("i", $subscriber_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('المشترك غير موجود');
    }
    
    // حفظ أو تحديث الاشتراك
    if ($id > 0) {
        // تحديث اشتراك موجود
        $stmt = $conn->prepare("UPDATE subscriptions SET 
            subscriber_type = ?,
            subscriber_id = ?,
            package_id = ?,
            start_date = ?,
            end_date = ?,
            status = ?,
            updated_at = NOW()
            WHERE id = ?");
        $stmt->bind_param("siisssi", 
            $subscriber_type,
            $subscriber_id,
            $package_id,
            $start_date,
            $end_date,
            $status,
            $id
        );
    } else {
        // إنشاء اشتراك جديد
        $stmt = $conn->prepare("INSERT INTO subscriptions 
            (subscriber_type, subscriber_id, package_id, start_date, end_date, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->bind_param("siisss", 
            $subscriber_type,
            $subscriber_id,
            $package_id,
            $start_date,
            $end_date,
            $status
        );
    }
    
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    
    $response_id = $id > 0 ? $id : $stmt->insert_id;
    
    echo json_encode([
        'success' => true,
        'message' => $id > 0 ? 'تم تحديث الاشتراك بنجاح' : 'تم إنشاء الاشتراك بنجاح',
        'id' => $response_id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
