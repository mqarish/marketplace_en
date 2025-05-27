<?php
session_start();

// تدمير جلسة المستخدم
session_destroy();

// إعادة توجيه المستخدم إلى الصفحة الرئيسية
header("Location: index.php");
exit();
