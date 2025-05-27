<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'Orders Management';
$page_icon = 'fa-shopping-cart';

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if required files exist
$required_files = ['admin_header.php', 'admin_navbar.php', 'alert_messages.php'];
foreach ($required_files as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        die("Required file not found: " . $file);
    }
}

// Check if customers table exists and create it if not
$create_customers_table = "CREATE TABLE IF NOT EXISTS customers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    status ENUM('pending', 'active', 'blocked') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

// Check if tables exist and create them if not
$create_orders_table = "CREATE TABLE IF NOT EXISTS orders (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_number VARCHAR(50) NOT NULL,
    customer_id INT(11),
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY (order_number),
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$create_order_items_table = "CREATE TABLE IF NOT EXISTS order_items (
    id INT(11) NOT NULL AUTO_INCREMENT,
    order_id INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
    $conn->query($create_customers_table);
    
    // Check if user_id column exists in orders table
    $check_user_id = $conn->query("SHOW COLUMNS FROM orders LIKE 'user_id'");
    if ($check_user_id->num_rows > 0) {
        // Add customer_id column if it doesn't exist
        $conn->query("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_id INT(11)");
        
        // Transfer data from user_id to customer_id
        $conn->query("UPDATE orders SET customer_id = user_id WHERE customer_id IS NULL");
        
        // Delete user_id column
        $conn->query("ALTER TABLE orders DROP COLUMN user_id");
    }
    
    $conn->query($create_orders_table);
    $conn->query($create_order_items_table);
} catch (Exception $e) {
    die("Error creating orders tables: " . $e->getMessage());
}

// Define the base URL for the site
$base_url = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$base_url .= $_SERVER['HTTP_HOST'];
$base_url .= str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
$base_url = dirname($base_url);

// Process order status update
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $update_query = "UPDATE orders SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        if ($update_stmt) {
            $update_stmt->bind_param('si', $new_status, $order_id);
            if ($update_stmt->execute()) {
                $_SESSION['success'] = 'Order status updated successfully';
            } else {
                $_SESSION['error'] = 'Error updating order status';
            }
        }
    }
    header('Location: ' . $_SERVER['PHP_SELF'] . (isset($_GET['store_id']) ? '?store_id=' . $_GET['store_id'] : ''));
    exit;
}

// Prepare filters
$filters = [];
$params = [];
$param_types = '';

// Store filter
if (isset($_GET['store_id']) && is_numeric($_GET['store_id'])) {
    $filters[] = "p.store_id = ?";
    $params[] = (int)$_GET['store_id'];
    $param_types .= 'i';
}

// Order status filter
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters[] = "o.status = ?";
    $params[] = $_GET['status'];
    $param_types .= 's';
}

// Order date filter
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters[] = "DATE(o.created_at) >= ?";
    $params[] = $_GET['date_from'];
    $param_types .= 's';
}
if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters[] = "DATE(o.created_at) <= ?";
    $params[] = $_GET['date_to'];
    $param_types .= 's';
}

// Build SQL query
$base_query = "SELECT DISTINCT o.*, 
              c.name as customer_name,
              s.name as store_name,
              (SELECT GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ')
               FROM order_items oi2 
               JOIN products p ON oi2.product_id = p.id 
               WHERE oi2.order_id = o.id) as products_list
              FROM orders o
              LEFT JOIN order_items oi ON o.id = oi.order_id
              LEFT JOIN products p ON oi.product_id = p.id
              LEFT JOIN stores s ON p.store_id = s.id
              LEFT JOIN customers c ON o.customer_id = c.id";

if (!empty($filters)) {
    $base_query .= " WHERE " . implode(" AND ", $filters);
}

$base_query .= " ORDER BY o.created_at DESC";

