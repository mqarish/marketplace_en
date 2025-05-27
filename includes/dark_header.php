<?php
/**
 * Professional header with dark design for the e-marketplace
 * Modern design with black background and orange buttons
 */
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'E-Marketplace'; ?></title>
    
    <!-- Font links -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <!-- Core CSS files -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $root_path ?? ''; ?>customer/styles/dark-header.css">
    <link rel="stylesheet" href="<?php echo $root_path ?? ''; ?>customer/styles/mobile-fixes.css">
    <link rel="stylesheet" href="<?php echo $root_path ?? ''; ?>customer/styles/responsive-mobile.css">
    <link rel="stylesheet" href="<?php echo $root_path ?? ''; ?>customer/styles/mobile-account.css">
    <link rel="stylesheet" href="<?php echo $root_path ?? ''; ?>customer/styles/logout-button.css">
    <link rel="stylesheet" href="<?php echo $root_path ?? ''; ?>customer/styles/english-style.css">
    
    <!-- Mobile compatible files -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- Page-specific CSS files -->
    <?php 
    // Determine current page to include appropriate CSS files
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // CSS file for stores page
    if ($current_page == 'stores.php') {
        echo '<link rel="stylesheet" href="' . ($root_path ?? '') . 'customer/styles/stores-mobile.css">';
    }
    
    // CSS file for featured pages (top-rated, most-liked, deals)
    if ($current_page == 'top-rated.php' || $current_page == 'most-liked.php' || $current_page == 'deals.php') {
        echo '<link rel="stylesheet" href="' . ($root_path ?? '') . 'customer/styles/featured-pages-mobile.css">';
    }
    ?>
    
    <!-- Additional CSS files -->
    <?php if (isset($additional_css)) echo $additional_css; ?>
    
    <!-- Core JavaScript files -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $root_path ?? ''; ?>customer/js/mobile-navigation.js" defer></script>
    <script src="<?php echo $root_path ?? ''; ?>customer/js/location-search.js" defer></script>
    
    <!-- Script for user dropdown menu -->
    <script>
        // Open and close account menu on desktop
        function toggleDesktopMenu() {
            var menu = document.getElementById('desktopAccountMenu');
            if (menu.classList.contains('show')) {
                menu.classList.remove('show');
            } else {
                menu.classList.add('show');
            }
        }
        
        // Close desktop menu when clicking elsewhere
        document.addEventListener('click', function(e) {
            var menu = document.getElementById('desktopAccountMenu');
            var button = document.getElementById('desktopAccountBtn');
            
            if (menu && button) {
                if (!menu.contains(e.target) && !button.contains(e.target) && menu.classList.contains('show')) {
                    menu.classList.remove('show');
                }
            }
        });
        
        // Open account menu on mobile
        function openMobileAccountMenu() {
            var mobileMenu = document.getElementById('mobileAccountMenu');
            if (mobileMenu) {
                mobileMenu.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }
        
        // Close account menu on mobile
        function closeMobileAccountMenu() {
            var mobileMenu = document.getElementById('mobileAccountMenu');
            if (mobileMenu) {
                mobileMenu.classList.remove('show');
                document.body.style.overflow = '';
            }
        }
        
        // Add event listeners when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Add click listener for mobile menu close button
            var closeBtn = document.getElementById('mobileAccountClose');
            if (closeBtn) {
                closeBtn.addEventListener('click', closeMobileAccountMenu);
            }
            
            // Add click listener for background overlay
            var overlay = document.getElementById('mobileAccountOverlay');
            if (overlay) {
                overlay.addEventListener('click', closeMobileAccountMenu);
            }
            
            // Add click listener for mobile menu links
            var mobileMenu = document.getElementById('mobileAccountMenu');
            if (mobileMenu) {
                var links = mobileMenu.querySelectorAll('a');
                links.forEach(function(link) {
                    link.addEventListener('click', closeMobileAccountMenu);
                });
            }
        });
    </script>
