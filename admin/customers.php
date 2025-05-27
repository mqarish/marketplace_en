<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'Customers Management';
$page_icon = 'fa-users';

// Process actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    
    switch ($_POST['action']) {
        case 'change_status':
            $status = $_POST['status'];
            $stmt = $conn->prepare("UPDATE customers SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $customer_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Customer status updated successfully";
            } else {
                $_SESSION['error'] = "Error updating customer status";
            }
            $stmt->close();
            break;
            
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->bind_param("i", $customer_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Customer deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting customer";
            }
            $stmt->close();
            break;
    }
    
    header("Location: customers.php");
    exit;
}

// Fetch customers with additional information
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build query
$query = "SELECT * FROM customers WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$customers = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .status-active {
            background-color: #198754;
            color: #fff;
        }
        .status-blocked {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas <?php echo $page_icon; ?>"></i> <?php echo $page_title; ?></h2>
            <div>
                <a href="../customer/register.php" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus"></i> Add New Customer
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Search Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                            <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="blocked" <?php echo $status_filter === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Registration Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No customers found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-circle fs-4 me-2"></i>
                                                <div>
                                                    <?php echo htmlspecialchars($customer['name']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo isset($customer['phone']) ? htmlspecialchars($customer['phone']) : 'Not available'; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $customer['status']; ?>">
                                                <?php
                                                switch ($customer['status']) {
                                                    case 'pending':
                                                        echo 'Pending Approval';
                                                        break;
                                                    case 'active':
                                                        echo 'Active';
                                                        break;
                                                    case 'blocked':
                                                        echo 'Blocked';
                                                        break;
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($customer['created_at'])); ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-three-dots-vertical"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php if ($customer['status'] === 'pending'): ?>
                                                        <li>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="change_status">
                                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                                <input type="hidden" name="status" value="active">
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    <i class="bi bi-check-circle me-2"></i>
                                                                    Approve Account
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($customer['status'] === 'active'): ?>
                                                        <li>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="change_status">
                                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                                <input type="hidden" name="status" value="blocked">
                                                                <button type="submit" class="dropdown-item text-warning">
                                                                    <i class="bi bi-slash-circle me-2"></i>
                                                                    Block Account
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($customer['status'] === 'blocked'): ?>
                                                        <li>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="change_status">
                                                                <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                                <input type="hidden" name="status" value="active">
                                                                <button type="submit" class="dropdown-item text-success">
                                                                    <i class="bi bi-check-circle me-2"></i>
                                                                    Unblock Account
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    
                                                    <li>
                                                        <a href="edit_customer.php?id=<?php echo $customer['id']; ?>" class="dropdown-item text-primary">
                                                            <i class="bi bi-pencil-square me-2"></i>
                                                            Edit Information
                                                        </a>
                                                    </li>
                                                    
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="bi bi-trash me-2"></i>
                                                                Delete Account
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
