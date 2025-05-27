<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// التحقق من وجود معرف المتجر
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$store_id = (int)$_GET['id'];

// جلب بيانات المتجر
$sql = "SELECT stores.*, categories.name as category_name 
        FROM stores 
        LEFT JOIN categories ON stores.category_id = categories.id 
        WHERE stores.id = ? AND stores.status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$result = $stmt->get_result();
$store = $result->fetch_assoc();

if (!$store) {
    header("Location: index.php");
    exit;
}

// جلب منتجات المتجر
$products_sql = "SELECT * FROM products WHERE store_id = ? AND status = 'active' ORDER BY created_at DESC";
$stmt = $conn->prepare($products_sql);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$products_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($store['name']); ?> - السوق الإلكتروني</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .store-header {
            background-color: #f8f9fa;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid #dee2e6;
        }
        .store-image {
            width: 100%;
            height: 200px; /* تقليل ارتفاع الصورة */
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .store-info {
            padding: 0.5rem;
        }
        .product-grid {
            margin-top: 1.5rem;
        }
        .product-card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
            margin-bottom: 1.5rem;
            background-color: #fff;
        }
        .product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .product-image-container {
            position: relative;
            overflow: hidden;
            padding-top: 100%; /* نسبة 1:1 للصورة */
            border-radius: 8px 8px 0 0;
        }
        .product-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        .product-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 0.5rem;
        }
        .store-contact-info {
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-top: 1rem;
        }
        .store-contact-info i {
            width: 20px;
            color: #6c757d;
            margin-left: 8px;
        }
        
        /* Swiper Styles */
        .swiper {
            width: 100%;
            height: 250px; /* تقليل ارتفاع العرض */
            margin-bottom: 1rem;
            border-radius: 8px;
            overflow: hidden;
        }
        .swiper-slide {
            background-position: center;
            background-size: cover;
        }
        .swiper-pagination-bullet {
            width: 6px;
            height: 6px;
        }
        .swiper-pagination-bullet-active {
            background: #007bff;
        }

        /* تصميم جديد لبطاقة المنتج */
        .product-card .card-body {
            padding: 0.8rem;
        }
        .product-card .card-title {
            font-size: 1rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .product-card .card-text {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 40px;
        }
        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="store-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3"> <!-- تقليل عرض العمود -->
                    <div class="swiper storeSwiper">
                        <div class="swiper-wrapper">
                            <?php if (!empty($store['image_url']) && file_exists($store['image_url'])): ?>
                                <div class="swiper-slide">
                                    <img src="<?php echo $store['image_url']; ?>" class="store-image" alt="<?php echo htmlspecialchars($store['name']); ?>">
                                </div>
                            <?php else: ?>
                                <div class="swiper-slide">
                                    <img src="assets/images/store-placeholder.jpg" class="store-image" alt="صورة افتراضية">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
                <div class="col-md-9"> <!-- زيادة عرض العمود -->
                    <div class="store-info">
                        <h1 class="h2 mb-2"><?php echo htmlspecialchars($store['name']); ?></h1>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($store['category_name']); ?></p>
                        <div class="store-contact-info">
                            <p class="mb-2"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($store['phone']); ?></p>
                            <p class="mb-2"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($store['email']); ?></p>
                            <p class="mb-0"><i class="fas fa-info-circle"></i> <?php echo nl2br(htmlspecialchars($store['description'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <h2 class="h4 mb-3">منتجات المتجر</h2>
        <div class="row product-grid">
            <?php if ($products_result->num_rows > 0): ?>
                <?php while ($product = $products_result->fetch_assoc()): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6"> <!-- تعديل أحجام الأعمدة -->
                        <div class="card product-card h-100">
                            <div class="product-image-container">
                                <?php if (!empty($product['image_url']) && file_exists($product['image_url'])): ?>
                                    <img src="<?php echo $product['image_url']; ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <img src="assets/images/product-placeholder.jpg" class="product-image" alt="صورة افتراضية">
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars(substr($product['description'], 0, 60)); ?>...</p>
                                <div class="product-meta">
                                    <span class="product-price"><?php echo formatPrice($product['price']); ?></span>
                                    <button class="btn btn-sm btn-outline-primary">عرض التفاصيل</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        لا توجد منتجات متاحة حالياً
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script>
        var swiper = new Swiper(".storeSwiper", {
            effect: "fade",
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
        });
    </script>
</body>
</html>
