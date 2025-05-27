<?php
require_once '../includes/config.php';

// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>بدء عملية إصلاح قاعدة البيانات</h2>";

try {
    // 1. التحقق من وجود جدول users
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows == 0) {
        // إنشاء جدول users إذا لم يكن موجوداً
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT(11) NOT NULL AUTO_INCREMENT,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            role ENUM('admin', 'store', 'user') NOT NULL,
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if ($conn->query($sql)) {
            echo "تم إنشاء جدول users بنجاح<br>";
        } else {
            throw new Exception("خطأ في إنشاء جدول users: " . $conn->error);
        }
    } else {
        // 1. إضافة عمود phone إذا لم يكن موجوداً
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'phone'");
        if ($result->num_rows == 0) {
            if ($conn->query("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER password")) {
                echo "تم إضافة عمود phone بنجاح<br>";
            } else {
                throw new Exception("خطأ في إضافة عمود phone: " . $conn->error);
            }
        } else {
            echo "عمود phone موجود بالفعل<br>";
        }
        echo "جدول users موجود بالفعل<br>";
    }

    // 2. حذف المستخدمين القدامى إذا كانوا موجودين
    if ($conn->query("DELETE FROM users WHERE email IN ('mqarish@gmail.com', 'mqarish@yahoo.com')")) {
        echo "تم حذف المستخدمين القدامى (إن وجدوا)<br>";
    } else {
        throw new Exception("خطأ في حذف المستخدمين القدامى: " . $conn->error);
    }

    // 3. إضافة المستخدمين الجدد
    $users = [
        [
            'username' => 'بقالة أبو الرجل',
            'email' => 'mqarish@gmail.com',
            'password' => '123456',
            'phone' => '734111154',
            'role' => 'store'
        ],
        [
            'username' => 'سوبر مركة النجار',
            'email' => 'mqarish@yahoo.com',
            'password' => '123456',
            'phone' => '737000557',
            'role' => 'store'
        ]
    ];

    foreach ($users as $user) {
        $sql = "INSERT INTO users (username, email, password, phone, role, status) VALUES (?, ?, ?, ?, ?, 'active')";
        $stmt = $conn->prepare($sql);
        
        if ($stmt === false) {
            throw new Exception("خطأ في إعداد الاستعلام: " . $conn->error);
        }
        
        if (!$stmt->bind_param("sssss", 
            $user['username'],
            $user['email'],
            $user['password'],
            $user['phone'],
            $user['role']
        )) {
            throw new Exception("خطأ في ربط المعلمات: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("خطأ في تنفيذ الاستعلام: " . $stmt->error);
        }
        
        echo "تم إضافة المستخدم {$user['email']} بنجاح<br>";
        $stmt->close();
    }

    // 4. عرض المستخدمين للتأكد
    $result = $conn->query("SELECT * FROM users WHERE role = 'store'");
    if ($result === false) {
        throw new Exception("خطأ في استرجاع المستخدمين: " . $conn->error);
    }

    echo "<h2>المستخدمون في النظام:</h2>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "<br>";
        echo "الاسم: " . $row['username'] . "<br>";
        echo "البريد: " . $row['email'] . "<br>";
        echo "كلمة المرور: " . $row['password'] . "<br>";
        echo "الهاتف: " . $row['phone'] . "<br>";
        echo "الدور: " . $row['role'] . "<br>";
        echo "الحالة: " . $row['status'] . "<br>";
        echo "<hr>";
    }

    echo "<h2>تم إكمال العملية بنجاح!</h2>";
    echo "<p>يمكنك الآن تجربة تسجيل الدخول باستخدام:</p>";
    echo "<ul>";
    echo "<li>البريد الإلكتروني: mqarish@gmail.com</li>";
    echo "<li>كلمة المرور: 123456</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
    echo "<h3>حدث خطأ:</h3>";
    echo $e->getMessage();
    echo "</div>";
}
?>
