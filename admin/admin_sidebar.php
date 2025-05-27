<?php
// Ensure this file is included, not accessed directly
defined('BASEPATH') or define('BASEPATH', true);

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="p-3">
        <h5 class="text-light mb-3">لوحة التحكم</h5>
        <nav class="nav flex-column">
            <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                لوحة التحكم
            </a>
            <a href="stores.php" class="nav-link <?php echo $current_page === 'stores.php' ? 'active' : ''; ?>">
                <i class="fas fa-store"></i>
                المتاجر
            </a>
            <a href="categories.php" class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                التصنيفات
            </a>
            <a href="products.php" class="nav-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i>
                المنتجات
            </a>
            <a href="orders.php" class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                الطلبات
            </a>
            <a href="customers.php" class="nav-link <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                العملاء
            </a>
            <a href="reports.php" class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                التقارير
            </a>
            <a href="settings.php" class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                الإعدادات
            </a>
        </nav>
    </div>
</div>
