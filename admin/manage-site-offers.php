<?php
session_start();
require_once '../includes/init.php';

// التحقق من تسجيل دخول المسؤول
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// التحقق من وجود مجلد الصور
$upload_dir = '../uploads/banners/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// معالجة إضافة عرض جديد
if (isset($_POST['add_offer'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $link = $_POST['link'];
    $position = $_POST['position'];
    $active = isset($_POST['active']) ? 1 : 0;
    
    // معالجة الصورة
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $file_name = time() . '_' . $_FILES['image']['name'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_type = $_FILES['image']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $extensions = array("jpeg", "jpg", "png", "gif");
        
        if (in_array($file_ext, $extensions)) {
            $image_path = $upload_dir . $file_name;
            move_uploaded_file($file_tmp, $image_path);
            
            // حفظ في قاعدة البيانات
            $image_path_db = 'uploads/banners/' . $file_name;
            
            $sql = "INSERT INTO site_offers (title, description, image_url, link, position, active) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssii", $title, $description, $image_path_db, $link, $position, $active);
            
            if ($stmt->execute()) {
                $success_message = "تم إضافة العرض بنجاح";
            } else {
                $error_message = "حدث خطأ أثناء إضافة العرض: " . $conn->error;
            }
        } else {
            $error_message = "صيغة الملف غير مدعومة، يرجى استخدام: jpeg, jpg, png, gif";
        }
    } else {
        $error_message = "يرجى اختيار صورة للعرض";
    }
}

// معالجة تعديل عرض
if (isset($_POST['edit_offer'])) {
    $offer_id = $_POST['offer_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $link = $_POST['link'];
    $position = $_POST['position'];
    $active = isset($_POST['active']) ? 1 : 0;
    
    // التحقق من وجود صورة جديدة
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $file_name = time() . '_' . $_FILES['image']['name'];
        $file_tmp = $_FILES['image']['tmp_name'];
        $file_type = $_FILES['image']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $extensions = array("jpeg", "jpg", "png", "gif");
        
        if (in_array($file_ext, $extensions)) {
            $image_path = $upload_dir . $file_name;
            move_uploaded_file($file_tmp, $image_path);
            
            // حفظ في قاعدة البيانات
            $image_path_db = 'uploads/banners/' . $file_name;
            
            // حذف الصورة القديمة إذا وجدت
            $sql = "SELECT image_url FROM site_offers WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $offer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $old_image = '../' . $row['image_url'];
                if (file_exists($old_image) && $row['image_url'] != '') {
                    unlink($old_image);
                }
            }
            
            $sql = "UPDATE site_offers SET title = ?, description = ?, image_url = ?, link = ?, position = ?, active = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssiis", $title, $description, $image_path_db, $link, $position, $active, $offer_id);
        } else {
            $error_message = "صيغة الملف غير مدعومة، يرجى استخدام: jpeg, jpg, png, gif";
        }
    } else {
        // تحديث بدون تغيير الصورة
        $sql = "UPDATE site_offers SET title = ?, description = ?, link = ?, position = ?, active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssis", $title, $description, $link, $position, $active, $offer_id);
    }
    
    if (isset($stmt) && $stmt->execute()) {
        $success_message = "تم تحديث العرض بنجاح";
    } else {
        $error_message = "حدث خطأ أثناء تحديث العرض: " . $conn->error;
    }
}

// معالجة حذف عرض
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $offer_id = $_GET['delete'];
    
    // حذف الصورة أولاً
    $sql = "SELECT image_url FROM site_offers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $offer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $image_path = '../' . $row['image_url'];
        if (file_exists($image_path) && $row['image_url'] != '') {
            unlink($image_path);
        }
    }
    
    // حذف من قاعدة البيانات
    $sql = "DELETE FROM site_offers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $offer_id);
    
    if ($stmt->execute()) {
        $success_message = "تم حذف العرض بنجاح";
    } else {
        $error_message = "حدث خطأ أثناء حذف العرض: " . $conn->error;
    }
}

