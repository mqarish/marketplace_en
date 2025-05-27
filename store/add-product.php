<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['store_id'])) {
    header("Location: ../login.php");
    exit;
}

$store_id = $_SESSION['store_id'];
$errors = [];
$success = false;

// جلب تصنيفات المنتجات الخاصة بالمتجر
$categories_sql = "SELECT * FROM product_categories WHERE store_id = ? ORDER BY name ASC";
$categories_stmt = $conn->prepare($categories_sql);
$categories_stmt->bind_param("i", $store_id);
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $currency = isset($_POST['currency']) ? sanitize($_POST['currency']) : 'SAR';
    $hide_price = isset($_POST['hide_price']) ? 1 : 0;
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    $status = 'active'; // ضبط حالة المنتج الجديد على نشط
    
    // التحقق من البيانات
    if (empty($name)) $errors[] = "اسم المنتج مطلوب";
    if (empty($description)) $errors[] = "وصف المنتج مطلوب";
    if ($price <= 0) $errors[] = "السعر يجب أن يكون أكبر من 0";
    
    // معالجة الصور المتعددة
    $product_images = [];
    $has_primary_image = false;
    
    if (isset($_FILES['product_image']) && is_array($_FILES['product_image']['name'])) {
        // التحقق من وجود صورة واحدة على الأقل
        if (empty($_FILES['product_image']['name'][0]) || $_FILES['product_image']['error'][0] != 0) {
            $errors[] = "الصورة الرئيسية للمنتج مطلوبة";
        } else {
            // معالجة كل صورة تم رفعها
            for ($i = 0; $i < count($_FILES['product_image']['name']); $i++) {
                if (!empty($_FILES['product_image']['name'][$i]) && $_FILES['product_image']['error'][$i] == 0) {
                    // إنشاء مصفوفة للصورة الحالية
                    $current_image = [
                        'name' => $_FILES['product_image']['name'][$i],
                        'type' => $_FILES['product_image']['type'][$i],
                        'tmp_name' => $_FILES['product_image']['tmp_name'][$i],
                        'error' => $_FILES['product_image']['error'][$i],
                        'size' => $_FILES['product_image']['size'][$i]
                    ];
                    
                    // رفع الصورة
                    $upload_result = uploadImage($current_image, '../uploads/products/');
                    if ($upload_result['success']) {
                        $product_images[] = [
                            'filename' => $upload_result['filename'],
                            'is_primary' => ($i == 0) ? 1 : 0 // الصورة الأولى هي الرئيسية
                        ];
                        
                        if ($i == 0) {
                            $has_primary_image = true;
                            $primary_image_filename = $upload_result['filename'];
                        }
                    } else {
                        $errors[] = "خطأ في رفع الصورة " . ($i+1) . ": " . $upload_result['message'];
                    }
                }
            }
            
            if (!$has_primary_image) {
                $errors[] = "فشل رفع الصورة الرئيسية للمنتج";
            }
        }
    } else {
        $errors[] = "صورة المنتج مطلوبة";
    }
    
    // إذا لم يكن هناك أخطاء، قم بإضافة المنتج
    if (empty($errors)) {
        // استخدام الصورة الرئيسية للمنتج
        $image_path = 'uploads/products/' . $primary_image_filename;
        
        // بدء المعاملة
        $conn->begin_transaction();
        
        try {
            // إضافة المنتج مع التصنيف
            if ($category_id) {
                $sql = "INSERT INTO products (store_id, name, description, price, currency, hide_price, image_url, category_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issdsisss", $store_id, $name, $description, $price, $currency, $hide_price, $image_path, $category_id, $status);
            } else {
                $sql = "INSERT INTO products (store_id, name, description, price, currency, hide_price, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issdsiss", $store_id, $name, $description, $price, $currency, $hide_price, $image_path, $status);
            }
            
            if (!$stmt) {
                throw new Exception("حدث خطأ في الاستعلام: " . $conn->error);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("حدث خطأ أثناء إضافة المنتج: " . $stmt->error);
            }
            
            // الحصول على معرف المنتج المضاف حديثًا
            $product_id = $conn->insert_id;
            
            // إضافة صور المنتج إلى جدول product_images
            foreach ($product_images as $index => $image) {
                // تخزين المسار بالصيغة المناسبة لصفحات العرض
                $image_url = 'uploads/products/' . $image['filename'];
                $is_primary = $image['is_primary'];
                $sort_order = $index;
                
                $sql = "INSERT INTO product_images (product_id, image_url, display_order) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("خطأ في إعداد استعلام إضافة الصور: " . $conn->error);
                }
                $stmt->bind_param("isi", $product_id, $image_url, $sort_order);
                
                if (!$stmt->execute()) {
                    throw new Exception("حدث خطأ أثناء إضافة صورة المنتج: " . $stmt->error);
                }
            }
            
            // تأكيد المعاملة
            $conn->commit();
            $success = true;
            // إعادة تعيين النموذج
            $_POST = [];
            
        } catch (Exception $e) {
            // التراجع عن المعاملة في حالة حدوث خطأ
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة منتج جديد - لوحة التحكم</title>
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
            font-weight: 600;
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
        
        /* تنسيق الشريط العلوي */
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
        
        /* تنسيق معاينة الصورة */
        .image-preview {
            width: 100%;
            height: 200px;
            border-radius: var(--card-border-radius);
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 1rem;
            border: 2px dashed #e5e7eb;
            position: relative;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .image-preview-placeholder {
            color: #9ca3af;
            font-size: 3rem;
        }
        
        .image-preview-text {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        /* تنسيق المعلومات */
        .info-card {
            background-color: rgba(59, 130, 246, 0.05);
            border-radius: var(--card-border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-card-title {
            display: flex;
            align-items: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .info-card-title i {
            margin-left: 0.5rem;
            font-size: 1.2rem;
        }
        
        .info-card-content {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        /* تنسيق التصنيفات */
        .category-badge {
            display: inline-flex;
            align-items: center;
            background-color: #f3f4f6;
            color: #6b7280;
            border-radius: 20px;
            padding: 0.25rem 0.75rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            transition: all var(--transition-speed) ease;
        }
        
        .category-badge:hover {
            background-color: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
        }
        
        .category-badge i {
            margin-left: 0.25rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>

    <!-- الشريط العلوي -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1>إضافة منتج جديد</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                            <li class="breadcrumb-item"><a href="products.php">المنتجات</a></li>
                            <li class="breadcrumb-item active" aria-current="page">إضافة منتج</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="products.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-right me-1"></i> العودة للمنتجات
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill me-2"></i>
                تم إضافة المنتج بنجاح!
                <a href="products.php" class="alert-link">العودة إلى قائمة المنتجات</a>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>يرجى تصحيح الأخطاء التالية:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">بيانات المنتج</h5>
                        <span class="badge bg-primary">منتج جديد</span>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                تم إضافة المنتج بنجاح!
                                <a href="products.php" class="alert-link">العودة إلى قائمة المنتجات</a>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">اسم المنتج <span class="text-danger">*</span></label>
                                        <input type="text" name="name" id="name" class="form-control" placeholder="أدخل اسم المنتج" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">وصف المنتج <span class="text-danger">*</span></label>
                                        <textarea name="description" id="description" class="form-control" rows="5" placeholder="أدخل وصفاً تفصيلياً للمنتج" required></textarea>
                                        <div class="form-text">قم بوصف منتجك بشكل جيد لزيادة فرص المبيعات</div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="price" class="form-label">السعر <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" name="price" id="price" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                                                <select name="currency" id="currency" class="input-group-text" style="width: auto;">
                                                    <option value="SAR">ر.س</option>
                                                    <option value="YER">ر.ي</option>
                                                    <option value="USD">$</option>
                                                </select>
                                            </div>
                                            <div class="form-check mt-2">
                                                <input type="checkbox" class="form-check-input" id="hide_price" name="hide_price" value="1">
                                                <label class="form-check-label" for="hide_price">إخفاء السعر (سيظهر "اتصل للسعر" بدلاً من السعر)</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="category_id" class="form-label">التصنيف</label>
                                            <select name="category_id" id="category_id" class="form-select">
                                                <option value="">-- بدون تصنيف --</option>
                                                <?php 
                                                // إعادة تعيين مؤشر النتائج للبدء من جديد
                                                $categories_result->data_seek(0);
                                                while ($category = $categories_result->fetch_assoc()): 
                                                ?>
                                                    <option value="<?php echo $category['id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">صور المنتج <span class="text-danger">*</span></label>
                                        <div class="row mb-3" id="imagePreviewsContainer">
                                            <div class="col-md-4 mb-2">
                                                <div class="image-preview" id="imagePreview1">
                                                    <div class="image-preview-placeholder">
                                                        <i class="bi bi-image"></i>
                                                    </div>
                                                    <div class="image-preview-text">الصورة الرئيسية</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <input type="file" name="product_image[]" id="productImage1" class="form-control mb-2" accept="image/*" required>
                                        <div class="form-text mb-2">الصورة الأولى ستكون الصورة الرئيسية للمنتج</div>
                                        
                                        <div id="additionalImagesContainer">
                                            <!-- هنا سيتم إضافة حقول الصور الإضافية بشكل ديناميكي -->
                                        </div>
                                        
                                        <button type="button" id="addMoreImages" class="btn btn-outline-primary btn-sm mt-2">
                                            <i class="bi bi-plus-circle me-1"></i> إضافة صورة أخرى
                                        </button>
                                        
                                        <div class="form-text mt-2">يفضل صور بدقة عالية وخلفية بيضاء (الحد الأقصى 5 صور)</div>
                                    </div>
                                    
                                    <div class="info-card">
                                        <div class="info-card-title">
                                            <i class="bi bi-lightbulb"></i>
                                            نصائح لزيادة المبيعات
                                        </div>
                                        <div class="info-card-content">
                                            <ul class="mb-0 ps-3">
                                                <li>استخدم صورة عالية الجودة</li>
                                                <li>اكتب وصفاً تفصيلياً للمنتج</li>
                                                <li>حدد التصنيف المناسب</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <div class="d-flex justify-content-between">
                                <a href="products.php" class="btn btn-outline-secondary">إلغاء</a>
                                <button type="submit" class="btn btn-dashboard-primary">
                                    <i class="bi bi-plus-lg me-1"></i> إضافة المنتج
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">التصنيفات المتاحة</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        // إعادة تعيين مؤشر النتائج للبدء من جديد
                        $categories_result->data_seek(0);
                        if ($categories_result->num_rows > 0): 
                        ?>
                            <div class="mb-3">
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                    <div class="category-badge">
                                        <i class="bi bi-tag"></i>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="product_categories.php" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-lg me-1"></i> إضافة تصنيف جديد
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="mb-3">
                                    <i class="bi bi-tags text-muted" style="font-size: 2rem;"></i>
                                </div>
                                <p class="text-muted mb-3">لم تقم بإضافة أي تصنيفات بعد</p>
                                <a href="product_categories.php" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-lg me-1"></i> إضافة تصنيف جديد
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="mb-0">روابط سريعة</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <a href="products.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-box me-2"></i> إدارة المنتجات</span>
                                <i class="bi bi-chevron-left"></i>
                            </a>
                            <a href="product_categories.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-tags me-2"></i> إدارة التصنيفات</span>
                                <i class="bi bi-chevron-left"></i>
                            </a>
                            <a href="offers.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-percent me-2"></i> إدارة العروض</span>
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // معاينة الصورة الرئيسية
    const productImage1 = document.getElementById('productImage1');
    const imagePreview1 = document.getElementById('imagePreview1');
    
    productImage1.addEventListener('change', function() {
        previewImage(this, imagePreview1);
    });
    
    // إضافة المزيد من الصور
    let imageCounter = 1;
    const maxImages = 5;
    const addMoreImagesBtn = document.getElementById('addMoreImages');
    const additionalImagesContainer = document.getElementById('additionalImagesContainer');
    const imagePreviewsContainer = document.getElementById('imagePreviewsContainer');
    
    addMoreImagesBtn.addEventListener('click', function() {
        if (imageCounter < maxImages) {
            imageCounter++;
            
            // إضافة معاينة للصورة
            const previewCol = document.createElement('div');
            previewCol.className = 'col-md-4 mb-2';
            previewCol.innerHTML = `
                <div class="image-preview" id="imagePreview${imageCounter}">
                    <div class="image-preview-placeholder">
                        <i class="bi bi-image"></i>
                    </div>
                    <div class="image-preview-text">صورة إضافية</div>
                </div>
            `;
            imagePreviewsContainer.appendChild(previewCol);
            
            // إضافة حقل إدخال الصورة
            const imageInputGroup = document.createElement('div');
            imageInputGroup.className = 'input-group mb-2';
            imageInputGroup.innerHTML = `
                <input type="file" name="product_image[]" id="productImage${imageCounter}" class="form-control" accept="image/*">
                <button type="button" class="btn btn-outline-danger remove-image" data-image-id="${imageCounter}">
                    <i class="bi bi-x-circle"></i>
                </button>
            `;
            additionalImagesContainer.appendChild(imageInputGroup);
            
            // إضافة معاينة للصورة الجديدة
            const newImageInput = document.getElementById(`productImage${imageCounter}`);
            const newImagePreview = document.getElementById(`imagePreview${imageCounter}`);
            
            newImageInput.addEventListener('change', function() {
                previewImage(this, newImagePreview);
            });
            
            // إضافة وظيفة إزالة الصورة
            const removeBtn = imageInputGroup.querySelector('.remove-image');
            removeBtn.addEventListener('click', function() {
                const imageId = this.getAttribute('data-image-id');
                imageInputGroup.remove();
                previewCol.remove();
                // لا نقوم بتقليل العداد لتجنب تكرار المعرفات
            });
            
            // إخفاء زر الإضافة إذا وصلنا للحد الأقصى
            if (imageCounter >= maxImages) {
                addMoreImagesBtn.style.display = 'none';
            }
        }
    });
    
    // وظيفة معاينة الصورة
    function previewImage(input, previewElement) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewElement.innerHTML = `<img src="${e.target.result}" class="img-fluid" style="max-height: 150px; width: 100%; object-fit: contain;">`;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
});
</script>
    <script>
        // معاينة الصورة قبل الرفع
        document.addEventListener('DOMContentLoaded', function() {
            const imageInput = document.getElementById('productImage');
            const imagePreview = document.getElementById('imagePreview');
            
            imageInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        imagePreview.innerHTML = `<img src="${e.target.result}" alt="معاينة الصورة">`;
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });
            
            // جعل منطقة المعاينة قابلة للنقر لاختيار الصورة
            imagePreview.addEventListener('click', function() {
                imageInput.click();
            });
        });
    </script>
</body>
</html>
