<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="fas fa-store"></i> لوحة التحكم
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> الرئيسية
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="stores.php">
                        <i class="fas fa-store"></i> المتاجر
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="subscriptions.php">
                        <i class="fas fa-credit-card"></i> الاشتراكات
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i> المستخدمين
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box"></i> المنتجات
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="categories.php">
                        <i class="fas fa-tags"></i> التصنيفات
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-bar"></i> التقارير
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php
                // التحقق من وجود جلسة المشرف
                if (!isset($_SESSION)) {
                    session_start();
                }
                
                // تعيين اسم المشرف
                $admin_name = $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'المشرف';
                
                if (isset($_SESSION['admin_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($admin_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user-cog"></i> الملف الشخصي
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
