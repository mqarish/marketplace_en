<?php
session_start();
require_once '../includes/init.php';

// التحقق من تسجيل الدخول كمتجر
if (!isset($_SESSION['store_id'])) {
    header("Location: login.php");
    exit();
}

$store_id = $_SESSION['store_id'];
$errors = [];
$success_message = '';

// التحقق من وجود معرف المنتج
if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    header("Location: products.php");
    exit();
}

$product_id = (int)$_GET['product_id'];

// التحقق من أن المنتج ينتمي للمتجر
$product_check_sql = "SELECT * FROM products WHERE id = ? AND store_id = ?";
$product_check_stmt = $conn->prepare($product_check_sql);
$product_check_stmt->bind_param("ii", $product_id, $store_id);
$product_check_stmt->execute();
$product = $product_check_stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: products.php");
    exit();
}

// معالجة رفع الصور الجديدة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_images'])) {
    // التحقق من وجود ملفات مرفوعة
    if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
        $upload_dir = '../uploads/products/';
        
        // التأكد من وجود المجلد
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // معالجة كل صورة
        $file_count = count($_FILES['product_images']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            $file_name = $_FILES['product_images']['name'][$i];
            $file_tmp = $_FILES['product_images']['tmp_name'][$i];
            $file_error = $_FILES['product_images']['error'][$i];
            
            // التحقق من عدم وجود أخطاء في الرفع
            if ($file_error === 0) {
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                // التحقق من امتداد الملف
                if (in_array($file_ext, $allowed_extensions)) {
                    // إنشاء اسم فريد للملف
                    $new_file_name = 'product_' . $product_id . '_' . uniqid() . '.' . $file_ext;
                    $file_destination = $upload_dir . $new_file_name;
                    
                    // نقل الملف إلى المجلد المطلوب
                    if (move_uploaded_file($file_tmp, $file_destination)) {
                        // الحصول على أعلى قيمة ترتيب حالية
                        $max_order_sql = "SELECT MAX(sort_order) as max_order FROM product_images WHERE product_id = ?";
                        $max_order_stmt = $conn->prepare($max_order_sql);
                        $max_order_stmt->bind_param("i", $product_id);
                        $max_order_stmt->execute();
                        $max_order_result = $max_order_stmt->get_result()->fetch_assoc();
                        $sort_order = ($max_order_result['max_order'] !== null) ? $max_order_result['max_order'] + 1 : 0;
                        
                        // حفظ معلومات الصورة في قاعدة البيانات
                        $image_sql = "INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)";
                        $image_stmt = $conn->prepare($image_sql);
                        $image_path = 'uploads/products/' . $new_file_name;
                        $image_stmt->bind_param("isi", $product_id, $image_path, $sort_order);
                        
                        if ($image_stmt->execute()) {
                            $success_message = 'تم رفع الصور بنجاح';
                        } else {
                            $errors[] = 'حدث خطأ أثناء حفظ معلومات الصورة: ' . $conn->error;
                        }
                    } else {
                        $errors[] = 'حدث خطأ أثناء رفع الصورة: ' . $file_name;
                    }
                } else {
                    $errors[] = 'امتداد الملف غير مسموح به: ' . $file_name;
                }
            } else {
                $errors[] = 'حدث خطأ أثناء رفع الصورة: ' . $file_name;
            }
        }
    } else {
        $errors[] = 'الرجاء اختيار صورة واحدة على الأقل';
    }
}

// حذف الصورة
if (isset($_GET['delete_image']) && is_numeric($_GET['delete_image'])) {
    $image_id = (int)$_GET['delete_image'];
    
    // التحقق من أن الصورة تنتمي للمنتج الذي ينتمي للمتجر
    $image_check_sql = "SELECT pi.* FROM product_images pi 
                        JOIN products p ON pi.product_id = p.id 
                        WHERE pi.id = ? AND p.store_id = ?";
    $image_check_stmt = $conn->prepare($image_check_sql);
    $image_check_stmt->bind_param("ii", $image_id, $store_id);
    $image_check_stmt->execute();
    $image = $image_check_stmt->get_result()->fetch_assoc();
    
    if ($image) {
        // حذف الملف من المجلد
        $file_path = '../' . $image['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // حذف السجل من قاعدة البيانات
        $delete_sql = "DELETE FROM product_images WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $image_id);
        
        if ($delete_stmt->execute()) {
            $success_message = 'تم حذف الصورة بنجاح';
        } else {
            $errors[] = 'حدث خطأ أثناء حذف الصورة: ' . $conn->error;
        }
    } else {
        $errors[] = 'الصورة غير موجودة أو ليست من صلاحياتك';
    }
}

// تغيير ترتيب الصور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    if (isset($_POST['image_order']) && is_array($_POST['image_order'])) {
        foreach ($_POST['image_order'] as $image_id => $order) {
            // التحقق من أن الصورة تنتمي للمنتج الذي ينتمي للمتجر
            $image_check_sql = "SELECT pi.* FROM product_images pi 
                                JOIN products p ON pi.product_id = p.id 
                                WHERE pi.id = ? AND p.store_id = ?";
            $image_check_stmt = $conn->prepare($image_check_sql);
            $image_check_stmt->bind_param("ii", $image_id, $store_id);
            $image_check_stmt->execute();
            $image = $image_check_stmt->get_result()->fetch_assoc();
            
            if ($image) {
                // تحديث الترتيب
                $update_sql = "UPDATE product_images SET sort_order = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ii", $order, $image_id);
                $update_stmt->execute();
            }
        }
        
        $success_message = 'تم تحديث ترتيب الصور بنجاح';
    }
}

