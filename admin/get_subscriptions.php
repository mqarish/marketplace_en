<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

header('Content-Type: application/json');

try {
    // استعلام لجلب الاشتراكات مع معلومات المشتركين والباقات
    $sql = "SELECT 
                s.id,
                s.subscriber_type,
                s.subscriber_id,
                s.start_date,
                s.end_date,
                s.status,
                sp.name as package_name,
                CASE 
                    WHEN s.subscriber_type = 'store' THEN st.name
                    WHEN s.subscriber_type = 'customer' THEN c.name
                END as subscriber_name
            FROM subscriptions s
            LEFT JOIN subscription_packages sp ON s.package_id = sp.id
            LEFT JOIN stores st ON s.subscriber_type = 'store' AND s.subscriber_id = st.id
            LEFT JOIN customers c ON s.subscriber_type = 'customer' AND s.subscriber_id = c.id
            ORDER BY s.id DESC";

    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception($conn->error);
    }

    $subscriptions = array();
    while ($row = $result->fetch_assoc()) {
        // تنسيق التواريخ
        $row['start_date'] = date('Y-m-d', strtotime($row['start_date']));
        $row['end_date'] = date('Y-m-d', strtotime($row['end_date']));
        
        // ترجمة نوع المشترك
        $row['subscriber_type'] = $row['subscriber_type'] === 'store' ? 'متجر' : 'عميل';
        
        // ترجمة الحالة
        switch($row['status']) {
            case 'active':
                $row['status'] = 'نشط';
                break;
            case 'expired':
                $row['status'] = 'منتهي';
                break;
            case 'cancelled':
                $row['status'] = 'ملغي';
                break;
        }
        
        $subscriptions[] = $row;
    }

    echo json_encode([
        'data' => $subscriptions
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'حدث خطأ أثناء جلب البيانات: ' . $e->getMessage()
    ]);
}
