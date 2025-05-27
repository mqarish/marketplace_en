<?php
/**
 * Deals and Discounts Page - Display products with offers and discounts regardless of store
 */

session_start();

// Include required files
require_once '../includes/init.php';
require_once '../includes/functions.php';

// ===== User Verification =====
if (!isset($_SESSION['customer_id'])) {
    header('Location: /marketplace/customer/login.php');
    exit();
}

// Check customer status
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

// ===== Fetch Products with Offers =====
$products_sql = "SELECT p.*, 
                s.name as store_name, 
                s.address as store_address, 
                s.city as store_city,
                c.name as category_name,
                o.id as offer_id, 
                o.title as offer_title,
                o.discount_percentage, 
                o.end_date,
                ROUND(p.price - (p.price * o.discount_percentage / 100), 2) as final_price,
                AVG(r.rating) as avg_rating,
                COUNT(DISTINCT r.id) as rating_count,
                COUNT(DISTINCT l.id) as likes_count,
                CASE WHEN EXISTS (
                    SELECT 1 FROM product_likes 
                    WHERE product_id = p.id AND customer_id = ?
                ) THEN 1 ELSE 0 END as is_liked,
                DATEDIFF(o.end_date, CURDATE()) as days_remaining
                FROM products p
                INNER JOIN stores s ON p.store_id = s.id
                LEFT JOIN categories c ON p.category_id = c.id
                INNER JOIN (
                    SELECT DISTINCT store_id, offer_id 
                    FROM offer_store_products
                ) osp ON p.store_id = osp.store_id
                INNER JOIN offers o ON osp.offer_id = o.id 
                    AND o.start_date <= NOW() 
                    AND o.end_date >= NOW()
                    AND o.status = 'active'
                LEFT JOIN reviews r ON p.id = r.product_id
                LEFT JOIN product_likes l ON p.id = l.product_id
                WHERE p.status = 'active' AND s.status = 'active'
                GROUP BY p.id
                ORDER BY o.discount_percentage DESC, days_remaining ASC
                LIMIT 40";

$stmt = $conn->prepare($products_sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$products_result = $stmt->get_result();

// ===== Fetch Categories =====
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Best deals and discounts in the E-Marketplace">
    <title>Deals and Discounts | E-Marketplace</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
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
        .offer-badge .badge {
            font-size: 1rem;
            padding: 8px 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
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
        .original-price {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 0.9em;
        }
        .discount-price {
            color: #dc3545;
            font-weight: bold;
            font-size: 1.1em;
        }
        .countdown-badge {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: rgba(0,0,0,0.7);
            color: white;
            text-align: center;
            padding: 5px;
            font-size: 0.8rem;
        }
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Include the new dark header -->
    <?php 
    $root_path = '../';
    include '../includes/dark_header.php'; 
    ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col">
                <h1 class="section-title">Best Deals and Discounts</h1>
                <p class="text-muted">Explore the best deals and discounts currently available in the marketplace</p>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section mb-4">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label for="categoryFilter" class="form-label">Filter by Category</label>
                    <select class="form-select" id="categoryFilter">
                        <option value="">All Categories</option>
                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="discountFilter" class="form-label">Filter by Discount Percentage</label>
                    <select class="form-select" id="discountFilter">
                        <option value="">All Discounts</option>
                        <option value="50">50% off or more</option>
                        <option value="30">30% off or more</option>
                        <option value="20">20% off or more</option>
                        <option value="10">10% off or more</option>
                    </select>
                </div>
            </div>
        </div>

        <?php if ($products_result->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4" id="products-container">
                <?php while ($product = $products_result->fetch_assoc()): ?>
                    <div class="col mb-4 product-item" 
                         data-category="<?php echo $product['category_id']; ?>" 
                         data-discount="<?php echo $product['discount_percentage']; ?>">
                        <div class="card h-100 product-card">
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="product-link">
                                <div class="offer-badge">
                                    <span class="badge bg-danger">
                                        <?php echo $product['discount_percentage']; ?>% OFF
                                    </span>
                                </div>
                                
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
                                
                                <?php if ($product['days_remaining'] <= 3): ?>
                                    <div class="countdown-badge">
                                        <i class="bi bi-clock"></i>
                                        <?php if ($product['days_remaining'] == 0): ?>
                                            Ends today!
                                        <?php elseif ($product['days_remaining'] == 1): ?>
                                            Ends tomorrow!
                                        <?php else: ?>
                                            Ends in <?php echo $product['days_remaining']; ?> days
                                        <?php endif; ?>
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
                                
                                <!-- Display rating if available -->
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
                                
                                <!-- Display likes -->
                                <div class="likes-count mb-2">
                                    <span><?php echo $product['likes_count']; ?></span>
                                    <i class="bi bi-heart-fill"></i>
                                </div>
                                
                                <div class="product-price mb-3">
                                    <span class="discount-price">
                                        <?php echo number_format($product['final_price'], 2); ?> SAR
                                    </span>
                                    <br>
                                    <span class="original-price">
                                        <?php echo number_format($product['price'], 2); ?> SAR
                                    </span>
                                    <div class="savings mt-1 small text-success">
                                        Savings: <?php echo number_format($product['price'] - $product['final_price'], 2); ?> SAR
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye me-1"></i> View Details
                                    </a>
                                    
                                    <button class="btn btn-sm like-button <?php echo ($product['is_liked'] ? 'liked' : ''); ?>"
                                            data-product-id="<?php echo $product['id']; ?>"
                                            title="<?php echo ($product['is_liked'] ? 'Unlike' : 'Like'); ?>">
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
                <i class="bi bi-tag fs-1 d-block mb-3 text-muted"></i>
                <h4 class="alert-heading">No deals currently available</h4>
                <p class="mb-0">No products with active deals were found. Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Include required JavaScript libraries -->
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
                            this.title = 'Like';
                        } else {
                            this.classList.add('liked');
                            this.querySelector('i').classList.remove('bi-heart');
                            this.querySelector('i').classList.add('bi-heart-fill');
                            this.title = 'Unlike';
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

        // Filter functionality
        const categoryFilter = document.getElementById('categoryFilter');
        const discountFilter = document.getElementById('discountFilter');
        const productsContainer = document.getElementById('products-container');
        const productItems = document.querySelectorAll('.product-item');

        function applyFilters() {
            const selectedCategory = categoryFilter.value;
            const selectedDiscount = discountFilter.value;

            productItems.forEach(item => {
                const categoryMatch = !selectedCategory || item.dataset.category === selectedCategory;
                const discountMatch = !selectedDiscount || parseInt(item.dataset.discount) >= parseInt(selectedDiscount);
                
                if (categoryMatch && discountMatch) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });

            // Check if any products are visible
            const visibleProducts = document.querySelectorAll('.product-item[style=""]');
            if (visibleProducts.length === 0) {
                // If no products match the filters, show a message
                if (!document.getElementById('no-results-message')) {
                    const noResults = document.createElement('div');
                    noResults.id = 'no-results-message';
                    noResults.className = 'alert alert-info w-100 text-center';
                    noResults.innerHTML = 'No products match the selected filter criteria.';
                    productsContainer.appendChild(noResults);
                }
            } else {
                // Remove the no results message if it exists
                const noResultsMessage = document.getElementById('no-results-message');
                if (noResultsMessage) {
                    noResultsMessage.remove();
                }
            }
        }

        categoryFilter.addEventListener('change', applyFilters);
        discountFilter.addEventListener('change', applyFilters);
    });
    </script>
</body>
</html>
