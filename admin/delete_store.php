<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['store_id'])) {
    $store_id = (int)$_POST['store_id'];
    
    // بدء المعاملة
    $conn->begin_transaction();
    
    try {
        // حذف المنتجات المرتبطة بالمتجر
        $stmt = $conn->prepare("DELETE FROM products WHERE store_id = ?");
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        
        // حذف العروض المرتبطة بالمتجر
        $stmt = $conn->prepare("DELETE FROM offers WHERE store_id = ?");
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        
        // حذف الطلبات المرتبطة بالمتجر
        $stmt = $conn->prepare("DELETE FROM orders WHERE store_id = ?");
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        
        // الحصول على user_id للمتجر
        $stmt = $conn->prepare("SELECT user_id, logo FROM stores WHERE id = ?");
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $store = $result->fetch_assoc();
        
        // حذف المتجر
        $stmt = $conn->prepare("DELETE FROM stores WHERE id = ?");
        $stmt->bind_param("i", $store_id);
        $stmt->execute();
        
        // حذف المستخدم المرتبط بالمتجر
        if ($store && $store['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $store['user_id']);
            $stmt->execute();
        }
        
        // حذف شعار المتجر إذا كان موجوداً
        if ($store && !empty($store['logo'])) {
            $logo_path = '../uploads/stores/' . $store['logo'];
            if (file_exists($logo_path)) {
                unlink($logo_path);
            }
        }
        
        // تأكيد المعاملة
        $conn->commit();
        $_SESSION['success'] = "تم حذف المتجر وجميع البيانات المرتبطة به بنجاح";
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        $conn->rollback();
        $_SESSION['error'] = "حدث خطأ أثناء حذف المتجر: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "طلب غير صالح";
}

header("Location: stores.php");
exit;
?>
