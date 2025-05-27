<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['store_id'])) {
    header("Location: login.php");
    exit;
}

$store_id = $_SESSION['store_id'];
$success_message = '';
$error_message = '';

// إضافة تصنيف جديد
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    // التحقق من البيانات
    if (empty($name)) {
        $error_message = "اسم التصنيف مطلوب";
    } else {
        // معالجة الصورة
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
            // التحقق من وجود حقل store_id في جدول التصنيفات
            $check_column = $conn->query("SHOW COLUMNS FROM categories LIKE 'store_id'");
            
            if ($check_column->num_rows > 0) {
                // إذا كان حقل store_id موجود
                if (!empty($image_path)) {
                    $sql = "INSERT INTO categories (name, description, image_url, store_id) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssi", $name, $description, $image_path, $store_id);
                } else {
                    $sql = "INSERT INTO categories (name, description, store_id) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssi", $name, $description, $store_id);
                }
            } else {
                // إذا لم يكن حقل store_id موجود
                if (!empty($image_path)) {
                    $sql = "INSERT INTO categories (name, description, image_url) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $name, $description, $image_path);
                } else {
                    $sql = "INSERT INTO categories (name, description) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ss", $name, $description);
                }
            }
            
            if ($stmt->execute()) {
                $success_message = "تم إضافة التصنيف بنجاح";
            } else {
                $error_message = "حدث خطأ أثناء إضافة التصنيف: " . $conn->error;
            }
        }
    }
}

// حذف تصنيف
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    // التحقق من وجود حقل store_id في جدول التصنيفات
    $check_column = $conn->query("SHOW COLUMNS FROM categories LIKE 'store_id'");
    
    if ($check_column->num_rows > 0) {
        // إذا كان حقل store_id موجود
        // التحقق من أن التصنيف ينتمي للمتجر
        $check_sql = "SELECT id FROM categories WHERE id = ? AND store_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $category_id, $store_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // حذف التصنيف
            $delete_sql = "DELETE FROM categories WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $category_id);
            
            if ($delete_stmt->execute()) {
                $success_message = "تم حذف التصنيف بنجاح";
            } else {
                $error_message = "حدث خطأ أثناء حذف التصنيف: " . $conn->error;
            }
        } else {
            $error_message = "لا يمكنك حذف هذا التصنيف";
        }
    } else {
        // إذا لم يكن حقل store_id موجود
        // لا يمكن حذف التصنيفات العامة
        $error_message = "لا يمكنك حذف التصنيفات العامة";
    }
}

// جلب التصنيفات
// التحقق من وجود حقل store_id في جدول التصنيفات
$check_column = $conn->query("SHOW COLUMNS FROM categories LIKE 'store_id'");

if ($check_column->num_rows > 0) {
    // إذا كان حقل store_id موجود
    $sql = "SELECT * FROM categories WHERE store_id = ? OR store_id IS NULL ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // إذا لم يكن حقل store_id موجود
    $sql = "SELECT * FROM categories ORDER BY id DESC";
    $result = $conn->query($sql);
    
    if (!$result) {
        die("Error fetching categories: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التصنيفات - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
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
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">إدارة التصنيفات</h2>
                    <button type="button" class="btn btn-dashboard-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="bi bi-plus-lg me-1"></i> إضافة تصنيف جديد
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
                
                <!-- جدول التصنيفات -->
                <div class="dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">قائمة التصنيفات</h5>
                        <span class="badge bg-primary"><?php echo $result->num_rows; ?> تصنيف</span>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table categories-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>الصورة</th>
                                            <th>التصنيف</th>
                                            <th>الوصف</th>
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="category-image-container">
                                                        <?php if (!empty($row['image_url'])): ?>
                                                            <img src="../<?php echo $row['image_url']; ?>" 
                                                                 alt="<?php echo htmlspecialchars($row['name']); ?>"
                                                                 class="category-img">
                                                        <?php else: ?>
                                                            <div class="category-img-placeholder">
                                                                <i class="bi bi-grid"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-medium"><?php echo htmlspecialchars($row['name']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="text-muted small">
                                                        <?php 
                                                        $desc = isset($row['description']) ? $row['description'] : '';
                                                        echo mb_substr(htmlspecialchars($desc), 0, 50) . (mb_strlen($desc) > 50 ? '...' : ''); 
                                                        ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        <?php 
                                                        // التحقق من وجود حقل store_id في جدول التصنيفات
                                                        $has_store_id = $conn->query("SHOW COLUMNS FROM categories LIKE 'store_id'")->num_rows > 0;
                                                        
                                                        // التحقق مما إذا كان التصنيف خاص بالمتجر الحالي
                                                        $is_store_category = $has_store_id && isset($row['store_id']) && $row['store_id'] == $store_id;
                                                        
                                                        // إذا كان حقل store_id غير موجود، فلا يمكن تعديل أو حذف التصنيفات
                                                        if ($is_store_category): 
                                                        ?>
                                                            <a href="edit-category.php?id=<?php echo $row['id']; ?>" class="btn-action edit" title="تعديل">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </a>
                                                            <button type="button" class="btn-action delete" title="حذف" onclick="deleteCategory(<?php echo $row['id']; ?>)">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted small">تصنيف عام</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="bi bi-grid fs-1 text-muted"></i>
                                </div>
                                <h5>لا توجد تصنيفات</h5>
                                <p class="text-muted">قم بإضافة تصنيفات جديدة لتنظيم منتجاتك</p>
                                <button type="button" class="btn btn-dashboard-primary mt-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="bi bi-plus-lg me-1"></i> إضافة تصنيف جديد
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- مودال إضافة تصنيف جديد -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">إضافة تصنيف جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">اسم التصنيف</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">وصف التصنيف</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category_image" class="form-label">صورة التصنيف</label>
                            <input type="file" class="form-control" id="category_image" name="category_image" accept="image/*">
                            <div class="form-text">اختر صورة مناسبة للتصنيف (اختياري)</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_category" class="btn btn-dashboard-primary">إضافة التصنيف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- مودال تأكيد الحذف -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">تأكيد الحذف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من رغبتك في حذف هذا التصنيف؟</p>
                    <p class="text-danger">تحذير: سيتم حذف التصنيف نهائياً ولا يمكن التراجع عن هذا الإجراء.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">تأكيد الحذف</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // دالة حذف التصنيف
        function deleteCategory(categoryId) {
            // تعيين رابط التأكيد
            document.getElementById('confirmDeleteBtn').href = 'categories.php?delete=' + categoryId;
            // عرض مودال التأكيد
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>
