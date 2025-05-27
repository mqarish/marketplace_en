<?php
/**
 * صفحة عرض تفاصيل المتجر
 * تعرض معلومات المتجر ومنتجاته والتقييمات
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once('../includes/config.php');
require_once('../includes/functions.php');

// تعيين عنوان الصفحة
$page_title = "تفاصيل المتجر";
$root_path = "../";

// التحقق من وجود معرف المتجر في الرابط
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $store_id = intval($_GET['id']);
    
    // استعلام لجلب بيانات المتجر
    $stmt = $conn->prepare("SELECT * FROM stores WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $store = $result->fetch_assoc();
    } else {
        // إذا لم يتم العثور على المتجر، إعادة التوجيه إلى صفحة المتاجر
        header("Location: stores.php");
        exit;
    }
} else {
    // إذا لم يتم تحديد معرف المتجر، إعادة التوجيه إلى صفحة المتاجر
    header("Location: stores.php");
    exit;
}

// استعلام لجلب منتجات المتجر
$stmt = $conn->prepare("SELECT * FROM products WHERE store_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 12");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$products_result = $stmt->get_result();
$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

// استعلام لجلب تقييمات المتجر
$reviews_query = "SELECT r.*, c.name as customer_name, c.avatar 
                FROM reviews r 
                JOIN customers c ON r.customer_id = c.id 
                WHERE r.store_id = ? 
                ORDER BY r.created_at DESC LIMIT 5";
                
$reviews_stmt = $conn->prepare($reviews_query);

if ($reviews_stmt === false) {
    // إذا فشل تحضير الاستعلام
    $reviews = [];
    // التحقق من وجود جدول reviews
    $table_check = $conn->query("SHOW TABLES LIKE 'reviews'");
    if ($table_check->num_rows === 0) {
        // جدول التقييمات غير موجود
        $reviews_error = "جدول التقييمات غير موجود";
    } else {
        $reviews_error = "خطأ في استعلام التقييمات: " . $conn->error;
    }
} else {
    // إذا نجح تحضير الاستعلام
    $reviews_stmt->bind_param("i", $store_id);
    
    if ($reviews_stmt->execute()) {
        $reviews_result = $reviews_stmt->get_result();
        $reviews = [];
        while ($row = $reviews_result->fetch_assoc()) {
            $reviews[] = $row;
        }
    } else {
        $reviews = [];
        $reviews_error = "خطأ في تنفيذ استعلام التقييمات: " . $reviews_stmt->error;
    }
}

// تضمين ملف الهيدر الداكن كما في صفحة index.php
$root_path = '../';
include_once('../includes/dark_header.php');
?>

<!-- أنماط CSS مخصصة للصفحة -->
<style>
    .store-header {
        background-color: #f8f9fa;
        padding: 30px 0;
        margin-bottom: 40px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .store-logo {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #fff;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .store-info {
        padding: 20px;
    }
    
    .store-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: #333;
    }
    
    .store-description {
        color: #666;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    
    .store-meta {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #666;
    }
    
    .store-actions {
        display: flex;
        gap: 15px;
    }
    
    .store-action-btn {
        padding: 8px 20px;
        border-radius: 30px;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s ease;
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 25px;
        position: relative;
        padding-right: 15px;
        color: #333;
    }
    
    .section-title::before {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 5px;
        height: 25px;
        background-color: #FF7A00;
        border-radius: 5px;
    }
    
    .product-card {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        margin-bottom: 20px;
        background-color: #fff;
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .product-img {
        height: 200px;
        width: 100%;
        object-fit: cover;
    }
    
    .product-info {
        padding: 15px;
    }
    
    .product-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 10px;
        color: #333;
        height: 50px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    
    .product-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: #FF7A00;
        margin-bottom: 15px;
    }
    
    .product-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .product-rating {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #ffc107;
    }
    
    .product-category {
        font-size: 0.8rem;
        color: #666;
        background-color: #f8f9fa;
        padding: 3px 10px;
        border-radius: 15px;
    }
    
    .product-actions {
        display: flex;
        gap: 10px;
    }
    
    .product-btn {
        flex: 1;
        padding: 8px;
        border-radius: 5px;
        font-weight: 600;
        font-size: 0.9rem;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .review-card {
        background-color: #fff;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .review-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .reviewer-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .reviewer-info {
        flex: 1;
    }
    
    .reviewer-name {
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
    }
    
    .review-date {
        font-size: 0.8rem;
        color: #999;
    }
    
    .review-rating {
        display: flex;
        gap: 2px;
        margin-bottom: 10px;
    }
    
    .review-content {
        color: #666;
        line-height: 1.6;
    }
    
    .store-contact {
        background-color: #fff;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .contact-item {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .contact-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .contact-icon {
        width: 50px;
        height: 50px;
        background-color: #f8f9fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #FF7A00;
    }
    
    .contact-info {
        flex: 1;
    }
    
    .contact-label {
        font-size: 0.9rem;
        color: #999;
        margin-bottom: 5px;
    }
    
    .contact-value {
        font-weight: 600;
        color: #333;
    }
    
    .map-container {
        height: 250px;
        border-radius: 10px;
        overflow: hidden;
        margin-top: 20px;
    }
    
    @media (max-width: 768px) {
        .store-header {
            text-align: center;
        }
        
        .store-meta {
            justify-content: center;
        }
        
        .store-actions {
            justify-content: center;
        }
    }
</style>

<!-- محتوى الصفحة الرئيسي -->
<div class="container mt-4">
    <!-- رأس المتجر -->
    <div class="store-header">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <img src="<?php echo !empty($store['logo']) ? '../uploads/stores/'.$store['logo'] : 'images/default-store.png'; ?>" alt="<?php echo $store['name']; ?>" class="store-logo">
            </div>
            <div class="col-md-7">
                <div class="store-info">
                    <h1 class="store-title"><?php echo $store['name']; ?></h1>
                    <p class="store-description"><?php echo $store['description']; ?></p>
                    <div class="store-meta">
                        <div class="meta-item">
                            <i class="bi bi-star-fill text-warning"></i>
                            <span><?php echo isset($store['rating']) ? number_format($store['rating'], 1) : '0.0'; ?> (<?php echo isset($store['reviews_count']) ? $store['reviews_count'] : '0'; ?> تقييم)</span>
                        </div>
                        <div class="meta-item">
                            <i class="bi bi-box-seam"></i>
                            <span><?php echo isset($store['products_count']) ? $store['products_count'] : '0'; ?> منتج</span>
                        </div>
                        <div class="meta-item">
                            <i class="bi bi-geo-alt"></i>
                            <span><?php echo $store['city']; ?></span>
                        </div>
                    </div>
                    <div class="store-actions">
                        <a href="#contact" class="btn btn-outline-primary store-action-btn">
                            <i class="bi bi-chat-dots"></i>
                            <span>تواصل مع المتجر</span>
                        </a>
                        <a href="#products" class="btn btn-primary store-action-btn">
                            <i class="bi bi-bag"></i>
                            <span>تصفح المنتجات</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="store-contact">
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div class="contact-info">
                            <div class="contact-label">رقم الهاتف</div>
                            <div class="contact-value"><?php echo $store['phone']; ?></div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div class="contact-info">
                            <div class="contact-label">البريد الإلكتروني</div>
                            <div class="contact-value"><?php echo $store['email']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- منتجات المتجر -->
    <section id="products" class="mb-5">
        <h2 class="section-title">منتجات المتجر</h2>
        <div class="row">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-3 col-sm-6">
                        <div class="product-card">
                            <img src="<?php echo !empty($product['image']) ? '../uploads/products/'.$product['image'] : 'images/default-product.png'; ?>" alt="<?php echo $product['name']; ?>" class="product-img">
                            <div class="product-info">
                                <h3 class="product-title"><?php echo $product['name']; ?></h3>
                                <div class="product-meta">
                                    <div class="product-rating">
                                        <i class="bi bi-star-fill"></i>
                                        <span><?php echo number_format($product['rating'], 1); ?></span>
                                    </div>
                                    <div class="product-category"><?php echo $product['category']; ?></div>
                                </div>
                                <div class="product-price"><?php echo number_format($product['price'], 2); ?> ريال</div>
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="product-btn btn-primary">عرض التفاصيل</a>
                                    <button class="product-btn btn-outline-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        لا توجد منتجات متاحة حالياً لهذا المتجر
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php if (count($products) > 0): ?>
            <div class="text-center mt-4">
                <a href="products.php?store_id=<?php echo $store_id; ?>" class="btn btn-outline-primary">عرض جميع المنتجات</a>
            </div>
        <?php endif; ?>
    </section>
    
    <!-- تقييمات المتجر -->
    <section id="reviews" class="mb-5">
        <h2 class="section-title">تقييمات العملاء</h2>
        <div class="row">
            <div class="col-md-8">
                <?php if (count($reviews) > 0): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <img src="<?php echo !empty($review['avatar']) ? '../uploads/customers/'.$review['avatar'] : 'images/default-avatar.png'; ?>" alt="<?php echo $review['customer_name']; ?>" class="reviewer-avatar">
                                <div class="reviewer-info">
                                    <h5 class="reviewer-name"><?php echo $review['customer_name']; ?></h5>
                                    <div class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $review['rating']): ?>
                                        <i class="bi bi-star-fill text-warning"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star text-warning"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div class="review-content">
                                <?php echo $review['comment']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="text-center mt-4">
                        <a href="reviews.php?store_id=<?php echo $store_id; ?>" class="btn btn-outline-primary">عرض جميع التقييمات</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        لا توجد تقييمات متاحة حالياً لهذا المتجر
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <div class="store-contact" id="contact">
                    <h4 class="mb-4">معلومات التواصل</h4>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div class="contact-info">
                            <div class="contact-label">رقم الهاتف</div>
                            <div class="contact-value"><?php echo $store['phone']; ?></div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div class="contact-info">
                            <div class="contact-label">البريد الإلكتروني</div>
                            <div class="contact-value"><?php echo $store['email']; ?></div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div class="contact-info">
                            <div class="contact-label">العنوان</div>
                            <div class="contact-value"><?php echo $store['address']; ?></div>
                        </div>
                    </div>
                    
                    <?php if (!empty($store['location'])): ?>
                        <div class="map-container">
                            <div id="storeMap" style="width: 100%; height: 100%;"></div>
                        </div>
                        <script>
                            function initMap() {
                                const locationParts = "<?php echo $store['location']; ?>".split(',');
                                const lat = parseFloat(locationParts[0]);
                                const lng = parseFloat(locationParts[1]);
                                
                                const mapOptions = {
                                    center: { lat, lng },
                                    zoom: 15,
                                };
                                
                                const map = new google.maps.Map(document.getElementById("storeMap"), mapOptions);
                                
                                const marker = new google.maps.Marker({
                                    position: { lat, lng },
                                    map: map,
                                    title: "<?php echo $store['name']; ?>"
                                });
                            }
                        </script>
                        <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- سكريبت إضافة المنتج إلى سلة التسوق -->
<script>
$(document).ready(function() {
    // إضافة منتج إلى سلة التسوق
    $('.add-to-cart').on('click', function() {
        const productId = $(this).data('product-id');
        
        $.ajax({
            url: 'ajax/cart_actions.php',
            type: 'POST',
            data: {
                action: 'add',
                product_id: productId,
                quantity: 1
            },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    // تحديث عدد المنتجات في السلة
                    $('.cart-count').text(data.cart_count);
                    
                    // عرض رسالة نجاح
                    alert('تمت إضافة المنتج إلى سلة التسوق بنجاح');
                } else {
                    // عرض رسالة خطأ
                    alert(data.message);
                }
            },
            error: function() {
                alert('حدث خطأ أثناء إضافة المنتج إلى سلة التسوق');
            }
        });
    });
});
</script>

<?php
// تضمين ملف الفوتر
include_once('../includes/footer.php');
?>
