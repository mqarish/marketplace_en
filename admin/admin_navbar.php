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
            E-Marketplace
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="stores.php" class="nav-link <?php echo $current_page === 'stores.php' ? 'active' : ''; ?>">
                        <i class="fas fa-store"></i>
                        Stores
                    </a>
                </li>
                <li class="nav-item">
                    <a href="categories.php" class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>
                        Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a href="products.php" class="nav-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i>
                        Products
                    </a>
                </li>
                <li class="nav-item">
                    <a href="orders.php" class="nav-link <?php echo $current_page === 'orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i>
                        Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="customers.php" class="nav-link <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        Customers
                    </a>
                </li>
                <li class="nav-item">
                    <a href="subscriptions.php" class="nav-link <?php echo $current_page === 'subscriptions.php' ? 'active' : ''; ?>">
                        <i class="fas fa-credit-card"></i>
                        Subscriptions
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-cog"></i>
                        Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a href="suggestions.php" class="nav-link <?php echo $current_page === 'suggestions.php' ? 'active' : ''; ?>">
                        <i class="fas fa-lightbulb"></i>
                        Customer Suggestions
                        <?php
                        // Display number of new suggestions
                        $pending_suggestions = $conn->query("SELECT COUNT(*) as count FROM suggestions WHERE status = 'pending'")->fetch_assoc();
                        if ($pending_suggestions && $pending_suggestions['count'] > 0) {
                            echo '<span class="badge bg-danger rounded-pill ms-2">' . $pending_suggestions['count'] . '</span>';
                        }
                        ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        System Settings
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav user-menu">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i>
                        <?php echo $_SESSION['admin_name'] ?? 'Admin'; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
