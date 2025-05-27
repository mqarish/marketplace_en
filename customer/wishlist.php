<?php
/**
 * صفحة المفضلة - عرض المنتجات المفضلة للعميل
 */

session_start();
require_once '../includes/init.php';
require_once '../includes/functions.php';
require_once '../includes/customer_auth.php';

// التحقق من تسجيل دخول العميل
if (!isset($_SESSION['customer_id'])) {
    header('Location: /marketplace/customer/login.php');
    exit();
}

// جلب المنتجات المفضلة للعميل
$customer_id = $_SESSION['customer_id'];
$wishlist_sql = "SELECT p.*, 
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
                AVG(r.rating) as avg_rating,
                COUNT(DISTINCT r.id) as rating_count
                FROM product_likes pl
                INNER JOIN products p ON pl.product_id = p.id
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
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE pl.customer_id = ? AND p.status = 'active' AND s.status = 'active'
                GROUP BY p.id
                ORDER BY pl.created_at DESC";

$stmt = $conn->prepare($wishlist_sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$wishlist_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المفضلة - السوق الإلكتروني</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #000000 0%, #222222 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
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
        .rating-stars {
            color: #ffc107;
        }
        .rating-count {
            color: #6c757d;
            font-size: 0.8rem;
            margin-right: 5px;
        }
        .remove-btn {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 3;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            color: #dc3545;
            transition: all 0.2s ease;
        }
        .remove-btn:hover {
            background-color: #dc3545;
            color: white;
            transform: scale(1.1);
        }
        .empty-wishlist {
            text-align: center;
            padding: 3rem 0;
        }
        .empty-wishlist i {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- استدعاء الهيدر الداكن الجديد -->
    <?php 
    $root_path = '../';
    include '../includes/dark_header.php'; 
    ?>

    <div class="page-header text-center">
        <div class="container">
            <h1><i class="bi bi-heart me-2"></i> المفضلة</h1>
            <p class="lead">المنتجات التي أعجبتك وأضفتها إلى المفضلة</p>
        </div>
    </div>

    <div class="container py-4">
        <?php if ($wishlist_result && $wishlist_result->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                <?php while ($product = $wishlist_result->fetch_assoc()): ?>
                    <div class="col mb-4 wishlist-item" data-product-id="<?php echo $product['id']; ?>">
                        <div class="card h-100 product-card">
                            <button class="remove-btn" data-product-id="<?php echo $product['id']; ?>" title="إزالة من المفضلة">
                                <i class="bi bi-x"></i>
                            </button>
                            
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
                                
                                <!-- عرض التقييم -->
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
                                    <?php if (!empty($product['offer_id'])): ?>
                                        <span class="fw-bold text-danger">
                                            <?php echo number_format($product['final_price'], 2); ?> ريال
                                        </span>
                                        <br>
                                        <small class="text-decoration-line-through text-muted">
                                            <?php echo number_format($product['price'], 2); ?> ريال
                                        </small>
                                    <?php else: ?>
                                        <span class="fw-bold">
                                            <?php echo number_format($product['price'], 2); ?> ريال
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-grid">
                                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-dark">
                                        <i class="bi bi-eye me-1"></i> عرض التفاصيل
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <i class="bi bi-heart"></i>
                <h3>لا توجد منتجات في المفضلة</h3>
                <p class="text-muted">يمكنك إضافة المنتجات إلى المفضلة بالضغط على أيقونة القلب في صفحة المنتج</p>
                <a href="index.php" class="btn btn-primary mt-3">تصفح المنتجات</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- تضمين مكتبات JavaScript اللازمة -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle remove from wishlist buttons
        const removeButtons = document.querySelectorAll('.remove-btn');
        removeButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-product-id');
                const wishlistItem = document.querySelector(`.wishlist-item[data-product-id="${productId}"]`);
                
                // AJAX request to handle_like.php (same endpoint as liking/unliking)
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
                        // Remove the item from the UI with animation
                        wishlistItem.style.transition = 'all 0.3s ease';
                        wishlistItem.style.opacity = '0';
                        wishlistItem.style.transform = 'scale(0.8)';
                        
                        setTimeout(() => {
                            wishlistItem.remove();
                            
                            // Check if there are any items left
                            const remainingItems = document.querySelectorAll('.wishlist-item');
                            if (remainingItems.length === 0) {
                                // Show empty wishlist message
                                const container = document.querySelector('.container');
                                container.innerHTML = `
                                    <div class="empty-wishlist">
                                        <i class="bi bi-heart"></i>
                                        <h3>لا توجد منتجات في المفضلة</h3>
                                        <p class="text-muted">يمكنك إضافة المنتجات إلى المفضلة بالضغط على أيقونة القلب في صفحة المنتج</p>
                                        <a href="index.php" class="btn btn-primary mt-3">تصفح المنتجات</a>
                                    </div>
                                `;
                            }
                        }, 300);
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
