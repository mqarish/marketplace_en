<?php
session_start();

// تحقق من وجود طلب مسح الموقع
if (isset($_GET['clear'])) {
    unset($_SESSION['current_location']);
    header('Location: index.php' . (isset($_GET['view']) ? '?view=' . $_GET['view'] : ''));
    exit();
}

// تحقق من وجود بيانات الموقع
if (isset($_GET['location'])) {
    $_SESSION['current_location'] = $_GET['location'];
}

// إعادة التوجيه إلى الصفحة الرئيسية
header('Location: index.php' . (isset($_GET['view']) ? '?view=' . $_GET['view'] : ''));
exit();
?>
