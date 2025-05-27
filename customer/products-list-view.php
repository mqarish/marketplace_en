<?php
// Este es un archivo temporal para la vista de lista de productos
?>
<!-- عرض المنتجات بشكل قائمة عمودية -->
<div class="product-list" id="products-list">
    <?php 
    // إعادة مؤشر نتائج المنتجات إلى البداية
    if ($products_result) {
        $products_result->data_seek(0);
        while ($product = $products_result->fetch_assoc()): 
    ?>
        <div class="card mb-3 product-list-item">
            <div class="row g-0">
                <!-- صورة المنتج -->
                <div class="col-md-3 position-relative">
                    <?php if (!empty($product['offer_id'])): ?>
                        <div class="offer-badge">
                            <span class="badge bg-danger">
                                <?php echo $product['discount_percentage']; ?>% خصم
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <a href="product-details.php?id=<?php echo $product['id']; ?>" class="product-link">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                class="img-fluid rounded-start h-100 object-fit-cover" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100 bg-light rounded-start">
                                <i class="bi bi-box-seam text-secondary" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- تفاصيل المنتج -->
                <div class="col-md-9">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title">
                                <a href="product-details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                    <i class="bi bi-box me-2 text-primary"></i>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h5>
                            
                            <?php if (!empty($product['category_name'])): ?>
                                <span class="badge bg-light text-secondary">
                                    <i class="bi bi-tag me-1"></i>
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="card-text text-muted mb-2">
                            <?php 
                            // عرض جزء من الوصف
                            $description = !empty($product['description']) ? $product['description'] : 'لا يوجد وصف';
                            echo htmlspecialchars(mb_substr($description, 0, 100, 'UTF-8')) . (mb_strlen($description, 'UTF-8') > 100 ? '...' : '');
                            ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <a href="store-page.php?id=<?php echo $product['store_id']; ?>" class="text-decoration-none text-muted">
                                <i class="bi bi-shop me-1"></i> <?php echo htmlspecialchars($product['store_name']); ?>
                            </a>
                            
                            <div class="product-price">
                                <?php if (!empty($product['offer_id'])): ?>
                                    <span class="fw-bold text-danger">
                                        <?php echo number_format($product['final_price'], 2); ?> ريال
                                    </span>
                                    <span class="original-price ms-2">
                                        <?php echo number_format($product['price'], 2); ?> ريال
                                    </span>
                                <?php else: ?>
                                    <span class="fw-bold">
                                        <?php echo number_format($product['price'], 2); ?> ريال
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm" style="background-color: #ff7a00; color: white;">
                                <i class="bi bi-eye me-1"></i> عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php 
        endwhile; 
    }
    ?>
</div>
