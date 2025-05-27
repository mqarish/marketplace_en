<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'تفاصيل المنتج';
$page_icon = 'fa-box';

// التحقق من وجود معرف المنتج
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'معرف المنتج غير صحيح';
    header('Location: products.php');
    exit();
}

$product_id = intval($_GET['id']);

// جلب معلومات المنتج مع معلومات المتجر والتصنيف
$query = "SELECT p.*, 
          s.name as store_name, s.id as store_id, s.email as store_email, s.phone as store_phone,
          c.name as category_name, c.id as category_id
          FROM products p
          LEFT JOIN stores s ON p.store_id = s.id
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'المنتج غير موجود';
    header('Location: products.php');
    exit();
}

$product = $result->fetch_assoc();

// معالجة تحديث حالة المنتج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $new_status = $_POST['status'];
        $update_stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_status, $product_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = 'تم تحديث حالة المنتج بنجاح';
            // تحديث المنتج في الصفحة الحالية
            $product['status'] = $new_status;
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء تحديث حالة المنتج';
        }
    } elseif ($_POST['action'] === 'delete') {
        // حذف المنتج وصورته
        $conn->begin_transaction();
        try {
            // حذف الصورة إذا كانت موجودة
            if (!empty($product['image'])) {
                $image_path = "../uploads/products/" . $product['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // حذف المنتج
            $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $delete_stmt->bind_param("i", $product_id);
            $delete_stmt->execute();
            
            $conn->commit();
            $_SESSION['success'] = 'تم حذف المنتج بنجاح';
            header('Location: products.php');
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = 'حدث خطأ أثناء حذف المنتج: ' . $e->getMessage();
        }
    }
}

// تنسيق تاريخ الإنشاء
$created_date = new DateTime($product['created_at']);
$formatted_date = $created_date->format('Y-m-d');
$formatted_time = $created_date->format('h:i A');

// تحديد حالة المنتج
$status_class = ($product['status'] === 'active') ? 'success' : 'danger';
$status_text = ($product['status'] === 'active') ? 'نشط' : 'غير نشط';

// تحديد نص العملة
$currency_text = '';
switch ($product['currency']) {
    case 'SAR':
        $currency_text = 'ريال سعودي';
        break;
    case 'YER':
        $currency_text = 'ريال يمني';
        break;
    case 'USD':
        $currency_text = 'دولار أمريكي';
        break;
    default:
        $currency_text = $product['currency'];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .product-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .product-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .product-info h3 {
            margin-bottom: 20px;
            color: #333;
        }
        .product-info .row {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #333;
        }
        .price-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }
        .actions-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .description-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas <?php echo $page_icon; ?>"></i> <?php echo $page_title; ?></h2>
            <div>
                <a href="edit_product.php?id=<?php echo $product_id; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i>
                    تعديل المنتج
                </a>
                <a href="products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right"></i>
                    العودة إلى قائمة المنتجات
                </a>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="text-center mb-4">
                    <?php if (!empty($product['image_url']) && file_exists('../' . $product['image_url'])): ?>
                        <img src="../<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                    <?php else: ?>
                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 300px; border-radius: 8px;">
                            <i class="fas fa-image fa-4x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="actions-card mb-4">
                    <h4 class="mb-3">إجراءات</h4>
                    
                    <div class="mb-3">
                        <form method="POST" class="d-flex align-items-center">
                            <input type="hidden" name="action" value="update_status">
                            <select name="status" class="form-select me-2">
                                <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                                <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                            </select>
                            <button type="submit" class="btn btn-primary">تحديث الحالة</button>
                        </form>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="edit_product.php?id=<?php echo $product_id; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> تعديل المنتج
                        </a>
                        
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash"></i> حذف المنتج
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="product-info mb-4">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    
                    <div class="row">
                        <div class="col-md-3 info-label">الحالة:</div>
                        <div class="col-md-9 info-value">
                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 info-label">السعر:</div>
                        <div class="col-md-9 info-value">
                            <?php if ($product['hide_price']): ?>
                                <span class="badge bg-secondary">السعر مخفي</span>
                            <?php else: ?>
                                <span class="price-value"><?php echo number_format($product['price'], 2); ?></span>
                                <span class="text-muted"><?php echo $currency_text; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 info-label">المتجر:</div>
                        <div class="col-md-9 info-value">
                            <?php if (!empty($product['store_name'])): ?>
                                <a href="view_store.php?id=<?php echo $product['store_id']; ?>">
                                    <?php echo htmlspecialchars($product['store_name']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">غير محدد</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 info-label">معلومات المتجر:</div>
                        <div class="col-md-9 info-value">
                            <?php if (!empty($product['store_email'])): ?>
                                <div><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($product['store_email']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($product['store_phone'])): ?>
                                <div><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($product['store_phone']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 info-label">التصنيف:</div>
                        <div class="col-md-9 info-value">
                            <?php if (!empty($product['category_name'])): ?>
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            <?php else: ?>
                                <span class="text-muted">غير مصنف</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 info-label">تاريخ الإضافة:</div>
                        <div class="col-md-9 info-value">
                            <i class="far fa-calendar-alt me-1"></i> <?php echo $formatted_date; ?>
                            <i class="far fa-clock ms-3 me-1"></i> <?php echo $formatted_time; ?>
                        </div>
                    </div>
                </div>
                
                <div class="description-card">
                    <h4 class="mb-3">وصف المنتج</h4>
                    <div class="p-3 bg-light rounded">
                        <?php if (!empty($product['description'])): ?>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted mb-0">لا يوجد وصف للمنتج</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal تأكيد الحذف -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">تأكيد حذف المنتج</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    هل أنت متأكد من رغبتك في حذف المنتج <strong><?php echo htmlspecialchars($product['name']); ?></strong>؟
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        هذا الإجراء لا يمكن التراجع عنه.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-danger">حذف المنتج</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
