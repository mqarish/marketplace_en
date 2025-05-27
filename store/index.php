<?php
// Make sure there are no outputs before starting the session
require_once '../includes/init.php';

// Check store login and status
require_once '../includes/check_store_status.php';

// Get store data
$store_id = $_SESSION['store_id'];
$stmt = $conn->prepare("SELECT * FROM stores WHERE id = ?");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$store = $stmt->get_result()->fetch_assoc();

// Get products
$products_stmt = $conn->prepare("SELECT * FROM products WHERE store_id = ? ORDER BY created_at DESC");
$products_stmt->bind_param("i", $store_id);
$products_stmt->execute();
$products = $products_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get orders
$orders_stmt = $conn->prepare("
    SELECT o.*, c.name as customer_name 
    FROM orders o 
    JOIN customers c ON o.customer_id = c.id 
    WHERE o.store_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$orders_stmt->bind_param("i", $store_id);
$orders_stmt->execute();
$recent_orders = $orders_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Store statistics
// 1. Total products
$products_count = count($products);

// 2. Total orders
$orders_count = 0;
foreach ($recent_orders as $order) {
    $orders_count++;
}

// 3. Total sales
$total_sales = 0;
foreach ($recent_orders as $order) {
    $total_sales += $order['total_price'];
}

// 4. Number of unique customers
$customers_count = count(array_unique(array_column($recent_orders, 'customer_id')));

// Sales data for chart (last 7 days)
try {
    $chart_data_query = "SELECT DATE(created_at) as date, SUM(total_price) as daily_sales 
                        FROM orders 
                        WHERE store_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
                        GROUP BY DATE(created_at) 
                        ORDER BY date ASC";
    $chart_stmt = $conn->prepare($chart_data_query);
    
    if ($chart_stmt === false) {
        // If preparation fails, log the error
        error_log("Error in query preparation: " . $conn->error);
        $chart_data_result = [];
    } else {
        $chart_stmt->bind_param("i", $store_id);
        $chart_stmt->execute();
        $chart_data_result = $chart_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $chart_stmt->close();
    }
} catch (Exception $e) {
    // Catch any exception
    error_log("Error in chart query: " . $e->getMessage());
    $chart_data_result = [];
}

// Convert chart data to JSON format
$chart_dates = [];
$chart_sales = [];

// Create array for the last seven days
$last_seven_days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $last_seven_days[$date] = 0;
}

// Fill in existing data
foreach ($chart_data_result as $data) {
    $last_seven_days[$data['date']] = $data['daily_sales'];
}

// Convert data to appropriate format for the chart
foreach ($last_seven_days as $date => $sales) {
    $chart_dates[] = date('D', strtotime($date)); // Abbreviated day name
    $chart_sales[] = $sales;
}

