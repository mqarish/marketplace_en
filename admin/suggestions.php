<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'Manage Suggestions';
$page_icon = 'fa-lightbulb';

// Update suggestion status
if (isset($_POST['update_status'])) {
    $suggestion_id = $_POST['suggestion_id'];
    $status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes']);
    
    $update_query = $conn->prepare("UPDATE suggestions SET status = ?, admin_notes = ? WHERE id = ?");
    $update_query->bind_param("ssi", $status, $admin_notes, $suggestion_id);
    
    if ($update_query->execute()) {
        $success_msg = 'Suggestion status updated successfully';
    } else {
        $error_msg = 'Error updating suggestion status';
    }
}

// Delete suggestion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $suggestion_id = $_GET['delete'];
    
    $delete_query = $conn->prepare("DELETE FROM suggestions WHERE id = ?");
    $delete_query->bind_param("i", $suggestion_id);
    
    if ($delete_query->execute()) {
        $success_msg = 'Suggestion deleted successfully';
    } else {
        $error_msg = 'Error deleting suggestion';
    }
}

// Set items per page
$items_per_page = 10;

// Determine current page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Set filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where_clause = '';
$params = [];
$types = '';

if (!empty($status_filter) && $status_filter !== 'all') {
    $where_clause = " WHERE status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Get total suggestions count
$count_query = "SELECT COUNT(*) as total FROM suggestions" . $where_clause;
$count_stmt = $conn->prepare($count_query);

if (!empty($types)) {
    $count_stmt->bind_param($types, ...$params);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$total_items = $count_result['total'];

// Calculate total pages
$total_pages = ceil($total_items / $items_per_page);

// Get suggestions
$query = "SELECT * FROM suggestions" . $where_clause . " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

$limit_params = [$items_per_page, $offset];
$limit_types = 'ii';

if (!empty($types)) {
    $all_params = array_merge($params, $limit_params);
    $all_types = $types . $limit_types;
    $stmt->bind_param($all_types, ...$all_params);
} else {
    $stmt->bind_param($limit_types, ...$limit_params);
}

$stmt->execute();
$suggestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .status-badge {
            font-size: 0.8rem;
            padding: 0.35rem 0.65rem;
        }
        .suggestion-text {
            max-height: 100px;
            overflow-y: auto;
        }
        .table-responsive {
            min-height: 400px;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Manage Suggestions</h2>
                    <div>
                        <form class="d-flex" method="GET">
                            <select class="form-select me-2" name="status" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter === 'all' || empty($status_filter) ? 'selected' : ''; ?>>All Suggestions</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="reviewed" <?php echo $status_filter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="implemented" <?php echo $status_filter === 'implemented' ? 'selected' : ''; ?>>Implemented</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </form>
                    </div>
                </div>
                
                <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Suggestions List (<?php echo $total_items; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($suggestions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Suggestion</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($suggestions as $suggestion): ?>
                                            <tr>
                                                <td><?php echo $suggestion['id']; ?></td>
                                                <td><?php echo htmlspecialchars($suggestion['name']); ?></td>
                                                <td><?php echo htmlspecialchars($suggestion['email']); ?></td>
                                                <td>
                                                    <div class="suggestion-text">
                                                        <?php echo nl2br(htmlspecialchars($suggestion['suggestion_text'])); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    $status_text = '';
                                                    
                                                    switch ($suggestion['status']) {
                                                        case 'pending':
                                                            $status_class = 'bg-warning text-dark';
                                                            $status_text = 'Pending';
                                                            break;
                                                        case 'reviewed':
                                                            $status_class = 'bg-info text-dark';
                                                            $status_text = 'Reviewed';
                                                            break;
                                                        case 'implemented':
                                                            $status_class = 'bg-success';
                                                            $status_text = 'Implemented';
                                                            break;
                                                        case 'rejected':
                                                            $status_class = 'bg-danger';
                                                            $status_text = 'Rejected';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('Y-m-d H:i', strtotime($suggestion['created_at'])); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewSuggestionModal<?php echo $suggestion['id']; ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <a href="?delete=<?php echo $suggestion['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this suggestion?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            
                                            <!-- View and Update Suggestion Modal -->
                                            <div class="modal fade" id="viewSuggestionModal<?php echo $suggestion['id']; ?>" tabindex="-1" aria-labelledby="viewSuggestionModalLabel<?php echo $suggestion['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-primary text-white">
                                                            <h5 class="modal-title" id="viewSuggestionModalLabel<?php echo $suggestion['id']; ?>">Suggestion Details #<?php echo $suggestion['id']; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row mb-4">
                                                                <div class="col-md-6">
                                                                    <h6 class="fw-bold">Sender Information:</h6>
                                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($suggestion['name']); ?></p>
                                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($suggestion['email']); ?></p>
                                                                    <p><strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($suggestion['created_at'])); ?></p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6 class="fw-bold">Suggestion Status:</h6>
                                                                    <span class="badge <?php echo $status_class; ?> status-badge">
                                                                        <?php echo $status_text; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="mb-4">
                                                                <h6 class="fw-bold">Suggestion Text:</h6>
                                                                <div class="p-3 bg-light rounded">
                                                                    <?php echo nl2br(htmlspecialchars($suggestion['suggestion_text'])); ?>
                                                                </div>
                                                            </div>
                                                            
                                                            <form method="POST" action="">
                                                                <input type="hidden" name="suggestion_id" value="<?php echo $suggestion['id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label for="status<?php echo $suggestion['id']; ?>" class="form-label">Update Status:</label>
                                                                    <select class="form-select" id="status<?php echo $suggestion['id']; ?>" name="status">
                                                                        <option value="pending" <?php echo $suggestion['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="reviewed" <?php echo $suggestion['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                                                        <option value="implemented" <?php echo $suggestion['status'] === 'implemented' ? 'selected' : ''; ?>>Implemented</option>
                                                                        <option value="rejected" <?php echo $suggestion['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="admin_notes<?php echo $suggestion['id']; ?>" class="form-label">Admin Notes:</label>
                                                                    <textarea class="form-control" id="admin_notes<?php echo $suggestion['id']; ?>" name="admin_notes" rows="3"><?php echo htmlspecialchars($suggestion['admin_notes'] ?? ''); ?></textarea>
                                                                </div>
                                                                
                                                                <button type="submit" name="update_status" class="btn btn-primary">Save Changes</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <?php if ($current_page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&status=<?php echo $status_filter; ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($current_page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&status=<?php echo $status_filter; ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No suggestions available.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
