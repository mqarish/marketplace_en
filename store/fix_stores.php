<?php
require_once '../includes/config.php';

// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تحديث المتاجر بربطها بالمستخدمين
$conn->query("UPDATE stores SET user_id = 12 WHERE name = 'بقالة أبو الرجل'");
$conn->query("UPDATE stores SET user_id = 13 WHERE name = 'سوبر ماركة النجار'");

echo "<div dir='rtl'>";
echo "<h2>تم تحديث المتاجر بنجاح!</h2>";

// عرض المتاجر بعد التحديث
$result = $conn->query("SELECT stores.*, users.email 
                       FROM stores 
                       LEFT JOIN users ON stores.user_id = users.id");
echo "<h3>المتاجر بعد التحديث:</h3>";
while($row = $result->fetch_assoc()) {
    echo "معرف المتجر: " . $row['id'] . "<br>";
    echo "معرف المستخدم: " . $row['user_id'] . "<br>";
    echo "اسم المتجر: " . $row['name'] . "<br>";
    echo "البريد الإلكتروني للمستخدم: " . ($row['email'] ?? 'غير متوفر') . "<br>";
    echo "الحالة: " . $row['status'] . "<br>";
    echo "<hr>";
}
echo "</div>";
?>