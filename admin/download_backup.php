<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// التأكد من أن المستخدم مسؤول
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// التحقق من وجود اسم الملف
if (!isset($_GET['file'])) {
    $_SESSION['error'] = 'لم يتم تحديد اسم الملف للتنزيل';
    header("Location: settings.php");
    exit;
}

// تنظيف اسم الملف
$filename = basename($_GET['file']);
$backup_dir = '../backups/';
$file_path = $backup_dir . $filename;

// التحقق من وجود الملف وأنه ملف SQL
if (!file_exists($file_path) || pathinfo($filename, PATHINFO_EXTENSION) !== 'sql') {
    $_SESSION['error'] = 'الملف غير موجود أو غير صالح';
    header("Location: settings.php");
    exit;
}

// إعداد رأس التنزيل
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($file_path));

// قراءة الملف وإرساله للمستخدم
readfile($file_path);
exit;
