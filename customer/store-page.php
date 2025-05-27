<?php
session_start();
require_once '../includes/init.php';
require_once '../includes/functions.php';

// التحقق من وجود معرف المتجر
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$store_id = (int)$_GET['id'];
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// جلب تفاصيل المتجر
$store_sql = "SELECT s.*, 
              COUNT(DISTINCT p.id) as products_count,
              COUNT(DISTINCT CASE 
                  WHEN o.id IS NOT NULL 
                  AND o.start_date <= CURDATE()
                  AND o.end_date >= CURDATE()
                  AND o.status = 'active'
                  THEN o.id
              END) as active_offers_count,
              s.phone,
              s.address,
              s.city
              FROM stores s
              LEFT JOIN products p ON s.id = p.store_id
              LEFT JOIN offers o ON s.id = o.store_id
                  AND o.start_date <= CURDATE()
                  AND o.end_date >= CURDATE()
                  AND o.status = 'active'
              WHERE s.id = ? AND s.status = 'active'
              GROUP BY s.id";

$store_stmt = $conn->prepare($store_sql);
$store_stmt->bind_param("i", $store_id);
$store_stmt->execute();
$store = $store_stmt->get_result()->fetch_assoc();

if (!$store) {
    header("Location: index.php");
    exit();
}

// جلب المنتجات مع البحث
$products_sql = "SELECT p.*, c.name as category_name,
                IFNULL(AVG(r.rating), 0) as avg_rating,
                COUNT(DISTINCT r.id) as rating_count,
                COUNT(DISTINCT l.id) as likes_count
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN reviews r ON p.id = r.product_id
                LEFT JOIN product_likes l ON p.id = l.product_id
                WHERE p.store_id = ? AND p.status = 'active'";
$params = array($store_id);
$types = "i";

