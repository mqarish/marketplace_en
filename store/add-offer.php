<?php
session_start();
require_once '../includes/init.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['store_id'])) {
    header("Location: login.php");
    exit();
}

$store_id = $_SESSION['store_id'];

// معالجة إضافة العرض
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $discount_percentage = $_POST['discount_percentage'] ?? 0;
    $offer_price = $_POST['offer_price'] ?? 0;
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = 'active';
    $errors = [];

    // التحقق من البيانات
    if (empty($title)) {
        $errors[] = "عنوان العرض مطلوب";
    }
    if (empty($discount_percentage)) {
        $errors[] = "نسبة الخصم مطلوبة";
    }
    if (empty($offer_price)) {
        $errors[] = "سعر العرض مطلوب";
    }
    if (empty($start_date)) {
        $errors[] = "تاريخ بداية العرض مطلوب";
    }
    if (empty($end_date)) {
        $errors[] = "تاريخ نهاية العرض مطلوب";
    }
    if (strtotime($end_date) < strtotime($start_date)) {
        $errors[] = "تاريخ نهاية العرض يجب أن يكون بعد تاريخ البداية";
    }

    // معالجة الصورة
    $image_path = '';
    if (isset($_FILES['offer_image']) && $_FILES['offer_image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['offer_image']['type'], $allowed_types)) {
            $errors[] = "نوع الملف غير مدعوم. الأنواع المدعومة هي: JPG, PNG, GIF";
        } elseif ($_FILES['offer_image']['size'] > $max_size) {
            $errors[] = "حجم الصورة يجب أن لا يتجاوز 5 ميجابايت";
        } else {
            $upload_dir = '../uploads/offers/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['offer_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['offer_image']['tmp_name'], $target_path)) {
                $image_path = 'uploads/offers/' . $new_filename;
            } else {
                $errors[] = "حدث خطأ أثناء رفع الصورة";
            }
        }
    }

    // إضافة العرض إذا لم يكن هناك أخطاء
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // إضافة العرض
            $sql = "INSERT INTO offers (store_id, title, description, image_path, discount_percentage, offer_price, start_date, end_date, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("خطأ في إعداد استعلام إضافة العرض");
            }
            
            $stmt->bind_param("issssssss", $store_id, $title, $description, $image_path, $discount_percentage, $offer_price, $start_date, $end_date, $status);
            if (!$stmt->execute()) {
                throw new Exception("خطأ في إضافة العرض");
            }
            
            $conn->commit();
            $_SESSION['success'] = "تم إضافة العرض بنجاح";
            header("Location: offers.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "حدث خطأ: " . $e->getMessage();
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">القائمة الرئيسية</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="index.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-speedometer2 me-2"></i>لوحة التحكم
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-person me-2"></i>الملف الشخصي
                        </a>
                        <a href="products.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-box me-2"></i>المنتجات
                        </a>
                        <a href="offers.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-tag me-2"></i>العروض
                        </a>
                        <a href="add-offer.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-plus-circle me-2"></i>إضافة عرض
                        </a>
                        <a href="orders.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-cart me-2"></i>الطلبات
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">إضافة عرض جديد</h5>
                        <a href="offers.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-right me-1"></i>
                            العودة للعروض
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">عنوان العرض</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="discount_percentage" class="form-label">نسبة الخصم (%)</label>
                                    <input type="number" class="form-control" id="discount_percentage" 
                                           name="discount_percentage" min="1" max="100" 
                                           value="<?php echo isset($_POST['discount_percentage']) ? htmlspecialchars($_POST['discount_percentage']) : ''; ?>" required>
                                    <div class="form-text">أدخل نسبة الخصم من 1 إلى 100</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="offer_price" class="form-label">سعر العرض</label>
                                    <input type="number" class="form-control" id="offer_price" 
                                           name="offer_price" step="0.01" 
                                           value="<?php echo isset($_POST['offer_price']) ? htmlspecialchars($_POST['offer_price']) : ''; ?>" required>
                                    <div class="form-text">أدخل سعر المنتج بعد الخصم</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">تاريخ البداية</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">تاريخ النهاية</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">وصف العرض</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="offer_image" class="form-label">صورة العرض</label>
                                    <input type="file" class="form-control" id="offer_image" name="offer_image" accept="image/*">
                                    <div class="form-text">الحد الأقصى لحجم الصورة: 5 ميجابايت. الأنواع المدعومة: JPG, PNG, GIF</div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-plus-lg me-1"></i>
                                    إضافة العرض
                                </button>
                                <a href="offers.php" class="btn btn-secondary">
                                    <i class="bi bi-x-lg me-1"></i>
                                    إلغاء
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // تحديد الحد الأدنى لتاريخ البداية (اليوم)
    document.getElementById('start_date').min = new Date().toISOString().split('T')[0];
    
    // تحديث الحد الأدنى لتاريخ النهاية عند تغيير تاريخ البداية
    document.getElementById('start_date').addEventListener('change', function() {
        document.getElementById('end_date').min = this.value;
        // إذا كان تاريخ النهاية أقل من تاريخ البداية، نجعله مساوياً لتاريخ البداية
        if (document.getElementById('end_date').value < this.value) {
            document.getElementById('end_date').value = this.value;
        }
    });
    </script>
</body>
</html>