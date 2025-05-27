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
    .product-image {
        max-width: 100%;
        max-height: 400px;
        object-fit: contain;
    }
    .product-detail-wrapper {
        height: 450px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        position: relative;
        background-color: #f8f9fa;
    }
    .product-price-tag {
        position: absolute;
        bottom: 10px;
        right: 10px;
        background-color: #ff7a00;
        color: white;
        padding: 5px 10px;
        border-radius: 5px;
        font-weight: bold;
    }
    </style>
</head>
<body>
    <!-- استدعاء الهيدر الداكن الجديد -->
    <?php 
    $root_path = '../';
    include '../includes/dark_header.php'; 
    ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-6 order-md-2"> <!-- تغيير ترتيب العمود ليكون على اليسار -->
                <!-- حاوية صورة المنتج - مبسطة -->
                <div class="product-detail-wrapper rounded">
                    <!-- إضافة كاروسيل للصور -->
                    <div id="productDetailCarousel" class="carousel slide product-image-carousel" data-bs-ride="false">
                        <div class="carousel-inner">
                            <?php
                            // جلب صور المنتج من جدول product_images
                            $images_sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC";
                            $images_stmt = $conn->prepare($images_sql);
                            $has_multiple_images = false;
                            $product_images = [];
                            
                            if ($images_stmt) {
                                $images_stmt->bind_param("i", $product_id);
                                $images_stmt->execute();
                                $images_result = $images_stmt->get_result();
                                $has_multiple_images = ($images_result->num_rows > 0);
                                
                                // تخزين الصور في مصفوفة لاستخدامها لاحقًا
                                while ($image = $images_result->fetch_assoc()) {
                                    $product_images[] = $image;
                                }
                            }
                            
                            // إذا كانت هناك صور متعددة، عرضها
                            if ($has_multiple_images) {
                                foreach ($product_images as $index => $image): 
                            ?>
                                <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                                    <img 
                                        src="../<?php echo htmlspecialchars($image['image_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>"
                                        class="product-image"
                                        onerror="this.src='../assets/images/default-product.jpg';"
                                    >
                                </div>
                            <?php 
                                endforeach; 
                            } else { 
                                // إذا لم تكن هناك صور متعددة، عرض الصورة الرئيسية
                            ?>
                                <div class="carousel-item active">
                                    <?php if (!empty($product['image_url'])): ?>
                                    <img 
                                        src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>"
                                        class="product-image"
                                        onerror="this.src='../assets/images/default-product.jpg';"
                                    >
                                    <?php else: ?>
                                    <img 
                                        src="../assets/images/default-product.jpg" 
                                        alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>"
                                        class="product-image"
                                    >
                                    <?php endif; ?>
                                </div>
                            <?php } ?>
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
                    </div>
                </div>
            </div>
            <div class="col-md-6 order-md-1"> <!-- تغيير ترتيب العمود ليكون على اليمين -->
                <!-- زرار العودة إلى صفحة المتجر -->
                <div class="mb-3">
                    <a href="store-page.php?id=<?php echo $product['store_id']; ?>" class="btn" style="background-color: #ff7a00; color: white; border: none;">
                        <i class="bi bi-arrow-right-circle"></i> العودة إلى المتجر
                    </a>
                </div>
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
                
                <div class="mb-4">
                    <h5 class="fw-bold">الوصف</h5>
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?></p>
                </div>
                
                <div class="mb-4">
                    <h5 class="fw-bold">التصنيف</h5>
                    <p><?php echo htmlspecialchars($product['category_name'] ?? ''); ?></p>
                </div>
                
                <div class="mb-4">
                    <h5 class="fw-bold">المتجر</h5>
                    <p>
                        <i class="bi bi-shop"></i> <?php echo htmlspecialchars($product['store_name']); ?>
                        <br>
                        <i class="bi bi-geo-alt"></i> 
                        <?php 
                        $address = '';
                        if (!empty($product['store_address'])) {
                            $address .= $product['store_address'];
                        }
                        if (!empty($product['store_city'])) {
                            if (!empty($address)) {
                                $address .= '، ';
                            }
                            $address .= $product['store_city'];
                        }
                        echo htmlspecialchars($address);
                        ?>
                        <?php if (!empty($address)): ?>
                            <button onclick="openMap('<?php echo addslashes($address); ?>')" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="bi bi-map"></i> عرض على الخريطة
                            </button>
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
    </script>
</body>
</html>
