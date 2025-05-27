<?php
/**
 * Search Results Page - Displays search results for products and stores
 * 
 * Uses dark header and displays search results in a beautiful and user-friendly format
 */

session_start();
require_once '../includes/init.php';
require_once '../includes/functions.php';

// ===== User Verification =====

// Check if logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: /marketplace/customer/login.php');
    exit();
}

// Check customer status
$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT status FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $customer = $result->fetch_assoc();
    if ($customer['status'] !== 'active') {
        session_destroy();
        header('Location: /marketplace/customer/login.php?error=inactive');
        exit();
    }
} else {
    session_destroy();
    header('Location: /marketplace/customer/login.php?error=invalid');
    exit();
}

// ===== Processing Search Criteria =====

// Get search criteria
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : '';
$view_type = isset($_GET['view']) ? $_GET['view'] : 'all'; // all, products, stores
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest'; // newest, price_low, price_high, rating
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Get location information if available
$use_location = isset($_GET['use_location']) && $_GET['use_location'] == '1';

// Check for stored location in session
$location_coords = null;
if (isset($_SESSION['current_location'])) {
    $location_coords = explode(',', $_SESSION['current_location']);
}

// Set coordinates from session if available
$latitude = null;
$longitude = null;
if ($use_location && $location_coords && count($location_coords) == 2) {
    $latitude = (float)$location_coords[0];
    $longitude = (float)$location_coords[1];
}

$location_radius = 5; // Search radius in kilometers

// Check if search term exists
if (empty($search) && !$use_location) {
    $search_error = 'Please enter a search term';
}

// Check if location is specified when location search is enabled
if ($use_location && (!$latitude || !$longitude)) {
    $location_error = 'Your location could not be determined correctly. Please try again.';
}

// ===== Get Categories =====
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
$categories = [];
while ($category = $categories_result->fetch_assoc()) {
    $categories[] = $category;
}

// ===== Get Search Results =====

// Determine search type (products, stores, all)
$products_count = 0;
$stores_count = 0;
$products = [];
$stores = [];

