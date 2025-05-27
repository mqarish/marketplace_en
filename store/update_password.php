<?php
require_once '../includes/config.php';

// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تحديث كلمة المرور للمستخدم
$email = 'mqarish@gmail.com';
$new_password = '123456789';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->bind_param("ss", $hashed_password, $email);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "<div dir='rtl'>";
    echo "<h2>تم تحديث كلمة المرور بنجاح!</h2>";
    echo "<p>البريد الإلكتروني: " . $email . "</p>";
    echo "<p>كلمة المرور الجديدة: " . $new_password . "</p>";
    echo "<p><a href='login.php'>اضغط هنا لتسجيل الدخول</a></p>";
    echo "</div>";
} else {
    echo "<div dir='rtl'>";
    echo "<h2>حدث خطأ في تحديث كلمة المرور</h2>";
    echo "<p>لم يتم العثور على المستخدم بالبريد الإلكتروني: " . $email . "</p>";
    echo "</div>";
}

$stmt->close();
?>
