<?php
if (!isset($_SESSION)) {
    session_start();
}

// التحقق من تسجيل دخول المشرف
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark rtl">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">لوحة التحكم</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">الرئيسية</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="stores.php">المتاجر</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="customers.php">العملاء</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="subscriptions.php">الاشتراكات</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="packages.php">الباقات</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">الإعدادات</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">الملف الشخصي</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">تسجيل الخروج</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
