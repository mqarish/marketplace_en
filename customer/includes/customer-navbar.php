<?php
if (!isset($_SESSION)) {
    session_start();
}

// التحقق من تسجيل دخول العميل
if (!isset($_SESSION['customer_id'])) {
    header('Location: ../login.php');
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-store me-2"></i>
            منصة المتاجر
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" 
                       href="index.php">
                        <i class="fas fa-home me-1"></i>
                        الرئيسية
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>" 
                       href="orders.php">
                        <i class="fas fa-shopping-cart me-1"></i>
                        طلباتي
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" 
                       href="profile.php">
                        <i class="fas fa-user me-1"></i>
                        حسابي
                    </a>
                </li>
            </ul>
            
            <div class="d-flex">
                <form class="d-flex me-2" action="search.php" method="GET">
                    <input class="form-control me-2" type="search" 
                           placeholder="ابحث عن منتجات..." 
                           name="q" required>
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                
                <a href="cart.php" class="btn btn-outline-light position-relative me-2">
                    <i class="fas fa-shopping-cart"></i>
                    <?php
                    // عرض عدد المنتجات في السلة
                    if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
                        echo '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">'
                            . count($_SESSION['cart']) . '</span>';
                    }
                    ?>
                </a>
                
                <a href="../logout.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i>
                    تسجيل خروج
                </a>
            </div>
        </div>
    </div>
</nav>
