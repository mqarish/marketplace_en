<?php
require_once '../includes/config.php';

// حذف المستخدمين الحاليين والمتاجر المرتبطة بهم
$conn->query("DELETE FROM stores WHERE user_id IN (SELECT id FROM users WHERE email IN ('mqarish@gmail.com', 'mqarish@yahoo.com'))");
$conn->query("DELETE FROM users WHERE email IN ('mqarish@gmail.com', 'mqarish@yahoo.com')");

// إضافة المستخدم الأول
$username1 = 'بقالة ابو الرجل';
$email1 = 'mqarish@gmail.com';
$password1 = password_hash('123456', PASSWORD_DEFAULT);
$phone1 = '734111154';
$role = 'store';

$stmt = $conn->prepare("INSERT INTO users (username, email, password, phone, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
$stmt->bind_param("sssss", $username1, $email1, $password1, $phone1, $role);
$stmt->execute();
$user1_id = $conn->insert_id;

// إضافة المتجر الأول
$store_name1 = $username1;
$stmt = $conn->prepare("INSERT INTO stores (user_id, name, description, status) VALUES (?, ?, ?, 'active')");
$description1 = "متجر " . $username1;
$stmt->bind_param("iss", $user1_id, $store_name1, $description1);
$stmt->execute();

// إضافة المستخدم الثاني
$username2 = 'سوبر مركة النجار';
$email2 = 'mqarish@yahoo.com';
$password2 = password_hash('123456', PASSWORD_DEFAULT);
$phone2 = '737000557';

$stmt = $conn->prepare("INSERT INTO users (username, email, password, phone, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
$stmt->bind_param("sssss", $username2, $email2, $password2, $phone2, $role);
$stmt->execute();
$user2_id = $conn->insert_id;

// إضافة المتجر الثاني
$store_name2 = $username2;
$stmt = $conn->prepare("INSERT INTO stores (user_id, name, description, status) VALUES (?, ?, ?, 'active')");
$description2 = "متجر " . $username2;
$stmt->bind_param("iss", $user2_id, $store_name2, $description2);
$stmt->execute();

echo "تم إضافة المستخدمين والمتاجر بنجاح!";

// عرض بيانات المستخدمين للتأكد
$result = $conn->query("SELECT * FROM users WHERE email IN ('mqarish@gmail.com', 'mqarish@yahoo.com')");
echo "<h2>المستخدمون المضافون:</h2>";
while ($row = $result->fetch_assoc()) {
    echo "الاسم: " . $row['username'] . "<br>";
    echo "البريد: " . $row['email'] . "<br>";
    echo "الهاتف: " . $row['phone'] . "<br>";
    echo "الحالة: " . $row['status'] . "<br>";
    echo "<hr>";
}
?>
