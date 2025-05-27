<?php
// معلومات الاتصال بقاعدة البيانات
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'marketplace';

// إنشاء اتصال بقاعدة البيانات
$conn = new mysqli($host, $username, $password, $database);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

// تعيين ترميز الاتصال
$conn->set_charset("utf8mb4");

// بدء الجلسة إذا لم تكن قد بدأت
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
