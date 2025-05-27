<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'Users Management';
$page_icon = 'fa-users';

// Process actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    if ($user_id > 0) {
        switch ($_POST['action']) {
            case 'change_status':
                $status = $_POST['status'];
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role IN ('customer', 'store')");
                $stmt->bind_param("si", $status, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "User status updated successfully";
                } else {
                    $_SESSION['error'] = "Error updating user status";
                }
                $stmt->close();
                break;

            case 'reset_password':
                // Create a new random password
                $new_password = bin2hex(random_bytes(4)); // 8 characters
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role IN ('customer', 'store')");
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Password reset successfully. The new password is: " . $new_password;
                } else {
                    $_SESSION['error'] = "Error resetting password";
                }
                $stmt->close();
                break;
        }
    }
    
    header("Location: users.php");
    exit;
}

// Fetch all users with their information
try {
    $query = "SELECT u.*, 
                     CASE 
                         WHEN u.role = 'store' THEN s.name
                         WHEN u.role = 'customer' THEN c.name
                         ELSE ''
                     END as additional_info,
                     CASE 
                         WHEN u.role = 'store' THEN s.phone
                         WHEN u.role = 'customer' THEN c.phone
                         ELSE ''
                     END as phone,
                     CASE 
                         WHEN u.role = 'store' THEN s.id
                         WHEN u.role = 'customer' THEN c.id
                         ELSE NULL
                     END as detail_id,
                     CASE 
                         WHEN u.role = 'store' THEN 'Store'
                         WHEN u.role = 'customer' THEN 'Customer'
                         ELSE u.role
                     END as role_name
              FROM users u 
              LEFT JOIN customers c ON u.email = c.email 
              LEFT JOIN stores s ON u.id = s.user_id 
              WHERE u.role IN ('customer', 'store')
              ORDER BY u.created_at DESC";
    
    $result = $conn->query($query);
    $users = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Make sure data is cleaned before display
            foreach ($row as $key => $value) {
                $row[$key] = $value !== null ? htmlspecialchars($value) : '';
            }
            $row['id'] = (int)$row['id'];
            $users[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .user-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f8f9fa;
        }
        .btn-group {
            display: flex;
            gap: 5px;
        }
        .table td {
            vertical-align: middle;
        }
        .badge-store {
            background-color: #28a745 !important;
        }
        .badge-customer {
            background-color: #17a2b8 !important;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Users List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>User Type</th>
                                <th>Additional Info</th>
                                <th>Registration Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">No users found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $index => $user): 
                                    $status = $user['status'];
                                    $status_class = $status === 'active' ? 'bg-success' : 'bg-warning';
                                    $status_text = $status === 'active' ? 'Active' : 'Inactive';
                                    $role_class = $user['role'] === 'store' ? 'badge-store' : 'badge-customer';
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-icon">
                                                    <i class="fas <?php echo $user['role'] === 'store' ? 'fa-store' : 'fa-user'; ?>"></i>
                                                </div>
                                                <div>
                                                    <?php echo $user['username']; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $user['email']; ?></td>
                                        <td><?php echo $user['phone']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $role_class; ?>">
                                                <?php echo $user['role_name']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $user['additional_info']; ?></td>
                                        <td><?php echo date('Y/m/d', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <?php if ($user['role'] === 'customer' && $user['detail_id']): ?>
                                                     <a href="customer_details.php?id=<?php echo $user['detail_id']; ?>" 
                                                        class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php elseif ($user['role'] === 'store' && $user['detail_id']): ?>
                                                    <a href="store_details.php?id=<?php echo $user['detail_id']; ?>" 
                                                       class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <button type="button" 
                                                        class="btn btn-sm <?php echo $status === 'active' ? 'btn-warning' : 'btn-success'; ?>"
                                                        onclick="changeStatus(<?php echo $user['id']; ?>, '<?php echo $status === 'active' ? 'inactive' : 'active'; ?>')"
                                                        title="<?php echo $status === 'active' ? 'Block' : 'Unblock'; ?>">
                                                    <i class="fas <?php echo $status === 'active' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                                </button>

                                                <button type="button" 
                                                        class="btn btn-sm btn-secondary"
                                                        onclick="resetPassword(<?php echo $user['id']; ?>)"
                                                        title="Reset Password">
                                                    <i class="fas fa-key"></i>
                                                </button>
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
    <script>
    function changeStatus(userId, newStatus) {
        const action = newStatus === 'active' ? 'unblock' : 'block';
        if (confirm(`Are you sure you want to ${action} this user?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="change_status">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="status" value="${newStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function resetPassword(userId) {
        if (confirm('Are you sure you want to reset the password for this user?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>