// Convert data to JSON for use in JavaScript
$chart_dates_json = json_encode($chart_dates);
$chart_sales_json = json_encode($chart_sales);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($store['name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Add new font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --light-bg: #f9fafb;
            --dark-bg: #1f2937;
            --card-border-radius: 0.75rem;
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f8fa;
            color: #333;
        }
        
        /* Sidebar styling */
        .dashboard-sidebar {
            background-color: white;
            border-radius: var(--card-border-radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all var(--transition-speed) ease;
        }
        
        .dashboard-sidebar .sidebar-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 1.25rem 1rem;
            text-align: center;
        }
        
        .dashboard-sidebar .sidebar-header img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 0.75rem;
        }
        
        .dashboard-sidebar .nav-link {
            padding: 0.75rem 1.25rem;
            color: #4b5563;
            border-right: 3px solid transparent;
            transition: all var(--transition-speed) ease;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .dashboard-sidebar .nav-link:hover {
            background-color: #f3f4f6;
            color: var(--primary-color);
        }
        
        .dashboard-sidebar .nav-link.active {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            border-right-color: var(--primary-color);
        }
        
        .dashboard-sidebar .nav-link i {
            margin-left: 0.75rem;
            font-size: 1.1rem;
            opacity: 0.8;
        }
        
        /* Card styling */
        .dashboard-card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all var(--transition-speed) ease;
            height: 100%;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .dashboard-card .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 1rem 1.25rem;
        }
        
        .dashboard-card .card-footer {
            background-color: white;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1.25rem;
        }
        
        /* Statistics cards styling */
        .stat-card {
            border: none;
            border-radius: var(--card-border-radius);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all var(--transition-speed) ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card .card-body {
            display: flex;
            align-items: center;
            padding: 1.25rem;
        }
        
        .stat-card .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-left: 1rem;
            color: white;
        }
        
        .stat-card .stat-content h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-card .stat-content p {
            margin-bottom: 0;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .stat-card.products .icon-box {
            background-color: rgba(16, 185, 129, 0.2);
            color: var(--success-color);
        }
        
        .stat-card.orders .icon-box {
            background-color: rgba(59, 130, 246, 0.2);
            color: var(--info-color);
        }
        
        .stat-card.revenue .icon-box {
            background-color: rgba(245, 158, 11, 0.2);
            color: var(--warning-color);
        }
        
        .stat-card.customers .icon-box {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--danger-color);
        }
        
        /* Table styling */
        .dashboard-table {
            border-radius: var(--card-border-radius);
            overflow: hidden;
        }
        
        .dashboard-table th {
            background-color: #f9fafb;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .dashboard-table .table-action-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            margin-right: 0.25rem;
            color: #6b7280;
            background-color: #f3f4f6;
            border: none;
            transition: all var(--transition-speed) ease;
        }
        
        .dashboard-table .table-action-btn:hover {
            background-color: #e5e7eb;
            color: #374151;
        }
        
        .dashboard-table .table-action-btn.edit:hover {
            background-color: rgba(59, 130, 246, 0.2);
            color: var(--info-color);
        }
        
        .dashboard-table .table-action-btn.delete:hover {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--danger-color);
        }
        
        /* Button styling */
        .btn-dashboard-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
            font-weight: 500;
            border-radius: 6px;
            padding: 0.5rem 1.25rem;
            transition: all var(--transition-speed) ease;
        }
        
        .btn-dashboard-primary:hover {
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);
            transform: translateY(-1px);
            color: white;
        }
        
        /* Chart styling */
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        
        /* Statistics styling */
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .bg-primary-subtle {
            background-color: rgba(37, 99, 235, 0.15);
        }
        
        .bg-success-subtle {
            background-color: rgba(16, 185, 129, 0.15);
        }
        
        .bg-info-subtle {
            background-color: rgba(6, 182, 212, 0.15);
        }
        
        .bg-warning-subtle {
            background-color: rgba(245, 158, 11, 0.15);
        }
        
        .bg-danger-subtle {
            background-color: rgba(239, 68, 68, 0.15);
        }
        
        .stat-title {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .stat-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding-top: 0.75rem;
        }
        
        .store-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .store-logo-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #9ca3af;
            margin: 0 auto;
        }
        
        /* Mobile device styling */
        @media (max-width: 992px) {
            .dashboard-sidebar {
                margin-bottom: 1.5rem;
            }
            
            .stat-card .icon-box {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }
            
            .stat-card .stat-content h3 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>
    
    <div class="container-fluid py-4 px-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Page title -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($store['name']); ?></h2>
                <p class="text-muted">Here's an overview of your store</p>
            </div>
            <div class="d-flex align-items-center">
                <div class="input-group me-3" style="max-width: 300px;">
                    <input type="text" id="storeUrl" class="form-control" value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/customer/store-page.php?id=' . $store_id; ?>" readonly>
                    <button class="btn btn-outline-primary" type="button" onclick="copyStoreUrl()">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>
                <a href="add-product.php" class="btn btn-dashboard-primary">
                    <i class="bi bi-plus-circle me-2"></i> Add Product
                </a>
            </div>
        </div>

        <!-- Statistics cards -->
        <div class="row mb-4">
            <!-- Products statistics -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="dashboard-card h-100">
                    <div class="card-body">
                        <div class="row align-items-center no-gutters">
                            <div class="col-auto">
                                <div class="stat-icon bg-primary-subtle text-primary">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                            </div>
                            <div class="col ps-3">
                                <h5 class="stat-title text-muted">Products</h5>
                                <h3 class="stat-value mb-0"><?php echo $products_count; ?></h3>
                            </div>
                        </div>
                        <div class="stat-footer mt-3">
                            <a href="products.php" class="text-decoration-none">
                                <span class="small">Manage Products</span>
                                <i class="bi bi-chevron-right small"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders statistics -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="dashboard-card h-100">
                    <div class="card-body">
                        <div class="row align-items-center no-gutters">
                            <div class="col-auto">
                                <div class="stat-icon bg-success-subtle text-success">
                                    <i class="bi bi-cart3"></i>
                                </div>
                            </div>
                            <div class="col ps-3">
                                <h5 class="stat-title text-muted">Orders</h5>
                                <h3 class="stat-value mb-0"><?php echo $orders_count; ?></h3>
                            </div>
                        </div>
                        <div class="stat-footer mt-3">
                            <a href="orders.php" class="text-decoration-none">
                                <span class="small">Manage Orders</span>
                                <i class="bi bi-chevron-right small"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue statistics -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="dashboard-card h-100">
                    <div class="card-body">
                        <div class="row align-items-center no-gutters">
                            <div class="col-auto">
                                <div class="stat-icon bg-info-subtle text-info">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                            </div>
                            <div class="col ps-3">
                                <h5 class="stat-title text-muted">Sales</h5>
                                <h3 class="stat-value mb-0"><?php echo number_format($total_sales, 2); ?> SAR</h3>
                            </div>
                        </div>
                        <div class="stat-footer mt-3">
                            <span class="badge bg-success-subtle text-success">
                                <i class="bi bi-graph-up"></i> Sales Statistics
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer statistics -->
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="dashboard-card h-100">
                    <div class="card-body">
                        <div class="row align-items-center no-gutters">
                            <div class="col-auto">
                                <div class="stat-icon bg-warning-subtle text-warning">
                                    <i class="bi bi-people"></i>
                                </div>
                            </div>
                            <div class="col ps-3">
                                <h5 class="stat-title text-muted">Customers</h5>
                                <h3 class="stat-value mb-0"><?php echo $customers_count; ?></h3>
                            </div>
                        </div>
                        <div class="stat-footer mt-3">
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-person-check"></i> Unique Customers
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mb-4">
                <!-- Sales chart -->
                <div class="dashboard-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Sales Report</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="reportOptions" data-bs-toggle="dropdown" aria-expanded="false">
                                Last 7 days
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="reportOptions">
                                <li><a class="dropdown-item active" href="#">Last 7 days</a></li>
                                <li><a class="dropdown-item" href="#">Last 30 days</a></li>
                                <li><a class="dropdown-item" href="#">This month</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="position: relative; height: 300px;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 mb-4">
                <!-- Store information -->
                <div class="dashboard-card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">Store Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($store['logo'])): ?>
                                <img src="../uploads/stores/<?php echo $store['logo']; ?>" class="store-logo mb-3" alt="<?php echo htmlspecialchars($store['name']); ?>">
                            <?php else: ?>
                                <div class="store-logo-placeholder mb-3">
                                    <i class="bi bi-shop"></i>
                                </div>
                            <?php endif; ?>
                            <h4><?php echo htmlspecialchars($store['name']); ?></h4>
                            <p class="text-muted small">Since <?php echo date('Y/m/d', strtotime($store['created_at'])); ?></p>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#updateLogoModal">
                                <i class="bi bi-camera me-1"></i> Change Logo
                            </button>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small">Store URL</label>
                            <div class="input-group">
                                <input type="text" id="storeUrl" class="form-control" value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/customer/store-page.php?id=' . $store_id; ?>" readonly>
                                <button class="btn btn-outline-primary" type="button" onclick="copyStoreUrl()">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <div class="text-end mt-2">
                                <button class="btn btn-sm btn-link text-decoration-none" data-bs-toggle="modal" data-bs-target="#editUrlModal">
                                    <i class="bi bi-pencil-square me-1"></i> Edit URL
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <a href="edit-profile.php" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i> Edit Information
                            </a>
                            <a href="../customer/store-page.php?id=<?php echo $store_id; ?>" class="btn btn-sm btn-primary" target="_blank">
                                <i class="bi bi-box-arrow-up-right me-1"></i> View Store
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Products -->
            <div class="col-md-6 mb-4">
                <div class="dashboard-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Products</h5>
                        <a href="products.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($products)): ?>
                            <div class="table-responsive">
                                <table class="table dashboard-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($products, 0, 5) as $product): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($product['image'])): ?>
                                                            <img src="../uploads/products/<?php echo $product['image']; ?>" class="product-thumbnail me-2" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                                        <?php else: ?>
                                                            <div class="product-thumbnail-placeholder me-2" style="width: 40px; height: 40px; background-color: #f3f4f6; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                                <i class="bi bi-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                                            <div class="text-muted small">#<?php echo $product['id']; ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo number_format($product['price'], 2); ?> SAR</td>
                                                <td>
                                                    <?php 
                                                    // Check if 'stock' key exists and if it's greater than 0
                                                    $is_available = isset($product['stock']) ? ($product['stock'] > 0) : (isset($product['quantity']) ? ($product['quantity'] > 0) : true);
                                                    if ($is_available): 
                                                    ?>
                                                        <span class="badge bg-success-subtle text-success">Available</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger-subtle text-danger">Not Available</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-box text-muted" style="font-size: 2rem;"></i>
                                <p class="text-muted mt-2">No products available</p>
                                <a href="add-product.php" class="btn btn-sm btn-primary mt-2">Add New Product</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

                    <!-- Recent Orders -->
                    <div class="col-md-6 mb-4">
                        <div class="dashboard-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Recent Orders</h5>
                                <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <?php if (!empty($recent_orders)): ?>
                                    <div class="table-responsive">
                                        <table class="table dashboard-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Customer</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recent_orders as $order): ?>
                                                    <tr>
                                                        <td><a href="order-details.php?id=<?php echo $order['id']; ?>" class="text-decoration-none">#<?php echo $order['id']; ?></a></td>
                                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                        <td><?php echo number_format($order['total_price'], 2); ?> SAR</td>
                                                        <td>
                                                            <?php 
                                                            $status_class = '';
                                                            $status_text = '';
                                                            
                                                            switch ($order['status']) {
                                                                case 'pending':
                                                                    $status_class = 'bg-warning';
                                                                    $status_text = 'Pending';
                                                                    break;
                                                                case 'processing':
                                                                    $status_class = 'bg-info';
                                                                    $status_text = 'Processing';
                                                                    break;
                                                                case 'shipped':
                                                                    $status_class = 'bg-primary';
                                                                    $status_text = 'Shipped';
                                                                    break;
                                                                case 'completed':
                                                                    $status_class = 'bg-success';
                                                                    $status_text = 'Completed';
                                                                    break;
                                                                case 'cancelled':
                                                                    $status_class = 'bg-danger';
                                                                    $status_text = 'Cancelled';
                                                                    break;
                                                                default:
                                                                    $status_class = 'bg-secondary';
                                                                    $status_text = $order['status'];
                                                            }
                                                            ?>
                                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-cart-x text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mt-2">No orders available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for editing store URL -->
    <div class="modal fade" id="editUrlModal" tabindex="-1" aria-labelledby="editUrlModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUrlModalLabel">Edit Store URL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newStoreUrl" class="form-label">New URL</label>
                        <input type="text" class="form-control" id="newStoreUrl" value="<?php echo htmlspecialchars($store['slug'] ?? ''); ?>">
                        <div class="form-text">URL must contain only English letters, numbers, and hyphens</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateStoreUrl()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for updating store logo -->
    <div class="modal fade" id="updateLogoModal" tabindex="-1" aria-labelledby="updateLogoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateLogoModalLabel">Change Store Logo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateLogoForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="storeLogo" class="form-label">Choose a new logo image</label>
                            <input type="file" class="form-control" id="storeLogo" name="logo" accept="image/*" required>
                            <div class="form-text">Image must be in JPG, PNG, or GIF format and not exceed 5MB in size</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateStoreLogo()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    function copyStoreUrl() {
        var urlInput = document.getElementById('storeUrl');
        urlInput.select();
        document.execCommand('copy');
        
        // Show feedback
        var button = urlInput.nextElementSibling;
        var originalHtml = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check"></i>';
        button.classList.add('btn-success');
        button.classList.remove('btn-outline-primary');
        
        setTimeout(function() {
            button.innerHTML = originalHtml;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-primary');
        }, 2000);
    }

    function updateStoreUrl() {
        var newUrl = document.getElementById('newStoreUrl').value;
        
        // Validate URL
        if (!/^[a-zA-Z0-9-]+$/.test(newUrl)) {
            alert('URL must contain only English letters, numbers, and hyphens');
            return;
        }

        // Send request to update URL
        fetch('update_store_url.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'slug=' + encodeURIComponent(newUrl)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('storeUrl').value = newUrl;
                $('#editUrlModal').modal('hide');
                // Refresh page to show changes
                location.reload();
            } else {
                alert(data.message || 'An error occurred while updating the URL');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the URL');
        });
    }

    function updateStoreLogo() {
        var formData = new FormData();
        var fileInput = document.getElementById('storeLogo');
        
        if (fileInput.files.length === 0) {
            alert('Please select an image');
            return;
        }

        formData.append('logo', fileInput.files[0]);

        $.ajax({
            url: 'update_store_logo.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    response = typeof response === 'string' ? JSON.parse(response) : response;
                    if (response.success) {
                        $('#updateLogoModal').modal('hide');
                        alert('Store logo updated successfully');
                        location.reload();
                    } else {
                        alert(response.message || 'An error occurred while updating the logo');
                    }
                } catch (e) {
                    alert('An unexpected error occurred');
                }
            },
            error: function() {
                alert('An error occurred while connecting to the server');
            }
        });
    }
    
    // Create sales chart
    document.addEventListener('DOMContentLoaded', function() {
        // Sales data from PHP
        const salesDates = <?php echo $chart_dates_json ?? json_encode(['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri']); ?>;
        const salesData = <?php echo $chart_sales_json ?? json_encode([0, 0, 0, 0, 0, 0, 0]); ?>;
        
        // Configure the chart
        const ctx = document.getElementById('salesChart');
        if (ctx) {
            const salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: salesDates,
                    datasets: [{
                        label: 'Sales',
                        data: salesData,
                        fill: true,
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderColor: 'rgba(37, 99, 235, 1)',
                        tension: 0.4,
                        pointBackgroundColor: 'rgba(37, 99, 235, 1)',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.7)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            titleFont: {
                                family: 'Tajawal, sans-serif',
                                size: 14
                            },
                            bodyFont: {
                                family: 'Tajawal, sans-serif',
                                size: 13
                            },
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' SAR';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Tajawal, sans-serif'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: {
                                    family: 'Tajawal, sans-serif'
                                },
                                callback: function(value) {
                                    return value + ' SAR';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
    </script>
</body>
</html>