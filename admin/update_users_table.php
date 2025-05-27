<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

try {
    // First, modify the status enum to include 'pending'
    $sql = "ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'pending') DEFAULT 'pending'";
    if ($conn->query($sql)) {
        echo "تم تحديث جدول المستخدمين بنجاح.<br>";
    }
    
    // Update existing customers to be active if they're not pending
    $sql = "UPDATE users SET status = 'active' WHERE role = 'customer' AND status = 'inactive'";
    if ($conn->query($sql)) {
        echo "تم تحديث حالة العملاء الحاليين بنجاح.<br>";
    }
    
    echo "<a href='customers.php' class='btn btn-primary'>العودة إلى صفحة العملاء</a>";
} catch (Exception $e) {
    echo "حدث خطأ: " . $e->getMessage();
}
?>
