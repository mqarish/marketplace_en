<?php
/**
 * صفحة المنتجات الأكثر إعجاباً - عرض المنتجات حسب عدد الإعجابات بغض النظر عن المتجر
 */

session_start();

// تضمين الملفات المطلوبة
require_once '../includes/init.php';
require_once '../includes/functions.php';

// ===== التحقق من المستخدم =====
if (!isset($_SESSION['customer_id'])) {
    header('Location: /marketplace/customer/login.php');
    exit();
}

// التحقق من حالة العميل
$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT status FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $customer = $result->fetch_assoc();
    if ($customer['status'] !== 'active') {
        session_destroy();
        header('Location: /marketplace/customer/login.php?error=inactive');
        exit();
    }
} else {
    session_destroy();
    header('Location: /marketplace/customer/login.php?error=invalid');
    exit();
}

// ===== جلب المنتجات الأكثر إعجاباً =====
$products_sql = "SELECT p.*, 
                s.name as store_name, 
                s.address as store_address, 
                s.city as store_city,
                c.name as category_name,
                o.id as offer_id, 
                o.discount_percentage, 
                o.end_date,
                CASE 
                    WHEN o.id IS NOT NULL 
                    AND o.start_date <= NOW() 
                    AND o.end_date >= NOW() 
                    AND o.status = 'active'
                    THEN ROUND(p.price - (p.price * o.discount_percentage / 100), 2)
                    ELSE p.price 
                END as final_price,
                COUNT(DISTINCT l.id) as likes_count,
                AVG(r.rating) as avg_rating,
                COUNT(DISTINCT r.id) as rating_count,
                CASE WHEN EXISTS (
                    SELECT 1 FROM product_likes 
                    WHERE product_id = p.id AND customer_id = ?
                ) THEN 1 ELSE 0 END as is_liked
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
                LEFT JOIN product_likes l ON p.id = l.product_id
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.status = 'active' AND s.status = 'active'
                GROUP BY p.id
                HAVING likes_count > 0
                ORDER BY likes_count DESC
                LIMIT 20";

