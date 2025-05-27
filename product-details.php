<?php
session_start();
require_once '../includes/init.php';

// التحقق من وجود معرف المنتج
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$product_id = (int)$_GET['id'];

// جلب تفاصيل المنتج مع معلومات المتجر
$product_sql = "SELECT p.*, s.name as store_name, s.address as store_address, s.city as store_city,
                c.name as category_name,
                o.id as offer_id, o.discount_percentage, o.end_date,
                CASE 
                    WHEN o.id IS NOT NULL 
                    AND o.start_date <= NOW() 
                    AND o.end_date >= NOW() 
                    AND o.status = 'active'
                    THEN ROUND(p.price - (p.price * o.discount_percentage / 100), 2)
                    ELSE p.price 
                END as final_price
                FROM products p
                LEFT JOIN stores s ON p.store_id = s.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN (
                    SELECT DISTINCT store_id, offer_id 
                    FROM offer_store_products
                ) osp ON p.store_id = osp.store_id
                LEFT JOIN offers o ON osp.offer_id = o.id 
                    AND o.start_date <= NOW() 
                    AND o.end_date >= NOW()
                    AND o.status = 'active'
                WHERE p.id = ? AND p.status = 'active'";

$stmt = $conn->prepare($product_sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - السوق</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
    .offer-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 2;
    }
    .offer-badge .badge {
        font-size: 0.9rem;
        padding: 8px 12px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .original-price {
        text-decoration: line-through;
        color: #6c757d;
        font-size: 0.9em;
    }
    .product-detail-image:hover {
        transform: scale(1.03);
    }
    .product-detail-wrapper:hover {
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    /* أنماط الكاروسيل */
    .product-image-carousel {
        height: 100%;
        width: 100%;
    }
    .carousel-item {
        height: 400px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f8f9fa;
    }
    .carousel-item img {
        max-height: 380px;
        max-width: 90%;
        object-fit: contain;
        transition: transform 0.3s ease;
    }
    /* أزرار التنقل */
    .carousel-control-prev,
    .carousel-control-next {
        width: 40px;
        height: 40px;
        background-color: rgba(255, 255, 255, 0.7);
        border-radius: 50%;
        top: 50%;
        transform: translateY(-50%);
        position: absolute;
        opacity: 0;
        transition: opacity 0.3s;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    /* موضع الزر السابق - على اليمين */
    .carousel-control-prev {
        right: 10px !important;
        left: auto !important;
    }
    /* موضع الزر التالي - على اليسار */
    .carousel-control-next {
        left: 10px !important;
        right: auto !important;
    }
    /* إظهار الأزرار عند المرور بالمؤشر */
    .product-detail-wrapper:hover .carousel-control-prev,
    .product-detail-wrapper:hover .carousel-control-next {
        opacity: 1;
    }
    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        width: 20px;
        height: 20px;
    }
    </style>
</head>
<body>
    <?php include '../includes/customer_navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-6">
                <div class="product-detail-wrapper rounded" style="height: 450px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: all 0.3s ease; position: relative;">
                    <?php 
                    // تحديد الصورة المناسبة
                    $image_path = '';
                    if (!empty($product['image_url'])) {
                        $image_path = $product['image_url'];
                    } elseif (!empty($product['image'])) {
                        $image_path = 'uploads/products/' . $product['image'];
                    }
                    ?>
                    
                    <?php if (!empty($image_path)): ?>
                        <!-- إضافة كاروسيل للصور -->
                        <div id="productDetailCarousel" class="carousel slide product-image-carousel" data-bs-ride="false">
                            <div class="carousel-inner">
                                <!-- الصورة الرئيسية -->
                                <div class="carousel-item active">
                                    <img src="/marketplace/<?php echo htmlspecialchars($image_path); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="product-detail-image">
                                </div>
                                <!-- صورة إضافية للعرض - استخدم نفس الصورة كمثال -->
                                <div class="carousel-item">
                                    <img src="/marketplace/<?php echo htmlspecialchars($image_path); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="product-detail-image">
                                </div>
                                <!-- صورة إضافية ثالثة - استخدم نفس الصورة كمثال -->
                                <div class="carousel-item">
                                    <img src="/marketplace/<?php echo htmlspecialchars($image_path); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="product-detail-image">
                                </div>
                            </div>
                            
                            <!-- أزرار التنقل -->
                            <button class="carousel-control-prev" type="button" data-bs-target="#productDetailCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">السابق</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#productDetailCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">التالي</span>
                            </button>
                            
                            <!-- مؤشرات الشرائح في الأسفل -->
                            <div class="carousel-indicators" style="position: relative; margin-top: 10px; margin-bottom: 0;">
                                <button type="button" data-bs-target="#productDetailCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                                <button type="button" data-bs-target="#productDetailCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                                <button type="button" data-bs-target="#productDetailCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <i class="bi bi-image text-secondary" style="font-size: 5rem;"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <h1 class="mb-3 fw-bold" style="font-size: 2rem;"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <?php if (!empty($product['offer_id'])): ?>
                    <div class="mb-3">
                        <span class="badge bg-danger">
                            خصم <?php echo $product['discount_percentage']; ?>%
                        </span>
                        <div class="mt-2">
                            <span class="h3 text-danger">
                                <?php echo number_format($product['final_price'], 2); ?> ريال
                            </span>
                            <br>
                            <span class="original-price">
                                <?php echo number_format($product['price'], 2); ?> ريال
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <span class="h3">
                            <?php echo number_format($product['price'], 2); ?> ريال
                        </span>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <h5>التصنيف</h5>
                    <p><?php echo htmlspecialchars($product['category_name']); ?></p>
                </div>

                <div class="mb-3">
                    <h5>الوصف</h5>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <div class="mb-3">
                    <h5>المتجر</h5>
                    <p>
                        <a href="store-page.php?id=<?php echo $product['store_id']; ?>" 
                           class="text-decoration-none">
                            <?php echo htmlspecialchars($product['store_name']); ?>
                        </a>
                        <?php if (!empty($product['store_address'])): ?>
                            <br>
                            <small class="text-muted">
                                <i class="bi bi-geo-alt"></i>
                                <a href="#" onclick="openMap('<?php echo htmlspecialchars($product['store_address'] . ', ' . $product['store_city']); ?>')" 
                                   class="text-decoration-none text-muted">
                                    <?php echo htmlspecialchars($product['store_address']); ?>
                                    <?php if (!empty($product['store_city'])): ?>
                                        ، <?php echo htmlspecialchars($product['store_city']); ?>
                                    <?php endif; ?>
                                </a>
                            </small>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function openMap(address) {
        if (address) {
            const mapUrl = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(address);
            window.open(mapUrl, '_blank');
        }
    }
    // تفعيل الكاروسيل عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        const productCarousel = document.getElementById('productDetailCarousel');
        if (productCarousel) {
            const carousel = new bootstrap.Carousel(productCarousel, {
                interval: false, // لا يتم التبديل تلقائيًا
                wrap: true // الانتقال من الشريحة الأخيرة إلى الأولى والعكس
            });
        }
    });
    </script>
</body>
</html>
