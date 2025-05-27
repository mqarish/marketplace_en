<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'Categories Management';
$page_icon = 'fa-tags';

// Get categories with product count
$query = "
    SELECT c.*, COUNT(p.id) as products_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.name ASC";

// Debug query
error_log("Categories query: " . $query);

$result = $conn->query($query);
if (!$result) {
    error_log("Error in categories query: " . $conn->error);
    // Create an empty result to avoid errors
    $result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
        public function data_seek($pos) { }
    };
}

// Get statistics
$total_categories = $result->num_rows;
$active_categories = $total_categories; // All categories are active by default
$inactive_categories = 0;
$total_products = 0;

// Calculate total products only if there are categories
if ($result->num_rows > 0) {
    while ($category = $result->fetch_assoc()) {
        // Ensure products_count is a number
        $product_count = isset($category['products_count']) ? intval($category['products_count']) : 0;
        $total_products += $product_count;
    }
    // Reset result pointer
    $result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <?php include 'admin_header.php'; ?>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Categories</h5>
                        <p class="card-text display-6"><?php echo number_format($total_categories); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Active Categories</h5>
                        <p class="card-text display-6"><?php echo number_format($active_categories); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">Inactive Categories</h5>
                        <p class="card-text display-6"><?php echo number_format($inactive_categories); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Products</h5>
                        <p class="card-text display-6"><?php echo number_format($total_products); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Categories List</h3>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    <i class="fas fa-plus"></i>
                    Add New Category
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category Name</th>
                                <th>Icon</th>
                                <th>Description</th>
                                <th>Products Count</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($category = $result->fetch_assoc()): ?>
                                    <?php if ($category): ?>
                                    <tr>
                                        <td><?php echo isset($category['id']) ? $category['id'] : ''; ?></td>
                                        <td><?php echo isset($category['name']) ? htmlspecialchars($category['name']) : ''; ?></td>
                                        <td>
                                            <?php if (!empty($category['image_url'])): ?>
                                                <img src="../uploads/categories/<?php echo htmlspecialchars($category['image_url']); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="max-height: 40px; max-width: 40px;">
                                            <?php elseif (!empty($category['icon'])): ?>
                                                <?php if (strpos($category['icon'], 'fa-') === 0): ?>
                                                    <i class="fas <?php echo htmlspecialchars($category['icon']); ?>"></i>
                                                <?php else: ?>
                                                    <i class="bi <?php echo htmlspecialchars($category['icon']); ?>"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($category['icon']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo isset($category['description']) ? htmlspecialchars($category['description']) : ''; ?></td>
                                        <td>
                                            <span class="text-primary">
                                                <i class="fas fa-box-open me-1"></i>
                                                <?php echo isset($category['products_count']) ? number_format(intval($category['products_count'])) : '0'; ?>
                                            </span>
                                            <?php if (isset($category['products_count']) && intval($category['products_count']) > 0): ?>
                                            <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary ms-2" title="View Products">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                Active
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-info" onclick="editCategory(<?php echo $category['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No categories added yet. You can add a new category by clicking the "Add New Category" button.
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addCategoryForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="icon" class="form-label">Category Icon (optional)</label>
                            <input type="text" class="form-control" id="icon" name="icon" placeholder="Example: bi-laptop or fa-shopping-cart">
                            <small class="form-text text-muted">You can use Bootstrap Icons or Font Awesome icons. Enter only the icon name.</small>
                        </div>
                        <div class="mb-3 border p-3 bg-light rounded">
                            <label for="category_image" class="form-label fw-bold text-primary">Category Image <span class="badge bg-primary">Important</span></label>
                            <input type="file" class="form-control" id="category_image" name="category_image" accept="image/*">
                            <div class="mt-2 d-flex align-items-center">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                <small class="form-text">Please upload an image for the category to display on the customer page. Supported formats: JPG, PNG, GIF.</small>
                            </div>
                            <div class="form-text text-danger mt-2"><i class="fas fa-exclamation-triangle"></i> Note: The image is necessary to display the category attractively in the customer interface.</div>
                        </div>
                        <!-- Campo de estado eliminado ya que no existe en la base de datos -->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addCategory()">Add</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editCategoryForm" enctype="multipart/form-data">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_icon" class="form-label">Category Icon (optional)</label>
                            <input type="text" class="form-control" id="edit_icon" name="icon" placeholder="Example: bi-laptop or fa-shopping-cart">
                            <small class="form-text text-muted">You can use Bootstrap Icons or Font Awesome icons. Enter only the icon name.</small>
                        </div>
                        <div class="mb-3 border p-3 bg-light rounded">
                            <label for="edit_category_image" class="form-label fw-bold text-primary">Category Image <span class="badge bg-primary">Important</span></label>
                            <input type="file" class="form-control" id="edit_category_image" name="category_image" accept="image/*">
                            <div class="mt-2 d-flex align-items-center">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                <small class="form-text">Please upload an image for the category to display on the customer page. You can upload a new image or leave this field empty to keep the current image.</small>
                            </div>
                            <div class="form-text text-danger mt-2"><i class="fas fa-exclamation-triangle"></i> Note: The image is necessary to display the category attractively in the customer interface.</div>
                        </div>
                        <div class="mb-3" id="current_image_container" style="display: none;">
                            <label class="form-label">Current Image</label>
                            <div class="current-image-preview">
                                <img id="current_category_image" src="" alt="Category Image" class="img-thumbnail" style="max-height: 150px;">
                            </div>
                        </div>
                        <!-- Campo de estado eliminado ya que no existe en la base de datos -->
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateCategory()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include JavaScript files -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function addCategory() {
        // Check if category name exists
        if (!$('#name').val().trim()) {
            alert('Please enter a category name');
            return;
        }
        
        // Show loading indicator
        $('#addCategoryModal .modal-footer .btn-primary').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...');
        $('#addCategoryModal .modal-footer .btn-primary').prop('disabled', true);
        
        // Create FormData object to send files
        var formData = new FormData($('#addCategoryForm')[0]);
        formData.append('action', 'add');
        
        $.ajax({
            url: 'handle_category.php',
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,  // مهم لمعالجة الملفات
            contentType: false,  // مهم لمعالجة الملفات
            success: function(response) {
                console.log('Response:', response);
                if (response && response.success) {
                    alert('Category added successfully');
                    location.reload();
                } else {
                    var errorMsg = (response && response.message) ? response.message : 'An error occurred while adding the category';
                    alert(errorMsg);
                    // Reset add button
                    $('#addCategoryModal .modal-footer .btn-primary').html('Add');
                    $('#addCategoryModal .modal-footer .btn-primary').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.error('Status:', status);
                console.error('Response Text:', xhr.responseText);
                try {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse && jsonResponse.message) {
                        alert(jsonResponse.message);
                    } else {
                        alert('An error occurred connecting to the server. Please try again.');
                    }
                } catch (e) {
                    alert('An error occurred connecting to the server. Please try again.');
                }
                // Reset add button
                $('#addCategoryModal .modal-footer .btn-primary').html('Add');
                $('#addCategoryModal .modal-footer .btn-primary').prop('disabled', false);
            }
        });
    }

    function editCategory(id) {
        $.get('handle_category.php', {
            action: 'get',
            id: id
        }, function(response) {
            if (response.success) {
                $('#edit_id').val(response.category.id);
                $('#edit_name').val(response.category.name);
                $('#edit_description').val(response.category.description);
                $('#edit_icon').val(response.category.icon);
                
                // Display current image if it exists
                if (response.category.image_url) {
                    $('#current_category_image').attr('src', '../uploads/categories/' + response.category.image_url);
                    $('#current_image_container').show();
                } else {
                    $('#current_image_container').hide();
                }
                
                $('#editCategoryModal').modal('show');
            }
        });
    }

    function updateCategory() {
        // Check if category name exists
        if (!$('#edit_name').val().trim()) {
            alert('Please enter a category name');
            return;
        }
        
        // Show loading indicator
        $('#editCategoryModal .modal-footer .btn-primary').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
        $('#editCategoryModal .modal-footer .btn-primary').prop('disabled', true);
        
        // Create FormData object to send files
        var formData = new FormData($('#editCategoryForm')[0]);
        formData.append('action', 'update');
        
        $.ajax({
            url: 'handle_category.php',
            type: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,  // مهم لمعالجة الملفات
            contentType: false,  // مهم لمعالجة الملفات
            success: function(response) {
                console.log('Update Response:', response);
                if (response && response.success) {
                    alert('تم تحديث التصنيف بنجاح');
                    location.reload();
                } else {
                    var errorMsg = (response && response.message) ? response.message : 'حدث خطأ أثناء تحديث التصنيف';
                    alert(errorMsg);
                    // إعادة تعيين زر التحديث
                    $('#editCategoryModal .modal-footer .btn-primary').html('حفظ التغييرات');
                    $('#editCategoryModal .modal-footer .btn-primary').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.error('Status:', status);
                console.error('Response Text:', xhr.responseText);
                try {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse && jsonResponse.message) {
                        alert(jsonResponse.message);
                    } else {
                        alert('حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.');
                    }
                } catch (e) {
                    alert('حدث خطأ في الاتصال بالخادم. يرجى المحاولة مرة أخرى.');
                }
                // إعادة تعيين زر التحديث
                $('#editCategoryModal .modal-footer .btn-primary').html('حفظ التغييرات');
                $('#editCategoryModal .modal-footer .btn-primary').prop('disabled', false);
            }
        });
    }

    function deleteCategory(id) {
        if (confirm('هل أنت متأكد من حذف هذا التصنيف؟')) {
            $.post('handle_category.php', {
                action: 'delete',
                id: id
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('حدث خطأ أثناء حذف التصنيف');
                }
            });
        }
    }
    </script>

</body>
</html>
