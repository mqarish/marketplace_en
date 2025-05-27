<?php
if (!isset($_SESSION)) {
    session_start();
}

// تضمين ملف الإعدادات إذا لم يكن موجوداً
if (!isset($conn)) {
    require_once __DIR__ . '/init.php';
}

// استعلام الفئات للقائمة المنسدلة
$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);

// تحديد الصفحة النشطة
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- هيدر على غرار Shopify -->
<header class="shopify-header">
    <div class="shopify-header__container">
        <!-- الجزء العلوي من الهيدر - الشعار والتنقل الرئيسي -->
        <div class="shopify-header__main">
            <!-- الشعار -->
            <div class="shopify-logo">
                <a href="index.php" class="shopify-logo__link">
                    <i class="bi bi-shop"></i>
                    <span class="shopify-logo__text">ماركت</span>
                </a>
            </div>
            
            <!-- قائمة التنقل الرئيسية -->
            <nav class="shopify-nav">
                <ul class="shopify-nav__list">
                    <li class="shopify-nav__item shopify-nav__item--has-dropdown">
                        <a href="products.php" class="shopify-nav__link">المنتجات</a>
                        <div class="shopify-dropdown">
                            <div class="shopify-dropdown__container">
                                <div class="shopify-dropdown__section">
                                    <h4 class="shopify-dropdown__heading">تصفح المنتجات</h4>
                                    <ul class="shopify-dropdown__list">
                                        <li><a href="products.php" class="shopify-dropdown__link">جميع المنتجات</a></li>
                                        <li><a href="daily-offers.php" class="shopify-dropdown__link">العروض اليومية</a></li>
                                        <li><a href="best-sellers.php" class="shopify-dropdown__link">الأكثر مبيعاً</a></li>
                                        <li><a href="top-rated.php" class="shopify-dropdown__link">الأعلى تقييماً</a></li>
                                        <li><a href="most-liked.php" class="shopify-dropdown__link">الأكثر إعجاباً</a></li>
                                    </ul>
                                </div>
                                <div class="shopify-dropdown__section">
                                    <h4 class="shopify-dropdown__heading">الفئات</h4>
                                    <ul class="shopify-dropdown__list">
                                        <?php 
                                        if ($categories_result) {
                                            $categories_result->data_seek(0);
                                            $count = 0;
                                            while ($category = $categories_result->fetch_assoc()): 
                                                if ($count < 6): // عرض 6 فئات فقط لتجنب قائمة طويلة
                                        ?>
                                            <li><a href="products.php?category=<?php echo $category['id']; ?>" class="shopify-dropdown__link"><?php echo htmlspecialchars($category['name']); ?></a></li>
                                        <?php 
                                                endif;
                                                $count++;
                                            endwhile; 
                                            
                                            if ($count > 6): // إذا كان هناك أكثر من 6 فئات، أضف رابط "عرض المزيد"
                                        ?>
                                            <li><a href="categories.php" class="shopify-dropdown__link shopify-dropdown__link--more">عرض المزيد من الفئات</a></li>
                                        <?php 
                                            endif;
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="shopify-nav__item shopify-nav__item--has-dropdown">
                        <a href="stores.php" class="shopify-nav__link">المتاجر</a>
                        <div class="shopify-dropdown">
                            <div class="shopify-dropdown__container">
                                <div class="shopify-dropdown__section">
                                    <h4 class="shopify-dropdown__heading">استكشف المتاجر</h4>
                                    <ul class="shopify-dropdown__list">
                                        <li><a href="stores.php" class="shopify-dropdown__link">جميع المتاجر</a></li>
                                        <li><a href="stores.php?sort=newest" class="shopify-dropdown__link">أحدث المتاجر</a></li>
                                        <li><a href="stores.php?sort=popular" class="shopify-dropdown__link">المتاجر الشائعة</a></li>
                                        <li><a href="stores.php?verified=1" class="shopify-dropdown__link">المتاجر الموثقة</a></li>
                                    </ul>
                                </div>
                                <div class="shopify-dropdown__section">
                                    <div class="shopify-dropdown__promo">
                                        <h4 class="shopify-dropdown__promo-title">هل تريد إنشاء متجرك الخاص؟</h4>
                                        <p class="shopify-dropdown__promo-text">انضم إلى آلاف البائعين في سوقنا الإلكتروني</p>
                                        <a href="../store/register.php" class="shopify-dropdown__promo-button">إنشاء متجر</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    <li class="shopify-nav__item">
                        <a href="about.php" class="shopify-nav__link">عن المتجر</a>
                    </li>
                    <li class="shopify-nav__item">
                        <a href="contact.php" class="shopify-nav__link">اتصل بنا</a>
                    </li>
                </ul>
            </nav>
            
            <!-- جزء البحث والحساب والسلة -->
            <div class="shopify-header__actions">
                <!-- زر البحث -->
                <button type="button" class="shopify-header__search-toggle" aria-label="البحث" id="searchToggle">
                    <i class="bi bi-search"></i>
                </button>
                
                <!-- روابط الحساب -->
                <?php if (isset($_SESSION['customer_id'])): ?>
                    <div class="shopify-account-dropdown">
                        <button type="button" class="shopify-account-dropdown__toggle" id="accountDropdownToggle">
                            <i class="bi bi-person-circle"></i>
                            <span class="shopify-account-dropdown__name d-none d-md-inline"><?php echo isset($_SESSION['customer_name']) ? htmlspecialchars($_SESSION['customer_name']) : 'حسابي'; ?></span>
                        </button>
                        <div class="shopify-account-dropdown__menu" id="accountDropdownMenu">
                            <div class="shopify-account-dropdown__header">
                                <div class="shopify-account-dropdown__user">
                                    <i class="bi bi-person-circle shopify-account-dropdown__avatar"></i>
                                    <div>
                                        <div class="shopify-account-dropdown__username"><?php echo isset($_SESSION['customer_name']) ? htmlspecialchars($_SESSION['customer_name']) : 'العميل'; ?></div>
                                        <div class="shopify-account-dropdown__email"><?php echo isset($_SESSION['customer_email']) ? htmlspecialchars($_SESSION['customer_email']) : ''; ?></div>
                                    </div>
                                </div>
                            </div>
                            <ul class="shopify-account-dropdown__list">
                                <li><a href="account.php" class="shopify-account-dropdown__link"><i class="bi bi-person me-2"></i> حسابي</a></li>
                                <li><a href="orders.php" class="shopify-account-dropdown__link"><i class="bi bi-box me-2"></i> طلباتي</a></li>
                                <li><a href="wishlist.php" class="shopify-account-dropdown__link"><i class="bi bi-heart me-2"></i> قائمة المفضلة</a></li>
                                <li><a href="addresses.php" class="shopify-account-dropdown__link"><i class="bi bi-geo-alt me-2"></i> عناويني</a></li>
                                <li><hr class="shopify-account-dropdown__divider"></li>
                                <li><a href="logout.php" class="shopify-account-dropdown__link shopify-account-dropdown__link--danger"><i class="bi bi-box-arrow-right me-2"></i> تسجيل الخروج</a></li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="shopify-header__login">
                        <i class="bi bi-person"></i>
                        <span class="d-none d-md-inline">تسجيل الدخول</span>
                    </a>
                <?php endif; ?>
                
                <!-- رابط سلة التسوق -->
                <a href="cart.php" class="shopify-header__cart">
                    <i class="bi bi-cart3"></i>
                    <span class="shopify-header__cart-count"><?php echo isset($_SESSION['cart_count']) ? $_SESSION['cart_count'] : '0'; ?></span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- شريط البحث -->
    <div class="shopify-search" id="searchContainer">
        <div class="shopify-search__container">
            <form action="products.php" method="GET" class="shopify-search__form">
                <div class="shopify-search__input-group">
                    <div class="shopify-search__input-wrapper">
                        <i class="bi bi-search shopify-search__icon"></i>
                        <input type="text" name="q" class="shopify-search__input" placeholder="ابحث عن منتجات، متاجر، وأكثر..." autocomplete="off">
                    </div>
                    <button type="button" class="shopify-search__close" id="searchClose">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>

<!-- زر القائمة المتحركة للشاشات الصغيرة -->
<button type="button" class="shopify-mobile-menu-toggle d-md-none" id="mobileMenuToggle">
    <i class="bi bi-list"></i>
</button>

<!-- القائمة المتحركة للشاشات الصغيرة -->
<div class="shopify-mobile-menu" id="mobileMenu">
    <div class="shopify-mobile-menu__header">
        <button type="button" class="shopify-mobile-menu__close" id="mobileMenuClose">
            <i class="bi bi-x-lg"></i>
        </button>
        <div class="shopify-mobile-menu__logo">
            <i class="bi bi-shop"></i>
            <span>ماركت</span>
        </div>
    </div>
    <div class="shopify-mobile-menu__body">
        <ul class="shopify-mobile-menu__nav">
            <li class="shopify-mobile-menu__item shopify-mobile-menu__item--has-children">
                <div class="shopify-mobile-menu__item-header">
                    <a href="products.php" class="shopify-mobile-menu__link">المنتجات</a>
                    <button type="button" class="shopify-mobile-menu__toggle">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
                <ul class="shopify-mobile-menu__subnav">
                    <li><a href="products.php" class="shopify-mobile-menu__sublink">جميع المنتجات</a></li>
                    <li><a href="daily-offers.php" class="shopify-mobile-menu__sublink">العروض اليومية</a></li>
                    <li><a href="best-sellers.php" class="shopify-mobile-menu__sublink">الأكثر مبيعاً</a></li>
                    <li><a href="top-rated.php" class="shopify-mobile-menu__sublink">الأعلى تقييماً</a></li>
                    <li><a href="most-liked.php" class="shopify-mobile-menu__sublink">الأكثر إعجاباً</a></li>
                    
                    <li class="shopify-mobile-menu__divider">الفئات</li>
                    
                    <?php 
                    if ($categories_result) {
                        $categories_result->data_seek(0);
                        while ($category = $categories_result->fetch_assoc()): 
                    ?>
                        <li><a href="products.php?category=<?php echo $category['id']; ?>" class="shopify-mobile-menu__sublink"><?php echo htmlspecialchars($category['name']); ?></a></li>
                    <?php 
                        endwhile; 
                    }
                    ?>
                </ul>
            </li>
            <li class="shopify-mobile-menu__item shopify-mobile-menu__item--has-children">
                <div class="shopify-mobile-menu__item-header">
                    <a href="stores.php" class="shopify-mobile-menu__link">المتاجر</a>
                    <button type="button" class="shopify-mobile-menu__toggle">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
                <ul class="shopify-mobile-menu__subnav">
                    <li><a href="stores.php" class="shopify-mobile-menu__sublink">جميع المتاجر</a></li>
                    <li><a href="stores.php?sort=newest" class="shopify-mobile-menu__sublink">أحدث المتاجر</a></li>
                    <li><a href="stores.php?sort=popular" class="shopify-mobile-menu__sublink">المتاجر الشائعة</a></li>
                    <li><a href="stores.php?verified=1" class="shopify-mobile-menu__sublink">المتاجر الموثقة</a></li>
                </ul>
            </li>
            <li class="shopify-mobile-menu__item">
                <a href="about.php" class="shopify-mobile-menu__link">عن المتجر</a>
            </li>
            <li class="shopify-mobile-menu__item">
                <a href="contact.php" class="shopify-mobile-menu__link">اتصل بنا</a>
            </li>
        </ul>
    </div>
    <div class="shopify-mobile-menu__footer">
        <?php if (isset($_SESSION['customer_id'])): ?>
            <div class="shopify-mobile-menu__user">
                <i class="bi bi-person-circle"></i>
                <div>
                    <div class="shopify-mobile-menu__username"><?php echo isset($_SESSION['customer_name']) ? htmlspecialchars($_SESSION['customer_name']) : 'العميل'; ?></div>
                    <div class="shopify-mobile-menu__email"><?php echo isset($_SESSION['customer_email']) ? htmlspecialchars($_SESSION['customer_email']) : ''; ?></div>
                </div>
            </div>
            <div class="shopify-mobile-menu__actions">
                <a href="account.php" class="shopify-mobile-menu__action-btn">حسابي</a>
                <a href="logout.php" class="shopify-mobile-menu__action-btn shopify-mobile-menu__action-btn--logout">تسجيل الخروج</a>
            </div>
        <?php else: ?>
            <div class="shopify-mobile-menu__auth">
                <a href="login.php" class="shopify-mobile-menu__auth-btn shopify-mobile-menu__auth-btn--login">تسجيل الدخول</a>
                <a href="register.php" class="shopify-mobile-menu__auth-btn shopify-mobile-menu__auth-btn--register">إنشاء حساب</a>
            </div>
        <?php endif; ?>
    </div>
</div>