if (!empty($search_query)) {
    // البحث في اسم المنتج والوصف للحصول على نتائج أكثر
    $search_param = "%{$search_query}%";
    $products_sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$products_sql .= " GROUP BY p.id";
$products_sql .= " ORDER BY p.created_at DESC";
$products_stmt = $conn->prepare($products_sql);
$products_stmt->bind_param($types, ...$params);
$products_stmt->execute();
$products_result = $products_stmt->get_result();

// جلب العروض النشطة للمتجر
$active_offers_sql = "SELECT o.*, 
                     COUNT(DISTINCT p.id) as products_count
                     FROM offers o
                     LEFT JOIN products p ON o.store_id = p.store_id
                     WHERE o.store_id = ? 
                     AND o.status = 'active'
                     AND o.start_date <= CURDATE()
                     AND o.end_date >= CURDATE()
                     GROUP BY o.id
                     ORDER BY o.created_at DESC";

$active_offers_stmt = $conn->prepare($active_offers_sql);
$active_offers_stmt->bind_param("i", $store_id);
$active_offers_stmt->execute();
$active_offers_result = $active_offers_stmt->get_result();

?>
<?php
// تعيين عنوان الصفحة
$page_title = htmlspecialchars($store['name']) . ' - السوق';

// تحديد مسار الجذر
$root_path = '../';

// تعريف ملفات CSS الإضافية قبل تضمين الهيدر
$additional_css = '<link rel="stylesheet" href="styles/store-page-custom.css">';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- روابط الخطوط -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <!-- ملفات CSS الأساسية -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $root_path; ?>customer/styles/dark-header.css">
    <link rel="stylesheet" href="<?php echo $root_path; ?>customer/styles/mobile-fixes.css">
    <?php if (isset($additional_css)) echo $additional_css; ?>
    
    <!-- ملفات الجافاسكريبت الأساسية -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body>

    <!-- استدعاء الهيدر الداكن الجديد -->
    <?php 
    include '../includes/dark_header.php'; 
    ?>

    <!-- رأس صفحة المتجر بتصميم احترافي -->
    <section class="store-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center mb-4">
                    <?php if (!empty($store['logo'])): ?>
                        <img src="/uploads/stores/<?php echo htmlspecialchars($store['logo']); ?>" 
                             alt="<?php echo htmlspecialchars($store['name']); ?>" 
                             class="store-logo">
                    <?php else: ?>
                        <div class="store-logo d-flex align-items-center justify-content-center">
                            <i class="bi bi-shop" style="font-size: 3rem; color: #333;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <div class="store-info">
                        <h1 class="store-name"><?php echo htmlspecialchars($store['name']); ?></h1>
                        <?php if (!empty($store['description'])): ?>
                            <p class="store-description"><?php echo htmlspecialchars($store['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="row mt-3">
                            <div class="col-md-7">
                                <!-- شريط البحث المخصص للمتجر -->
                                <div class="store-search-container">
                                    <form action="store-page.php" method="GET" class="store-search-form">
                                        <input type="hidden" name="id" value="<?php echo $store_id; ?>">
                                        <div class="search-input-wrap">
                                            <input type="text" name="search" class="store-search-input" 
                                                placeholder="ابحث في منتجات المتجر..." 
                                                value="<?php echo htmlspecialchars($search_query); ?>">
                                        </div>
                                        <button type="submit" class="store-search-btn">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="store-contact">
                                    <?php if (!empty($store['phone'])): ?>
                                        <div class="mb-2">
                                            <i class="bi bi-telephone-fill me-2"></i>
                                            <span><?php echo htmlspecialchars($store['phone']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($store['address'])): ?>
                                        <div class="mb-2">
                                            <i class="bi bi-geo-alt-fill me-2"></i>
                                            <span><?php echo htmlspecialchars($store['address']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($store['city'])): ?>
                                        <div class="mb-2">
                                            <i class="bi bi-building me-2"></i>
                                            <span><?php echo htmlspecialchars($store['city']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- روابط التواصل الاجتماعي -->
                                    <div class="social-links mt-3">
                                        <?php if (!empty($store['facebook_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($store['facebook_url']); ?>" target="_blank" class="social-icon facebook me-2">
                                                <i class="bi bi-facebook"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($store['twitter_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($store['twitter_url']); ?>" target="_blank" class="social-icon twitter me-2">
                                                <i class="bi bi-twitter"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($store['instagram_url'])): ?>
                                            <a href="<?php echo htmlspecialchars($store['instagram_url']); ?>" target="_blank" class="social-icon instagram me-2">
                                                <i class="bi bi-instagram"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if (!empty($store['whatsapp'])): ?>
                                            <a href="https://wa.me/<?php echo htmlspecialchars($store['whatsapp']); ?>" target="_blank" class="social-icon whatsapp">
                                                <i class="bi bi-whatsapp"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- إحصائيات المتجر -->
                        <div class="store-stats">
                            <div class="stat-item">
                                <i class="bi bi-box-seam"></i>
                                <span><?php echo $store['products_count']; ?> منتج</span>
                            </div>
                            <?php if ($store['active_offers_count'] > 0): ?>
                            <div class="stat-item">
                                <i class="bi bi-tags"></i>
                                <span><?php echo $store['active_offers_count']; ?> عرض نشط</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container mb-5">
        <!-- العروض النشطة -->
        <?php if ($active_offers_result->num_rows > 0): ?>
            <section class="mb-5">
                <h2 class="mb-4">العروض النشطة</h2>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php while ($offer = $active_offers_result->fetch_assoc()): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="offer-image-container">
                                    <?php if (!empty($offer['image_path'])): ?>
                                        <img src="../<?php echo htmlspecialchars($offer['image_path']); ?>" 
                                             class="offer-image" alt="<?php echo htmlspecialchars($offer['title']); ?>">
                                    <?php else: ?>
                                        <div class="offer-image d-flex align-items-center justify-content-center">
                                            <i class="bi bi-tag text-secondary" style="font-size: 4rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($offer['title']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($offer['description']); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-danger">خصم <?php echo $offer['discount_percentage']; ?>%</span>
                                            <?php if (isset($offer['offer_price'])): ?>
                                                <span class="badge bg-success ms-2"><?php echo number_format($offer['offer_price'], 2); ?> ريال</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            ينتهي في <?php echo date('d/m/Y', strtotime($offer['end_date'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($products_result->num_rows > 0): ?>
            <section>
                <h2 class="mb-4">المنتجات المتوفرة</h2>

                <div class="row row-cols-1 row-cols-md-4 g-4">
                    <?php while ($product = $products_result->fetch_assoc()): ?>
                        <div class="col">
                            <div class="card h-100">
                                <?php if (!empty($product['image_url'])): ?>
                                    <!-- حاوية الصورة مع الكاروسيل -->
                                    <div class="product-card-image">
                                        <!-- معرف للصورة -->
                                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="d-block">
                                            <div id="productCarousel-<?php echo $product['id']; ?>" class="carousel slide" data-bs-ride="false" data-bs-interval="false">
                                                <div class="carousel-inner">
                                                    <?php
                                                    // جلب صور المنتج من جدول product_images
                                                    $images_sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC";
                                                    $images_stmt = $conn->prepare($images_sql);
                                                    $has_multiple_images = false;
                                                    $product_images = [];
                                                    
                                                    if ($images_stmt) {
                                                        $images_stmt->bind_param("i", $product['id']);
                                                        $images_stmt->execute();
                                                        $images_result = $images_stmt->get_result();
                                                        $has_multiple_images = ($images_result->num_rows > 1);
                                                        
                                                        // تخزين الصور في مصفوفة لاستخدامها لاحقًا
                                                        while ($image = $images_result->fetch_assoc()) {
                                                            $product_images[] = $image;
                                                        }
                                                    }
                                                    
                                                    // إضافة مؤشرات الكاروسيل (النقاط) إذا كان هناك أكثر من صورة
                                                    if ($has_multiple_images) {
                                                        // إضافة مؤشرات الكاروسيل
                                                        ?>
                                                        <div class="carousel-indicators">
                                                            <?php foreach ($product_images as $idx => $img): ?>
                                                                <button type="button" data-bs-target="#productCarousel-<?php echo $product['id']; ?>" data-bs-slide-to="<?php echo $idx; ?>" class="<?php echo ($idx === 0) ? 'active' : ''; ?>" aria-current="<?php echo ($idx === 0) ? 'true' : 'false'; ?>" aria-label="شريحة <?php echo $idx + 1; ?>"></button>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <?php
                                                        
                                                        // عرض الصور المتعددة
                                                        foreach ($product_images as $index => $image): 
                                                    ?>
                                                        <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                                                            <img src="../<?php echo htmlspecialchars($image['image_url']); ?>" 
                                                                 class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                        </div>
                                                    <?php 
                                                        endforeach; 
                                                    } else { 
                                                        // إذا لم تكن هناك صور متعددة، عرض الصورة الرئيسية
                                                    ?>
                                                        <div class="carousel-item active">
                                                            <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                                 class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                                        </div>
                                                    <?php } ?>
                                                </div>
                                
                                                <!-- أزرار التنقل -->
                                                <?php if ($has_multiple_images): ?>
                                                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel-<?php echo $product['id']; ?>" data-bs-slide="prev" 
                                                        style="position: absolute; right: 10px; left: auto; width: 35px; height: 35px; background-color: rgba(255, 153, 51, 0.9); border-radius: 50%; top: 50%; transform: translateY(-50%); opacity: 1; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 5;" 
                                                        onclick="event.preventDefault(); event.stopPropagation(); var carousel = bootstrap.Carousel.getInstance(document.getElementById('productCarousel-<?php echo $product['id']; ?>')); carousel.prev();">
                                                    <span class="carousel-control-prev-icon" aria-hidden="true" style="width: 20px; height: 20px;"></span>
                                                    <span class="visually-hidden">السابق</span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel-<?php echo $product['id']; ?>" data-bs-slide="next"
                                                        style="position: absolute; left: 10px; right: auto; width: 35px; height: 35px; background-color: rgba(255, 153, 51, 0.9); border-radius: 50%; top: 50%; transform: translateY(-50%); opacity: 1; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 5;" 
                                                        onclick="event.preventDefault(); event.stopPropagation(); var carousel = bootstrap.Carousel.getInstance(document.getElementById('productCarousel-<?php echo $product['id']; ?>')); carousel.next();">
                                                    <span class="carousel-control-next-icon" aria-hidden="true" style="width: 20px; height: 20px;"></span>
                                                    <span class="visually-hidden">التالي</span>
                                                </button>
                                                <?php endif; ?>
                                                
                                                <!-- تم إزالة أزرار التنقل المخفية غير الضرورية -->
                                            </div>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                         style="height: 200px;">
                                        <i class="bi bi-image text-secondary" style="font-size: 4rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <?php if (!empty($product['description'])): ?>
                                        <p class="card-text small text-muted"><?php echo htmlspecialchars(substr($product['description'], 0, 50)) . (strlen($product['description']) > 50 ? '...' : ''); ?></p>
                                    <?php endif; ?>
                                    <p class="card-price" style="direction: rtl;">
                                        <?php if (isset($product['hide_price']) && $product['hide_price'] == 1): ?>
                                            <span class="text-primary">اتصل للسعر</span>
                                        <?php else: ?>
                                            <?php echo number_format($product['price'], 2); ?>
                                            <?php 
                                            // عرض العملة المناسبة
                                            if (isset($product['currency'])) {
                                                switch ($product['currency']) {
                                                    case 'SAR':
                                                        echo ' ر.س';
                                                        break;
                                                    case 'YER':
                                                        echo ' ر.ي';
                                                        break;
                                                    case 'USD':
                                                        echo ' $';
                                                        break;
                                                    default:
                                                        echo ' ر.س';
                                                }
                                            } else {
                                                echo ' ر.س';
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <!-- عرض التقييم والإعجابات بشكل احترافي جديد -->
                                    <div class="product-meta">
                                        <?php 
                                        $rating = round($product['avg_rating'] * 2) / 2; // تدوير لأقرب 0.5
                                        ?>
                                        <div class="rating-pill" id="rating-<?php echo $product['id']; ?>">
                                            <span class="value"><?php echo number_format($rating, 1); ?></span>
                                            <div class="stars-wrap">
                                                <?php 
                                                // عرض النجوم مع إمكانية التقييم
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo '<i class="bi bi-star' . ($i <= $rating ? '-fill' : '') . ' rate-star" data-product="' . $product['id'] . '" data-rating="' . $i . '"></i>';
                                                }
                                                ?>
                                            </div>
                                            <span class="meta-count">(<?php echo $product['rating_count']; ?>)</span>
                                        </div>
                                        
                                        <div class="likes-pill">
                                            <?php 
                                            // التحقق مما إذا كان المستخدم قد أعجب بالمنتج مسبقاً
                                            $isLiked = false;
                                            if (isset($_SESSION['customer_id'])) {
                                                $check_like = $conn->prepare("SELECT id FROM product_likes WHERE product_id = ? AND customer_id = ?");
                                                $check_like->bind_param("ii", $product['id'], $_SESSION['customer_id']);
                                                $check_like->execute();
                                                $like_result = $check_like->get_result();
                                                $isLiked = $like_result->num_rows > 0;
                                            }
                                            ?>
                                            <i class="bi <?php echo $isLiked ? 'bi-heart-fill liked' : 'bi-heart'; ?> like-btn" data-product="<?php echo $product['id']; ?>"></i>
                                            <span class="like-count" id="like-count-<?php echo $product['id']; ?>"><?php echo $product['likes_count']; ?></span>
                                            <span class="meta-count">إعجاب</span>
                                        </div>
                                    </div>

                                    <div class="text-center mb-2">
                                        <span class="category-badge"><?php echo htmlspecialchars($product['category_name'] ?? 'غير مصنف'); ?></span>
                                    </div>
                                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn-details">عرض التفاصيل</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
        <?php else: ?>
            <div class="alert alert-info">
                لا توجد منتجات متوفرة حالياً
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // تم إزالة وظيفة المفضلة
    
    // تفعيل كاروسيل بوتستراب لجميع المنتجات
    document.addEventListener('DOMContentLoaded', function() {
        // تمكين مؤشرات bootstrap
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
        
        // تهيئة جميع كاروسيلات المنتجات
        document.querySelectorAll('.carousel').forEach(function(carousel) {
            new bootstrap.Carousel(carousel, {
                interval: false,  // إيقاف التقلب التلقائي للصور
                wrap: true,       // التفاف الكاروسيل عند الوصول إلى النهاية
                touch: true,      // دعم اللمس للأجهزة المحمولة
                pause: 'hover',   // إيقاف التقلب عند تمرير مؤشر الماوس فوق الكاروسيل
                ride: false       // لا تشغيل الكاروسيل تلقائيًا
            });
        });
        
        // منع انتشار الحدث عند النقر على أزرار التنقل
        document.querySelectorAll('.carousel-control-prev, .carousel-control-next').forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
        
        // تفعيل الكاروسيل
        const carousels = document.querySelectorAll('.carousel');
        carousels.forEach(carousel => {
            const instance = new bootstrap.Carousel(carousel, {
                interval: false, // لا تقوم بالتدوير تلقائياً
                wrap: true
            });
        });
        
        // إضافة مستمعي الأحداث للتقييم
        const rateStars = document.querySelectorAll('.rate-star');
        rateStars.forEach(star => {
            // مستمع لتأثير التحويم
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                const productId = this.getAttribute('data-product');
                const starsContainer = this.closest('.stars-wrap');
                const stars = starsContainer.querySelectorAll('.rate-star');
                
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('hovered');
                    } else {
                        s.classList.remove('hovered');
                    }
                });
            });
            
            // مستمع لإزالة تأثير التحويم
            star.addEventListener('mouseleave', function() {
                const starsContainer = this.closest('.stars-wrap');
                const stars = starsContainer.querySelectorAll('.rate-star');
                stars.forEach(s => s.classList.remove('hovered'));
            });
            
            // مستمع للنقر لإرسال التقييم
            star.addEventListener('click', function() {
                <?php if (isset($_SESSION['customer_id'])): ?>
                const rating = parseInt(this.getAttribute('data-rating'));
                const productId = this.getAttribute('data-product');
                
                // إرسال التقييم باستخدام AJAX
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('rating', rating);
                
                fetch('handle_rating.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // تحديث النجوم والتقييم
                        const ratingContainer = document.getElementById('rating-' + productId);
                        const ratingValue = ratingContainer.querySelector('.value');
                        const stars = ratingContainer.querySelectorAll('.rate-star');
                        
                        // تحديث قيمة التقييم
                        ratingValue.textContent = rating.toFixed(1);
                        
                        // تحديث النجوم
                        stars.forEach((s, index) => {
                            if (index < rating) {
                                s.classList.remove('bi-star');
                                s.classList.add('bi-star-fill');
                            } else {
                                s.classList.remove('bi-star-fill');
                                s.classList.add('bi-star');
                            }
                        });
                        
                        // إظهار رسالة نجاح
                        alert('تم تسجيل تقييمك بنجاح!');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في معالجة طلبك');
                });
                <?php else: ?>
                alert('يرجى تسجيل الدخول لتتمكن من تقييم المنتجات');
                <?php endif; ?>
            });
        });
        
        // إضافة مستمعي الأحداث للإعجاب
        const likeButtons = document.querySelectorAll('.like-btn');
        likeButtons.forEach(button => {
            button.addEventListener('click', function() {
                <?php if (isset($_SESSION['customer_id'])): ?>
                const productId = this.getAttribute('data-product');
                const isLiked = this.classList.contains('liked');
                
                // إرسال الإعجاب باستخدام AJAX
                const formData = new FormData();
                formData.append('product_id', productId);
                
                fetch('handle_like.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // تبديل حالة الإعجاب
                        const likeCount = document.getElementById('like-count-' + productId);
                        let count = parseInt(likeCount.textContent);
                        
                        if (data.action === 'like') {
                            // إضافة إعجاب
                            this.classList.remove('bi-heart');
                            this.classList.add('bi-heart-fill');
                            this.classList.add('liked');
                            count++;
                        } else {
                            // إلغاء إعجاب
                            this.classList.remove('bi-heart-fill');
                            this.classList.remove('liked');
                            this.classList.add('bi-heart');
                            count--;
                        }
                        
                        likeCount.textContent = count;
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في معالجة طلبك');
                });
                <?php else: ?>
                alert('يرجى تسجيل الدخول لتتمكن من الإعجاب بالمنتجات');
                <?php endif; ?>
            });
        });
    });
</script>
</body>
</html>
