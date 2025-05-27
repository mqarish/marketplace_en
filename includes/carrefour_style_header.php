<?php
/**
 * هيدر كارفور - الهيدر الرئيسي للسوق الإلكتروني
 * 
 * يعرض هذا الملف هيدر بتصميم مشابه لموقع كارفور مع شريط بحث وقائمة تنقل
 */

// تحديد الصفحة النشطة
$current_page = basename($_SERVER['PHP_SELF']);

// تحديد المتغيرات للبحث
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$view_type = isset($_GET['view']) ? $_GET['view'] : 'products';

// استعلام للحصول على التصنيفات
$categories_sql = "SELECT * FROM categories WHERE status = 'active' ORDER BY name";
$categories_result = $conn->query($categories_sql);
?>

<!-- هيدر كارفور -->
<header class="carrefour-header">
    <!-- الجزء العلوي من الهيدر -->
    <div class="carrefour-header__top">
        <div class="container carrefour-header__top-container">
            <div class="carrefour-header__top-links">
                <a href="about.php" class="carrefour-header__top-link">
                    <i class="bi bi-info-circle"></i> من نحن
                </a>
                <a href="contact.php" class="carrefour-header__top-link">
                    <i class="bi bi-headset"></i> اتصل بنا
                </a>
                <a href="help.php" class="carrefour-header__top-link">
                    <i class="bi bi-question-circle"></i> المساعدة
                </a>
            </div>
            <div class="carrefour-header__top-links">
                <?php if (isset($_SESSION['customer_id'])): ?>
                    <a href="profile.php" class="carrefour-header__top-link">
                        <i class="bi bi-person"></i> حسابي
                    </a>
                    <a href="logout.php" class="carrefour-header__top-link">
                        <i class="bi bi-box-arrow-right"></i> تسجيل الخروج
                    </a>
                <?php else: ?>
                    <a href="login.php" class="carrefour-header__top-link">
                        <i class="bi bi-box-arrow-in-right"></i> تسجيل الدخول
                    </a>
                    <a href="register.php" class="carrefour-header__top-link">
                        <i class="bi bi-person-plus"></i> إنشاء حساب
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- الجزء الرئيسي من الهيدر -->
    <div class="carrefour-header__main">
        <div class="container carrefour-header__main-container">
            <!-- الشعار -->
            <div class="carrefour-logo">
                <a href="index.php" class="carrefour-logo__link">
                    <span class="carrefour-logo__text">السوق الإلكتروني</span>
                </a>
            </div>
            
            <!-- شريط البحث -->
            <div class="carrefour-search">
                <form action="index.php" method="GET" class="carrefour-search__form">
                    <div class="carrefour-search__input-group">
                        <select name="category" class="carrefour-search__select">
                            <option value="">كل الفئات</option>
                            <?php 
                            if ($categories_result) {
                                while ($category = $categories_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php 
                                endwhile;
                                // إعادة مؤشر النتائج إلى البداية للاستخدام لاحقًا
                                $categories_result->data_seek(0);
                            }
                            ?>
                        </select>
                        <input type="text" name="search" class="carrefour-search__input" placeholder="ابحث عن منتجات..." value="<?php echo $search; ?>">
                        <input type="hidden" name="view" value="<?php echo $view_type; ?>">
                        <button type="submit" class="carrefour-search__button">
                            <i class="bi bi-search"></i> بحث
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- أيقونات الهيدر -->
            <div class="carrefour-header__icons">
                <a href="wishlist.php" class="carrefour-header__icon">
                    <i class="bi bi-heart"></i>
                    <span class="carrefour-header__icon-text">المفضلة</span>
                    <?php if (isset($_SESSION['wishlist_count']) && $_SESSION['wishlist_count'] > 0): ?>
                        <span class="carrefour-header__icon-badge"><?php echo $_SESSION['wishlist_count']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="cart.php" class="carrefour-header__icon">
                    <i class="bi bi-cart3"></i>
                    <span class="carrefour-header__icon-text">السلة</span>
                    <?php if (isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                        <span class="carrefour-header__icon-badge"><?php echo $_SESSION['cart_count']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="profile.php" class="carrefour-header__icon">
                    <i class="bi bi-person"></i>
                    <span class="carrefour-header__icon-text">حسابي</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- قائمة التنقل -->
    <nav class="carrefour-nav" style="background-color: #000000;">
        <div class="container carrefour-nav__container">
            <button class="carrefour-mobile-toggle" id="mobileToggle">
                <i class="bi bi-list"></i>
            </button>
            
            <ul class="carrefour-nav__list" id="navList">
                <li class="carrefour-nav__item">
                    <a href="index.php" class="carrefour-nav__link <?php echo ($current_page === 'index.php' && empty($category_id)) ? 'active' : ''; ?>">
                        <i class="bi bi-house"></i> الرئيسية
                    </a>
                </li>
                
                <li class="carrefour-nav__item">
                    <a href="#" class="carrefour-nav__link">
                        <i class="bi bi-grid"></i> الفئات <i class="bi bi-chevron-down"></i>
                    </a>
                    <div class="carrefour-dropdown">
                        <?php 
                        if ($categories_result) {
                            $categories_result->data_seek(0);
                            while ($category = $categories_result->fetch_assoc()): 
                        ?>
                            <div class="carrefour-dropdown__item">
                                <a href="index.php?category=<?php echo $category['id']; ?>" class="carrefour-dropdown__link">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </div>
                        <?php 
                            endwhile;
                        }
                        ?>
                    </div>
                </li>
                
                <li class="carrefour-nav__item">
                    <a href="index.php?view=products" class="carrefour-nav__link <?php echo ($view_type === 'products' && $current_page === 'index.php') ? 'active' : ''; ?>">
                        <i class="bi bi-box"></i> المنتجات
                    </a>
                </li>
                
                <li class="carrefour-nav__item">
                    <a href="index.php?view=stores" class="carrefour-nav__link <?php echo ($view_type === 'stores' && $current_page === 'index.php') ? 'active' : ''; ?>">
                        <i class="bi bi-shop"></i> المتاجر
                    </a>
                </li>
                
                <li class="carrefour-nav__item">
                    <a href="offers.php" class="carrefour-nav__link <?php echo ($current_page === 'offers.php') ? 'active' : ''; ?>">
                        <i class="bi bi-tag"></i> العروض
                    </a>
                </li>
                
                <li class="carrefour-nav__item">
                    <a href="most-liked.php" class="carrefour-nav__link <?php echo ($current_page === 'most-liked.php') ? 'active' : ''; ?>">
                        <i class="bi bi-heart"></i> الأكثر إعجاباً
                    </a>
                </li>
                
                <li class="carrefour-nav__item">
                    <a href="top-rated.php" class="carrefour-nav__link <?php echo ($current_page === 'top-rated.php') ? 'active' : ''; ?>">
                        <i class="bi bi-star"></i> الأعلى تقييماً
                    </a>
                </li>
            </ul>
            
            <div class="carrefour-location" onclick="getLocation()">
                <i class="bi bi-geo-alt"></i>
                <span class="carrefour-location__text">تسوق حسب موقعك</span>
            </div>
        </div>
    </nav>
</header>

<!-- سكريبت للقائمة المتحركة -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileToggle = document.getElementById('mobileToggle');
    const navList = document.getElementById('navList');
    
    if (mobileToggle && navList) {
        mobileToggle.addEventListener('click', function() {
            navList.classList.toggle('active');
        });
    }
    
    // التعامل مع القوائم المنسدلة في وضع الموبايل
    const navItems = document.querySelectorAll('.carrefour-nav__item');
    
    navItems.forEach(item => {
        const link = item.querySelector('.carrefour-nav__link');
        const dropdown = item.querySelector('.carrefour-dropdown');
        
        if (link && dropdown) {
            link.addEventListener('click', function(e) {
                if (window.innerWidth <= 767.98) {
                    e.preventDefault();
                    item.classList.toggle('active');
                }
            });
        }
    });
});
</script>
