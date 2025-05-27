<?php
session_start();
require_once '../includes/init.php';

// التحقق من تسجيل الدخول كمتجر
if (!isset($_SESSION['store_id'])) {
    header("Location: login.php");
    exit();
}

$store_id = $_SESSION['store_id'];
$error_msg = '';
$success_msg = '';

// معالجة إضافة العرض
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $discount = floatval($_POST['discount']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    if (empty($title)) {
        $error_msg = 'يرجى إدخال عنوان العرض';
    } elseif ($discount <= 0 || $discount > 100) {
        $error_msg = 'نسبة الخصم يجب أن تكون بين 1 و 100';
    } elseif (strtotime($start_date) > strtotime($end_date)) {
        $error_msg = 'تاريخ البداية يجب أن يكون قبل تاريخ النهاية';
    } else {
        // معالجة صورة العرض
        $image_path = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_path = __DIR__ . '/../uploads/offers/';
            if (!file_exists($upload_path)) {
                mkdir($upload_path, 0777, true);
            }
            
            try {
                $file_info = pathinfo($_FILES['image']['name']);
                $file_extension = strtolower($file_info['extension']);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception('نوع الملف غير مدعوم. الأنواع المدعومة هي: ' . implode(', ', $allowed_extensions));
                }
                
                if ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5 MB
                    throw new Exception('حجم الملف كبير جداً. الحد الأقصى هو 5 ميجابايت');
                }
                
                $new_file_name = uniqid() . '.' . $file_extension;
                $destination = $upload_path . $new_file_name;
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    throw new Exception('فشل في تحميل الصورة');
                }
                
                $image_path = 'uploads/offers/' . $new_file_name;
            } catch (Exception $e) {
                $error_msg = $e->getMessage();
            }
        }

        if (empty($error_msg)) {
            try {
                // بدء المعاملة
                $conn->begin_transaction();

                // إضافة العرض
                $insert_sql = "INSERT INTO offers (store_id, title, description, image_path, 
                                         discount_percentage, start_date, end_date, status, offer_price) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 0.00)";
        
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("isssiss", 
                    $store_id, 
                    $title, 
                    $description, 
                    $image_path,
                    $discount, 
                    $start_date, 
                    $end_date
                );

                if (!$insert_stmt->execute()) {
                    throw new Exception('حدث خطأ أثناء إضافة العرض: ' . $insert_stmt->error);
                }

                $offer_id = $conn->insert_id;

                // إضافة منتجات العرض
                if (isset($_POST['items']) && is_array($_POST['items'])) {
                    $insert_items_sql = "INSERT INTO offer_items (offer_id, product_id, name, price) 
                                       VALUES (?, ?, ?, ?)";
                    $insert_items_stmt = $conn->prepare($insert_items_sql);

                    foreach ($_POST['items']['product_id'] as $index => $product_id) {
                        if (empty($product_id)) continue;
                        
                        // Get product details from database
                        $product_query = "SELECT name FROM products WHERE id = ?";
                        $product_stmt = $conn->prepare($product_query);
                        $product_stmt->bind_param("i", $product_id);
                        $product_stmt->execute();
                        $product_result = $product_stmt->get_result();
                        $product = $product_result->fetch_assoc();
                        
                        $name = $product['name'];
                        $price = floatval($_POST['items']['price'][$index]);
                        
                        $insert_items_stmt->bind_param("iiss", 
                            $offer_id,
                            $product_id,
                            $name,
                            $price
                        );

                        if (!$insert_items_stmt->execute()) {
                            throw new Exception('حدث خطأ أثناء إضافة منتج العرض: ' . $insert_items_stmt->error);
                        }
                    }
                } else {
                    throw new Exception('يجب إضافة منتج واحد على الأقل للعرض');
                }

                // تأكيد المعاملة
                $conn->commit();
                header("Location: offers.php?success=1");
                exit();

            } catch (Exception $e) {
                // التراجع عن المعاملة في حالة حدوث خطأ
                $conn->rollback();
                $error_msg = $e->getMessage();
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
    <title>إضافة عرض جديد - لوحة التحكم</title>
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
        
        .btn-dashboard-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #0d9488 100%);
            border: none;
            color: white;
            font-weight: 500;
            border-radius: 6px;
            padding: 0.5rem 1.25rem;
            transition: all var(--transition-speed) ease;
        }
        
        .btn-dashboard-success:hover {
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
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
        
        /* تنسيق منتجات العرض */
        .offer-item {
            border: 1px solid #e5e7eb;
            border-radius: var(--card-border-radius);
            margin-bottom: 1rem;
            transition: all var(--transition-speed) ease;
        }
        
        .offer-item:hover {
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }
        
        .offer-item .card-header {
            background-color: #f9fafb;
            padding: 0.75rem 1rem;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .offer-item .card-body {
            padding: 1rem;
        }
        
        .remove-item {
            width: 32px;
            height: 32px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: none;
            transition: all var(--transition-speed) ease;
        }
        
        .remove-item:hover {
            background-color: rgba(239, 68, 68, 0.2);
            transform: translateY(-1px);
        }
        
        /* تنسيق الملخص */
        .summary-card {
            background-color: #fff;
            border-radius: var(--card-border-radius);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }
        
        .summary-card .card-header {
            background-color: #f9fafb;
            padding: 1rem;
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .summary-card .card-body {
            padding: 1rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .summary-value {
            font-weight: 500;
        }
        
        /* تنسيق التاريخ */
        .date-range {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .date-range-divider {
            margin: 0 0.5rem;
            color: #9ca3af;
        }
        
        /* تنسيق المراحل */
        .steps {
            display: flex;
            margin-bottom: 2rem;
        }
        
        .step {
            flex: 1;
            text-align: center;
            padding: 1rem 0.5rem;
            position: relative;
        }
        
        .step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 2rem;
            right: calc(50% + 1rem);
            width: calc(100% - 2rem);
            height: 2px;
            background-color: #e5e7eb;
        }
        
        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: #f3f4f6;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.5rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .step.active .step-number {
            background-color: var(--primary-color);
            color: white;
        }
        
        .step.completed .step-number {
            background-color: var(--success-color);
            color: white;
        }
        
        .step-title {
            font-size: 0.9rem;
            font-weight: 500;
            color: #6b7280;
        }
        
        .step.active .step-title {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .step.completed .step-title {
            color: var(--success-color);
        }
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>
    
    <!-- الشريط العلوي مع مسار التنقل -->
    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="bi bi-tags"></i> إضافة عرض جديد</h1>
                </div>
                <div class="col-md-6">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-md-end">
                            <li class="breadcrumb-item"><a href="dashboard.php">لوحة التحكم</a></li>
                            <li class="breadcrumb-item"><a href="offers.php">العروض</a></li>
                            <li class="breadcrumb-item active" aria-current="page">إضافة عرض</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container py-4">
        <!-- مراحل إضافة العرض -->
        <div class="steps mb-4">
            <div class="step active">
                <div class="step-number">1</div>
                <div class="step-title">معلومات العرض</div>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-title">إضافة المنتجات</div>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-title">المراجعة والنشر</div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <!-- بطاقة المعلومات -->
                <div class="info-card mb-4">
                    <div class="info-card-title">
                        <i class="bi bi-lightbulb"></i>
                        <span>نصائح لزيادة المبيعات</span>
                    </div>
                    <div class="info-card-content">
                        <ul class="mb-0 ps-3">
                            <li>استخدم صورة جذابة للعرض</li>
                            <li>حدد نسبة خصم منافسة</li>
                            <li>اختر عنوان واضح ومختصر</li>
                            <li>حدد فترة زمنية مناسبة للعرض</li>
                        </ul>
                    </div>
                </div>
                
                <!-- روابط سريعة -->
                <div class="dashboard-card">
                    <div class="card-header">روابط سريعة</div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="offers.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="bi bi-tag me-2"></i> جميع العروض
                            </a>
                            <a href="products.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="bi bi-box me-2"></i> إدارة المنتجات
                            </a>
                            <a href="add-product.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="bi bi-plus-circle me-2"></i> إضافة منتج جديد
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>معلومات العرض الأساسية</span>
                        <span class="badge bg-primary">الخطوة 1 من 3</span>
                    </div>
                    <div class="card-body">
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

                        <form method="post" enctype="multipart/form-data" id="offerForm">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">عنوان العرض <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-tag"></i></span>
                                            <input type="text" name="title" class="form-control" placeholder="أدخل عنوان العرض" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                                        </div>
                                        <small class="text-muted">اختر عنوان واضح ومختصر يصف العرض بشكل دقيق</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">وصف العرض</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                                            <textarea name="description" class="form-control" rows="4" placeholder="أدخل وصف تفصيلي للعرض"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                        </div>
                                        <small class="text-muted">أضف وصفاً تفصيلياً للعرض لجذب المزيد من العملاء</small>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label">نسبة الخصم (%) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="bi bi-percent"></i></span>
                                                <input type="number" name="discount" class="form-control" min="1" max="100" placeholder="نسبة الخصم" required value="<?php echo isset($_POST['discount']) ? htmlspecialchars($_POST['discount']) : ''; ?>">
                                            </div>
                                            <small class="text-muted">أدخل قيمة بين 1 و 100</small>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-4">
                                        <label class="form-label">فترة العرض <span class="text-danger">*</span></label>
                                        <div class="col-md-6">
                                            <div class="input-group mb-2">
                                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                                <input type="date" name="start_date" class="form-control" required value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-d'); ?>">
                                            </div>
                                            <small class="text-muted">تاريخ بداية العرض</small>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group mb-2">
                                                <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                                                <input type="date" name="end_date" class="form-control" required value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : date('Y-m-d', strtotime('+7 days')); ?>">
                                            </div>
                                            <small class="text-muted">تاريخ نهاية العرض</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label class="form-label">صورة العرض</label>
                                    <div class="image-preview mb-3" id="imagePreview">
                                        <div class="image-preview-placeholder">
                                            <i class="bi bi-image"></i>
                                        </div>
                                        <div class="image-preview-text">انقر لاختيار صورة</div>
                                    </div>
                                    <input type="file" name="image" id="imageInput" class="form-control" accept="image/*" style="display: none;">
                                    <div class="d-grid">
                                        <button type="button" class="btn btn-outline-primary" id="selectImageBtn">
                                            <i class="bi bi-upload me-2"></i> اختيار صورة
                                        </button>
                                    </div>
                                    <small class="d-block text-muted mt-2">الصيغ المدعومة: JPG, JPEG, PNG, GIF</small>
                                    <small class="d-block text-muted">الحجم الأقصى: 5 ميجابايت</small>
                                </div>
                            </div>
                            
                            <hr class="my-4">

                            <div class="dashboard-card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-box-seam me-2"></i> منتجات العرض</span>
                                    <button type="button" class="btn btn-dashboard-success btn-sm" id="add-item">
                                        <i class="bi bi-plus-circle"></i> إضافة منتج
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info mb-3">
                                        <i class="bi bi-info-circle-fill me-2"></i>
                                        يجب إضافة منتج واحد على الأقل للعرض. يمكنك إضافة منتجات متعددة للعرض الواحد.
                                    </div>
                                    <div id="offer-items-container" class="mb-3">
                                        <!-- سيتم إضافة منتجات العرض هنا -->
                                    </div>
                                    <div id="no-items-message" class="text-center py-4 text-muted" style="display: none;">
                                        <i class="bi bi-basket3 fs-1 mb-2"></i>
                                        <p>لم تقم بإضافة أي منتجات للعرض بعد</p>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="offers.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-right me-1"></i> العودة للعروض
                                </a>
                                <button type="submit" class="btn btn-dashboard-primary">
                                    <i class="bi bi-check-circle me-1"></i> حفظ العرض
                                </button>
                            </div>
                        </form>

                        <template id="offer-item-template">
                            <div class="offer-item mb-3">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span class="product-name-display">منتج جديد</span>
                                    <button type="button" class="remove-item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">المنتج <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-box"></i></span>
                                                    <select name="items[product_id][]" class="form-select product-select" required>
                                                        <option value="">اختر المنتج</option>
                                                        <?php
                                                        $products_query = "SELECT id, name, price FROM products WHERE store_id = ? AND status = 'active'";
                                                        $products_stmt = $conn->prepare($products_query);
                                                        $products_stmt->bind_param('i', $store_id);
                                                        $products_stmt->execute();
                                                        $products_result = $products_stmt->get_result();
                                                        
                                                        if ($products_result->num_rows > 0) {
                                                            while ($product = $products_result->fetch_assoc()):
                                                            ?>
                                                            <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>">
                                                                <?php echo htmlspecialchars($product['name']); ?> - <?php echo formatPrice($product['price']); ?>
                                                            </option>
                                                            <?php endwhile;
                                                        } else {
                                                            ?>
                                                            <option value="" disabled>لا توجد منتجات متاحة</option>
                                                            <?php
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <small class="text-muted">اختر المنتج الذي تريد إضافته للعرض</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">السعر الجديد <span class="text-danger">*</span></label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                                    <input type="number" step="0.01" min="0.01" name="items[price][]" class="form-control item-price" required>
                                                </div>
                                                <small class="text-muted">السعر بعد تطبيق الخصم</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-0">
                                                <label class="form-label">صورة المنتج (اختياري)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="bi bi-image"></i></span>
                                                    <input type="file" name="items[image][]" class="form-control" accept="image/*">
                                                </div>
                                                <small class="text-muted">يمكنك إضافة صورة خاصة بالمنتج في العرض</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                // معاينة الصورة
                                const imagePreview = document.getElementById('imagePreview');
                                const imageInput = document.getElementById('imageInput');
                                const selectImageBtn = document.getElementById('selectImageBtn');
                                
                                // عند النقر على زر اختيار الصورة
                                selectImageBtn.addEventListener('click', function() {
                                    imageInput.click();
                                });
                                
                                // عند النقر على معاينة الصورة
                                imagePreview.addEventListener('click', function() {
                                    imageInput.click();
                                });
                                
                                // عند تغيير الصورة
                                imageInput.addEventListener('change', function() {
                                    const file = this.files[0];
                                    if (file) {
                                        const reader = new FileReader();
                                        reader.onload = function(e) {
                                            // إزالة العناصر الحالية
                                            while (imagePreview.firstChild) {
                                                imagePreview.removeChild(imagePreview.firstChild);
                                            }
                                            
                                            // إضافة الصورة
                                            const img = document.createElement('img');
                                            img.src = e.target.result;
                                            imagePreview.appendChild(img);
                                        };
                                        reader.readAsDataURL(file);
                                    }
                                });
                                
                                // منتجات العرض
                                const container = document.getElementById('offer-items-container');
                                const template = document.getElementById('offer-item-template');
                                const addButton = document.getElementById('add-item');
                                const noItemsMessage = document.getElementById('no-items-message');
                                
                                // التحقق من وجود منتجات
                                function checkItems() {
                                    if (container.children.length === 0) {
                                        noItemsMessage.style.display = 'block';
                                    } else {
                                        noItemsMessage.style.display = 'none';
                                    }
                                }
                                
                                // إضافة منتج جديد
                                function addNewItem() {
                                    const clone = template.content.cloneNode(true);
                                    const offerItem = clone.querySelector('.offer-item');
                                    const productSelect = clone.querySelector('.product-select');
                                    const priceInput = clone.querySelector('.item-price');
                                    const productNameDisplay = clone.querySelector('.product-name-display');
                                    
                                    // تحديث اسم المنتج عند الاختيار
                                    productSelect.addEventListener('change', function() {
                                        const selectedOption = this.options[this.selectedIndex];
                                        if (selectedOption.value) {
                                            const price = selectedOption.dataset.price;
                                            const name = selectedOption.dataset.name;
                                            
                                            // تحديث السعر
                                            if (price) {
                                                // حساب السعر بعد الخصم
                                                const discountInput = document.querySelector('input[name="discount"]');
                                                const discount = parseFloat(discountInput.value) || 0;
                                                if (discount > 0 && discount <= 100) {
                                                    const discountedPrice = (price * (100 - discount) / 100).toFixed(2);
                                                    priceInput.value = discountedPrice;
                                                } else {
                                                    priceInput.value = price;
                                                }
                                            }
                                            
                                            // تحديث اسم المنتج في العنوان
                                            if (name) {
                                                productNameDisplay.textContent = name;
                                            }
                                        }
                                    });
                                    
                                    // حذف المنتج
                                    const removeButton = clone.querySelector('.remove-item');
                                    removeButton.addEventListener('click', function() {
                                        offerItem.remove();
                                        checkItems();
                                    });
                                    
                                    container.appendChild(clone);
                                    checkItems();
                                }
                                
                                // إضافة منتج افتراضي
                                addNewItem();
                                
                                // إضافة منتج عند النقر على زر الإضافة
                                addButton.addEventListener('click', addNewItem);
                                
                                // التحقق من وجود منتجات عند تحميل الصفحة
                                checkItems();
                                
                                // تحديث أسعار المنتجات عند تغيير نسبة الخصم
                                const discountInput = document.querySelector('input[name="discount"]');
                                discountInput.addEventListener('change', function() {
                                    const discount = parseFloat(this.value) || 0;
                                    if (discount > 0 && discount <= 100) {
                                        // تحديث أسعار جميع المنتجات
                                        const productSelects = document.querySelectorAll('.product-select');
                                        productSelects.forEach(select => {
                                            if (select.value) {
                                                const selectedOption = select.options[select.selectedIndex];
                                                const price = parseFloat(selectedOption.dataset.price) || 0;
                                                const priceInput = select.closest('.offer-item').querySelector('.item-price');
                                                const discountedPrice = (price * (100 - discount) / 100).toFixed(2);
                                                priceInput.value = discountedPrice;
                                            }
                                        });
                                    }
                                });
                                
                                // التحقق من النموذج قبل الإرسال
                                const offerForm = document.getElementById('offerForm');
                                offerForm.addEventListener('submit', function(e) {
                                    // التحقق من وجود منتج واحد على الأقل
                                    if (container.children.length === 0) {
                                        e.preventDefault();
                                        alert('يجب إضافة منتج واحد على الأقل للعرض');
                                        return false;
                                    }
                                    
                                    // التحقق من تواريخ العرض
                                    const startDate = new Date(document.querySelector('input[name="start_date"]').value);
                                    const endDate = new Date(document.querySelector('input[name="end_date"]').value);
                                    
                                    if (startDate > endDate) {
                                        e.preventDefault();
                                        alert('تاريخ بداية العرض يجب أن يكون قبل تاريخ نهايته');
                                        return false;
                                    }
                                    
                                    return true;
                                });
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
