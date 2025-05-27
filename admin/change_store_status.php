<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// التحقق من وجود البيانات المطلوبة
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['store_id']) && isset($_GET['status'])) {
    $store_id = (int)$_GET['store_id'];
    $status = $_GET['status'];
    
    // التحقق من صحة القيم
    // تحويل inactive إلى suspended لتتوافق مع قاعدة البيانات
    if ($status == 'inactive') {
        $status = 'suspended';
    }
    
    if ($store_id > 0 && in_array($status, ['active', 'suspended', 'pending'])) {
        try {
            // تحديث حالة المتجر مباشرة
            $stmt = $conn->prepare("UPDATE stores SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $store_id);
            $result1 = $stmt->execute();
            
            // إضافة معلومات عن حالة المتجر في الجلسة
            if ($status == 'suspended') {
                $_SESSION['store_status_note'][$store_id] = "المتجر قيد التنشيط مرة أخرى";
            }
            
            // تحديث حالة المستخدم المرتبط (إن وجد)
            $stmt2 = $conn->prepare("UPDATE users u 
                                   JOIN stores s ON u.id = s.user_id 
                                   SET u.status = ? 
                                   WHERE s.id = ?");
            $stmt2->bind_param("si", $status, $store_id);
            $result2 = $stmt2->execute();
            
            // ملاحظة: تم تعطيل حذف الجلسات لأن جدول sessions غير موجود في قاعدة البيانات المباشرة
            // يمكن للمستخدمين تسجيل الخروج وإعادة تسجيل الدخول لتطبيق التغييرات
            
            // تعيين رسالة النجاح
            if ($status == 'suspended') {
                $_SESSION['success'] = "تم تعطيل المتجر بنجاح وهو قيد التنشيط مرة أخرى";
            } else {
                $_SESSION['success'] = "تم " . ($status == 'active' ? 'تفعيل' : 'تعطيل') . " المتجر بنجاح";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "حدث خطأ أثناء تحديث حالة المتجر: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "بيانات غير صالحة";
    }
} else {
    $_SESSION['error'] = "طلب غير صالح";
}

// إعادة التوجيه إلى صفحة المتاجر
header("Location: stores.php");
exit;
?>
