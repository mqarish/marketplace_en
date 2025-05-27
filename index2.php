<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// التحقق من وجود تصنيف محدد
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// جلب التصنيفات للفلتر
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories = $conn->query($categories_sql);

// بناء استعلام المتاجر
$stores_sql = "SELECT stores.*, categories.name as category_name, 
               (SELECT COUNT(*) FROM products WHERE store_id = stores.id) as products_count
               FROM stores 
               LEFT JOIN categories ON stores.category_id = categories.id 
               WHERE stores.status = 'active'";

if ($category_id > 0) {
    $stores_sql .= " AND stores.category_id = " . $category_id;
}

$stores = $conn->query($stores_sql);

// جلب معلومات التصنيف الحالي إذا كان محدداً
$current_category = null;
if ($category_id > 0) {
    $category_sql = "SELECT * FROM categories WHERE id = " . $category_id;
    $category_result = $conn->query($category_sql);
    $current_category = $category_result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_category ? htmlspecialchars($current_category['name']) . ' - ' : ''; ?>السوق الإلكتروني</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #4a90e2, #7646ff);
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
        }
        .category-card {
            transition: transform 0.3s;
            cursor: pointer;
        }
        .category-card:hover {
            transform: translateY(-5px);
        }
        .store-card {
            transition: all 0.3s;
            height: 100%;
        }
        .store-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        .store-logo {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
        }
        .stats-badge {
            background: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            margin: 0 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 mb-4">مرحباً بك في السوق الإلكتروني</h1>
            <p class="lead mb-4">اكتشف أفضل المتاجر والمنتجات في مكان واحد</p>
            <div class="d-flex justify-content-center align-items-center">
                <span class="stats-badge">
                    <i class="fas fa-store"></i>
                    <?php 
                    $total_stores = $conn->query("SELECT COUNT(*) as count FROM stores WHERE status = 'active'")->fetch_assoc();
                    echo number_format($total_stores['count']);
                    ?> متجر
                </span>
                <span class="stats-badge">
                    <i class="fas fa-box"></i>
                    <?php 
                    $total_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'")->fetch_assoc();
                    echo number_format($total_products['count']);
                    ?> منتج
                </span>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Category Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="mb-0">
                        <?php if ($current_category): ?>
                            <i class="fas fa-store me-2"></i>متاجر <?php echo htmlspecialchars($current_category['name']); ?>
                        <?php else: ?>
                            <i class="fas fa-store me-2"></i>جميع المتاجر
                        <?php endif; ?>
                    </h2>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-filter me-1"></i>تصفية حسب التصنيف
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item <?php echo $category_id == 0 ? 'active' : ''; ?>" href="index.php">جميع التصنيفات</a></li>
                            <?php while($category = $categories->fetch_assoc()): ?>
                                <li>
                                    <a class="dropdown-item <?php echo $category_id == $category['id'] ? 'active' : ''; ?>" 
                                       href="index.php?category=<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stores Grid -->
        <div class="row g-4">
            <?php while($store = $stores->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card store-card h-100">
                        <div class="card-body text-center">
                            <img src="<?php echo getImageUrl($store['logo'] ?? '', 'store'); ?>" 
                                alt="<?php echo htmlspecialchars($store['name']); ?>" 
                                class="store-logo">
                            <h3 class="h5 card-title"><?php echo htmlspecialchars($store['name']); ?></h3>
                            <?php if($store['category_name']): ?>
                                <p class="text-muted mb-3">
                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($store['category_name']); ?>
                                </p>
                            <?php endif; ?>
                            <p class="card-text text-muted mb-3">
                                <?php 
                                    $description = isset($store['description']) ? $store['description'] : '';
                                    echo mb_substr(htmlspecialchars($description), 0, 100) . (strlen($description) > 100 ? '...' : '');
                                ?>
                            </p>
                            <div class="d-flex justify-content-around mb-3">
                                <span class="text-muted">
                                    <i class="fas fa-box me-1"></i><?php echo $store['products_count']; ?> منتج
                                </span>
                            </div>
                            <a href="store.php?id=<?php echo $store['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-shopping-bag me-1"></i>زيارة المتجر
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
