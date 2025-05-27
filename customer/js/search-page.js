/**
 * ملف جافاسكريبت لتحسين تجربة المستخدم في صفحة البحث
 * يتضمن وظائف لفتح وإغلاق فلاتر البحث على الأجهزة المحمولة
 */

document.addEventListener('DOMContentLoaded', function() {
    // عناصر فلاتر البحث
    const filterToggle = document.getElementById('filterToggle');
    const filterContent = document.getElementById('filterContent');
    const mobileFilterButton = document.getElementById('mobileFilterButton');
    const mobileSortButton = document.getElementById('mobileSortButton');
    
    // تبديل عرض/إخفاء فلاتر البحث على الأجهزة المحمولة
    if (filterToggle && filterContent) {
        filterToggle.addEventListener('click', function() {
            filterContent.classList.toggle('show');
            
            // تغيير أيقونة الزر
            if (filterContent.classList.contains('show')) {
                filterToggle.innerHTML = '<i class="bi bi-chevron-up"></i>';
            } else {
                filterToggle.innerHTML = '<i class="bi bi-chevron-down"></i>';
            }
        });
    }
    
    // فتح نافذة منبثقة للفلاتر على الأجهزة المحمولة
    if (mobileFilterButton) {
        mobileFilterButton.addEventListener('click', function() {
            // إنشاء طبقة التغطية إذا لم تكن موجودة
            let overlay = document.querySelector('.mobile-filter-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'mobile-filter-overlay';
                document.body.appendChild(overlay);
            }
            
            // إظهار طبقة التغطية
            overlay.classList.add('show');
            
            // إنشاء نافذة الفلاتر
            let filterModal = document.querySelector('.mobile-filter-modal');
            if (!filterModal) {
                filterModal = document.createElement('div');
                filterModal.className = 'mobile-filter-modal';
                
                // نسخ محتوى الفلاتر
                const filtersContainer = document.querySelector('.search-filters');
                if (filtersContainer) {
                    filterModal.innerHTML = `
                        <div class="modal-header">
                            <h5>فلترة النتائج</h5>
                            <button type="button" class="close-modal">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="modal-body">
                            ${filtersContainer.innerHTML}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">تطبيق الفلاتر</button>
                        </div>
                    `;
                }
                
                document.body.appendChild(filterModal);
                
                // إضافة حدث لزر الإغلاق
                const closeModalBtn = filterModal.querySelector('.close-modal');
                if (closeModalBtn) {
                    closeModalBtn.addEventListener('click', function() {
                        filterModal.classList.remove('show');
                        overlay.classList.remove('show');
                    });
                }
                
                // إغلاق النافذة عند النقر على طبقة التغطية
                overlay.addEventListener('click', function() {
                    filterModal.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
            
            // إظهار نافذة الفلاتر
            filterModal.classList.add('show');
        });
    }
    
    // فتح نافذة منبثقة للترتيب على الأجهزة المحمولة
    if (mobileSortButton) {
        mobileSortButton.addEventListener('click', function() {
            // إنشاء طبقة التغطية إذا لم تكن موجودة
            let overlay = document.querySelector('.mobile-filter-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'mobile-filter-overlay';
                document.body.appendChild(overlay);
            }
            
            // إظهار طبقة التغطية
            overlay.classList.add('show');
            
            // إنشاء نافذة الترتيب
            let sortModal = document.querySelector('.mobile-sort-modal');
            if (!sortModal) {
                sortModal = document.createElement('div');
                sortModal.className = 'mobile-sort-modal';
                
                // إنشاء خيارات الترتيب
                const sortSelect = document.getElementById('sortFilter');
                let sortOptions = '';
                
                if (sortSelect) {
                    Array.from(sortSelect.options).forEach(option => {
                        sortOptions += `
                            <div class="sort-option ${option.selected ? 'active' : ''}" data-value="${option.value}">
                                <span>${option.textContent}</span>
                                ${option.selected ? '<i class="bi bi-check-lg"></i>' : ''}
                            </div>
                        `;
                    });
                }
                
                sortModal.innerHTML = `
                    <div class="modal-header">
                        <h5>ترتيب النتائج</h5>
                        <button type="button" class="close-modal">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="sort-options">
                            ${sortOptions}
                        </div>
                    </div>
                `;
                
                document.body.appendChild(sortModal);
                
                // إضافة حدث لزر الإغلاق
                const closeModalBtn = sortModal.querySelector('.close-modal');
                if (closeModalBtn) {
                    closeModalBtn.addEventListener('click', function() {
                        sortModal.classList.remove('show');
                        overlay.classList.remove('show');
                    });
                }
                
                // إغلاق النافذة عند النقر على طبقة التغطية
                overlay.addEventListener('click', function() {
                    sortModal.classList.remove('show');
                    overlay.classList.remove('show');
                });
                
                // إضافة أحداث لخيارات الترتيب
                const sortOptionElements = sortModal.querySelectorAll('.sort-option');
                sortOptionElements.forEach(option => {
                    option.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        if (sortSelect) {
                            sortSelect.value = value;
                            applyFilters();
                            sortModal.classList.remove('show');
                            overlay.classList.remove('show');
                        }
                    });
                });
            }
            
            // إظهار نافذة الترتيب
            sortModal.classList.add('show');
        });
    }
    
    // تحسين عرض علامات التبويب على الأجهزة المحمولة
    const searchTabs = document.querySelector('.search-tabs');
    if (searchTabs && window.innerWidth <= 768) {
        // تفعيل التمرير الأفقي بالسحب
        let isDown = false;
        let startX;
        let scrollLeft;
        
        searchTabs.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - searchTabs.offsetLeft;
            scrollLeft = searchTabs.scrollLeft;
        });
        
        searchTabs.addEventListener('mouseleave', () => {
            isDown = false;
        });
        
        searchTabs.addEventListener('mouseup', () => {
            isDown = false;
        });
        
        searchTabs.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - searchTabs.offsetLeft;
            const walk = (x - startX) * 2; // سرعة التمرير
            searchTabs.scrollLeft = scrollLeft - walk;
        });
        
        // دعم اللمس للأجهزة المحمولة
        searchTabs.addEventListener('touchstart', (e) => {
            isDown = true;
            startX = e.touches[0].pageX - searchTabs.offsetLeft;
            scrollLeft = searchTabs.scrollLeft;
        }, { passive: true });
        
        searchTabs.addEventListener('touchend', () => {
            isDown = false;
        }, { passive: true });
        
        searchTabs.addEventListener('touchmove', (e) => {
            if (!isDown) return;
            const x = e.touches[0].pageX - searchTabs.offsetLeft;
            const walk = (x - startX) * 2;
            searchTabs.scrollLeft = scrollLeft - walk;
        }, { passive: true });
    }
});

// وظيفة تطبيق الفلاتر
function applyFilters() {
    const searchParam = new URLSearchParams(window.location.search);
    const search = searchParam.get('search') || '';
    const view = searchParam.get('view') || 'all';
    
    // الحصول على قيم الفلاتر
    const categoryFilter = document.getElementById('categoryFilter');
    const sortFilter = document.getElementById('sortFilter');
    
    let category = '';
    let sort = 'newest';
    
    if (categoryFilter) {
        category = categoryFilter.value;
    }
    
    if (sortFilter) {
        sort = sortFilter.value;
    }
    
    // بناء رابط التصفية
    let filterUrl = `search.php?search=${encodeURIComponent(search)}&view=${view}`;
    
    if (category) {
        filterUrl += `&category=${category}`;
    }
    
    if (sort) {
        filterUrl += `&sort=${sort}`;
    }
    
    // الانتقال إلى الرابط الجديد
    window.location.href = filterUrl;
}