// Search in products
if ($view_type == 'all' || $view_type == 'products') {
    try {
        // Use a simpler query for counting to avoid issues
        $simple_count_sql = "SELECT COUNT(DISTINCT p.id) as total FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    LEFT JOIN stores s ON p.store_id = s.id 
                    WHERE p.status = 'active' AND s.status = 'active'";
                    
        if (!empty($search)) {
            $simple_count_sql .= " AND (p.name LIKE '%" . $conn->real_escape_string($search) . "%' 
                                OR p.description LIKE '%" . $conn->real_escape_string($search) . "%' 
                                OR s.name LIKE '%" . $conn->real_escape_string($search) . "%')";
        }
        
        if (!empty($category_id)) {
            $simple_count_sql .= " AND p.category_id = " . (int)$category_id;
        }
        
        $count_result = $conn->query($simple_count_sql);
        if ($count_result) {
            $products_count = $count_result->fetch_assoc()['total'];
        } else {
            error_log("Error in simple count query: " . $conn->error);
            $products_count = 0;
        }
    } catch (Exception $e) {
        error_log("Exception in count query: " . $e->getMessage());
        $products_count = 0;
    }
    
    try {
        // Create product query with distance calculation
        $simple_products_sql = "SELECT p.*, c.name as category_name, s.name as store_name, s.logo as store_logo,
                        IFNULL(AVG(r.rating), 0) as avg_rating,
                        COUNT(DISTINCT r.id) as rating_count,
                        COUNT(DISTINCT l.id) as likes_count";
                        
        // Add distance calculation if location is specified
        if ($use_location && $latitude && $longitude) {
            $simple_products_sql .= ",
                        (6371 * acos(cos(radians(" . (float)$latitude . ")) * 
                        cos(radians(s.latitude)) * 
                        cos(radians(s.longitude) - radians(" . (float)$longitude . ")) + 
                        sin(radians(" . (float)$latitude . ")) * 
                        sin(radians(s.latitude)))) AS distance";
        }
        
        $simple_products_sql .= " FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        LEFT JOIN stores s ON p.store_id = s.id 
                        LEFT JOIN reviews r ON p.id = r.product_id
                        LEFT JOIN product_likes l ON p.id = l.product_id
                        WHERE p.status = 'active' AND s.status = 'active'";
                        
        if ($use_location && $latitude && $longitude) {
            // Add condition for stores with coordinates within 5 km
            $simple_products_sql .= " AND s.latitude IS NOT NULL AND s.longitude IS NOT NULL";
            
            // Add distance condition (within 5 km only)
            $simple_products_sql .= " HAVING distance <= 5";
        }
        
        if (!empty($search)) {
            $simple_products_sql .= " AND (p.name LIKE '%" . $conn->real_escape_string($search) . "%' 
                                OR p.description LIKE '%" . $conn->real_escape_string($search) . "%' 
                                OR s.name LIKE '%" . $conn->real_escape_string($search) . "%')";
        }
        
        if (!empty($category_id)) {
            $simple_products_sql .= " AND p.category_id = " . (int)$category_id;
        }
        
        $simple_products_sql .= " GROUP BY p.id";
        
        // Sort results
        if ($use_location && $latitude && $longitude && $sort == 'newest') {
            // If location is specified and no different sort is specified, sort by distance
            $simple_products_sql .= " ORDER BY distance ASC";
            $sort = 'distance'; // Change sort type for display in UI
        } else {
            switch ($sort) {
                case 'price_low':
                    $simple_products_sql .= " ORDER BY p.price ASC";
                    break;
                case 'price_high':
                    $simple_products_sql .= " ORDER BY p.price DESC";
                    break;
                case 'rating':
                    $simple_products_sql .= " ORDER BY avg_rating DESC";
                    break;
                case 'distance':
                    if ($use_location && $latitude && $longitude) {
                        $simple_products_sql .= " ORDER BY distance ASC";
                    } else {
                        $simple_products_sql .= " ORDER BY p.created_at DESC";
                    }
                    break;
                case 'newest':
                default:
                    $simple_products_sql .= " ORDER BY p.created_at DESC";
                    break;
            }
        }
    }
    
        // Add pagination limits
        $simple_products_sql .= " LIMIT " . $items_per_page . " OFFSET " . $offset;
        
        // Execute the query
        $products_result = $conn->query($simple_products_sql);
        if ($products_result) {
            while ($product = $products_result->fetch_assoc()) {
                $products[] = $product;
            }
        } else {
            error_log("Error in products query: " . $conn->error);
        }
    } catch (Exception $e) {
        error_log("Exception in products query: " . $e->getMessage());
    }
}

// Search in stores
if ($view_type == 'all' || $view_type == 'stores') {
    $stores_sql = "SELECT s.*, 
                  COUNT(DISTINCT p.id) as products_count,
                  COUNT(DISTINCT CASE 
                      WHEN o.id IS NOT NULL 
                      AND o.start_date <= CURDATE()
                      AND o.end_date >= CURDATE()
                      AND o.status = 'active'
                      THEN o.id
                  END) as active_offers_count
                  FROM stores s
                  LEFT JOIN products p ON s.id = p.store_id AND p.status = 'active'
                  LEFT JOIN offers o ON s.id = o.store_id
                      AND o.start_date <= CURDATE()
                      AND o.end_date >= CURDATE()
                      AND o.status = 'active'";
    
    $where_clauses = ["s.status = 'active'"];
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $where_clauses[] = "(s.name LIKE ? OR s.description LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    $stores_sql .= " WHERE " . implode(" AND ", $where_clauses);
    $stores_sql .= " GROUP BY s.id";
    $stores_sql .= " ORDER BY s.name ASC";
    
    // Total number of stores (for pagination)
    $count_sql = "SELECT COUNT(*) as total FROM (" . $stores_sql . ") as subquery";
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $stores_count = $count_stmt->get_result()->fetch_assoc()['total'];
    
    // Add limits for current page
    $stores_sql .= " LIMIT ? OFFSET ?";
    $params[] = $items_per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $stores_stmt = $conn->prepare($stores_sql);
    if (!empty($params)) {
        $stores_stmt->bind_param($types, ...$params);
    }
    $stores_stmt->execute();
    $stores_result = $stores_stmt->get_result();
    
    while ($store = $stores_result->fetch_assoc()) {
        $stores[] = $store;
    }
}

