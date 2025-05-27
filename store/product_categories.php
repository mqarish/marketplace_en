<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check login status
if (!isset($_SESSION['store_id'])) {
    header("Location: login.php");
    exit;
}

$store_id = $_SESSION['store_id'];
$success_message = '';
$error_message = '';

// Check if product categories table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'product_categories'")->num_rows > 0;

// Create product categories table if it doesn't exist
if (!$table_exists) {
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS product_categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        store_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        image_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (store_id) REFERENCES stores(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if (!$conn->query($create_table_sql)) {
        die("Error creating product_categories table: " . $conn->error);
    }
    
    // Add category_id field to products table if it doesn't exist
    $alter_products_sql = "
    ALTER TABLE products ADD COLUMN IF NOT EXISTS category_id INT DEFAULT NULL;
    ";
    
    if (!$conn->query($alter_products_sql)) {
        die("Error altering products table: " . $conn->error);
    }
}

// Add new category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    // Validate data
    if (empty($name)) {
        $error_message = "Category name is required";
    } else {
        // Process image
        $image_path = '';
        if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
            $upload_result = uploadImage($_FILES['category_image'], '../uploads/categories/');
            if ($upload_result['success']) {
                $image_path = 'uploads/categories/' . $upload_result['filename'];
            } else {
                $error_message = $upload_result['message'];
            }
        }
        
        if (empty($error_message)) {
            // Insert category into database
            if (!empty($image_path)) {
                $sql = "INSERT INTO product_categories (store_id, name, description, image_url) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $store_id, $name, $description, $image_path);
            } else {
                $sql = "INSERT INTO product_categories (store_id, name, description) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iss", $store_id, $name, $description);
            }
            
            if ($stmt->execute()) {
                $success_message = "Category added successfully";
            } else {
                $error_message = "Error adding category: " . $conn->error;
            }
        }
    }
}

// Delete category
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    // Verify that the category belongs to the store
    $check_sql = "SELECT id FROM product_categories WHERE id = ? AND store_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $category_id, $store_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update products associated with this category
        $update_products_sql = "UPDATE products SET category_id = NULL WHERE category_id = ?";
        $update_products_stmt = $conn->prepare($update_products_sql);
        $update_products_stmt->bind_param("i", $category_id);
        $update_products_stmt->execute();
        
        // Delete the category
        $delete_sql = "DELETE FROM product_categories WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $category_id);
        
        if ($delete_stmt->execute()) {
            $success_message = "Category deleted successfully";
        } else {
            $error_message = "Error deleting category: " . $conn->error;
        }
    } else {
        $error_message = "You cannot delete this category";
    }
}

// Fetch product categories for the store
$sql = "SELECT * FROM product_categories WHERE store_id = ? ORDER BY name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$result = $stmt->get_result();

