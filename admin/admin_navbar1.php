<?php
// Ensure this file is included, not accessed directly
defined('BASEPATH') or define('BASEPATH', true);

// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-shopping-cart me-2"></i>
            السوق الإلكتروني
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        لوحة التحكم
                    </a>
                </li>
                <li class="nav-item">
                    <a href="stores.php" class="nav-link <?php echo $current_page === 'stores.php' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i>
                        المتاجر
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>
                        التصنيفات
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products.php" class="nav-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i>
                        المنتجات
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i>
                        الطلبات
                    </a>
                </li>
                <li class="nav-item">
                    <a href="customers.php" class="nav-link <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        العملاء
                    </a>
                </li>
                <li class="nav-item">
                    <a href="subscriptions.php" class="nav-link <?php echo $current_page === 'subscriptions.php' ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card"></i>
                        الاشتراكات
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-cog"></i>
                        المستخدمين
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        التقارير
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav user-menu">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                        <?php echo $_SESSION['admin_name'] ?? 'المدير'; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> الملف الشخصي</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> الإعدادات</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> تسجيل الخروج</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<?php if (isset($page_title)): ?>
<div class="bg-light py-2 px-3 mb-3">
    <div class="container-fluid">
        <h1 class="h3 mb-0">
            <?php if (isset($page_icon)): ?>
                <i class="fas <?php echo $page_icon; ?> me-2"></i>
            <?php endif; ?>
            <?php echo $page_title; ?>
        </h1>
    </div>
</div>
<?php endif; ?>
