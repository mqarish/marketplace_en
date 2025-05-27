/**
 * Shopify Style Header JavaScript
 * سكريبت لتفعيل وظائف الهيدر بتصميم Shopify
 */

document.addEventListener('DOMContentLoaded', function() {
    // تفعيل قائمة الحساب المنسدلة
    const accountToggle = document.getElementById('accountDropdownToggle');
    const accountMenu = document.getElementById('accountDropdownMenu');

    if (accountToggle && accountMenu) {
        accountToggle.addEventListener('click', function(e) {
            e.preventDefault();
            accountMenu.classList.toggle('active');
            
            // إغلاق القائمة عند النقر خارجها
            document.addEventListener('click', function closeMenu(e) {
                if (!accountToggle.contains(e.target) && !accountMenu.contains(e.target)) {
                    accountMenu.classList.remove('active');
                    document.removeEventListener('click', closeMenu);
                }
            });
        });
    }

    // تفعيل شريط البحث
    const searchToggle = document.getElementById('searchToggle');
    const searchContainer = document.getElementById('searchContainer');
    const searchClose = document.getElementById('searchClose');
    const searchInput = searchContainer ? searchContainer.querySelector('input') : null;

    if (searchToggle && searchContainer && searchClose) {
        searchToggle.addEventListener('click', function(e) {
            e.preventDefault();
            searchContainer.classList.add('active');
            if (searchInput) {
                setTimeout(() => searchInput.focus(), 100);
            }
        });

        searchClose.addEventListener('click', function() {
            searchContainer.classList.remove('active');
        });

        // إغلاق البحث عند الضغط على ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && searchContainer.classList.contains('active')) {
                searchContainer.classList.remove('active');
            }
        });
    }

    // تفعيل القائمة المتحركة للموبايل
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuClose = document.getElementById('mobileMenuClose');

    if (mobileMenuToggle && mobileMenu && mobileMenuClose) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        mobileMenuClose.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // تفعيل القوائم الفرعية في القائمة المتحركة
    const mobileMenuToggles = document.querySelectorAll('.shopify-mobile-menu__toggle');
    
    mobileMenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const parent = this.closest('.shopify-mobile-menu__item');
            
            // إغلاق القوائم الفرعية الأخرى
            if (!parent.classList.contains('active')) {
                document.querySelectorAll('.shopify-mobile-menu__item--has-children.active').forEach(item => {
                    if (item !== parent) {
                        item.classList.remove('active');
                        item.querySelector('.shopify-mobile-menu__toggle').classList.remove('active');
                    }
                });
            }
            
            // تبديل حالة القائمة الحالية
            parent.classList.toggle('active');
            this.classList.toggle('active');
        });
    });
});
