<?php
session_start();
require_once '../includes/init.php';

// Check if store is logged in
if (!isset($_SESSION['store_id'])) {
    header("Location: login.php");
    exit();
}

$store_id = $_SESSION['store_id'];
$error_msg = '';
$success_msg = '';

// Get store information
$store_query = "SELECT name FROM stores WHERE id = ?";
$store_stmt = $conn->prepare($store_query);
$store_stmt->bind_param("i", $store_id);
$store_stmt->execute();
$store_result = $store_stmt->get_result();
$store = $store_result->fetch_assoc();

// Delete offer if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $offer_id = (int)$_GET['delete'];
    
    // Verify that the offer belongs to the store
    $check_sql = "SELECT image_path FROM offers WHERE id = ? AND store_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $offer_id, $store_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $offer = $result->fetch_assoc();
        
        // Delete the image if it exists
        if (!empty($offer['image_path'])) {
            $image_path = "../" . $offer['image_path'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Delete the offer
        $delete_sql = "DELETE FROM offers WHERE id = ? AND store_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $offer_id, $store_id);
        
        if ($delete_stmt->execute()) {
            $success_msg = "Offer deleted successfully";
        } else {
            $error_msg = "An error occurred while deleting the offer";
        }
    }
}

// Fetch offers with additional information
$offers_sql = "SELECT o.*, 
               COUNT(DISTINCT p.id) as products_count,
               CASE 
                  WHEN o.end_date < CURDATE() THEN 'expired'
                  WHEN o.start_date > CURDATE() THEN 'upcoming'
                  ELSE 'active'
               END as offer_status,
               DATEDIFF(o.end_date, CURDATE()) as days_remaining
               FROM offers o
               LEFT JOIN products p ON o.store_id = p.store_id
               WHERE o.store_id = ?
               GROUP BY o.id
               ORDER BY 
                  CASE 
                     WHEN o.start_date <= CURDATE() AND o.end_date >= CURDATE() THEN 1
                     WHEN o.start_date > CURDATE() THEN 2
                     ELSE 3
                  END,
                  o.created_at DESC";

$offers_stmt = $conn->prepare($offers_sql);
$offers_stmt->bind_param("i", $store_id);
$offers_stmt->execute();
$offers_result = $offers_stmt->get_result();

// Offers statistics
$stats_sql = "SELECT 
              COUNT(*) as total_offers,
              SUM(CASE WHEN start_date <= CURDATE() AND end_date >= CURDATE() THEN 1 ELSE 0 END) as active_offers,
              SUM(CASE WHEN end_date < CURDATE() THEN 1 ELSE 0 END) as expired_offers,
              SUM(CASE WHEN start_date > CURDATE() THEN 1 ELSE 0 END) as upcoming_offers
              FROM offers 
              WHERE store_id = ?";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $store_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Ensure the images directory exists
