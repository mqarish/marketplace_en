<?php
if (!isset($_SESSION)) {
    session_start();
}

// تضمين ملف الإعدادات إذا لم يكن موجوداً
if (!isset($conn)) {
    require_once __DIR__ . '/init.php';
}

// للتأكد من حالة الجلسة - إضافة للتصحيح
error_reporting(E_ALL);
ini_set('display_errors', 1);

// معالجة تسجيل الخروج
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /customer/login.php');
    exit();
}

// جلب التصنيفات
$categories = [];
try {
    $categories_query = "SELECT id, name, icon FROM categories ORDER BY name ASC";
    $categories_result = $conn->query($categories_query);
    if ($categories_result) {
        while ($category = $categories_result->fetch_assoc()) {
            $categories[] = $category;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}
?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/customer/index.php">
            <i class="bi bi-shop"></i>
            السوق الإلكتروني
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo empty($_GET['view']) && empty($_GET['category']) ? 'active' : ''; ?>" href="/customer/index.php">
                        <i class="bi bi-house-door"></i> الرئيسية
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isset($_GET['view']) && $_GET['view'] === 'stores' ? 'active' : ''; ?>" href="/customer/index.php?view=stores">
                        <i class="bi bi-shop"></i> المتاجر
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isset($_GET['view']) && $_GET['view'] === 'categories' ? 'active' : ''; ?>" href="/customer/categories.php">
                        <i class="bi bi-grid"></i> التصنيفات
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isset($_GET['view']) && $_GET['view'] === 'products' ? 'active' : ''; ?>" href="/customer/index.php?view=products">
                        <i class="bi bi-box"></i> المنتجات
                    </a>
                </li>
            </ul>

            <!-- إضافة معلومات التصحيح -->
            <?php if (isset($_SESSION['customer_id'])): ?>
                <!-- User is logged in -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <?php 
                            echo htmlspecialchars($_SESSION['customer_name'] ?? 'المستخدم'); 
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="dropdown-item" href="/customer/profile.php">
                                    <i class="bi bi-person"></i> الملف الشخصي
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/customer/orders.php">
                                    <i class="bi bi-bag"></i> طلباتي
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="/customer/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <!-- User is not logged in -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/customer/login.php">
                            <i class="bi bi-box-arrow-in-left"></i> تسجيل الدخول
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
.navbar {
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.navbar-brand {
    font-weight: bold;
}

.navbar-brand i {
    margin-left: 0.5rem;
}

.nav-link {
    padding: 0.5rem 1rem;
    position: relative;
}

.nav-link i {
    margin-left: 0.5rem;
}

.nav-link:hover {
    color: #fff !important;
}

.nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background-color: #fff;
    border-radius: 3px 3px 0 0;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,.1);
    border-radius: 8px;
}

.dropdown-item {
    padding: 8px 20px;
}

.dropdown-item i {
    margin-left: 8px;
}

.dropdown-divider {
    margin: 0.5rem 0;
}

.navbar .nav-link {
    padding: 0.5rem 1rem;
}

.navbar-nav .dropdown-menu {
    right: 0;
    left: auto;
}

.navbar-dark .navbar-nav .nav-link {
    color: rgba(255,255,255,.9);
}

.navbar-dark .navbar-nav .nav-link:hover {
    color: #fff;
}

.navbar-dark .navbar-nav .nav-link.active {
    color: #fff;
    font-weight: bold;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}
</style>

<!-- إضافة Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
