<?php
// بدء الجلسة
session_start();

// حذف جميع متغيرات الجلسة
$_SESSION = array();

// حذف ملف تعريف الارتباط الخاص بمعرف الجلسة
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// تدمير الجلسة
session_destroy();

// إعادة التوجيه إلى صفحة تسجيل الدخول
header("Location: login.php");
exit();