// جلب صور المنتج
$images_sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC";
$images_stmt = $conn->prepare($images_sql);
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة صور المنتج - <?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .product-image-container {
            position: relative;
            margin-bottom: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: contain;
            background-color: #f8f9fa;
        }
        .image-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }
        .image-order {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: rgba(0,0,0,0.7);
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .btn-image-action {
            background-color: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: #333;
            transition: all 0.2s;
        }
        .btn-image-action:hover {
            background-color: white;
            transform: scale(1.1);
        }
        .btn-delete {
            color: #dc3545;
        }
        .image-preview {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            background-color: #f8f9fa;
        }
        #preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        .preview-item {
            position: relative;
            width: 100px;
            height: 100px;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 5px;
        }
        .remove-preview {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #dc3545;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .sortable-ghost {
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0">إدارة صور المنتج</h1>
            <a href="edit-product.php?id=<?php echo $product_id; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-right-short"></i>
                العودة للمنتج
            </a>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                <p class="card-text text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . (strlen($product['description']) > 100 ? '...' : ''); ?></p>
            </div>
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
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">صور المنتج الحالية</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($images_result->num_rows > 0): ?>
                            <form action="" method="POST">
                                <div class="row row-cols-1 row-cols-md-3 g-3" id="sortable-images">
                                    <?php while ($image = $images_result->fetch_assoc()): ?>
                                        <div class="col" data-id="<?php echo $image['id']; ?>">
                                            <div class="product-image-container">
                                                <img src="../<?php echo htmlspecialchars($image['image_path']); ?>" alt="صورة المنتج" class="product-image">
                                                <div class="image-actions">
                                                    <a href="?product_id=<?php echo $product_id; ?>&delete_image=<?php echo $image['id']; ?>" class="btn-image-action btn-delete" onclick="return confirm('هل أنت متأكد من حذف هذه الصورة؟');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                                <div class="image-order">
                                                    <input type="number" name="image_order[<?php echo $image['id']; ?>]" value="<?php echo $image['sort_order']; ?>" min="0" class="form-control form-control-sm order-input" style="width: 60px;">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" name="update_order" class="btn btn-primary">
                                        <i class="bi bi-sort-numeric-down"></i>
                                        تحديث الترتيب
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                لا توجد صور مضافة لهذا المنتج بعد.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">إضافة صور جديدة</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="product_images" class="form-label">اختر الصور</label>
                                <input type="file" class="form-control" id="product_images" name="product_images[]" multiple accept="image/*" onchange="previewImages(this)">
                                <div class="form-text">يمكنك اختيار عدة صور في نفس الوقت.</div>
                            </div>
                            
                            <div class="image-preview">
                                <div id="preview-container"></div>
                                <div id="preview-placeholder">
                                    <i class="bi bi-images" style="font-size: 2rem; color: #aaa;"></i>
                                    <p class="mt-2 mb-0 text-muted">سيتم عرض معاينة للصور هنا</p>
                                </div>
                            </div>
                            
                            <button type="submit" name="upload_images" class="btn btn-success w-100">
                                <i class="bi bi-cloud-upload"></i>
                                رفع الصور
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        // معاينة الصور قبل الرفع
        function previewImages(input) {
            const previewContainer = document.getElementById('preview-container');
            const previewPlaceholder = document.getElementById('preview-placeholder');
            
            previewContainer.innerHTML = '';
            
            if (input.files && input.files.length > 0) {
                previewPlaceholder.style.display = 'none';
                
                for (let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        
                        const removeBtn = document.createElement('div');
                        removeBtn.className = 'remove-preview';
                        removeBtn.innerHTML = '<i class="bi bi-x"></i>';
                        removeBtn.onclick = function() {
                            previewItem.remove();
                            
                            // إذا لم تعد هناك صور، أظهر النص الافتراضي
                            if (previewContainer.children.length === 0) {
                                previewPlaceholder.style.display = 'block';
                            }
                        };
                        
                        previewItem.appendChild(img);
                        previewItem.appendChild(removeBtn);
                        previewContainer.appendChild(previewItem);
                    };
                    
                    reader.readAsDataURL(file);
                }
            } else {
                previewPlaceholder.style.display = 'block';
            }
        }
        
        // ترتيب الصور بالسحب والإفلات
        document.addEventListener('DOMContentLoaded', function() {
            const sortableContainer = document.getElementById('sortable-images');
            
            if (sortableContainer) {
                new Sortable(sortableContainer, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function(evt) {
                        // تحديث قيم الترتيب بعد السحب والإفلات
                        const items = sortableContainer.querySelectorAll('.col');
                        items.forEach((item, index) => {
                            const id = item.getAttribute('data-id');
                            const input = item.querySelector('.order-input');
                            input.value = index;
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>