// Get product count for each category
$categories_with_count = [];
while ($row = $result->fetch_assoc()) {
    $count_sql = "SELECT COUNT(*) as product_count FROM products WHERE category_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $row['id']);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result()->fetch_assoc();
    
    $row['product_count'] = $count_result['product_count'];
    $categories_with_count[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Categories - Dashboard</title>
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
        
        /* تنسيق البطاقات */
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
        
        /* تنسيق الجدول */
        .categories-table {
            border-radius: var(--card-border-radius);
            overflow: hidden;
        }
        
        .categories-table th {
            background-color: #f9fafb;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            padding: 0.75rem 1rem;
        }
        
        .categories-table td {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }
        
        .categories-table tr:hover {
            background-color: rgba(37, 99, 235, 0.03);
        }
        
        /* تنسيق الأزرار */
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
        
        .btn-action {
            width: 36px;
            height: 36px;
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
        
        .btn-action:hover {
            background-color: #e5e7eb;
            color: #374151;
        }
        
        .btn-action.edit:hover {
            background-color: rgba(59, 130, 246, 0.2);
            color: var(--primary-color);
        }
        
        .btn-action.view:hover {
            background-color: rgba(16, 185, 129, 0.2);
            color: var(--success-color);
        }
        
        .btn-action.delete:hover {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--danger-color);
        }
        
        /* تنسيق صور التصنيفات */
        .category-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .category-img-placeholder {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f3f4f6;
            border-radius: 8px;
            color: #9ca3af;
            font-size: 1.5rem;
        }
        
        /* تنسيق النماذج */
        .form-control, .form-select {
            border-radius: 6px;
            border-color: #e5e7eb;
            padding: 0.5rem 0.75rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.1);
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        /* تنسيق الرسائل */
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
        
        /* تنسيق مودال التأكيد */
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
        
        /* تنسيق بطاقات التصنيفات */
        .category-card {
            border-radius: var(--card-border-radius);
            border: 1px solid #e5e7eb;
            overflow: hidden;
            transition: all var(--transition-speed) ease;
            height: 100%;
        }
        
        .category-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .category-card-img {
            height: 160px;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .category-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .category-card-img .placeholder {
            font-size: 3rem;
            color: #9ca3af;
        }
        
        .category-card-body {
            padding: 1rem;
        }
        
        .category-card-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .category-card-text {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            max-height: 40px;
            overflow: hidden;
        }
        
        .category-card-footer {
            padding: 0.75rem 1rem;
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .category-card-count {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .category-card-actions {
            display: flex;
        }
        
        .category-card-actions .btn-action {
            width: 32px;
            height: 32px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Product Categories</h2>
                    <button type="button" class="btn btn-dashboard-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg me-1"></i> Add New Category
                    </button>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Display categories as cards -->
                <div class="row">
                    <?php if (count($categories_with_count) > 0): ?>
                        <?php foreach ($categories_with_count as $category): ?>
                            <div class="col-md-4 col-lg-3 mb-4">
                                <div class="category-card">
                                    <div class="category-card-img">
                                        <?php if (!empty($category['image_url'])): ?>
                                            <img src="../<?php echo $category['image_url']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>">
                                        <?php else: ?>
                                            <div class="placeholder">
                                                <i class="bi bi-grid"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="category-card-body">
                                        <h5 class="category-card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                                        <p class="category-card-text">
                                            <?php 
                                            $desc = isset($category['description']) ? $category['description'] : '';
                                            echo mb_substr(htmlspecialchars($desc), 0, 60) . (mb_strlen($desc) > 60 ? '...' : ''); 
                                            ?>
                                        </p>
                                    </div>
                                    <div class="category-card-footer">
                                        <div class="category-card-count">
                                            <i class="bi bi-box me-1"></i> <?php echo $category['product_count']; ?> product(s)
                                        </div>
                                        <div class="category-card-actions">
                                            <a href="edit_product_category.php?id=<?php echo $category['id']; ?>" class="btn-action edit" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <button type="button" class="btn-action delete" title="Delete" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="bi bi-grid fs-1 text-muted"></i>
                                </div>
                                <h5>No Categories Found</h5>
                                <p class="text-muted">Add new categories to organize your products</p>
                                <button type="button" class="btn btn-dashboard-primary mt-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="bi bi-plus-lg me-1"></i> Add New Category
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add New Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Category Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category_image" class="form-label">Category Image</label>
                            <input type="file" class="form-control" id="category_image" name="category_image" accept="image/*">
                            <div class="form-text">Choose an appropriate image for the category (optional)</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_category" class="btn btn-dashboard-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this category?</p>
                    <p class="text-danger">Warning: The category will be permanently deleted and this action cannot be undone.</p>
                    <p>Note: Products associated with this category will not be deleted, but they will no longer be associated with this category.</p>
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
        // Function to delete a category
        function deleteCategory(categoryId) {
            // Set confirmation link
            document.getElementById('confirmDeleteBtn').href = 'product_categories.php?delete=' + categoryId;
            // Show confirmation modal
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>
