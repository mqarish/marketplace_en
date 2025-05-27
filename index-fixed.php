<?php
// نسخة من ملف index.php مع تصحيح عرض صور المنتجات
// يمكنك نسخ محتوى هذا الملف إلى index.php بعد التأكد من صحته

// كود عرض المنتجات
?>
<!-- قسم عرض المنتجات -->
<div class="section-header d-flex justify-content-between align-items-center mb-4">
    <h2 class="section-title">المنتجات المتوفرة</h2>
    <div class="view-options">
        <button class="btn btn-sm btn-outline-secondary me-2" id="grid-view"><i class="bi bi-grid"></i></button>
        <button class="btn btn-sm btn-outline-secondary" id="list-view"><i class="bi bi-list"></i></button>
    </div>
</div>

<!-- شبكة المنتجات بتنسيق متجاوب -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">
    <?php while ($product = $products_result->fetch_assoc()): ?>
        <div class="col">
            <div class="card h-100 product-card shadow-sm">
                <?php if (!empty($product['image_url'])): ?>
                    <!-- حاوية الصورة مع الكاروسيل -->
                    <div class="product-card-image">
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="d-block">
                            <div id="productCarousel-<?php echo $product['id']; ?>" class="carousel slide" data-bs-ride="false">
                                <div class="carousel-inner">
                                    <!-- الصورة الرئيسية -->
                                    <div class="carousel-item active">
                                        <?php
                                        $image_path = '';
                                        if (strpos($product['image_url'], 'http') === 0) {
                                            $image_path = $product['image_url'];
                                        } else if (file_exists('../uploads/products/' . basename($product['image_url']))) {
                                            $image_path = '../uploads/products/' . basename($product['image_url']);
                                        } else if (file_exists('../' . $product['image_url'])) {
                                            $image_path = '../' . $product['image_url'];
                                        } else {
                                            $image_path = 'assets/images/product-placeholder.jpg';
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                             class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    </div>
                                    
                                    <!-- الصور الإضافية -->
                                    <?php if (!empty($product['additional_images'])): ?>
                                        <?php 
                                        $additional_images = explode(',', $product['additional_images']);
                                        foreach ($additional_images as $img): 
                                            if (!empty($img)):
                                                $add_img_path = '';
                                                if (file_exists('../' . trim($img))) {
                                                    $add_img_path = '../' . trim($img);
                                                } else {
                                                    continue;
                                                }
                                        ?>
                                        <div class="carousel-item">
                                            <img src="<?php echo htmlspecialchars($add_img_path); ?>" 
                                                 class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- أزرار التنقل -->
                                <?php if (!empty($product['additional_images'])): ?>
                                <button class="carousel-control-prev" type="button" 
                                        data-bs-target="#productCarousel-<?php echo $product['id']; ?>" data-bs-slide="prev"
                                        onclick="event.stopPropagation()">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">السابق</span>
                                </button>
                                <button class="carousel-control-next" type="button" 
                                        data-bs-target="#productCarousel-<?php echo $product['id']; ?>" data-bs-slide="next"
                                        onclick="event.stopPropagation()">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">التالي</span>
                                </button>
                                <?php endif; ?>
                            </div>
                        </a>
                        
                        <!-- شارات المنتج -->
                        <?php if (isset($product['price']) && isset($product['final_price']) && $product['final_price'] < $product['price']): ?>
                            <?php 
                            $discount_percentage = round((($product['price'] - $product['final_price']) / $product['price']) * 100);
                            ?>
                            <div class="product-badge bg-danger text-white position-absolute top-0 end-0 m-2 px-2 py-1 rounded-pill">
                                <?php echo $discount_percentage; ?>% خصم
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($product['created_at']) && (time() - strtotime($product['created_at']) < 7 * 24 * 60 * 60)): ?>
                            <div class="product-badge bg-success text-white position-absolute top-0 start-0 m-2 px-2 py-1 rounded-pill">
                                جديد
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                         style="height: 250px;">
                        <i class="bi bi-image text-secondary" style="font-size: 4rem;"></i>
                    </div>
                <?php endif; ?>
                
                <!-- معلومات المنتج -->
                <div class="card-body p-3">
                    <h5 class="card-title product-title">
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h5>
                    
                    <?php if (!empty($product['description'])): ?>
                    <p class="card-text product-description small text-muted mb-2">
                        <?php echo mb_substr(htmlspecialchars($product['description']), 0, 60) . '...'; ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="product-price mb-2">
                        <?php if (isset($product['price']) && isset($product['final_price']) && $product['final_price'] < $product['price']): ?>
                            <span class="text-decoration-line-through text-muted me-2">
                                <?php echo number_format($product['price'], 2); ?> ريال
                            </span>
                            <span class="fw-bold text-danger">
                                <?php echo number_format($product['final_price'], 2); ?> ريال
                            </span>
                        <?php else: ?>
                            <span class="fw-bold">
                                <?php echo number_format($product['price'], 2); ?> ريال
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- التقييم -->
                    <?php if (isset($product['avg_rating'])): ?>
                    <div class="product-rating mb-2">
                        <?php 
                        $rating = floatval($product['avg_rating']);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $rating) {
                                echo '<i class="bi bi-star-fill text-warning"></i>';
                            } elseif ($i - 0.5 <= $rating) {
                                echo '<i class="bi bi-star-half text-warning"></i>';
                            } else {
                                echo '<i class="bi bi-star text-warning"></i>';
                            }
                        }
                        ?>
                        <span class="text-muted ms-1">(<?php echo isset($product['rating_count']) ? $product['rating_count'] : 0; ?>)</span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- اسم المتجر -->
                    <?php if (isset($product['store_name'])): ?>
                    <div class="store-name small mb-2">
                        <i class="bi bi-shop me-1"></i>
                        <a href="store-page.php?id=<?php echo $product['store_id']; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($product['store_name']); ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- أزرار الإجراءات -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <button class="btn btn-sm btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                            <i class="bi bi-cart-plus"></i> إضافة للسلة
                        </button>
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye"></i> التفاصيل
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>
