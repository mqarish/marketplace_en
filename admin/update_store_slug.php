<?php
require_once '../includes/init.php';
require_once '../includes/functions.php';

// تحديث slug المتجر
$store_id = 3; // معرف متجر بهارات الشاعر
$store_name = "بهارات الشاعر";

// إنشاء slug جديد
$new_slug = generate_slug($store_name);

// إضافة رقم عشوائي للتأكد من عدم تكرار slug
$new_slug .= '-' . rand(100, 999);

// تحديث slug في قاعدة البيانات
$stmt = $conn->prepare("UPDATE stores SET slug = ? WHERE id = ?");
$stmt->bind_param("si", $new_slug, $store_id);

if ($stmt->execute()) {
    echo "تم تحديث slug المتجر بنجاح. الـ slug الجديد هو: " . $new_slug;
} else {
    echo "حدث خطأ أثناء تحديث slug المتجر: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
