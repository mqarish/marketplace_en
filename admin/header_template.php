<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'تفاصيل المتجر';
$page_icon = 'fa-store';

// تفعيل عرض الأخطاء للتصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// التحقق من وجود معرف المتجر
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'معرف المتجر غير صالح';
    header('Location: stores.php');
    exit;
}

$store_id = (int)$_GET['id'];

// استرجاع بيانات المتجر وباقي الكود PHP هنا
// ...
// ...
// ...
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php include 'admin_header.php'; ?>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <!-- عنوان الصفحة -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas <?php echo $page_icon; ?> me-1"></i> <?php echo $page_title; ?>
            </h1>
            <a href="stores.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة إلى قائمة المتاجر
            </a>
        </div>

        <?php include 'alert_messages.php'; ?>

        <!-- باقي محتوى الصفحة هنا -->
        <!-- ... -->
        <!-- ... -->
        <!-- ... -->
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
