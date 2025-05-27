<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'إضافة منتج جديد';
$page_icon = 'fa-plus';

// جلب قائمة المتاجر
$stores_query = "SELECT id, name FROM stores WHERE status = 'active' ORDER BY name ASC";
$stores_result = $conn->query($stores_query);
$stores = [];
if ($stores_result) {
    while ($row = $stores_result->fetch_assoc()) {
        $stores[] = $row;
    }
}

// جلب قائمة التصنيفات
$categories_query = "SELECT id, name FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// معالجة إضافة المنتج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استلام البيانات
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $currency = $_POST['currency'] ?? 'SAR';
    $hide_price = isset($_POST['hide_price']) ? 1 : 0;
    $store_id = intval($_POST['store_id'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    
    // التحقق من البيانات
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'يرجى إدخال اسم المنتج';
    }
    
    if ($price <= 0 && $hide_price == 0) {
        $errors[] = 'يرجى إدخال سعر صحيح للمنتج أو تفعيل خيار إخفاء السعر';
    }
    
    if ($store_id <= 0) {
        $errors[] = 'يرجى اختيار المتجر';
    }
    
    // معالجة الصورة
    $image_name = '';
    $image_url = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = 'نوع الملف غير مسموح به. الأنواع المسموح بها: JPG, PNG, GIF, WEBP';
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = 'حجم الصورة كبير جدًا. الحد الأقصى هو 5 ميجابايت';
        } else {
            // إنشاء اسم فريد للصورة
            $image_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('product_') . '.' . $image_extension;
            $upload_dir = '../uploads/products/';
            $image_url = 'uploads/products/' . $image_name;
            
            // التأكد من وجود المجلد
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // نقل الصورة
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
                $errors[] = 'حدث خطأ أثناء رفع الصورة';
                $image_name = '';
                $image_url = '';
            }
        }
    }
    
    // إذا لم تكن هناك أخطاء، قم بإضافة المنتج
    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, currency, hide_price, store_id, category_id, status, image, image_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssdsiiisss", $name, $description, $price, $currency, $hide_price, $store_id, $category_id, $status, $image_name, $image_url);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'تم إضافة المنتج بنجاح';
                header('Location: products.php');
                exit();
            } else {
                $errors[] = 'حدث خطأ أثناء إضافة المنتج: ' . $stmt->error;
                
                // حذف الصورة إذا كان هناك خطأ
                if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                    unlink($upload_dir . $image_name);
                }
            }
        } catch (Exception $e) {
            $errors[] = 'حدث خطأ أثناء إضافة المنتج: ' . $e->getMessage();
            
            // حذف الصورة إذا كان هناك خطأ
            if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                unlink($upload_dir . $image_name);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .form-label {
            font-weight: 500;
        }
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
            display: none;
        }
        .required::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas <?php echo $page_icon; ?>"></i> <?php echo $page_title; ?></h2>
            <a href="products.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i>
                العودة إلى قائمة المنتجات
            </a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form action="add_product.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- معلومات المنتج الأساسية -->
                            <div class="mb-3">
                                <label for="name" class="form-label required">اسم المنتج</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">وصف المنتج</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="price" class="form-label required">السعر</label>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="currency" class="form-label">العملة</label>
                                        <select class="form-select" id="currency" name="currency">
                                            <option value="SAR" <?php echo (isset($_POST['currency']) && $_POST['currency'] === 'SAR') ? 'selected' : ''; ?>>ريال سعودي (SAR)</option>
                                            <option value="YER" <?php echo (isset($_POST['currency']) && $_POST['currency'] === 'YER') ? 'selected' : ''; ?>>ريال يمني (YER)</option>
                                            <option value="USD" <?php echo (isset($_POST['currency']) && $_POST['currency'] === 'USD') ? 'selected' : ''; ?>>دولار أمريكي (USD)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label d-block">&nbsp;</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="hide_price" name="hide_price" value="1" <?php echo (isset($_POST['hide_price']) && $_POST['hide_price'] == 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="hide_price">
                                                إخفاء السعر
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="store_id" class="form-label required">المتجر</label>
                                        <select class="form-select" id="store_id" name="store_id" required>
                                            <option value="">-- اختر المتجر --</option>
                                            <?php foreach ($stores as $store): ?>
                                                <option value="<?php echo $store['id']; ?>" <?php echo (isset($_POST['store_id']) && $_POST['store_id'] == $store['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($store['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">التصنيف</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">-- بدون تصنيف --</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">الحالة</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'active') ? 'selected' : ''; ?>>نشط</option>
                                    <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>غير نشط</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- صورة المنتج -->
                            <div class="mb-3">
                                <label for="image" class="form-label">صورة المنتج</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="text-muted">الحد الأقصى لحجم الصورة: 5 ميجابايت. الأنواع المسموح بها: JPG, PNG, GIF, WEBP</small>
                                <img id="imagePreview" class="image-preview" alt="معاينة الصورة">
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-save me-2"></i>
                            حفظ المنتج
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // معاينة الصورة قبل الرفع
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
        
        // التحقق من إخفاء السعر
        document.getElementById('hide_price').addEventListener('change', function() {
            const priceField = document.getElementById('price');
            if (this.checked) {
                priceField.removeAttribute('required');
            } else {
                priceField.setAttribute('required', 'required');
            }
        });
    </script>
</body>
</html>