</head>
<body>
    <!-- Main Header -->
    <header class="dark-header">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <a href="<?php echo $root_path ?? ''; ?>customer/index.php">
                        <i class="bi bi-shop"></i>
                        <span>E-Marketplace</span>
                    </a>
                </div>
                
                <!-- Search button removed based on user request -->
                
                <?php
                // Setup search component parameters for header
                $form_action = ($root_path ?? '') . "customer/search.php";
                $placeholder = "Search for products or stores...";
                $search_value = isset($_GET['search']) ? $_GET['search'] : '';
                $hidden_fields = [];
                $show_type_selector = true;
                $show_location = true;
                $search_class = "search-form";
                $input_class = "search-input";
                $button_class = "round-btn";
                
                // Call search component
                // Use absolute path to avoid path issues when uploading
                $search_component_path = __DIR__ . "/search_component.php";
                
                // Check if file exists before including it
                if (file_exists($search_component_path)) {
                    include_once $search_component_path;
                } else {
                    // If file not found, include search component directly here
                    ?>
                    <div class="search-container">
                        <form action="<?php echo htmlspecialchars($form_action); ?>" method="GET" class="<?php echo $search_class; ?>" id="search-form">
                            <?php if ($show_type_selector): ?>
                            <div class="search-type">
                                <select name="view" class="search-select">
                                    <option value="products" <?php echo (isset($_GET['view']) && $_GET['view'] == 'stores') ? '' : 'selected'; ?>>Products</option>
                                    <option value="stores" <?php echo (isset($_GET['view']) && $_GET['view'] == 'stores') ? 'selected' : ''; ?>>Stores</option>
                                </select>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="search-input-wrap">
                                <input type="text" name="search" class="<?php echo $input_class; ?>" 
                                       placeholder="<?php echo htmlspecialchars($placeholder); ?>" 
                                       value="<?php echo htmlspecialchars($search_value); ?>" 
                                       autocomplete="off">
                            </div>
                        </form>
                    </div>
                    <?php
                }
                ?>
                
                <div class="user-actions">
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <!-- Account menu for desktop devices -->
                        <div class="user-dropdown d-none d-md-block">
                            <a href="javascript:void(0);" class="user-btn" id="desktopAccountBtn" onclick="toggleDesktopMenu()">
                                <i class="bi bi-person"></i>
                                <span>My Account</span>
                                <i class="bi bi-chevron-down"></i>
                            </a>
                            <div class="dropdown-menu user-menu" id="desktopAccountMenu">
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <i class="bi bi-person-circle"></i>
                                    </div>
                                    <div>
                                        <h6><?php echo $_SESSION['customer_name'] ?? 'User'; ?></h6>
                                        <p><?php echo $_SESSION['customer_email'] ?? ''; ?></p>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo $root_path ?? ''; ?>customer/profile.php" class="dropdown-item">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                                <a href="<?php echo $root_path ?? ''; ?>customer/orders.php" class="dropdown-item">
                                    <i class="bi bi-bag"></i> My Orders
                                </a>
                                <a href="<?php echo $root_path ?? ''; ?>customer/wishlist.php" class="dropdown-item">
                                    <i class="bi bi-heart"></i> Wishlist
                                </a>
                                <a href="<?php echo $root_path ?? ''; ?>customer/addresses.php" class="dropdown-item">
                                    <i class="bi bi-geo-alt"></i> Addresses
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo $root_path ?? ''; ?>customer/logout.php" class="dropdown-item logout-item">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </div>
                        </div>
                        
                        <!-- Account button for mobile devices - opens separate menu -->
                        <a href="javascript:void(0);" class="user-btn d-flex d-md-none mobile-account-link" onclick="openMobileAccountMenu()">
                            <i class="bi bi-person"></i>
                        </a>
                    <?php else: ?>
                        <!-- Login button for desktop devices -->
                        <a href="<?php echo $root_path ?? ''; ?>customer/login.php" class="user-btn d-none d-md-flex">
                            <i class="bi bi-person"></i>
                            <span>Login</span>
                        </a>
                        
                        <!-- Login button for mobile devices -->
                        <a href="<?php echo $root_path ?? ''; ?>customer/login.php" class="user-btn d-flex d-md-none mobile-account-link">
                            <i class="bi bi-person"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <nav class="main-nav">
                <ul class="nav-list">
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" id="categoriesDropdown">
                            <i class="bi bi-list-ul"></i> Categories <i class="bi bi-chevron-down"></i>
                        </a>
                        <div class="dropdown-menu categories-menu" id="categoriesDropdownMenu">
                            <div class="categories-grid">
                                <?php 
                                // Display categories if available
                                if (isset($categories_result) && $categories_result->num_rows > 0) {
                                    $categories_result->data_seek(0);
                                    while ($category = $categories_result->fetch_assoc()): 
                                ?>
                                    <a href="<?php echo $root_path ?? ''; ?>customer/index.php?category=<?php echo $category['id']; ?>" class="category-item">
                                        <div class="category-icon">
                                            <i class="bi bi-tag"></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                                    </a>
                                <?php 
                                    endwhile;
                                } else {
                                    // If categories are not available, display default categories
                                ?>
                                    <a href="#" class="category-item">
                                        <div class="category-icon">
                                            <i class="bi bi-laptop"></i>
                                        </div>
                                        <span>Electronics</span>
                                    </a>
                                    <a href="#" class="category-item">
                                        <div class="category-icon">
                                            <i class="bi bi-bag"></i>
                                        </div>
                                        <span>Clothing</span>
                                    </a>
                                    <a href="#" class="category-item">
                                        <div class="category-icon">
                                            <i class="bi bi-house"></i>
                                        </div>
                                        <span>Home</span>
                                    </a>
                                    <a href="#" class="category-item">
                                        <div class="category-icon">
                                            <i class="bi bi-gem"></i>
                                        </div>
                                        <span>Accessories</span>
                                    </a>
                                    <a href="#" class="category-item">
                                        <div class="category-icon">
                                            <i class="bi bi-controller"></i>
                                        </div>
                                        <span>Games</span>
                                    </a>
                                    <a href="#" class="category-item">
                                        <div class="category-icon">
                                            <i class="bi bi-phone"></i>
                                        </div>
                                        <span>Phones</span>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/products.php" class="nav-link">
                            <i class="bi bi-grid"></i> Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/stores.php" class="nav-link">
                            <i class="bi bi-shop"></i> Stores
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/deals.php" class="nav-link">
                            <i class="bi bi-tag"></i> Deals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/top-rated.php" class="nav-link">
                            <i class="bi bi-star"></i> Top Rated
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/most-liked.php" class="nav-link">
                            <i class="bi bi-heart"></i> Most Liked
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/deals.php" class="nav-link special">
                            <i class="bi bi-lightning"></i> Deals
                        </a>
                    </li>
                </ul>
                
                <button class="mobile-menu-toggle" id="mobileMenuToggle">
                    <i class="bi bi-list"></i>
                </button>
            </nav>
        </div>
    </header>

    <!-- Mobile sidebar menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="mobile-logo">
                <i class="bi bi-shop"></i>
                <span>E-Marketplace</span>
            </div>
            <button class="mobile-menu-close" id="mobileMenuClose">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="mobile-menu-body">
            <ul class="mobile-nav-list">
                <li class="mobile-nav-item">
                    <a href="#" class="mobile-nav-link mobile-dropdown-toggle">
                        <i class="bi bi-list-ul"></i> Categories
                        <i class="bi bi-chevron-down"></i>
                    </a>
                    <div class="mobile-dropdown-menu">
                        <a href="#" class="mobile-dropdown-item">
                            <i class="bi bi-laptop"></i> Electronics
                        </a>
                        <a href="#" class="mobile-dropdown-item">
                            <i class="bi bi-bag"></i> Clothing
                        </a>
                        <a href="#" class="mobile-dropdown-item">
                            <i class="bi bi-house"></i> Home
                        </a>
                        <a href="#" class="mobile-dropdown-item">
                            <i class="bi bi-gem"></i> Accessories
                        </a>
                    </div>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo $root_path ?? ''; ?>customer/products.php" class="mobile-nav-link">
                        <i class="bi bi-grid"></i> Products
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo $root_path ?? ''; ?>customer/stores.php" class="mobile-nav-link">
                        <i class="bi bi-shop"></i> Stores
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo $root_path ?? ''; ?>customer/top-rated.php" class="mobile-nav-link">
                        <i class="bi bi-star"></i> Top Rated
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo $root_path ?? ''; ?>customer/most-liked.php" class="mobile-nav-link">
                        <i class="bi bi-heart"></i> Most Liked
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo $root_path ?? ''; ?>customer/deals.php" class="mobile-nav-link special">
                        <i class="bi bi-lightning"></i> Deals
                    </a>
                </li>
                <li class="mobile-nav-divider"></li>
                <li class="mobile-nav-item">
                    <a href="<?php echo $root_path ?? ''; ?>customer/profile.php" class="mobile-nav-link">
                        <i class="bi bi-person"></i> حسابي
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="<?php echo $root_path ?? ''; ?>customer/cart.php" class="mobile-nav-link">
                        <i class="bi bi-cart3"></i> سلة التسوق
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- قائمة الحساب للأجهزة المحمولة -->
    <?php if (isset($_SESSION['customer_id'])): ?>
    <div class="mobile-account-menu" id="mobileAccountMenu">
        <div class="mobile-account-overlay" id="mobileAccountOverlay"></div>
        <div class="mobile-account-content">
            <div class="mobile-account-header">
                <div class="mobile-account-user">
                    <div class="mobile-account-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="mobile-account-info">
                        <h6><?php echo $_SESSION['customer_name'] ?? 'User'; ?></h6>
                        <p><?php echo $_SESSION['customer_email'] ?? ''; ?></p>
                    </div>
                </div>
                <button class="mobile-account-close" id="mobileAccountClose">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="mobile-account-body">
                <a href="<?php echo $root_path ?? ''; ?>customer/profile.php" class="mobile-account-item">
                    <i class="bi bi-person"></i>
                    <span>Profile</span>
                </a>
                <a href="<?php echo $root_path ?? ''; ?>customer/orders.php" class="mobile-account-item">
                    <i class="bi bi-bag"></i>
                    <span>My Orders</span>
                </a>
                <a href="<?php echo $root_path ?? ''; ?>customer/wishlist.php" class="mobile-account-item">
                    <i class="bi bi-heart"></i>
                    <span>Wishlist</span>
                </a>
                <a href="<?php echo $root_path ?? ''; ?>customer/addresses.php" class="mobile-account-item">
                    <i class="bi bi-geo-alt"></i>
                    <span>Addresses</span>
                </a>
                <div class="mobile-account-divider"></div>
                <a href="<?php echo $root_path ?? ''; ?>customer/logout.php" class="mobile-account-item mobile-account-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