// Total number of results
$total_results = $products_count + $stores_count;

// Calculate total number of pages
$total_pages = ceil($total_results / $items_per_page);

// Set page title
$page_title = 'Search Results: ' . htmlspecialchars($search);

// Set root path
$root_path = '../';

// Include dark header
include_once('../includes/dark_header.php');
?>

<!-- Include search page stylesheet with no caching -->
<link rel="stylesheet" href="styles/search-page.css?v=<?php echo time(); ?>">

<!-- Direct styling for buttons to ensure they appear correctly -->
<style>
    /* Main button styling */
    .btn-primary {
        background-color: #333 !important;
        border-color: #333 !important;
        color: #fff !important;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background-color: #555 !important;
        border-color: #555 !important;
    }
    
    .btn-primary:active, .btn-primary:focus {
        background-color: #FF7A00 !important;
        border-color: #FF7A00 !important;
        box-shadow: 0 0 0 0.25rem rgba(255, 122, 0, 0.25) !important;
    }
    
    /* Filter and sort button styling */
    .mobile-filter-button, .mobile-sort-button {
        background-color: #333 !important;
        color: #fff !important;
        border: none !important;
        transition: all 0.3s ease;
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
    }
    
    .mobile-filter-button:hover, .mobile-sort-button:hover {
        background-color: #555 !important;
    }
    
    .mobile-filter-button:active, .mobile-sort-button:active,
    .mobile-filter-button:focus, .mobile-sort-button:focus {
        background-color: #FF7A00 !important;
        color: #fff !important;
        box-shadow: 0 0 0 0.25rem rgba(255, 122, 0, 0.25) !important;
    }
    
    /* Visit store button styling */
    .btn-outline-primary {
        color: #333 !important;
        border-color: #333 !important;
        background-color: transparent !important;
        transition: all 0.3s ease;
    }
    
    .btn-outline-primary:hover {
        background-color: #f8f9fa !important;
        color: #333 !important;
        border-color: #333 !important;
    }
    
    .btn-outline-primary:active, .btn-outline-primary:focus {
        background-color: #FF7A00 !important;
        border-color: #FF7A00 !important;
        color: #fff !important;
        box-shadow: 0 0 0 0.25rem rgba(255, 122, 0, 0.25) !important;
    }
</style>

