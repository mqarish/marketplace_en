<?php
// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// حذف متغيرات الجلسة
unset($_SESSION['store_id']);
unset($_SESSION['store_name']);
unset($_SESSION['store_email']);

// تدمير الجلسة
session_destroy();

// إعادة التوجيه إلى صفحة تسجيل الدخول
header('Location: login.php');
exit();
?>