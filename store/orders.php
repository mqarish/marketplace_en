<?php
session_start();
require_once '../includes/init.php';

// Check if store is logged in
if (!isset($_SESSION['store_id'])) {
    header("Location: login.php");
    exit();
}

$store_id = $_SESSION['store_id'];

// Query to get store data
$store_query = "SELECT * FROM stores WHERE id = ?";
$store_stmt = $conn->prepare($store_query);
$store_stmt->bind_param("i", $store_id);
$store_stmt->execute();
$store_result = $store_stmt->get_result();
$store = $store_result->fetch_assoc();

// Check if orders table exists
$table_exists_query = "SHOW TABLES LIKE 'orders'";
$table_exists_result = $conn->query($table_exists_query);
$orders_table_exists = ($table_exists_result && $table_exists_result->num_rows > 0);

// Initialize variables
$orders = [];
$total_orders = 0;
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Fetch orders
if ($orders_table_exists) {
    // Build orders query
    $count_query = "SELECT COUNT(*) as total FROM orders WHERE store_id = ?";
    $orders_query = "
        SELECT 
            o.*,
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
        FROM 
            orders o
        WHERE 
            o.store_id = ?
    ";
    
    // Add status filter
    if ($filter_status != 'all') {
        $count_query .= " AND status = ?";
        $orders_query .= " AND o.status = ?";
    }
    
    // Add search
    if (!empty($search_term)) {
        $count_query .= " AND (id LIKE ? OR shipping_address LIKE ? OR phone LIKE ?)";
        $orders_query .= " AND (o.id LIKE ? OR o.shipping_address LIKE ? OR o.phone LIKE ?)";
    }
    
    // Add sorting and pagination
    $orders_query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
    
    // Execute total count query
    $count_stmt = $conn->prepare($count_query);
    
    if ($filter_status != 'all' && !empty($search_term)) {
        $search_param = "%$search_term%";
        $count_stmt->bind_param("issss", $store_id, $filter_status, $search_param, $search_param, $search_param);
    } elseif ($filter_status != 'all') {
        $count_stmt->bind_param("is", $store_id, $filter_status);
    } elseif (!empty($search_term)) {
        $search_param = "%$search_term%";
        $count_stmt->bind_param("isss", $store_id, $search_param, $search_param, $search_param);
    } else {
        $count_stmt->bind_param("i", $store_id);
    }
    
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $count_row = $count_result->fetch_assoc();
    $total_orders = $count_row['total'];
    $total_pages = ceil($total_orders / $per_page);
    
    // Execute orders query
    $orders_stmt = $conn->prepare($orders_query);
    
    if ($filter_status != 'all' && !empty($search_term)) {
        $search_param = "%$search_term%";
        $orders_stmt->bind_param("isssssii", $store_id, $filter_status, $search_param, $search_param, $search_param, $per_page, $offset);
    } elseif ($filter_status != 'all') {
        $orders_stmt->bind_param("isii", $store_id, $filter_status, $per_page, $offset);
    } elseif (!empty($search_term)) {
        $search_param = "%$search_term%";
        $orders_stmt->bind_param("issssii", $store_id, $search_param, $search_param, $search_param, $per_page, $offset);
    } else {
        $orders_stmt->bind_param("iii", $store_id, $per_page, $offset);
    }
    
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    
    while ($order = $orders_result->fetch_assoc()) {
        $orders[] = $order;
    }
} else {
    // If orders table doesn't exist, show message to user
    $total_orders = 0;
    $total_pages = 1;
}