// جلب جميع العروض
$sql = "SELECT * FROM site_offers ORDER BY position ASC";
$offers_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة عروض الموقع - لوحة التحكم</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .offer-image {
            width: 150px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        .position-badge {
            font-size: 0.8rem;
        }
        .main {
            background-color: #28a745;
        }
        .secondary {
            background-color: #17a2b8;
        }
        .banner {
            background-color: #fd7e14;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>إدارة عروض الموقع</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOfferModal">
                <i class="bi bi-plus-lg"></i> إضافة عرض جديد
            </button>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">العروض الحالية</h5>
            </div>
            <div class="card-body">
                <?php if ($offers_result && $offers_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الصورة</th>
                                    <th>العنوان</th>
                                    <th>الوصف</th>
                                    <th>الموضع</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($offer = $offers_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $offer['id']; ?></td>
                                        <td>
                                            <img src="../<?php echo $offer['image_url']; ?>" alt="<?php echo $offer['title']; ?>" class="offer-image">
                                        </td>
                                        <td><?php echo htmlspecialchars($offer['title']); ?></td>
                                        <td><?php echo htmlspecialchars(mb_substr($offer['description'], 0, 50)) . (mb_strlen($offer['description']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <?php 
                                            $position_class = '';
                                            $position_text = '';
                                            
                                            switch ($offer['position']) {
                                                case 1:
                                                    $position_class = 'main';
                                                    $position_text = 'رئيسي';
                                                    break;
                                                case 2:
                                                    $position_class = 'secondary';
                                                    $position_text = 'ثانوي 1';
                                                    break;
                                                case 3:
                                                    $position_class = 'secondary';
                                                    $position_text = 'ثانوي 2';
                                                    break;
                                                default:
                                                    $position_class = 'banner';
                                                    $position_text = 'بانر';
                                            }
                                            ?>
                                            <span class="badge <?php echo $position_class; ?>"><?php echo $position_text; ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $offer['active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $offer['active'] ? 'مفعل' : 'غير مفعل'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#editOfferModal<?php echo $offer['id']; ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="?delete=<?php echo $offer['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا العرض؟')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    
                                    <!-- Modal تعديل العرض -->
                                    <div class="modal fade" id="editOfferModal<?php echo $offer['id']; ?>" tabindex="-1" aria-labelledby="editOfferModalLabel<?php echo $offer['id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editOfferModalLabel<?php echo $offer['id']; ?>">تعديل العرض</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="" method="POST" enctype="multipart/form-data">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="offer_id" value="<?php echo $offer['id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label for="title<?php echo $offer['id']; ?>" class="form-label">عنوان العرض</label>
                                                            <input type="text" class="form-control" id="title<?php echo $offer['id']; ?>" name="title" value="<?php echo htmlspecialchars($offer['title']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="description<?php echo $offer['id']; ?>" class="form-label">وصف العرض</label>
                                                            <textarea class="form-control" id="description<?php echo $offer['id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($offer['description']); ?></textarea>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="link<?php echo $offer['id']; ?>" class="form-label">رابط العرض</label>
                                                            <input type="text" class="form-control" id="link<?php echo $offer['id']; ?>" name="link" value="<?php echo htmlspecialchars($offer['link']); ?>">
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="position<?php echo $offer['id']; ?>" class="form-label">موضع العرض</label>
                                                            <select class="form-select" id="position<?php echo $offer['id']; ?>" name="position">
                                                                <option value="1" <?php echo ($offer['position'] == 1) ? 'selected' : ''; ?>>رئيسي</option>
                                                                <option value="2" <?php echo ($offer['position'] == 2) ? 'selected' : ''; ?>>ثانوي 1</option>
                                                                <option value="3" <?php echo ($offer['position'] == 3) ? 'selected' : ''; ?>>ثانوي 2</option>
                                                                <option value="4" <?php echo ($offer['position'] == 4) ? 'selected' : ''; ?>>بانر</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="image<?php echo $offer['id']; ?>" class="form-label">صورة العرض</label>
                                                            <input type="file" class="form-control" id="image<?php echo $offer['id']; ?>" name="image">
                                                            <div class="form-text">اترك هذا الحقل فارغاً إذا كنت لا ترغب في تغيير الصورة.</div>
                                                            <div class="mt-2">
                                                                <img src="../<?php echo $offer['image_url']; ?>" alt="<?php echo $offer['title']; ?>" class="img-thumbnail" style="max-height: 100px;">
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3 form-check">
                                                            <input type="checkbox" class="form-check-input" id="active<?php echo $offer['id']; ?>" name="active" <?php echo ($offer['active'] == 1) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="active<?php echo $offer['id']; ?>">مفعل</label>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" name="edit_offer" class="btn btn-primary">حفظ التغييرات</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        لا توجد عروض متاحة حالياً.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Modal إضافة عرض جديد -->
    <div class="modal fade" id="addOfferModal" tabindex="-1" aria-labelledby="addOfferModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addOfferModalLabel">إضافة عرض جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">عنوان العرض</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">وصف العرض</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="link" class="form-label">رابط العرض</label>
                            <input type="text" class="form-control" id="link" name="link">
                        </div>
                        
                        <div class="mb-3">
                            <label for="position" class="form-label">موضع العرض</label>
                            <select class="form-select" id="position" name="position">
                                <option value="1">رئيسي</option>
                                <option value="2">ثانوي 1</option>
                                <option value="3">ثانوي 2</option>
                                <option value="4">بانر</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">صورة العرض</label>
                            <input type="file" class="form-control" id="image" name="image" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="active" name="active" checked>
                            <label class="form-check-label" for="active">مفعل</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_offer" class="btn btn-primary">إضافة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
