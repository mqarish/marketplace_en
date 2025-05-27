<?php
session_start();

// تدمير جلسة المستخدم
session_destroy();

// إعادة التوجيه إلى صفحة تسجيل الدخول
header('Location: login.php');
exit();
