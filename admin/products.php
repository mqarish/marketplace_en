<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'Products Management';
$page_icon = 'fa-box';

// Process product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $product_id = $_POST['product_id'] ?? 0;
    
    if ($product_id > 0) {
        // حذف المنتج وصورته
        $conn->begin_transaction();
        try {
            // Get the image filename before deleting
            $stmt = $conn->prepare("SELECT image, image_url FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            
            // Delete the image file if it exists
            if (!empty($product['image'])) {
                $image_path = "../uploads/products/" . $product['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Delete the product
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            
            $conn->commit();
            $_SESSION['success'] = 'Product deleted successfully';
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = 'Error while deleting the product: ' . $e->getMessage();
        }
        
        header('Location: products.php');
        exit();
    }
}

// Get product statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive,
    SUM(CASE WHEN status = 'active' THEN price ELSE 0 END) as total_value
FROM products";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get product list with store and category information
$query = "SELECT p.*, 
          s.name as store_name,
          c.name as category_name
          FROM products p
          LEFT JOIN stores s ON p.store_id = s.id
          LEFT JOIN categories c ON p.category_id = c.id
          ORDER BY p.created_at DESC";
$result = $conn->query($query);
$products = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Make sure data is cleaned before display
        $row['name'] = isset($row['name']) ? $row['name'] : '';
        $row['description'] = isset($row['description']) ? $row['description'] : '';
        $row['store_name'] = isset($row['store_name']) ? $row['store_name'] : '';
        $row['category_name'] = isset($row['category_name']) ? $row['category_name'] : '';
        $row['price'] = isset($row['price']) ? $row['price'] : 0;
        $row['image_url'] = isset($row['image_url']) ? $row['image_url'] : '';
        $products[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .product-title {
            color: #333;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .product-description {
            font-size: 0.85em;
            color: #666;
        }
        .price {
            font-weight: bold;
            color: #28a745;
        }
        .currency {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Products</h5>
                        <p class="card-text display-6"><?php echo number_format($stats['total']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active Products</h5>
                        <p class="card-text display-6"><?php echo number_format($stats['active']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Inactive Products</h5>
                        <p class="card-text display-6"><?php echo number_format($stats['inactive']); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Value</h5>
                        <p class="card-text display-6"><?php echo number_format($stats['total_value'], 2); ?> <span class="currency">SAR</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerts -->
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

        <!-- Products Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Products List</h3>
                <a href="add_product.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Add New Product
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Store</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Added Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])): ?>
                                            <img src="../<?php echo $product['image_url']; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                 class="product-image">
                                        <?php else: ?>
                                            <img src="../assets/images/product-placeholder.jpg" 
                                                 alt="Default Image" 
                                                 class="product-image">
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="product-title">
                                            <?php echo !empty($product['name']) ? htmlspecialchars($product['name']) : ''; ?>
                                        </div>
                                        <?php if (!empty($product['description'])): ?>
                                            <div class="product-description">
                                                <?php echo mb_substr(htmlspecialchars($product['description']), 0, 50) . '...'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo !empty($product['store_name']) ? htmlspecialchars($product['store_name']) : ''; ?></td>
                                    <td><?php echo !empty($product['category_name']) ? htmlspecialchars($product['category_name']) : ''; ?></td>
                                    <td class="price">
                                        <?php echo number_format($product['price'], 2); ?>
                                        <span class="currency">SAR</span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'active' => 'success',
                                            'inactive' => 'danger'
                                        ];
                                        $status_text = [
                                            'active' => 'Active',
                                            'inactive' => 'Inactive'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $status_class[$product['status']]; ?>">
                                            <?php echo $status_text[$product['status']]; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view_product.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-info btn-sm" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" 
                                               class="btn btn-warning btn-sm"
                                               title="Edit Product">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="" method="POST" class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Delete Product">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
