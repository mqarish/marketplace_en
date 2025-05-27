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

// التحقق من وجود معرف المنتج
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$product_id = (int)$_GET['id'];

// جلب بيانات المنتج
$sql = "SELECT * FROM products WHERE id = ? AND store_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $product_id, $store_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// جلب تصنيفات المنتجات الخاصة بالمتجر
$categories_sql = "SELECT * FROM product_categories WHERE store_id = ? ORDER BY name ASC";
$categories_stmt = $conn->prepare($categories_sql);
$categories_stmt->bind_param("i", $store_id);
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();

if (!$product) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $currency = isset($_POST['currency']) ? $_POST['currency'] : 'SAR';
    $hide_price = isset($_POST['hide_price']) ? 1 : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : 'inactive';
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : null;
    
    // التحقق من البيانات
    if (empty($name)) $errors[] = "اسم المنتج مطلوب";
    if (empty($description)) $errors[] = "وصف المنتج مطلوب";
    if ($price <= 0) $errors[] = "السعر يجب أن يكون أكبر من 0";
    
    // معالجة الصور الجديدة إذا تم رفعها
    $image_path = $product['image_url']; // الاحتفاظ بالصورة القديمة كقيمة افتراضية
    $new_images = [];
    $primary_image_changed = false;
    
    // معالجة الصور الجديدة
    if (isset($_FILES['product_image']) && is_array($_FILES['product_image']['name'])) {
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
                
                try {
                    // رفع الصورة
                    $upload_result = uploadImage($current_image, '../uploads/products/');
                    if ($upload_result['success']) {
                        $new_images[] = [
                            'filename' => $upload_result['filename'],
                            'path' => 'uploads/products/' . $upload_result['filename']
                        ];
                    } else {
                        $errors[] = "خطأ في رفع الصورة " . ($i+1) . ": " . $upload_result['message'];
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
    }
    
    // معالجة الصور المراد حذفها
    $removed_images = [];
    if (isset($_POST['removed_images']) && is_array($_POST['removed_images'])) {
        $removed_images = array_map('intval', $_POST['removed_images']);
    }
    
    // معالجة تغيير الصورة الرئيسية
    $primary_image_id = 0;
    if (isset($_POST['primary_image'])) {
        $primary_image_id = intval($_POST['primary_image']);
    }
    
    // تحديث المنتج إذا لم يكن هناك أخطاء
    if (empty($errors)) {
        // بدء المعاملة
        $conn->begin_transaction();
        
        try {
            // استخدام استعلام SQL مباشر بدلاً من prepared statement لتجنب مشاكل bind_param
            $name_escaped = $conn->real_escape_string($name);
            $description_escaped = $conn->real_escape_string($description);
            $price_escaped = floatval($price);
            $image_path_escaped = $conn->real_escape_string($image_path);
            $status_escaped = $conn->real_escape_string($status);
            $product_id_escaped = intval($product_id);
            $store_id_escaped = intval($store_id);
            
            // التحقق من وجود أعمدة العملة وإخفاء المبلغ
            $check_columns_query = "SHOW COLUMNS FROM products LIKE 'currency'";
            $column_exists = $conn->query($check_columns_query);
            $currency_exists = ($column_exists && $column_exists->num_rows > 0);
            
            if (!$currency_exists) {
                // إذا لم تكن الأعمدة موجودة، قم بإضافتها
                $add_currency_query = "ALTER TABLE products ADD COLUMN currency ENUM('SAR', 'YER', 'USD') DEFAULT 'SAR' AFTER price";
                $conn->query($add_currency_query);
                
                $add_hide_price_query = "ALTER TABLE products ADD COLUMN hide_price TINYINT(1) NOT NULL DEFAULT 0 AFTER currency";
                $conn->query($add_hide_price_query);
                
                $currency_exists = true; // الآن أصبحت الأعمدة موجودة
            }
            
            // إعداد المتغيرات الإضافية للعملة وإخفاء المبلغ
            $currency_escaped = $conn->real_escape_string($currency);
            $hide_price_escaped = $hide_price ? 1 : 0;
            
            // بناء استعلام SQL بناءً على وجود التصنيف
            if ($category_id === null || $category_id === '') {
                $update_sql = "UPDATE products SET 
                    name = '$name_escaped', 
                    description = '$description_escaped', 
                    price = $price_escaped, 
                    currency = '$currency_escaped', 
                    hide_price = $hide_price_escaped, 
                    image_url = '$image_path_escaped', 
                    status = '$status_escaped', 
                    category_id = NULL 
                    WHERE id = $product_id_escaped AND store_id = $store_id_escaped";
            } else {
                $category_id_escaped = intval($category_id);
                $update_sql = "UPDATE products SET 
                    name = '$name_escaped', 
                    description = '$description_escaped', 
                    price = $price_escaped, 
                    currency = '$currency_escaped', 
                    hide_price = $hide_price_escaped, 
                    image_url = '$image_path_escaped', 
                    status = '$status_escaped', 
                    category_id = $category_id_escaped 
                    WHERE id = $product_id_escaped AND store_id = $store_id_escaped";
            }
            
            // تنفيذ الاستعلام
            if (!$conn->query($update_sql)) {
                throw new Exception("خطأ في تحديث المنتج: " . $conn->error);
            }
            
            // معالجة الصور الجديدة
            if (!empty($new_images)) {
                foreach ($new_images as $index => $image) {
                    $image_url = $image['path'];
                    $display_order = $index;
                    
                    $sql = "INSERT INTO product_images (product_id, image_url, display_order) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        throw new Exception("خطأ في إعداد استعلام إضافة صورة: " . $conn->error);
                    }
                    $stmt->bind_param("isi", $product_id, $image_url, $display_order);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("خطأ في إضافة صورة المنتج: " . $stmt->error);
                    }
                }
            }
            
            // معالجة الصور المراد حذفها
            if (!empty($removed_images)) {
                foreach ($removed_images as $image_id) {
                    // الحصول على مسار الصورة قبل الحذف
                    $get_image_sql = "SELECT image_url FROM product_images WHERE id = ? AND product_id = ?";
                    $stmt = $conn->prepare($get_image_sql);
                    $stmt->bind_param("ii", $image_id, $product_id);
                    $stmt->execute();
                    $image_result = $stmt->get_result();
                    
                    if ($image_row = $image_result->fetch_assoc()) {
                        // حذف الملف من الخادم
                        if (!empty($image_row['image_url']) && file_exists('../' . $image_row['image_url'])) {
                            unlink('../' . $image_row['image_url']);
                        }
                        
                        // حذف الصورة من قاعدة البيانات
                        $delete_sql = "DELETE FROM product_images WHERE id = ? AND product_id = ?";
                        $stmt = $conn->prepare($delete_sql);
                        $stmt->bind_param("ii", $image_id, $product_id);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("خطأ في حذف صورة المنتج: " . $stmt->error);
                        }
                    }
                }
            }
            
            // معالجة تغيير الصورة الرئيسية
            if ($primary_image_id > 0) {
                // الحصول على الصورة المحددة وتعيينها كصورة رئيسية للمنتج
                $get_primary_image_sql = "SELECT image_url FROM product_images WHERE id = ? AND product_id = ?";
                $stmt = $conn->prepare($get_primary_image_sql);
                if (!$stmt) {
                    throw new Exception("خطأ في إعداد استعلام الصورة الرئيسية: " . $conn->error);
                }
                $stmt->bind_param("ii", $primary_image_id, $product_id);
                $stmt->execute();
                $primary_image_result = $stmt->get_result();
                
                if ($primary_image_row = $primary_image_result->fetch_assoc()) {
                    $primary_image_url = $primary_image_row['image_url'];
                    
                    // تحديث الصورة الرئيسية في جدول المنتجات
                    $update_product_image_sql = "UPDATE products SET image_url = ? WHERE id = ?";
                    $stmt = $conn->prepare($update_product_image_sql);
                    if (!$stmt) {
                        throw new Exception("خطأ في إعداد استعلام تحديث الصورة الرئيسية: " . $conn->error);
                    }
                    $stmt->bind_param("si", $primary_image_url, $product_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("خطأ في تحديث الصورة الرئيسية للمنتج: " . $stmt->error);
                    }
                    
                    // تحديث ترتيب الصورة لتكون الأولى
                    $update_display_order_sql = "UPDATE product_images SET display_order = 0 WHERE id = ? AND product_id = ?";
                    $stmt = $conn->prepare($update_display_order_sql);
                    if (!$stmt) {
                        throw new Exception("خطأ في إعداد استعلام تحديث ترتيب الصورة: " . $conn->error);
                    }
                    $stmt->bind_param("ii", $primary_image_id, $product_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("خطأ في تحديث ترتيب الصورة الرئيسية: " . $stmt->error);
                    }
                }
            }
            
            // تأكيد المعاملة
            $conn->commit();
            $success = true;
            
            // تحديث بيانات المنتج المعروضة
            $product['name'] = $name;
            $product['description'] = $description;
            $product['price'] = $price;
            $product['currency'] = $currency;
            $product['hide_price'] = $hide_price;
            $product['status'] = $status;
            $product['category_id'] = $category_id;
            
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
    <title>تعديل المنتج - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">تعديل المنتج</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">تم تحديث المنتج بنجاح</div>
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
                            <div class="mb-3">
                                <label for="name" class="form-label">اسم المنتج</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">وصف المنتج</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">السعر</label>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           value="<?php echo htmlspecialchars($product['price']); ?>" 
                                           min="0.01" step="0.01" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="currency" class="form-label">العملة</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="SAR" <?php echo (isset($product['currency']) && $product['currency'] == 'SAR') ? 'selected' : ''; ?>>ريال سعودي (SAR)</option>
                                        <option value="YER" <?php echo (isset($product['currency']) && $product['currency'] == 'YER') ? 'selected' : ''; ?>>ريال يمني (YER)</option>
                                        <option value="USD" <?php echo (isset($product['currency']) && $product['currency'] == 'USD') ? 'selected' : ''; ?>>دولار أمريكي (USD)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="hide_price" name="hide_price" value="1" <?php echo (isset($product['hide_price']) && $product['hide_price'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="hide_price">إخفاء السعر (سيظهر "اتصل للسعر" بدلاً من السعر)</label>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">صور المنتج</label>
                                
                                <!-- عرض الصور الحالية -->
                                <?php
                                // جلب صور المنتج من جدول product_images
                                $images_sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC";
                                $images_stmt = $conn->prepare($images_sql);
                                if ($images_stmt) {
                                    $images_stmt->bind_param("i", $product_id);
                                    $images_stmt->execute();
                                    $images_result = $images_stmt->get_result();
                                    $has_multiple_images = ($images_result->num_rows > 0);
                                } else {
                                    $has_multiple_images = false;
                                }
                                ?>
                                
                                <div class="row mb-3" id="current-images">
                                    <?php if ($has_multiple_images): ?>
                                        <?php while ($image = $images_result->fetch_assoc()): ?>
                                            <div class="col-md-4 mb-2 current-image-container" data-image-id="<?php echo $image['id']; ?>">
                                                <div class="card">
                                                    <img src="<?php echo '../' . htmlspecialchars($image['image_url']); ?>" 
                                                         class="card-img-top" alt="صورة المنتج" 
                                                         style="height: 150px; object-fit: contain;">
                                                    <div class="card-body p-2 d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <?php if ($product['image_url'] == $image['image_url']): ?>
                                                                <span class="badge bg-primary">الصورة الرئيسية</span>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-sm btn-outline-primary make-primary" data-image-id="<?php echo $image['id']; ?>">
                                                                    جعلها رئيسية
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php if ($product['image_url'] != $image['image_url']): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger remove-image" data-image-id="<?php echo $image['id']; ?>">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php elseif (!empty($product['image_url'])): ?>
                                        <!-- عرض الصورة القديمة من حقل image_url في جدول products -->
                                        <div class="col-md-4 mb-2">
                                            <div class="card">
                                                <img src="<?php echo '../' . htmlspecialchars($product['image_url']); ?>" 
                                                     class="card-img-top" alt="صورة المنتج" 
                                                     style="height: 150px; object-fit: contain;">
                                                <div class="card-body p-2">
                                                    <span class="badge bg-primary">الصورة الرئيسية</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- إضافة صور جديدة -->
                                <div class="mb-3">
                                    <label class="form-label">إضافة صور جديدة</label>
                                    <div class="row mb-3" id="imagePreviewsContainer">
                                        <!-- هنا سيتم عرض معاينات الصور الجديدة -->
                                    </div>
                                    
                                    <div id="additionalImagesContainer">
                                        <!-- هنا سيتم إضافة حقول الصور الإضافية بشكل ديناميكي -->
                                        <div class="input-group mb-2">
                                            <input type="file" name="product_image[]" class="form-control new-image-input" accept="image/*">
                                        </div>
                                    </div>
                                    
                                    <button type="button" id="addMoreImages" class="btn btn-outline-primary btn-sm mt-2">
                                        <i class="bi bi-plus-circle me-1"></i> إضافة صورة أخرى
                                    </button>
                                    
                                    <div class="form-text mt-2">يفضل صور بدقة عالية وخلفية بيضاء (الحد الأقصى 5 صور)</div>
                                    
                                    <!-- حقول خفية لتخزين الصور المراد حذفها -->
                                    <div id="removed-images-container">
                                        <!-- سيتم إضافة معرفات الصور المراد حذفها هنا -->
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="category_id" class="form-label">تصنيف المنتج</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">-- بدون تصنيف --</option>
                                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo (isset($product['category_id']) && $product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">حالة المنتج</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="active" <?php echo $product['status'] == 'active' ? 'selected' : ''; ?>>نشط</option>
                                    <option value="inactive" <?php echo $product['status'] == 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                                </select>
                            </div>

                            <div class="text-end">
                                <a href="products.php" class="btn btn-secondary">إلغاء</a>
                                <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // معالجة إضافة صور جديدة
    let imageCounter = 1;
    const maxImages = 5;
    const addMoreImagesBtn = document.getElementById('addMoreImages');
    const additionalImagesContainer = document.getElementById('additionalImagesContainer');
    const imagePreviewsContainer = document.getElementById('imagePreviewsContainer');
    
    // معاينة الصور الجديدة عند اختيارها
    document.querySelectorAll('.new-image-input').forEach(input => {
        input.addEventListener('change', function() {
            previewNewImage(this);
        });
    });
    
    // إضافة المزيد من الصور
    addMoreImagesBtn.addEventListener('click', function() {
        const currentImageInputs = document.querySelectorAll('.new-image-input').length;
        const currentImages = document.querySelectorAll('#current-images .current-image-container').length;
        
        if (currentImageInputs + currentImages < maxImages) {
            imageCounter++;
            
            // إضافة حقل إدخال الصورة
            const imageInputGroup = document.createElement('div');
            imageInputGroup.className = 'input-group mb-2';
            imageInputGroup.innerHTML = `
                <input type="file" name="product_image[]" class="form-control new-image-input" accept="image/*">
                <button type="button" class="btn btn-outline-danger remove-new-image">
                    <i class="bi bi-x-circle"></i>
                </button>
            `;
            additionalImagesContainer.appendChild(imageInputGroup);
            
            // إضافة معاينة للصورة الجديدة
            const newImageInput = imageInputGroup.querySelector('.new-image-input');
            newImageInput.addEventListener('change', function() {
                previewNewImage(this);
            });
            
            // إضافة وظيفة إزالة الصورة
            const removeBtn = imageInputGroup.querySelector('.remove-new-image');
            removeBtn.addEventListener('click', function() {
                // إزالة معاينة الصورة إذا وجدت
                const inputId = newImageInput.id || '';
                if (inputId) {
                    const previewElement = document.querySelector(`[data-for-input="${inputId}"]`);
                    if (previewElement) {
                        previewElement.remove();
                    }
                }
                
                // إزالة حقل الإدخال
                imageInputGroup.remove();
            });
            
            // إخفاء زر الإضافة إذا وصلنا للحد الأقصى
            if (currentImageInputs + 1 + currentImages >= maxImages) {
                addMoreImagesBtn.style.display = 'none';
            }
        }
    });
    
    // معالجة حذف الصور الحالية
    document.querySelectorAll('.remove-image').forEach(button => {
        button.addEventListener('click', function() {
            const imageId = this.getAttribute('data-image-id');
            const container = document.querySelector(`.current-image-container[data-image-id="${imageId}"]`);
            
            if (container && imageId) {
                // إضافة معرف الصورة المراد حذفها إلى حقل خفي
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'removed_images[]';
                hiddenInput.value = imageId;
                document.getElementById('removed-images-container').appendChild(hiddenInput);
                
                // إخفاء الصورة من العرض
                container.style.opacity = '0.5';
                container.style.pointerEvents = 'none';
                
                // عرض رسالة
                const message = document.createElement('div');
                message.className = 'position-absolute top-0 start-0 end-0 bottom-0 d-flex align-items-center justify-content-center bg-dark bg-opacity-50 text-white';
                message.innerHTML = 'سيتم الحذف عند الحفظ';
                container.querySelector('.card').appendChild(message);
                
                // تحديث عداد الصور المتاحة
                const currentImageInputs = document.querySelectorAll('.new-image-input').length;
                const visibleCurrentImages = document.querySelectorAll('#current-images .current-image-container:not([style*="opacity: 0.5"])').length;
                
                if (currentImageInputs + visibleCurrentImages < maxImages) {
                    addMoreImagesBtn.style.display = '';
                }
            }
        });
    });
    
    // معالجة تغيير الصورة الرئيسية
    document.querySelectorAll('.make-primary').forEach(button => {
        button.addEventListener('click', function() {
            const imageId = this.getAttribute('data-image-id');
            
            // إضافة معرف الصورة المراد جعلها رئيسية إلى حقل خفي
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'primary_image';
            hiddenInput.value = imageId;
            document.getElementById('removed-images-container').appendChild(hiddenInput);
            
            // تحديث واجهة المستخدم
            document.querySelectorAll('#current-images .current-image-container').forEach(container => {
                const containerImageId = container.getAttribute('data-image-id');
                const isPrimaryBadge = container.querySelector('.badge.bg-primary');
                const makePrimaryBtn = container.querySelector('.make-primary');
                
                if (containerImageId === imageId) {
                    // جعل هذه الصورة رئيسية
                    if (makePrimaryBtn) {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-primary';
                        badge.textContent = 'الصورة الرئيسية';
                        makePrimaryBtn.replaceWith(badge);
                    }
                } else if (isPrimaryBadge) {
                    // إزالة علامة الصورة الرئيسية من الصورة السابقة
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'btn btn-sm btn-outline-primary make-primary';
                    button.setAttribute('data-image-id', containerImageId);
                    button.textContent = 'جعلها رئيسية';
                    isPrimaryBadge.replaceWith(button);
                    
                    // إضافة حدث النقر للزر الجديد
                    button.addEventListener('click', function() {
                        const imageId = this.getAttribute('data-image-id');
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'primary_image';
                        hiddenInput.value = imageId;
                        document.getElementById('removed-images-container').appendChild(hiddenInput);
                    });
                }
            });
        });
    });
    
    // وظيفة معاينة الصورة الجديدة
    function previewNewImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            // إنشاء معرف فريد للصورة إذا لم يكن موجودًا
            if (!input.id) {
                input.id = 'new-image-' + Date.now();
            }
            
            // إزالة المعاينة السابقة إذا وجدت
            const existingPreview = document.querySelector(`[data-for-input="${input.id}"]`);
            if (existingPreview) {
                existingPreview.remove();
            }
            
            reader.onload = function(e) {
                // إنشاء عنصر معاينة جديد
                const previewCol = document.createElement('div');
                previewCol.className = 'col-md-4 mb-2';
                previewCol.setAttribute('data-for-input', input.id);
                previewCol.innerHTML = `
                    <div class="card">
                        <img src="${e.target.result}" class="card-img-top" alt="صورة جديدة" style="height: 150px; object-fit: contain;">
                        <div class="card-body p-2 text-center">
                            <span class="badge bg-info">صورة جديدة</span>
                        </div>
                    </div>
                `;
                imagePreviewsContainer.appendChild(previewCol);
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
});
</script>
</body>
</html>