// Execute query
$orders = [];
try {
    $stmt = $conn->prepare($base_query);
    if ($stmt && !empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Error retrieving orders: ' . $e->getMessage();
}

// Get stores list for filter
$stores = [];
try {
    $stores_query = "SELECT id, name FROM stores ORDER BY name";
    $stores_result = $conn->query($stores_query);
    $stores = $stores_result ? $stores_result->fetch_all(MYSQLI_ASSOC) : [];
} catch (Exception $e) {
    // Ignore error here because stores list is not essential
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .status-badge {
            min-width: 100px;
            text-align: center;
        }
        .filter-section {
            background-color: #f8f9fc;
            padding: 1rem;
            border-radius: 0.35rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <!-- Page Title -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas <?php echo $page_icon; ?> me-1"></i> <?php echo $page_title; ?>
            </h1>
        </div>

        <?php include 'alert_messages.php'; ?>

        <!-- Filter Section -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filter Orders</h6>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <?php if (isset($_GET['store_id'])): ?>
                        <input type="hidden" name="store_id" value="<?php echo htmlspecialchars($_GET['store_id']); ?>">
                    <?php else: ?>
                    <div class="col-md-3">
                        <label for="store_id" class="form-label">Store</label>
                        <select name="store_id" id="store_id" class="form-select">
                            <option value="">All Stores</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?php echo $store['id']; ?>" <?php echo (isset($_GET['store_id']) && $_GET['store_id'] == $store['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($store['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo (isset($_GET['status']) && $_GET['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo (isset($_GET['status']) && $_GET['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo (isset($_GET['status']) && $_GET['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                        <a href="<?php echo $_SERVER['PHP_SELF'] . (isset($_GET['store_id']) ? '?store_id=' . $_GET['store_id'] : ''); ?>" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Orders List</h6>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle mb-2" style="font-size: 2rem;"></i>
                        <p class="mb-0">No orders found</p>
                        <?php if (!empty($_GET)): ?>
                            <div class="mt-2">
                                <a href="<?php echo $_SERVER['PHP_SELF'] . (isset($_GET['store_id']) ? '?store_id=' . $_GET['store_id'] : ''); ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i> Reset Filter
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Order Number</th>
                                    <th>Customer</th>
                                    <th>Store</th>
                                    <th>Products</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($order['store_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <small><?php echo htmlspecialchars($order['products_list'] ?? 'No products'); ?></small>
                                    </td>
                                    <td><?php echo number_format($order['total_amount'], 2); ?> SAR</td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm status-badge dropdown-toggle <?php 
                                                echo match($order['status']) {
                                                    'pending' => 'btn-warning',
                                                    'processing' => 'btn-info',
                                                    'shipped' => 'btn-primary',
                                                    'delivered' => 'btn-success',
                                                    'cancelled' => 'btn-danger',
                                                    default => 'btn-secondary'
                                                };
                                            ?>" type="button" data-bs-toggle="dropdown">
                                                <?php 
                                                echo match($order['status']) {
                                                    'pending' => 'Pending',
                                                    'processing' => 'Processing',
                                                    'shipped' => 'Shipped',
                                                    'delivered' => 'Delivered',
                                                    'cancelled' => 'Cancelled',
                                                    default => 'Unknown'
                                                };
                                                ?>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="update_status" value="1">
                                                        <button type="submit" name="status" value="pending" class="dropdown-item">Pending</button>
                                                        <button type="submit" name="status" value="processing" class="dropdown-item">Processing</button>
                                                        <button type="submit" name="status" value="shipped" class="dropdown-item">Shipped</button>
                                                        <button type="submit" name="status" value="delivered" class="dropdown-item">Delivered</button>
                                                        <button type="submit" name="status" value="cancelled" class="dropdown-item">Cancel Order</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Modals -->
    <?php foreach ($orders as $order): ?>
    <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Customer Information</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'Unknown'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Order Information</h6>
                            <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                            <p><strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></p>
                            <p><strong>Total Amount:</strong> <?php echo number_format($order['total_amount'], 2); ?> SAR</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h6>Products</h6>
                            <p><?php echo nl2br(htmlspecialchars($order['products_list'] ?? 'No products')); ?></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