$stmt = $conn->prepare($products_sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$products_result = $stmt->get_result();

// ===== جلب التصنيفات =====
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="المنتجات الأكثر إعجاباً في السوق الإلكتروني">
    <title>المنتجات الأكثر إعجاباً | السوق الإلكتروني</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .product-link {
            text-decoration: none;
            color: inherit;
        }
        .card-img-top {
            height: 200px;
            object-fit: contain;
            padding: 1rem;
            background-color: #f8f9fa;
        }
        .offer-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }
        .section-title {
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-weight: 700;
        }
        .section-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 50px;
            height: 3px;
            background-color: #ff7a00;
        }
        .rating-stars {
            color: #ffc107;
        }
        .rating-count {
            color: #6c757d;
            font-size: 0.8rem;
            margin-right: 5px;
        }
        .likes-count {
            display: inline-flex;
            align-items: center;
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        .likes-count i {
            margin-left: 5px;
        }
        .like-button {
            background: none;
            border: none;
            color: #6c757d;
            transition: all 0.2s ease;
            padding: 0;
            font-size: 1.2rem;
        }
        .like-button:hover {
            color: #dc3545;
            transform: scale(1.1);
        }
        .like-button.liked {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- استدعاء الهيدر الداكن الجديد -->
    <?php 
    $root_path = '../';
    include '../includes/dark_header.php'; 
    ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col">
                <h1 class="section-title">المنتجات الأكثر إعجاباً</h1>
                <p class="text-muted">استكشف المنتجات التي نالت أكبر عدد من الإعجابات في السوق</p>
            </div>
        </div>

        <?php if ($products_result->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                <?php while ($product = $products_result->fetch_assoc()): ?>
                    <div class="col mb-4">
                        <div class="card h-100 product-card">
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="product-link">
                                <?php if (!empty($product['offer_id'])): ?>
                                    <div class="offer-badge">
                                        <span class="badge bg-danger">
                                            <?php echo $product['discount_percentage']; ?>% خصم
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($product['image_url'])): ?>
                                    <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                        class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php elseif (!empty($product['image'])): ?>
                                    <img src="../uploads/products/<?php echo htmlspecialchars($product['image']); ?>" 
                                        class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image text-secondary" style="font-size: 4rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </a>
                            
                            <div class="card-body">
                                <h5 class="card-title mb-2">
                                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h5>
                                
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <a href="store-page.php?id=<?php echo $product['store_id']; ?>" class="text-decoration-none text-muted small">
                                        <i class="bi bi-shop ms-1"></i> <?php echo htmlspecialchars($product['store_name']); ?>
                                    </a>
                                    
                                    <?php if (!empty($product['category_name'])): ?>
                                        <span class="badge bg-light text-secondary small">
                                            <?php echo htmlspecialchars($product['category_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- عرض عدد الإعجابات -->
                                <div class="likes-count mb-2">
                                    <span><?php echo $product['likes_count']; ?></span>
                                    <i class="bi bi-heart-fill"></i>
                                </div>
                                
                                <!-- عرض التقييم إذا وجد -->
                                <?php if ($product['avg_rating']): ?>
                                <div class="product-rating mb-2">
                                    <?php 
                                    $avgRating = round($product['avg_rating'], 1);
                                    for ($i = 1; $i <= 5; $i++): 
                                        if ($i <= $avgRating): ?>
                                            <i class="bi bi-star-fill rating-stars"></i>
                                        <?php elseif ($i <= $avgRating + 0.5): ?>
                                            <i class="bi bi-star-half rating-stars"></i>
                                        <?php else: ?>
                                            <i class="bi bi-star rating-stars"></i>
                                        <?php endif;
                                    endfor; ?>
                                    
                                    <span class="rating-count">
                                        (<?php echo $product['rating_count']; ?>)
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="product-price mb-3">
                                    <?php if (isset($product['hide_price']) && $product['hide_price'] == 1): ?>
                                        <span class="fw-bold text-primary">
                                            <i class="bi bi-telephone-fill me-1"></i> اتصل للسعر
                                        </span>
                                    <?php elseif (!empty($product['offer_id'])): ?>
                                        <span class="fw-bold text-danger">
                                            <?php echo number_format($product['final_price'], 2); ?>
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
                                        </span>
                                        <br>
                                        <small class="text-decoration-line-through text-muted">
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
                                        </small>
                                    <?php else: ?>
                                        <span class="fw-bold">
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
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-dark">
                                        <i class="bi bi-eye me-1"></i> عرض التفاصيل
                                    </a>
                                    
                                    <button class="btn btn-sm like-button <?php echo ($product['is_liked'] ? 'liked' : ''); ?>"
                                            data-product-id="<?php echo $product['id']; ?>"
                                            title="<?php echo ($product['is_liked'] ? 'إلغاء الإعجاب' : 'أعجبني'); ?>">
                                        <i class="bi <?php echo ($product['is_liked'] ? 'bi-heart-fill' : 'bi-heart'); ?>"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info py-4 text-center">
                <i class="bi bi-heart fs-1 d-block mb-3 text-muted"></i>
                <h4 class="alert-heading">لا توجد منتجات حاصلة على إعجابات حالياً</h4>
                <p class="mb-0">لم يتم العثور على أي منتجات حاصلة على إعجابات. يمكنك تصفح المنتجات والإعجاب بها لتظهر هنا.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- تضمين مكتبات JavaScript اللازمة -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle like buttons
        const likeButtons = document.querySelectorAll('.like-button');
        likeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const isLiked = this.classList.contains('liked');
                
                // AJAX request to handle_like.php
                fetch('handle_like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update UI
                        if (isLiked) {
                            this.classList.remove('liked');
                            this.querySelector('i').classList.remove('bi-heart-fill');
                            this.querySelector('i').classList.add('bi-heart');
                            this.title = 'أعجبني';
                        } else {
                            this.classList.add('liked');
                            this.querySelector('i').classList.remove('bi-heart');
                            this.querySelector('i').classList.add('bi-heart-fill');
                            this.title = 'إلغاء الإعجاب';
                        }
                        
                        // Update likes count
                        const productCard = this.closest('.product-card');
                        const likesCountElement = productCard.querySelector('.likes-count span');
                        const currentCount = parseInt(likesCountElement.textContent);
                        likesCountElement.textContent = isLiked ? currentCount - 1 : currentCount + 1;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    });
    </script>
</body>
</html>
