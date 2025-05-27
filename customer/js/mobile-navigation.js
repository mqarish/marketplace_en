/**
 * ملف جافاسكريبت لتحسين تجربة التنقل على الأجهزة المحمولة
 * يتضمن وظائف لفتح وإغلاق القائمة الجانبية وزر العودة للأعلى
 * وإدارة قائمة المستخدم المنسدلة
 */

document.addEventListener('DOMContentLoaded', function() {
    // عناصر القائمة الجانبية
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    const body = document.body;
    
    // إنشاء طبقة التغطية للقائمة الجانبية إذا لم تكن موجودة
    let mobileMenuOverlay = document.querySelector('.mobile-menu-overlay');
    if (!mobileMenuOverlay) {
        mobileMenuOverlay = document.createElement('div');
        mobileMenuOverlay.className = 'mobile-menu-overlay';
        body.appendChild(mobileMenuOverlay);
    }
    
    // فتح القائمة الجانبية
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('show');
            mobileMenuOverlay.classList.add('show');
            body.style.overflow = 'hidden'; // منع التمرير في الصفحة الرئيسية
        });
    }
    
    // إغلاق القائمة الجانبية
    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', function() {
            closeMenu();
        });
    }
    
    // إغلاق القائمة عند النقر على طبقة التغطية
    mobileMenuOverlay.addEventListener('click', function() {
        closeMenu();
    });
    
    // وظيفة إغلاق القائمة
    function closeMenu() {
        mobileMenu.classList.remove('show');
        mobileMenuOverlay.classList.remove('show');
        body.style.overflow = ''; // إعادة تفعيل التمرير
    }
    
    // إغلاق القائمة عند النقر على أي رابط فيها
    const mobileMenuLinks = document.querySelectorAll('.mobile-menu-link');
    mobileMenuLinks.forEach(link => {
        link.addEventListener('click', function() {
            closeMenu();
        });
    });
    
    // زر العودة للأعلى
    let backToTopBtn = document.querySelector('.back-to-top');
    
    // إنشاء زر العودة للأعلى إذا لم يكن موجوداً
    if (!backToTopBtn) {
        backToTopBtn = document.createElement('a');
        backToTopBtn.className = 'back-to-top';
        backToTopBtn.innerHTML = '<i class="bi bi-arrow-up"></i>';
        backToTopBtn.setAttribute('href', '#');
        body.appendChild(backToTopBtn);
    }
    
    // إظهار/إخفاء زر العودة للأعلى عند التمرير
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });
    
    // التمرير للأعلى عند النقر على الزر
    backToTopBtn.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // تحسين التنقل بين الصفحات على الأجهزة المحمولة
    const navList = document.querySelector('.main-nav .nav-list');
    if (navList) {
        // تفعيل التمرير الأفقي بالسحب للقائمة الرئيسية
        let isDown = false;
        let startX;
        let scrollLeft;
        
        navList.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - navList.offsetLeft;
            scrollLeft = navList.scrollLeft;
        });
        
        navList.addEventListener('mouseleave', () => {
            isDown = false;
        });
        
        navList.addEventListener('mouseup', () => {
            isDown = false;
        });
        
        navList.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - navList.offsetLeft;
            const walk = (x - startX) * 2; // سرعة التمرير
            navList.scrollLeft = scrollLeft - walk;
        });
        
        // دعم اللمس للأجهزة المحمولة
        navList.addEventListener('touchstart', (e) => {
            isDown = true;
            startX = e.touches[0].pageX - navList.offsetLeft;
            scrollLeft = navList.scrollLeft;
        }, { passive: true });
        
        navList.addEventListener('touchend', () => {
            isDown = false;
        }, { passive: true });
        
        navList.addEventListener('touchmove', (e) => {
            if (!isDown) return;
            const x = e.touches[0].pageX - navList.offsetLeft;
            const walk = (x - startX) * 2;
            navList.scrollLeft = scrollLeft - walk;
        }, { passive: true });
    }
    
    // تحسين عرض القوائم المنسدلة على الأجهزة المحمولة
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                e.preventDefault();
                const dropdownMenu = this.nextElementSibling;
                if (dropdownMenu && dropdownMenu.classList.contains('dropdown-menu')) {
                    if (dropdownMenu.style.display === 'block') {
                        dropdownMenu.style.display = 'none';
                    } else {
                        // إغلاق جميع القوائم المنسدلة الأخرى
                        document.querySelectorAll('.dropdown-menu').forEach(menu => {
                            menu.style.display = 'none';
                        });
                        dropdownMenu.style.display = 'block';
                    }
                }
            }
        });
    });
    
    // إغلاق القوائم المنسدلة عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 992) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        }
    });
    
    // تحسين عرض الصور على الأجهزة المحمولة
    const productImages = document.querySelectorAll('.product-card-image img, .product-gallery-main img');
    productImages.forEach(img => {
        img.addEventListener('error', function() {
            this.src = '../assets/images/placeholder.png'; // صورة بديلة في حالة فشل تحميل الصورة
        });
    });
    
    // إدارة قائمة المستخدم المنسدلة
    const userDropdownToggle = document.getElementById('userDropdownToggle');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    // إنشاء طبقة التغطية لقائمة المستخدم إذا لم تكن موجودة
    let userMenuOverlay = document.querySelector('.user-menu-overlay');
    if (!userMenuOverlay) {
        userMenuOverlay = document.createElement('div');
        userMenuOverlay.className = 'user-menu-overlay';
        body.appendChild(userMenuOverlay);
    }
    
    if (userDropdownToggle && userDropdownMenu) {
        userDropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // تبديل حالة العرض للقائمة المنسدلة
            if (userDropdownMenu.classList.contains('show')) {
                closeUserMenu();
            } else {
                openUserMenu();
            }
        });
        
        // فتح قائمة المستخدم
        function openUserMenu() {
            userDropdownMenu.classList.add('show');
            userMenuOverlay.classList.add('show');
            
            // منع التمرير في الصفحة الرئيسية على الجوال
            if (window.innerWidth <= 768) {
                body.style.overflow = 'hidden';
            }
        }
        
        // إغلاق قائمة المستخدم
        function closeUserMenu() {
            userDropdownMenu.classList.remove('show');
            userMenuOverlay.classList.remove('show');
            body.style.overflow = '';
        }
        
        // إغلاق القائمة عند النقر على طبقة التغطية
        userMenuOverlay.addEventListener('click', function() {
            closeUserMenu();
        });
        
        // إغلاق القائمة المنسدلة عند النقر على أي عنصر في القائمة
        const userMenuLinks = userDropdownMenu.querySelectorAll('a');
        userMenuLinks.forEach(link => {
            link.addEventListener('click', function() {
                closeUserMenu();
            });
        });
    }
});
