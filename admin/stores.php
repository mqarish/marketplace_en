<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'Stores Management';
$page_icon = 'fa-store';

// Process actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $store_id = isset($_POST['store_id']) ? (int)$_POST['store_id'] : 0;
    
    if ($store_id > 0) {
        switch ($_POST['action']) {
            case 'reset_password':
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Get user_id for the store
                    $stmt = $conn->prepare("SELECT user_id FROM stores WHERE id = ?");
                    $stmt->bind_param("i", $store_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $store = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($store) {
                        // Create a new random password
                        $new_password = bin2hex(random_bytes(4)); // 8 characters
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        // Update password in stores table
                        $stmt1 = $conn->prepare("UPDATE stores SET password = ? WHERE id = ?");
                        $stmt1->bind_param("si", $hashed_password, $store_id);
                        $stmt1->execute();
                        $stmt1->close();
                        
                        // Update password in users table if there is a user_id
                        if (!empty($store['user_id'])) {
                            $stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt2->bind_param("si", $hashed_password, $store['user_id']);
                            $stmt2->execute();
                            $stmt2->close();
                        }
                        
                        // Commit transaction
                        $conn->commit();
                        $_SESSION['success'] = "Password has been reset successfully. The new password is: " . $new_password;
                    } else {
                        throw new Exception("Store not found");
                    }
                } catch (Exception $e) {
                    // Rollback transaction in case of error
                    $conn->rollback();
                    $_SESSION['error'] = "An error occurred while resetting the password: " . $e->getMessage();
                }
                break;
        }
    }
    
    header("Location: stores.php");
    exit;
}

try {
    // Fetch stores data with user information
    $query = "SELECT 
                s.*,
                CASE 
                    WHEN u.email IS NOT NULL AND u.email != '' THEN u.email 
                    WHEN s.email IS NOT NULL AND s.email != '' THEN s.email
                    ELSE NULL
                END as display_email,
                u.status as user_status,
                s.status as store_status 
              FROM stores s 
              LEFT JOIN users u ON s.user_id = u.id 
              ORDER BY s.created_at DESC";
    
    $result = $conn->query($query);
    $stores = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Clean data
            foreach ($row as $key => $value) {
                $row[$key] = $value !== null ? htmlspecialchars($value) : '';
            }
            $row['id'] = (int)$row['id'];
            $stores[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $stores = [];
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .store-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .store-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f8f9fa;
        }
        .store-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .btn-group {
            display: flex;
            gap: 5px;
        }
        .table td {
            vertical-align: middle;
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
                <h5 class="card-title mb-0">Stores List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Logo</th>
                                <th>Store Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Description</th>
                                <th>Registration Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stores)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">No stores found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stores as $index => $store): 
                                    $status = $store['store_status'];
                                    $status_class = $status === 'active' ? 'bg-success' : ($status === 'suspended' ? 'bg-warning' : 'bg-secondary');
                                    
                                    if ($status === 'active') {
                                        $status_text = 'Active';
                                    } elseif ($status === 'suspended') {
                                        $status_text = 'Suspended';
                                    } else {
                                        $status_text = 'Under Review';
                                    }
                                    
                                    $logo_path = !empty($store['logo']) ? '../uploads/stores/' . $store['logo'] : '../assets/images/default-store.png';
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <img src="<?php echo $logo_path; ?>" 
                                                 alt="<?php echo $store['name']; ?>" 
                                                 class="store-logo">
                                        </td>
                                        <td>
                                            <div class="store-info">
                                                <div>
                                                    <?php echo $store['name']; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php 
                                            $email = trim($store['display_email']);
                                            echo !empty($email) ? htmlspecialchars($email) : '<span class="text-muted">-</span>'; 
                                        ?></td>
                                        <td><?php echo $store['phone']; ?></td>
                                        <td><?php echo $store['address']; ?></td>
                                        <td><?php echo $store['description']; ?></td>
                                        <td><?php echo date('Y/m/d', strtotime($store['created_at'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                            <?php if ($status === 'suspended'): ?>
                                                <br>
                                                <small class="text-muted">Store is being reactivated</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit_store.php?id=<?php echo $store['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="change_store_status.php?store_id=<?php echo $store['id']; ?>&status=<?php echo $status === 'active' ? 'inactive' : 'active'; ?>" 
                                                   class="btn btn-sm <?php echo $status === 'active' ? 'btn-danger' : 'btn-success'; ?>" 
                                                   onclick="return confirm('Are you sure you want to <?php echo $status === 'active' ? 'suspend' : 'activate'; ?> this store?');">
                                                     <?php echo $status === 'active' ? 'Suspend' : 'Activate'; ?>
                                                 </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-info" 
                                                        onclick="resetPassword(<?php echo $store['id']; ?>)">
                                                    Reset Password
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        onclick="confirmDelete(<?php echo $store['id']; ?>, '<?php echo $store['name']; ?>')">
                                                    <i class="fas fa-trash-alt"></i> Delete
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete the store "<span id="storeName"></span>"?
                    <br>
                    <strong class="text-danger">Warning: All data associated with the store will be deleted, including products, orders, and offers.</strong>
                </div>
                <div class="modal-footer">
                    <form action="delete_store.php" method="POST">
                        <input type="hidden" name="store_id" id="deleteStoreId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Confirm Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Status change function has been moved to a separate page change_store_status.php

    function resetPassword(storeId) {
        if (confirm('Are you sure you want to reset the password for this store?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="store_id" value="${storeId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function confirmDelete(storeId, storeName) {
        document.getElementById('deleteStoreId').value = storeId;
        document.getElementById('storeName').textContent = storeName;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
</body>
</html>
