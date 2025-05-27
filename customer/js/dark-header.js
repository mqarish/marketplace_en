/**
 * سكريبت الهيدر الداكن للسوق الإلكتروني
 * يضيف وظائف تفاعلية للقوائم المنسدلة والقائمة المتجاوبة
 */

document.addEventListener('DOMContentLoaded', function() {
    // ===== القوائم المنسدلة =====
    const userDropdownToggle = document.getElementById('userDropdownToggle');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    const categoriesDropdown = document.getElementById('categoriesDropdown');
    const categoriesDropdownMenu = document.getElementById('categoriesDropdownMenu');
    
    // تفعيل قائمة المستخدم
    if (userDropdownToggle && userDropdownMenu) {
        userDropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // إغلاق القوائم الأخرى
            if (categoriesDropdownMenu) {
                categoriesDropdownMenu.classList.remove('show');
                if (categoriesDropdown) categoriesDropdown.classList.remove('active');
            }
            
            // تبديل قائمة المستخدم
            userDropdownMenu.classList.toggle('show');
        });
    }
    
    // تفعيل قائمة الفئات
    if (categoriesDropdown && categoriesDropdownMenu) {
        categoriesDropdown.addEventListener('click', function(e) {
            e.preventDefault();
            
            // إغلاق القوائم الأخرى
            if (userDropdownMenu) {
                userDropdownMenu.classList.remove('show');
            }
            
            // تبديل قائمة الفئات
            categoriesDropdownMenu.classList.toggle('show');
            this.classList.toggle('active');
        });
    }
    
    // إغلاق القوائم عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (userDropdownMenu && userDropdownToggle) {
            if (!userDropdownToggle.contains(e.target) && !userDropdownMenu.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
            }
        }
        
        if (categoriesDropdownMenu && categoriesDropdown) {
            if (!categoriesDropdown.contains(e.target) && !categoriesDropdownMenu.contains(e.target)) {
                categoriesDropdownMenu.classList.remove('show');
                categoriesDropdown.classList.remove('active');
            }
        }
    });
    
    // ===== القائمة المتجاوبة للجوال =====
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    
    // فتح القائمة الجانبية
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden'; // منع التمرير
        });
    }
    
    // إغلاق القائمة الجانبية
    if (mobileMenuClose && mobileMenu) {
        mobileMenuClose.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = ''; // إعادة التمرير
        });
    }
    
    // تفعيل القوائم الفرعية في الجوال
    const mobileDropdownToggles = document.querySelectorAll('.mobile-dropdown-toggle');
    
    mobileDropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // الحصول على القائمة الفرعية التالية
            const dropdownMenu = this.nextElementSibling;
            
            if (dropdownMenu && dropdownMenu.classList.contains('mobile-dropdown-menu')) {
                // تبديل حالة القائمة
                dropdownMenu.classList.toggle('show');
                this.classList.toggle('active');
            }
        });
    });
    
    // ===== وظيفة تحديد الموقع =====
    window.getLocation = function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                // إرسال الإحداثيات إلى الخادم
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // إنشاء نموذج وإرساله
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;
                
                // إضافة حقول الإحداثيات
                const latField = document.createElement('input');
                latField.type = 'hidden';
                latField.name = 'lat';
                latField.value = lat;
                form.appendChild(latField);
                
                const lngField = document.createElement('input');
                lngField.type = 'hidden';
                lngField.name = 'lng';
                lngField.value = lng;
                form.appendChild(lngField);
                
                // إضافة النموذج للصفحة وإرساله
                document.body.appendChild(form);
                form.submit();
            }, function(error) {
                // عرض رسالة خطأ
                alert('لم نتمكن من تحديد موقعك. يرجى التحقق من إعدادات الموقع والمحاولة مرة أخرى.');
                console.error('خطأ في تحديد الموقع:', error);
            });
        } else {
            alert('متصفحك لا يدعم تحديد الموقع.');
        }
    };
    
    // ===== وظيفة إلغاء تحديد الموقع =====
    window.clearLocation = function() {
        // إنشاء نموذج وإرساله
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        
        // إضافة حقل لإلغاء الموقع
        const clearField = document.createElement('input');
        clearField.type = 'hidden';
        clearField.name = 'clear_location';
        clearField.value = '1';
        form.appendChild(clearField);
        
        // إضافة النموذج للصفحة وإرساله
        document.body.appendChild(form);
        form.submit();
    };
    
    // ===== تأثيرات إضافية =====
    
    // تغيير لون الهيدر عند التمرير
    const darkHeader = document.querySelector('.dark-header');
    let lastScrollTop = 0;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 100) {
            darkHeader.classList.add('header-scrolled');
            
            // عند التمرير لأسفل
            if (scrollTop > lastScrollTop) {
                darkHeader.classList.add('header-hidden');
            } 
            // عند التمرير لأعلى
            else {
                darkHeader.classList.remove('header-hidden');
            }
        } else {
            darkHeader.classList.remove('header-scrolled');
            darkHeader.classList.remove('header-hidden');
        }
        
        lastScrollTop = scrollTop;
    });
    
    // إضافة تنسيقات CSS إضافية
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        /* تأثيرات التمرير */
        .header-scrolled {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: rgba(0, 0, 0, 0.95);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            animation: slideDown 0.3s ease;
        }
        
        .header-hidden {
            transform: translateY(-100%);
            transition: transform 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-100%);
            }
            to {
                transform: translateY(0);
            }
        }
        
        /* تأثير الخلفية عند فتح القائمة الجانبية */
        .mobile-menu-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease;
        }
        
        .mobile-menu-backdrop.active {
            opacity: 1;
            visibility: visible;
        }
    `;
    
    document.head.appendChild(styleElement);
    
    // إضافة خلفية معتمة عند فتح القائمة الجانبية
    const backdrop = document.createElement('div');
    backdrop.className = 'mobile-menu-backdrop';
    document.body.appendChild(backdrop);
    
    if (mobileMenuToggle && backdrop) {
        mobileMenuToggle.addEventListener('click', function() {
            backdrop.classList.add('active');
        });
    }
    
    if (mobileMenuClose && backdrop) {
        mobileMenuClose.addEventListener('click', function() {
            backdrop.classList.remove('active');
        });
    }
    
    backdrop.addEventListener('click', function() {
        if (mobileMenu) {
            mobileMenu.classList.remove('active');
            backdrop.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});
