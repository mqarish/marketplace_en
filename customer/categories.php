<?php
require_once '../includes/init.php';
require_once '../includes/customer_auth.php';

// التحقق من تسجيل دخول العميل
check_customer_auth();
check_customer_status($conn, $_SESSION['customer_id']);

// تعيين ترميز قاعدة البيانات
mysqli_set_charset($conn, "utf8mb4");

// جلب جميع التصنيفات مع عدد المتاجر النشطة في كل تصنيف
$categories_query = "
    SELECT 
        c.id,
        c.name,
        c.description,
        c.icon,
        COUNT(DISTINCT CASE WHEN s.status = 'active' THEN s.id END) as store_count
    FROM categories c
    LEFT JOIN store_categories sc ON c.id = sc.category_id
    LEFT JOIN stores s ON sc.store_id = s.id
    GROUP BY c.id, c.name, c.description, c.icon
    ORDER BY c.name";

$result = $conn->query($categories_query);
if (!$result) {
    die("خطأ في الاستعلام: " . $conn->error);
}
$categories = $result->fetch_all(MYSQLI_ASSOC);

// جلب المتاجر لتصنيف معين إذا تم تحديده
$selected_category = isset($_GET['category']) ? (int)$_GET['category'] : null;
$stores = [];
$selected_category_name = '';

if ($selected_category) {
    // جلب المتاجر النشطة في التصنيف المحدد
    $stores_query = "
        SELECT DISTINCT 
            s.id,
            s.name,
            s.description,
            s.logo,
            c.name as category_name
        FROM stores s
        INNER JOIN store_categories sc ON s.id = sc.store_id
        INNER JOIN categories c ON sc.category_id = c.id
        WHERE sc.category_id = ? 
        AND s.status = 'active'
        ORDER BY s.name
    ";
    
    $stmt = $conn->prepare($stores_query);
    
    if ($stmt === false) {
        die('خطأ في إعداد الاستعلام: ' . $conn->error);
    }
    
    if (!$stmt->bind_param('i', $selected_category)) {
        die('خطأ في ربط المعامل: ' . $stmt->error);
    }
    
    if (!$stmt->execute()) {
        die('خطأ في تنفيذ الاستعلام: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $stores = $result->fetch_all(MYSQLI_ASSOC);
    
    if (!empty($stores)) {
        $selected_category_name = $stores[0]['category_name'];
    }
    
    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $selected_category ? htmlspecialchars($selected_category_name) : 'تصنيفات المتاجر'; ?> - السوق الإلكتروني</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .page-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        .breadcrumb-item.active {
            color: white;
        }
        .category-card {
            transition: transform 0.2s;
            height: 100%;
        }
        .category-card:hover {
            transform: translateY(-5px);
        }
        .category-icon {
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        .store-card {
            transition: transform 0.2s;
        }
        .store-card:hover {
            transform: translateY(-5px);
        }
        .store-logo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state i {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/customer_navbar.php'; ?>

    <div class="page-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="../index.php">الرئيسية</a></li>
                    <?php if ($selected_category): ?>
                        <li class="breadcrumb-item"><a href="categories.php">التصنيفات</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($selected_category_name); ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active">التصنيفات</li>
                    <?php endif; ?>
                </ol>
            </nav>
            <h1 class="mb-0">
                <?php if ($selected_category): ?>
                    متاجر <?php echo htmlspecialchars($selected_category_name); ?>
                <?php else: ?>
                    تصنيفات المتاجر
                <?php endif; ?>
            </h1>
        </div>
    </div>

    <div class="container mb-5">
        <?php if ($selected_category): ?>
            <?php if (!empty($stores)): ?>
                <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                    <?php foreach ($stores as $store): ?>
                        <div class="col">
                            <div class="card store-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?php echo !empty($store['logo']) ? '../uploads/stores/' . $store['logo'] : '../assets/images/store-placeholder.jpg'; ?>" 
                                             class="store-logo me-3" alt="<?php echo htmlspecialchars($store['name']); ?>">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($store['name']); ?></h5>
                                    </div>
                                    <?php if (!empty($store['description'])): ?>
                                        <p class="card-text text-muted"><?php echo htmlspecialchars($store['description']); ?></p>
                                    <?php endif; ?>
                                    <a href="store-page.php?id=<?php echo $store['id']; ?>" class="btn btn-primary w-100">
                                        <i class="bi bi-shop me-2"></i> زيارة المتجر
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-shop"></i>
                    <h3>لا توجد متاجر في هذا التصنيف</h3>
                    <p>جرب تصنيفاً آخر أو عد إلى قائمة التصنيفات</p>
                    <a href="categories.php" class="btn btn-primary mt-3">
                        <i class="bi bi-grid me-2"></i> عرض جميع التصنيفات
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($categories as $category): ?>
                    <div class="col">
                        <a href="?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                            <div class="card category-card">
                                <div class="card-body text-center p-4">
                                    <i class="bi bi-<?php echo htmlspecialchars($category['icon'] ?? 'grid'); ?> category-icon"></i>
                                    <h5 class="card-title mb-2"><?php echo htmlspecialchars($category['name']); ?></h5>
                                    <?php if (isset($category['description'])): ?>
                                        <p class="card-text text-muted mb-3"><?php echo htmlspecialchars($category['description']); ?></p>
                                    <?php endif; ?>
                                    <p class="store-count mb-0">
                                        <i class="bi bi-shop me-1"></i>
                                        <?php 
                                            $store_count = isset($category['store_count']) ? intval($category['store_count']) : 0;
                                            echo $store_count . ' ' . ($store_count == 1 ? 'متجر' : 'متاجر');
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
