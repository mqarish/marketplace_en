<?php
/**
 * هيدر احترافي لموقع التجارة الإلكترونية
 * تصميم فريد وعصري بمعايير عالية
 */
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'السوق الإلكتروني'; ?></title>
    
    <!-- روابط الخطوط -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    
    <!-- ملفات CSS الأساسية -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $root_path ?? ''; ?>customer/styles/premium-header.css">
    
    <!-- ملفات الجافاسكريبت الأساسية -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>
<body>
    <!-- قسم الإشعارات العلوي -->
    <div class="premium-announcement-bar">
        <div class="container">
            <div class="premium-announcement-content">
                <div class="marquee-container">
                    <div class="marquee-content">
                        <span><i class="bi bi-tags-fill"></i> عروض حصرية لفترة محدودة - خصومات تصل إلى 50% على جميع المنتجات</span>
                        <span><i class="bi bi-truck"></i> شحن مجاني للطلبات أكثر من 200 ريال</span>
                        <span><i class="bi bi-shield-check"></i> ضمان جودة المنتج 100% مع إمكانية الإرجاع</span>
                    </div>
                </div>
                <div class="premium-lang-switcher d-none d-md-flex">
                    <a href="#" class="active">العربية</a>
                    <a href="#">English</a>
                </div>
            </div>
        </div>
    </div>

    <!-- الهيدر الرئيسي -->
    <header class="premium-header">
        <div class="container">
            <div class="premium-header-wrapper">
                <!-- الشعار -->
                <div class="premium-logo">
                    <a href="<?php echo $root_path ?? ''; ?>customer/index.php" class="premium-logo-link">
                        <div class="logo-icon">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div class="logo-text">
                            <span class="site-name">السوق الإلكتروني</span>
                            <span class="site-tagline">تسوق بذكاء وأمان</span>
                        </div>
                    </a>
                </div>
                
                <!-- شريط البحث -->
                <div class="premium-search">
                    <form action="<?php echo $root_path ?? ''; ?>customer/index.php" method="GET" class="search-form" id="searchForm">
                        <div class="search-input-wrapper">
                            <div class="search-category-select">
                                <select name="category" id="searchCategory">
                                    <option value="">جميع الفئات</option>
                                    <option value="electronics">إلكترونيات</option>
                                    <option value="fashion">أزياء</option>
                                    <option value="home">المنزل والمطبخ</option>
                                    <option value="beauty">الجمال والعناية</option>
                                    <option value="sports">رياضة</option>
                                </select>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                            <div class="search-input-container">
                                <input type="text" name="search" id="searchInput" class="search-input" placeholder="ابحث عن منتجات، متاجر، وعلامات تجارية..." autocomplete="off">
                                <div class="search-suggestions" id="searchSuggestions">
                                    <!-- سيتم ملؤها بواسطة JavaScript -->
                                </div>
                            </div>
                            <button type="submit" class="search-button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- أيقونات المستخدم -->
                <div class="premium-user-actions">
                    <div class="user-action-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/wishlist.php" class="action-link" data-tooltip="المفضلة">
                            <div class="action-icon">
                                <i class="bi bi-heart"></i>
                                <span class="count-badge">3</span>
                            </div>
                            <span class="action-label d-none d-lg-inline">المفضلة</span>
                        </a>
                    </div>
                    
                    <div class="user-action-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/orders.php" class="action-link" data-tooltip="طلباتي">
                            <div class="action-icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <span class="action-label d-none d-lg-inline">طلباتي</span>
                        </a>
                    </div>
                    
                    <div class="user-action-item user-dropdown">
                        <a href="#" class="action-link" id="userDropdownToggle" data-tooltip="حسابي">
                            <div class="action-icon">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <span class="action-label d-none d-lg-inline">حسابي <i class="bi bi-chevron-down"></i></span>
                        </a>
                        <div class="dropdown-menu" id="userDropdownMenu">
                            <?php if (isset($_SESSION['customer_id'])): ?>
                                <div class="dropdown-header">
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <i class="bi bi-person-circle"></i>
                                        </div>
                                        <div class="user-details">
                                            <h6 class="user-name"><?php echo $_SESSION['customer_name'] ?? 'المستخدم'; ?></h6>
                                            <p class="user-email"><?php echo $_SESSION['customer_email'] ?? ''; ?></p>
                                        </div>
                                    </div>
                                </div>
                                <a href="<?php echo $root_path ?? ''; ?>customer/profile.php" class="dropdown-item">
                                    <i class="bi bi-person"></i> الملف الشخصي
                                </a>
                                <a href="<?php echo $root_path ?? ''; ?>customer/orders.php" class="dropdown-item">
                                    <i class="bi bi-bag"></i> طلباتي
                                </a>
                                <a href="<?php echo $root_path ?? ''; ?>customer/addresses.php" class="dropdown-item">
                                    <i class="bi bi-geo-alt"></i> العناوين
                                </a>
                                <a href="<?php echo $root_path ?? ''; ?>customer/wishlist.php" class="dropdown-item">
                                    <i class="bi bi-heart"></i> المفضلة
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="<?php echo $root_path ?? ''; ?>customer/logout.php" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                                </a>
                            <?php else: ?>
                                <a href="<?php echo $root_path ?? ''; ?>customer/login.php" class="dropdown-item">
                                    <i class="bi bi-box-arrow-in-left"></i> تسجيل الدخول
                                </a>
                                <a href="<?php echo $root_path ?? ''; ?>customer/register.php" class="dropdown-item">
                                    <i class="bi bi-person-plus"></i> حساب جديد
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="user-action-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/cart.php" class="action-link cart-link" data-tooltip="سلة التسوق">
                            <div class="action-icon">
                                <i class="bi bi-cart3"></i>
                                <span class="count-badge cart-count">2</span>
                            </div>
                            <span class="action-label d-none d-lg-inline">السلة</span>
                        </a>
                        <div class="mini-cart" id="miniCart">
                            <div class="mini-cart-header">
                                <h5>سلة التسوق <span class="cart-count-text">(2)</span></h5>
                                <button class="close-mini-cart" id="closeMiniCart">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <div class="mini-cart-items">
                                <div class="cart-item">
                                    <div class="cart-item-image">
                                        <img src="<?php echo $root_path ?? ''; ?>assets/img/products/sample-product.jpg" alt="منتج">
                                    </div>
                                    <div class="cart-item-details">
                                        <h6 class="cart-item-title">سماعات بلوتوث لاسلكية</h6>
                                        <div class="cart-item-price">
                                            <span class="current-price">199 ريال</span>
                                        </div>
                                        <div class="cart-item-quantity">
                                            الكمية: <span>1</span>
                                        </div>
                                    </div>
                                    <button class="remove-cart-item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div class="cart-item">
                                    <div class="cart-item-image">
                                        <img src="<?php echo $root_path ?? ''; ?>assets/img/products/sample-product2.jpg" alt="منتج">
                                    </div>
                                    <div class="cart-item-details">
                                        <h6 class="cart-item-title">حقيبة ظهر للكمبيوتر المحمول</h6>
                                        <div class="cart-item-price">
                                            <span class="current-price">150 ريال</span>
                                            <span class="old-price">180 ريال</span>
                                        </div>
                                        <div class="cart-item-quantity">
                                            الكمية: <span>1</span>
                                        </div>
                                    </div>
                                    <button class="remove-cart-item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mini-cart-footer">
                                <div class="cart-subtotal">
                                    <span>المجموع:</span>
                                    <span class="subtotal-amount">349 ريال</span>
                                </div>
                                <div class="cart-actions">
                                    <a href="<?php echo $root_path ?? ''; ?>customer/cart.php" class="btn btn-outline-primary btn-sm">عرض السلة</a>
                                    <a href="<?php echo $root_path ?? ''; ?>customer/checkout.php" class="btn btn-primary btn-sm">إتمام الشراء</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- قائمة التنقل الرئيسية -->
    <nav class="premium-navbar">
        <div class="container">
            <div class="navbar-main">
                <!-- زر القائمة المتجاوبة للجوال -->
                <button class="nav-toggle-btn" id="navToggleBtn">
                    <i class="bi bi-list"></i>
                    <span>القائمة</span>
                </button>
                
                <!-- قائمة الفئات -->
                <div class="categories-dropdown">
                    <button class="categories-toggle" id="categoriesToggle">
                        <i class="bi bi-grid-fill"></i>
                        <span>تصفح الفئات</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="categories-menu" id="categoriesMenu">
                        <div class="categories-menu-content">
                            <div class="categories-list">
                                <div class="category-item has-submenu">
                                    <a href="#" class="category-link">
                                        <i class="bi bi-laptop"></i>
                                        <span>إلكترونيات</span>
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                    <div class="submenu">
                                        <div class="submenu-content">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <h6 class="submenu-title">الهواتف والأجهزة اللوحية</h6>
                                                    <ul class="submenu-links">
                                                        <li><a href="#">هواتف ذكية</a></li>
                                                        <li><a href="#">أجهزة لوحية</a></li>
                                                        <li><a href="#">ساعات ذكية</a></li>
                                                        <li><a href="#">سماعات بلوتوث</a></li>
                                                        <li><a href="#">اكسسوارات الهواتف</a></li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6 class="submenu-title">أجهزة الكمبيوتر</h6>
                                                    <ul class="submenu-links">
                                                        <li><a href="#">لابتوب</a></li>
                                                        <li><a href="#">كمبيوتر مكتبي</a></li>
                                                        <li><a href="#">شاشات</a></li>
                                                        <li><a href="#">طابعات وماسحات</a></li>
                                                        <li><a href="#">ملحقات الكمبيوتر</a></li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-4">
                                                    <h6 class="submenu-title">الصوت والفيديو</h6>
                                                    <ul class="submenu-links">
                                                        <li><a href="#">تلفزيونات</a></li>
                                                        <li><a href="#">أنظمة صوت</a></li>
                                                        <li><a href="#">كاميرات</a></li>
                                                        <li><a href="#">سماعات رأس</a></li>
                                                        <li><a href="#">مشغلات ووسائط</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="submenu-featured">
                                                <div class="featured-title">منتجات مميزة</div>
                                                <div class="featured-products">
                                                    <div class="featured-product">
                                                        <div class="featured-img">
                                                            <img src="<?php echo $root_path ?? ''; ?>assets/img/products/electronics-1.jpg" alt="منتج">
                                                        </div>
                                                        <div class="featured-info">
                                                            <h6>سماعات ذكية مع إلغاء الضوضاء</h6>
                                                            <div class="featured-price">349 ريال</div>
                                                        </div>
                                                    </div>
                                                    <div class="featured-product">
                                                        <div class="featured-img">
                                                            <img src="<?php echo $root_path ?? ''; ?>assets/img/products/electronics-2.jpg" alt="منتج">
                                                        </div>
                                                        <div class="featured-info">
                                                            <h6>كاميرا رقمية احترافية</h6>
                                                            <div class="featured-price">1299 ريال</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="category-item">
                                    <a href="#" class="category-link">
                                        <i class="bi bi-bag"></i>
                                        <span>أزياء</span>
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </div>
                                <div class="category-item">
                                    <a href="#" class="category-link">
                                        <i class="bi bi-house"></i>
                                        <span>المنزل والمطبخ</span>
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </div>
                                <div class="category-item">
                                    <a href="#" class="category-link">
                                        <i class="bi bi-gem"></i>
                                        <span>الجمال والعناية</span>
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </div>
                                <div class="category-item">
                                    <a href="#" class="category-link">
                                        <i class="bi bi-controller"></i>
                                        <span>ألعاب وهوايات</span>
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </div>
                                <div class="category-item">
                                    <a href="#" class="category-link">
                                        <i class="bi bi-book"></i>
                                        <span>كتب وقرطاسية</span>
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </div>
                                <div class="category-item">
                                    <a href="#" class="category-link">
                                        <i class="bi bi-bicycle"></i>
                                        <span>رياضة وخارجية</span>
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </div>
                                <div class="category-item">
                                    <a href="#" class="category-link">
                                        <i class="bi bi-car-front"></i>
                                        <span>سيارات وأدوات</span>
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </div>
                                <div class="view-all-categories">
                                    <a href="#" class="view-all-link">
                                        <span>عرض جميع الفئات</span>
                                        <i class="bi bi-arrow-left-short"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- روابط التنقل الرئيسية -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/index.php" class="nav-link active">
                            <i class="bi bi-house-fill"></i> الرئيسية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/deals.php" class="nav-link">
                            <i class="bi bi-lightning-fill"></i> العروض
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/new-arrivals.php" class="nav-link">
                            <i class="bi bi-stars"></i> وصل حديثاً
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/top-rated.php" class="nav-link">
                            <i class="bi bi-trophy-fill"></i> الأعلى تقييماً
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php echo $root_path ?? ''; ?>customer/brands.php" class="nav-link">
                            <i class="bi bi-tags-fill"></i> العلامات التجارية
                        </a>
                    </li>
                </ul>
                
                <!-- شارة العروض المميزة -->
                <a href="<?php echo $root_path ?? ''; ?>customer/special-deals.php" class="special-offer-badge">
                    <i class="bi bi-fire"></i>
                    <span>عروض خاصة</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- محتوى الصفحة الرئيسي -->
    <main>
