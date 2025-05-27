<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get store logo data if available
$store_logo = '';
if (isset($_SESSION['store_id'])) {
    $store_id = $_SESSION['store_id'];
    $logo_query = $conn->prepare("SELECT logo FROM stores WHERE id = ?");
    $logo_query->bind_param("i", $store_id);
    $logo_query->execute();
    $logo_result = $logo_query->get_result();
    if ($logo_result->num_rows > 0) {
        $logo_data = $logo_result->fetch_assoc();
        $store_logo = $logo_data['logo'];
    }
}
?>

<!-- Store Dashboard Header -->
<header class="store-dashboard-header">
    <!-- Top Bar -->
    <div class="top-bar bg-dark text-white py-1">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a href="../customer/index.php" class="text-white text-decoration-none small">
                        <i class="bi bi-house-door"></i> Main Website
                    </a>
                </div>
                <div>
                    <span class="small"><i class="bi bi-clock"></i> <?php echo date('Y-m-d h:i A'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <?php if (!empty($store_logo)): ?>
                    <img src="../uploads/stores/<?php echo htmlspecialchars($store_logo); ?>" alt="Store Logo" class="me-2 rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                <?php else: ?>
                    <i class="bi bi-shop me-2"></i>
                <?php endif; ?>
                <span>Store Dashboard</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-speedometer2 me-1"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-box me-1"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="product_categories.php">
                            <i class="bi bi-tags me-1"></i> Product Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="bi bi-cart me-1"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="offers.php">
                            <i class="bi bi-tag me-1"></i> Offers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up me-1"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="bi bi-person me-1"></i> Profile
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../customer/store-page.php?id=<?php echo $_SESSION['store_id'] ?? ''; ?>" target="_blank">
                            <i class="bi bi-eye me-1"></i> View Store
                        </a>
                    </li>
                    <?php if (isset($_SESSION['store_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if (!empty($store_logo)): ?>
                                    <img src="../uploads/stores/<?php echo htmlspecialchars($store_logo); ?>" alt="Store Logo" class="me-2 rounded-circle" style="width: 24px; height: 24px; object-fit: cover;">
                                <?php else: ?>
                                    <i class="bi bi-person-circle me-1"></i>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($_SESSION['store_name'] ?? 'My Account'); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="bi bi-person-badge me-2"></i> Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="settings.php">
                                        <i class="bi bi-gear me-2"></i> Settings
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Alert strip if there are notifications -->
    <?php if (isset($_SESSION['notification'])): ?>
    <div class="alert-strip bg-warning text-dark py-2 px-4 mb-0">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo $_SESSION['notification']; unset($_SESSION['notification']); ?>
                </div>
                <button type="button" class="btn-close" onclick="this.parentElement.parentElement.style.display='none';"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</header>

<style>
    .store-dashboard-header .navbar .nav-link {
        padding: 0.7rem 1rem;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .store-dashboard-header .navbar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }
    
    .store-dashboard-header .dropdown-menu {
        border: none;
        border-radius: 0.5rem;
    }
    
    .store-dashboard-header .dropdown-item {
        padding: 0.5rem 1rem;
        transition: all 0.2s ease;
    }
    
    .store-dashboard-header .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    .store-dashboard-header .dropdown-item.text-danger:hover {
        background-color: #f8d7da;
    }
    
    /* Improve menu appearance on mobile devices */
    @media (max-width: 992px) {
        .store-dashboard-header .navbar-collapse {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            padding: 1rem;
            border-radius: 0 0 1rem 1rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            max-height: 80vh;
            overflow-y: auto;
        }
    }
</style>
