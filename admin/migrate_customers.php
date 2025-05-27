<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

try {
    // Check if customers table exists
    $result = $conn->query("SHOW TABLES LIKE 'customers'");
    if ($result->num_rows > 0) {
        // Get all customers
        $customers = $conn->query("SELECT * FROM customers");
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            while ($customer = $customers->fetch_assoc()) {
                // Check if user already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $customer['email']);
                $stmt->execute();
                $exists = $stmt->get_result()->num_rows > 0;
                
                if (!$exists) {
                    // Insert into users table
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status, created_at) VALUES (?, ?, ?, 'customer', ?, ?)");
                    $stmt->bind_param("sssss", $customer['name'], $customer['email'], $customer['password'], $customer['status'], $customer['created_at']);
                    $stmt->execute();
                }
            }
            
            // If everything is successful, commit the transaction
            $conn->commit();
            echo "تم نقل جميع العملاء بنجاح.<br>";
            
            // Optionally drop the old table
            $conn->query("DROP TABLE customers");
            echo "تم حذف الجدول القديم بنجاح.<br>";
            
        } catch (Exception $e) {
            // If there's an error, rollback the changes
            $conn->rollback();
            throw $e;
        }
    } else {
        echo "جدول العملاء غير موجود. لا حاجة للترحيل.<br>";
    }
    
    echo "<a href='customers.php' class='btn btn-primary'>العودة إلى صفحة العملاء</a>";
} catch (Exception $e) {
    echo "حدث خطأ: " . $e->getMessage();
}
?>