// Function to get order status in English
function getOrderStatusInEnglish($status) {
    $statuses = [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled'
    ];
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

// Function to get payment method in English
function getPaymentMethodInEnglish($method) {
    $methods = [
        'cash_on_delivery' => 'Cash on Delivery',
        'credit_card' => 'Credit Card',
        'bank_transfer' => 'Bank Transfer'
    ];
    
    return isset($methods[$method]) ? $methods[$method] : $method;
}

// Function to get payment status in English
function getPaymentStatusInEnglish($status) {
    $statuses = [
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed'
    ];
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

// Function to get status color
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'processing' => 'info',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    
    return isset($colors[$status]) ? $colors[$status] : 'secondary';
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Store Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --light-color: #f3f4f6;
            --dark-color: #1f2937;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .page-header {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .breadcrumb {
            margin-bottom: 0;
        }
        
        .dashboard-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        .order-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-number {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .order-date {
            font-size: 0.85rem;
            color: #666;
        }
        
        .order-customer {
            font-weight: 500;
        }
        
        .order-total {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .order-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
        }
        
        .filter-bar {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-bar .form-select,
        .filter-bar .form-control {
            border-color: #eee;
        }
        
        .pagination {
            margin-bottom: 0;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .empty-state h4 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>
    
    <!-- Top navigation bar with breadcrumb -->
    <div class="page-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Manage Orders</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="container py-4">
        <!-- Filter and search bar -->
        <div class="filter-bar">
            <form action="" method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search by order number, address, or phone" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status" onchange="this.form.submit()">
                        <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $filter_status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $filter_status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $filter_status == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-5 text-md-end">
                    <span class="text-muted">Total Orders: <?php echo $total_orders; ?></span>
                </div>
            </form>
        </div>
        
        <!-- Orders List -->
        <div class="dashboard-card">
            <div class="card-header">
                <span>Orders</span>
            </div>
            
            <?php if ($orders_table_exists && count($orders) > 0): ?>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Payment Status</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <span class="order-number">#<?php echo $order['id']; ?></span>
                                        </td>
                                        <td>
                                            <div class="order-date">
                                                <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                                <div class="small text-muted">
                                                    <?php echo date('h:i A', strtotime($order['created_at'])); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="order-total">
                                                <?php echo number_format($order['total_amount'], 2); ?> SAR
                                            </span>
                                            <div class="small text-muted">
                                                <?php echo $order['items_count']; ?> item(s)
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo getPaymentMethodInEnglish($order['payment_method']); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : ($order['payment_status'] == 'failed' ? 'danger' : 'warning'); ?>">
                                                <?php echo getPaymentStatusInEnglish($order['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge bg-<?php echo getStatusColor($order['status']); ?>">
                                                <?php echo getOrderStatusInEnglish($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="order-actions">
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-order-id="<?php echo $order['id']; ?>" data-order-status="<?php echo $order['status']; ?>">
                                                    <i class="bi bi-arrow-repeat"></i> Update
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_term); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search_term); ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php elseif (!$orders_table_exists): ?>
                <div class="empty-state">
                    <i class="bi bi-exclamation-circle"></i>
                    <h4>Orders Tables Not Found</h4>
                    <p>It appears that the orders tables do not exist in the database. Please run the orders tables creation script first.</p>
                    <a href="../create_orders_tables.php" class="btn btn-primary">Create Orders Tables</a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-bag-x"></i>
                    <h4>No Orders Found</h4>
                    <p>No orders were found matching your search criteria.</p>
                    <?php if ($filter_status != 'all' || !empty($search_term)): ?>
                        <a href="orders.php" class="btn btn-primary">View All Orders</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Update Order Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="update-order-status.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="orderIdInput">
                        <div class="mb-3">
                            <label for="orderStatus" class="form-label">New Status</label>
                            <select class="form-select" id="orderStatus" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="statusNotes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="statusNotes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update status modal data
        document.addEventListener('DOMContentLoaded', function() {
            const updateStatusModal = document.getElementById('updateStatusModal');
            if (updateStatusModal) {
                updateStatusModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const orderId = button.getAttribute('data-order-id');
                    const orderStatus = button.getAttribute('data-order-status');
                    
                    const orderIdInput = document.getElementById('orderIdInput');
                    const orderStatusSelect = document.getElementById('orderStatus');
                    
                    orderIdInput.value = orderId;
                    orderStatusSelect.value = orderStatus;
                });
            }
        });
    </script>
</body>
</html>
