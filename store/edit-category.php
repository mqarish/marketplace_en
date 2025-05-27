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

// التحقق من وجود معرف التصنيف
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: categories.php");
    exit;
}

$category_id = $_GET['id'];

// التحقق من أن التصنيف ينتمي للمتجر
$check_sql = "SELECT * FROM categories WHERE id = ? AND store_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $category_id, $store_id);
$check_stmt->execute();
$category = $check_stmt->get_result()->fetch_assoc();

if (!$category) {
    header("Location: categories.php");
    exit;
}

// تحديث التصنيف
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    
    // التحقق من البيانات
    if (empty($name)) {
        $error_message = "اسم التصنيف مطلوب";
    } else {
        // معالجة الصورة
        $image_path = $category['image_url']; // الاحتفاظ بالصورة الحالية إذا لم يتم تحميل صورة جديدة
        
        if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
            $upload_result = uploadImage($_FILES['category_image'], '../uploads/categories/');
            if ($upload_result['success']) {
                $image_path = 'uploads/categories/' . $upload_result['filename'];
            } else {
                $error_message = $upload_result['message'];
            }
        }
        
        if (empty($error_message)) {
            // تحديث التصنيف في قاعدة البيانات
            $sql = "UPDATE categories SET name = ?, description = ?, image_url = ? WHERE id = ? AND store_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssii", $name, $description, $image_path, $category_id, $store_id);
            
            if ($stmt->execute()) {
                $success_message = "تم تحديث التصنيف بنجاح";
                // تحديث بيانات التصنيف بعد التحديث
                $category['name'] = $name;
                $category['description'] = $description;
                $category['image_url'] = $image_path;
            } else {
                $error_message = "حدث خطأ أثناء تحديث التصنيف: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعديل التصنيف - لوحة التحكم</title>
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
        
        /* تنسيق صور التصنيفات */
        .category-img-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            margin-bottom: 1rem;
        }
        
        .category-img-placeholder {
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f3f4f6;
            border-radius: 8px;
            color: #9ca3af;
            font-size: 2rem;
            margin-bottom: 1rem;
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
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">تعديل التصنيف</h2>
                    <a href="categories.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-right me-1"></i> العودة للتصنيفات
                    </a>
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
                
                <!-- نموذج تعديل التصنيف -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">تعديل بيانات التصنيف</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">اسم التصنيف</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">وصف التصنيف</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">الصورة الحالية</label>
                                        <div>
                                            <?php if (!empty($category['image_url'])): ?>
                                                <img src="../<?php echo $category['image_url']; ?>" 
                                                     alt="<?php echo htmlspecialchars($category['name']); ?>"
                                                     class="category-img-preview">
                                            <?php else: ?>
                                                <div class="category-img-placeholder">
                                                    <i class="bi bi-grid"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="category_image" class="form-label">تغيير الصورة</label>
                                        <input type="file" class="form-control" id="category_image" name="category_image" accept="image/*">
                                        <div class="form-text">اختر صورة جديدة للتصنيف (اختياري)</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" name="update_category" class="btn btn-dashboard-primary">
                                    <i class="bi bi-check-lg me-1"></i> حفظ التغييرات
                                </button>
                                <a href="categories.php" class="btn btn-outline-secondary ms-2">إلغاء</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
