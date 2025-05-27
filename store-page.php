<?php
require_once 'includes/init.php';
require_once 'includes/functions.php';
require_once 'includes/track_visit.php';

// الحصول على معرف المتجر من الرابط
$store_slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

if (empty($store_slug)) {
    header("Location: index.php");
    exit();
}

// جلب بيانات المتجر
$store = null;
$products = [];
try {
    $store_sql = "SELECT s.*, u.email 
                  FROM stores s 
                  JOIN users u ON s.user_id = u.id 
                  WHERE s.slug = ? LIMIT 1";
    $stmt = $conn->prepare($store_sql);
    $stmt->bind_param("s", $store_slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $store = $result->fetch_assoc();

    if (!$store) {
        header("Location: index.php");
        exit();
    }

    // Track this visit
    track_store_visit($store['id']);

    // جلب منتجات المتجر مع العروض
    $products_sql = "SELECT p.*, 
                    CASE 
                        WHEN p.image_url IS NOT NULL AND p.image_url != '' THEN p.image_url
                        WHEN p.image IS NOT NULL AND p.image != '' THEN CONCAT('uploads/products/', p.image)
                        ELSE NULL
                    END as product_image,
                    o.discount_percentage,
                    o.title as offer_title,
                    o.image_path as offer_image,
                    o.end_date
                    FROM products p
                    LEFT JOIN offers o ON o.store_id = p.store_id 
                        AND o.status = 'active'
                        AND CURRENT_DATE BETWEEN o.start_date AND o.end_date
                    WHERE p.store_id = ? AND p.status = 'active' 
                    ORDER BY o.discount_percentage DESC, p.created_at DESC";
    $stmt = $conn->prepare($products_sql);
    $stmt->bind_param("i", $store['id']);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = $e->getMessage();
}

// تحديد مسار الصور
$uploads_path = SITE_URL . '/uploads';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($store['name']); ?> - السوق الإلكتروني</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .product-card {
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .offer-badge {
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            background: linear-gradient(45deg, #ff6b6b, #ff4757);
            color: white;
            padding: 5px 10px;
            text-align: center;
            font-weight: bold;
            z-index: 2;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .offer-timer {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100%;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 5px;
            text-align: center;
            font-size: 0.8rem;
            z-index: 2;
        }
        .product-details {
            padding: 15px;
        }
        .price-section {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .original-price {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 0.9rem;
        }
        .discounted-price {
            color: #ff4757;
            font-weight: bold;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/customer_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- معلومات المتجر -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <?php if (!empty($store['logo'])): ?>
                                    <img src="<?php echo $uploads_path . '/stores/' . htmlspecialchars($store['logo']); ?>" 
                                         class="img-fluid rounded" alt="<?php echo htmlspecialchars($store['name']); ?>">
                                <?php else: ?>
                                    <div class="text-center">
                                        <i class="bi bi-shop display-4"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-10">
                                <h2 class="mb-3"><?php echo htmlspecialchars($store['name']); ?></h2>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($store['description']); ?></p>
                                <div class="d-flex gap-3">
                                    <?php if (!empty($store['phone'])): ?>
                                        <span><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($store['phone']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($store['email'])): ?>
                                        <span><i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($store['email']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($store['address'])): ?>
                                        <span><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($store['address']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المنتجات -->
            <div class="col-md-12">
                <h3 class="mb-4">منتجات المتجر</h3>
                <?php if (empty($products)): ?>
                    <div class="alert alert-info">
                        لا توجد منتجات متاحة حالياً
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col">
                                <div class="card product-card h-100">
                                    <?php if (!empty($product['discount_percentage'])): ?>
                                        <div class="offer-badge">
                                            خصم <?php echo number_format($product['discount_percentage'], 0); ?>%
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($product['product_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($product['product_image']); ?>" 
                                             class="product-image" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <div class="product-image d-flex align-items-center justify-content-center bg-light">
                                            <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($product['end_date'])): ?>
                                        <div class="offer-timer">
                                            ينتهي العرض: <?php echo date('Y/m/d', strtotime($product['end_date'])); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="product-details">
                                        <h5 class="card-title mb-2"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text text-muted small mb-2">
                                            <?php echo mb_substr(htmlspecialchars($product['description']), 0, 100); ?>...
                                        </p>
                                        <div class="price-section">
                                            <?php if (!empty($product['discount_percentage'])): ?>
                                                <span class="original-price"><?php echo number_format($product['price'], 2); ?> ريال</span>
                                                <span class="discounted-price">
                                                    <?php 
                                                    $discounted_price = $product['price'] * (1 - $product['discount_percentage'] / 100);
                                                    echo number_format($discounted_price, 2); 
                                                    ?> ريال
                                                </span>
                                            <?php else: ?>
                                                <span class="discounted-price"><?php echo number_format($product['price'], 2); ?> ريال</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