$upload_dir = '../uploads/offers/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Offers - Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #10b981;
            --info-color: #06b6d4;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-color: #f3f4f6;
            --dark-color: #1f2937;
            --card-border-radius: 10px;
            --transition-speed: 0.15s;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f5f7fb;
            color: #333;
        }
        
        /* Card styling */
        .dashboard-card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: all var(--transition-speed) ease;
            overflow: hidden;
            height: 100%;
            margin-bottom: 1.5rem;
        }
        
        .dashboard-card .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.25rem;
        }
        
        .dashboard-card .card-body {
            padding: 1.25rem;
            background-color: #fff;
        }
        
        /* Statistics styling */
        .stats-card {
            border-radius: var(--card-border-radius);
            border: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: all var(--transition-speed) ease;
            overflow: hidden;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stats-card .card-body {
            display: flex;
            align-items: center;
            padding: 1.5rem;
        }
        
        .stats-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            margin-left: 1rem;
            font-size: 1.5rem;
        }
        
        .stats-info h5 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            line-height: 1;
        }
        
        .stats-info p {
            color: #6b7280;
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        /* Offers styling */
        .offer-card {
            border-radius: var(--card-border-radius);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: all var(--transition-speed) ease;
            height: 100%;
            background-color: #fff;
        }
        
        .offer-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .offer-card-header {
            position: relative;
            height: 160px;
            background-color: #f3f4f6;
            overflow: hidden;
        }
        
        .offer-card-header img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .offer-discount {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: var(--danger-color);
            color: white;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .offer-status {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .offer-status.active {
            background-color: rgba(16, 185, 129, 0.2);
            color: var(--success-color);
        }
        
        .offer-status.upcoming {
            background-color: rgba(59, 130, 246, 0.2);
            color: var(--primary-color);
        }
        
        .offer-status.expired {
            background-color: rgba(107, 114, 128, 0.2);
            color: #6b7280;
        }
        
        .offer-card-body {
            padding: 1.25rem;
        }
        
        .offer-card-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .offer-card-dates {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.85rem;
            color: #6b7280;
        }
        
        .offer-card-dates span {
            display: flex;
            align-items: center;
        }
        
        .offer-card-dates i {
            margin-left: 0.25rem;
            font-size: 0.9rem;
        }
        
        .offer-card-footer {
            padding: 0.75rem 1.25rem;
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .offer-products {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            color: #6b7280;
        }
        
        .offer-products i {
            margin-left: 0.25rem;
        }
        
        .offer-actions .btn {
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
        
        .offer-actions .btn-edit:hover {
            background-color: rgba(59, 130, 246, 0.2);
            color: var(--primary-color);
        }
        
        .offer-actions .btn-delete:hover {
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
        
        /* Confirmation modal styling */
        .modal-content {
            border-radius: var(--card-border-radius);
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.25rem;
        }
        
        .modal-footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.25rem;
        }
        
        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #e5e7eb;
            margin-bottom: 1rem;
        }
        
        .empty-state h4 {
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }
        
        /* Alert message styling */
        .alert {
            border-radius: var(--card-border-radius);
            border: none;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }
        
        /* Header styling */
        .page-header {
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0;
        }
        
        .page-header .breadcrumb {
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        .page-header .breadcrumb-item a {
            color: #6b7280;
            text-decoration: none;
        }
        
        .page-header .breadcrumb-item.active {
            color: var(--primary-color);
        }
        
        /* Sidebar styling */
        .sidebar-card {
            border-radius: var(--card-border-radius);
            border: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .sidebar-card .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        
        .sidebar-card .card-body {
            padding: 1.25rem;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu li:last-child {
            margin-bottom: 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            color: #6b7280;
            text-decoration: none;
            transition: all var(--transition-speed) ease;
        }
        
        .sidebar-menu a:hover {
            background-color: #f3f4f6;
            color: var(--primary-color);
        }
        
        .sidebar-menu a.active {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .sidebar-menu i {
            margin-left: 0.75rem;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>
    
    <!-- Top header -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>Manage Offers</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Offers</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="add-offers.php" class="btn btn-dashboard-primary">
                        <i class="bi bi-plus-lg me-1"></i> Add New Offer
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Offers Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="stats-icon" style="background-color: rgba(37, 99, 235, 0.1); color: var(--primary-color);">
                            <i class="bi bi-tags"></i>
                        </div>
                        <div class="stats-info">
                            <h5><?php echo $stats['total_offers'] ?? 0; ?></h5>
                            <p>Total Offers</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="stats-icon" style="background-color: rgba(16, 185, 129, 0.1); color: var(--success-color);">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                        <div class="stats-info">
                            <h5><?php echo $stats['active_offers'] ?? 0; ?></h5>
                            <p>Active Offers</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="stats-icon" style="background-color: rgba(59, 130, 246, 0.1); color: var(--info-color);">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="stats-info">
                            <h5><?php echo $stats['upcoming_offers'] ?? 0; ?></h5>
                            <p>Upcoming Offers</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="card-body">
                        <div class="stats-icon" style="background-color: rgba(107, 114, 128, 0.1); color: #6b7280;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="stats-info">
                            <h5><?php echo $stats['expired_offers'] ?? 0; ?></h5>
                            <p>Expired Offers</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <?php if ($offers_result->num_rows > 0): ?>
                <?php while ($offer = $offers_result->fetch_assoc()): ?>
                    <div class="col-md-4 mb-4">
                        <div class="offer-card">
                            <div class="offer-card-header">
                                <?php if (!empty($offer['image_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($offer['image_path']); ?>" alt="<?php echo htmlspecialchars($offer['title']); ?>">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="offer-discount">
                                    <?php echo $offer['discount_percentage']; ?>% Off
                                </div>
                                
                                <?php 
                                $status_class = '';
                                $status_text = '';
                                
                                if ($offer['offer_status'] == 'active') {
                                    $status_class = 'active';
                                    $status_text = 'Active';
                                } elseif ($offer['offer_status'] == 'upcoming') {
                                    $status_class = 'upcoming';
                                    $status_text = 'Upcoming';
                                } else {
                                    $status_class = 'expired';
                                    $status_text = 'Expired';
                                }
                                ?>
                                
                                <div class="offer-status <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </div>
                            </div>
                            
                            <div class="offer-card-body">
                                <h5 class="offer-card-title"><?php echo htmlspecialchars($offer['title']); ?></h5>
                                
                                <div class="offer-card-dates">
                                    <span>
                                        <i class="bi bi-calendar-check"></i>
                                        <?php echo date('d/m/Y', strtotime($offer['start_date'])); ?>
                                    </span>
                                    <span>
                                        <i class="bi bi-calendar-x"></i>
                                        <?php echo date('d/m/Y', strtotime($offer['end_date'])); ?>
                                    </span>
                                </div>
                                
                                <?php if ($offer['offer_status'] == 'active' && $offer['days_remaining'] > 0): ?>
                                    <div class="progress" style="height: 8px;">
                                        <?php 
                                        $total_days = (strtotime($offer['end_date']) - strtotime($offer['start_date'])) / (60 * 60 * 24);
                                        $days_passed = $total_days - $offer['days_remaining'];
                                        $progress = ($days_passed / $total_days) * 100;
                                        ?>
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="text-end mt-1">
                                        <small class="text-muted"><?php echo $offer['days_remaining']; ?> days remaining</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="offer-card-footer">
                                <div class="offer-products">
                                    <i class="bi bi-box"></i>
                                    <?php echo $offer['products_count']; ?> product(s)
                                </div>
                                
                                <div class="offer-actions">
                                    <a href="edit-offer.php?id=<?php echo $offer['id']; ?>" class="btn btn-edit" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-delete" title="Delete" onclick="confirmDelete(<?php echo $offer['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="card-body">
                            <div class="empty-state">
                                <i class="bi bi-tags"></i>
                                <h4>No Offers Available</h4>
                                <p>You haven't added any offers yet. Add a new offer to attract more customers.</p>
                                <a href="add-offers.php" class="btn btn-dashboard-primary">
                                    <i class="bi bi-plus-lg me-1"></i> Add New Offer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteOfferModal" tabindex="-1" aria-labelledby="deleteOfferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteOfferModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this offer?</p>
                    <p class="text-danger">Warning: The offer will be permanently deleted and this action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Confirm Delete</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmDelete(offerId) {
        // Set confirmation link
        document.getElementById('confirmDeleteBtn').href = 'offers.php?delete=' + offerId;
        // Show confirmation modal
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteOfferModal'));
        deleteModal.show();
    }
    </script>
</body>
</html>
