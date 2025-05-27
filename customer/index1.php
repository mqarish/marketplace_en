<?php
// تفعيل عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تضمين ملف التهيئة
require_once '../includes/init.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

// تحديد حالة البحث والتصنيف
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

try {
    // استعلام المنتجات
    $sql = "SELECT p.*, s.name as store_name, s.logo as store_logo, s.id as store_id, 
            COALESCE(p.image_url, p.image) as product_image
            FROM products p
            JOIN stores s ON p.store_id = s.id
            WHERE p.status = 'active'";

    if (!empty($search)) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $search_param = "%$search%";
    }

    if ($category_id > 0) {
        $sql .= " AND p.category_id = ?";
    }

    $sql .= " ORDER BY p.created_at DESC LIMIT 12";

    $stmt = $conn->prepare($sql);
    
    if (!empty($search) && $category_id > 0) {
        $stmt->bind_param("ssi", $search_param, $search_param, $category_id);
    } elseif (!empty($search)) {
        $stmt->bind_param("ss", $search_param, $search_param);
    } elseif ($category_id > 0) {
        $stmt->bind_param("i", $category_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Error in query: " . $conn->error);
    }

    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    error_log("Database error in index.php: " . $e->getMessage());
    $error_message = "عذراً، حدث خطأ أثناء جلب المنتجات. الرجاء المحاولة مرة أخرى.";
    $products = [];
}

// تحديد المسار الأساسي للصور
$uploads_path = SITE_URL . '/uploads';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المنتجات - السوق الإلكتروني</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 1.5rem 0;
        }
        
        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .product-info {
            padding: 1rem;
        }
        
        .product-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .product-price {
            font-size: 1.2rem;
            color: #0d6efd;
            font-weight: bold;
        }
        
        .product-store {
            display: flex;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .store-logo {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-left: 0.5rem;
            object-fit: cover;
        }
        
        .store-name {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include '../includes/customer_navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <form action="" method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" placeholder="ابحث عن منتج..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">بحث</button>
                </form>
            </div>
        </div>

        <?php if (!empty($search)): ?>
            <div class="alert alert-info">
                نتائج البحث عن: <?php echo htmlspecialchars($search); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($products)): ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php
                        // تحديد مسار صورة المنتج
                        $product_image = $product['product_image'];
                        if (empty($product_image)) {
                            $product_image = 'assets/images/product-placeholder.jpg';
                        } elseif (strpos($product_image, 'http') !== 0) {
                            $product_image = 'uploads/products/' . $product_image;
                        }
                        
                        // تحديد مسار شعار المتجر
                        $store_logo = $product['store_logo'];
                        if (empty($store_logo)) {
                            $store_logo = 'assets/images/store-placeholder.jpg';
                        } elseif (strpos($store_logo, 'http') !== 0) {
                            $store_logo = 'uploads/stores/' . $store_logo;
                        }
                        ?>
                        <img src="<?php echo SITE_URL . '/' . $product_image; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image"
                             onerror="this.src='<?php echo SITE_URL; ?>/assets/images/product-placeholder.jpg'">
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="product-price"><?php echo number_format($product['price'], 2); ?> ريال</span>
                            </div>
                            <div class="product-store">
                                <img src="<?php echo SITE_URL . '/' . $store_logo; ?>" 
                                     alt="<?php echo htmlspecialchars($product['store_name']); ?>" 
                                     class="store-logo"
                                     onerror="this.src='<?php echo SITE_URL; ?>/assets/images/store-placeholder.jpg'">
                                <span class="store-name"><?php echo htmlspecialchars($product['store_name']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-search" style="font-size: 3rem; color: #dee2e6;"></i>
                <h3 class="mt-3">لا توجد منتجات</h3>
                <?php if (!empty($search)): ?>
                    <p class="text-muted">جرب البحث بكلمات مختلفة</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>