<!-- Additional styling for mobile popovers -->
<style>
    /* Popover overlay */
    .mobile-filter-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .mobile-filter-overlay.show {
        display: block;
        opacity: 1;
    }
    
    /* Filter popover */
    .mobile-filter-modal, .mobile-sort-modal {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: #fff;
        border-radius: 15px 15px 0 0;
        z-index: 1001;
        transform: translateY(100%);
        transition: transform 0.3s ease;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .mobile-filter-modal.show, .mobile-sort-modal.show {
        transform: translateY(0);
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    .modal-header h5 {
        margin: 0;
        font-weight: 600;
    }
    
    .close-modal {
        background: none;
        border: none;
        font-size: 1.2rem;
        color: #6c757d;
        cursor: pointer;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer {
        padding: 1rem;
        border-top: 1px solid #dee2e6;
    }
    
    /* Sort options */
    .sort-options {
        display: flex;
        flex-direction: column;
    }
    
    .sort-option {
        padding: 1rem;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
    }
    
    .sort-option:last-child {
        border-bottom: none;
    }
    
    .sort-option.active {
        color: #FF7A00;
        font-weight: 500;
    }
</style>

<!-- Search header section -->
<section class="search-header">
    <div class="container">
        <h1 class="search-title">Search Results: "<?php echo htmlspecialchars($search); ?>"</h1>
        <p class="search-info">Found <?php echo $total_results; ?> results</p>
        
        <!-- Search tabs -->
        <div class="search-tabs">
            <a href="search.php?search=<?php echo urlencode($search); ?>&view=all<?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?>&sort=<?php echo $sort; ?>" class="search-tab <?php echo $view_type == 'all' ? 'active' : ''; ?>">
                All <span class="badge"><?php echo $total_results; ?></span>
            </a>
            <a href="search.php?search=<?php echo urlencode($search); ?>&view=products<?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?>&sort=<?php echo $sort; ?>" class="search-tab <?php echo $view_type == 'products' ? 'active' : ''; ?>">
                Products <span class="badge"><?php echo $products_count; ?></span>
            </a>
            <a href="search.php?search=<?php echo urlencode($search); ?>&view=stores<?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?>&sort=<?php echo $sort; ?>" class="search-tab <?php echo $view_type == 'stores' ? 'active' : ''; ?>">
                Stores <span class="badge"><?php echo $stores_count; ?></span>
            </a>
        </div>
    </div>
</section>

<div class="container mb-5">
    <div class="row">
        <!-- Mobile filter bar -->
        <div class="d-lg-none mb-4">
            <div class="mobile-filter-bar">
                <button type="button" class="mobile-filter-button" id="mobileFilterButton">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <?php if ($view_type == 'all' || $view_type == 'products'): ?>
                <button type="button" class="mobile-sort-button" id="mobileSortButton">
                    <i class="bi bi-sort-down"></i> Sort
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filter section -->
        <div class="col-lg-3 mb-4 d-none d-lg-block">
            <div class="search-filters">
                <h4 class="filter-title">
                    Filter Results
                    <button type="button" class="filter-toggle" id="filterToggle">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </h4>
                
                <div class="filter-content" id="filterContent">
                    <!-- Category filter -->
                    <?php if ($view_type == 'all' || $view_type == 'products'): ?>
                    <div class="filter-group">
                        <label class="filter-label">Category</label>
                        <select class="sort-select" id="categoryFilter" onchange="applyFilters()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Sort filter -->
                    <?php if ($view_type == 'all' || $view_type == 'products'): ?>
                    <div class="filter-group">
                        <label class="filter-label">Sort By</label>
                        <select class="sort-select" id="sortFilter" onchange="applyFilters()">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Rating</option>
                            <?php if ($use_location && $latitude && $longitude): ?>
                            <option value="distance" <?php echo $sort == 'distance' ? 'selected' : ''; ?>>Distance</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Apply filters button -->
                    <div class="mt-3">
                        <button class="btn btn-primary w-100" onclick="applyFilters()">Apply Filters</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Results section -->
        <div class="col-lg-9 search-results-container">
            <?php if (empty($search)): ?>
                <!-- Search input error message -->
                <div class="no-results">
                    <i class="bi bi-search"></i>
                    <h3>Please enter a search term</h3>
                    <p>Enter a search term in the search bar above to find products and stores.</p>
                </div>
            <?php elseif ($total_results == 0): ?>
                <!-- No results found message -->
                <!-- No results found message -->
                <div class="no-results">
                    <i class="bi bi-emoji-frown"></i>
                    <h3>No Results Found</h3>
                    <p>We couldn't find any results matching your search "<?php echo htmlspecialchars($search); ?>". Please try different search terms or filters.</p>
                </div>
            <?php else: ?>
                <!-- Display product results -->
                <?php if (($view_type == 'all' || $view_type == 'products') && count($products) > 0): ?>
                    <h2 class="section-title mb-4">Products</h2>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-5">
                        <?php foreach ($products as $product): ?>
                            <div class="col">
                                <div class="card h-100 product-card <?php echo ($use_location && $latitude && $longitude && isset($product['distance']) && $product['distance'] <= 10) ? 'nearby-product' : ''; ?>">
                                    <?php if ($use_location && $latitude && $longitude && isset($product['distance']) && $product['distance'] <= 10): ?>
                                    <div class="nearby-badge">
                                        <i class="bi bi-geo-alt"></i> Near You
                                    </div>
                                    <?php endif; ?>
                                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="product-link">
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                 class="card-img-top product-image" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                                 style="height: 200px;">
                                                <i class="bi bi-image text-secondary" style="font-size: 4rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text small text-muted">
                                            <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <span class="fw-bold text-primary"><?php echo number_format($product['price'], 2); ?> ريال</span>
                                            <a href="store-page.php?id=<?php echo $product['store_id']; ?>" class="text-decoration-none">
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product['store_name']); ?></span>
                                            </a>
                                        </div>
                                        <div class="product-meta mt-3">
                                            <div class="rating-pill">
                                                <span class="value"><?php echo number_format($product['avg_rating'], 1); ?></span>
                                                <div class="stars-wrap">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= floor($product['avg_rating'])): ?>
                                                            <i class="bi bi-star-fill"></i>
                                                        <?php elseif ($i - 0.5 <= $product['avg_rating']): ?>
                                                            <i class="bi bi-star-half"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-star"></i>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>
                                                <?php if ($use_location && $latitude && $longitude && isset($product['distance'])): ?>
                                                <div class="distance-pill">
                                                    <i class="bi bi-geo-alt"></i>
                                                    <span><?php echo number_format($product['distance'], 1); ?> كم</span>
                                                </div>
                                                <?php endif; ?>
                                                <span class="meta-count">(<?php echo $product['rating_count']; ?>)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Display store results -->
                <?php if (($view_type == 'all' || $view_type == 'stores') && count($stores) > 0): ?>
                    <h2 class="section-title mb-4">Stores</h2>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-5">
                        <?php foreach ($stores as $store): ?>
                            <div class="col">
                                <div class="card h-100 store-card">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <?php if (!empty($store['logo'])): ?>
                                                <img src="../uploads/stores/<?php echo htmlspecialchars($store['logo']); ?>" 
                                                     class="store-logo rounded-circle" 
                                                     alt="<?php echo htmlspecialchars($store['name']); ?>"
                                                     style="width: 100px; height: 100px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="store-logo-placeholder rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto"
                                                     style="width: 100px; height: 100px;">
                                                    <i class="bi bi-shop text-secondary" style="font-size: 2.5rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <h5 class="card-title">
                                            <a href="store-page.php?id=<?php echo $store['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($store['name']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text small text-muted">
                                            <?php echo htmlspecialchars(substr($store['description'], 0, 100)) . (strlen($store['description']) > 100 ? '...' : ''); ?>
                                        </p>
                                        <div class="store-stats d-flex justify-content-center gap-3 mt-3">
                                            <div class="stat-badge">
                                                <i class="bi bi-box-seam text-primary me-1"></i>
                                                <span><?php echo $store['products_count']; ?> منتج</span>
                                            </div>
                                            <?php if ($store['active_offers_count'] > 0): ?>
                                                <div class="stat-badge">
                                                    <i class="bi bi-tags text-success me-1"></i>
                                                    <span><?php echo $store['active_offers_count']; ?> عرض</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent border-top-0 text-center">
                                        <a href="store-page.php?id=<?php echo $store['id']; ?>" class="btn btn-outline-primary">زيارة المتجر</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- ترقيم الصفحات -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="search.php?search=<?php echo urlencode($search); ?>&view=<?php echo $view_type; ?><?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="search.php?search=<?php echo urlencode($search); ?>&view=<?php echo $view_type; ?><?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?>&sort=<?php echo $sort; ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="search.php?search=<?php echo urlencode($search); ?>&view=<?php echo $view_type; ?><?php echo !empty($category_id) ? '&category=' . $category_id : ''; ?>&sort=<?php echo $sort; ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Include search page script -->
<script src="js/search-page.js"></script>

<!-- Filter application script -->
<script>
    function applyFilters() {
        const searchParams = new URLSearchParams(window.location.search);
        const category = document.getElementById('categoryFilter')?.value;
        const sort = document.getElementById('sortFilter')?.value;
        
        if (category) {
            searchParams.set('category', category);
        } else {
            searchParams.delete('category');
        }
        
        if (sort) {
            searchParams.set('sort', sort);
        }
        
        // إعادة تعيين رقم الصفحة عند تغيير الفلاتر
        searchParams.set('page', '1');
        
        window.location.href = `search.php?${searchParams.toString()}`;
    }
</script>

<?php include_once('../includes/footer.php'); ?>
