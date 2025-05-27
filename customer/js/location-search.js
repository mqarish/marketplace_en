/**
 * ملف جافاسكريبت لتنفيذ وظيفة البحث حسب الموقع
 * يستخدم واجهة برمجة التطبيقات Geolocation للحصول على موقع المستخدم
 */

// وظيفة الحصول على موقع المستخدم
function getLocation() {
    if (navigator.geolocation) {
        // عرض مؤشر التحميل
        showLocationLoading();
        
        // طلب موقع المستخدم
        navigator.geolocation.getCurrentPosition(
            // في حالة النجاح
            function(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                
                // تخزين الإحداثيات في ملفات تعريف الارتباط للاستخدام اللاحق
                setCookie('user_latitude', latitude, 1); // تخزين لمدة يوم واحد
                setCookie('user_longitude', longitude, 1);
                
                // توجيه المستخدم إلى صفحة البحث مع معلمات الموقع
                redirectToSearchWithLocation(latitude, longitude);
            },
            // في حالة الخطأ
            function(error) {
                hideLocationLoading();
                
                let errorMessage = '';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'تم رفض الوصول إلى الموقع. يرجى السماح للموقع بالوصول إلى موقعك للاستفادة من هذه الميزة.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'معلومات الموقع غير متاحة حالياً. يرجى المحاولة مرة أخرى لاحقاً.';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'انتهت مهلة طلب الموقع. يرجى المحاولة مرة أخرى.';
                        break;
                    default:
                        errorMessage = 'حدث خطأ غير معروف أثناء تحديد موقعك. يرجى المحاولة مرة أخرى.';
                        break;
                }
                
                showLocationError(errorMessage);
            },
            // خيارات تحديد الموقع
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        showLocationError('متصفحك لا يدعم تحديد الموقع. يرجى تحديث المتصفح أو استخدام متصفح آخر.');
    }
}

// وظيفة إعادة التوجيه إلى صفحة البحث مع معلمات الموقع
function redirectToSearchWithLocation(latitude, longitude) {
    // الحصول على قيمة البحث الحالية إن وجدت
    const searchForm = document.getElementById('search-form');
    const searchInput = searchForm.querySelector('input[name="search"]');
    const searchValue = searchInput ? searchInput.value : '';
    
    // الحصول على نوع البحث إن وجد
    let viewType = 'products'; // القيمة الافتراضية
    const viewSelect = searchForm.querySelector('select[name="view"]');
    if (viewSelect) {
        viewType = viewSelect.value;
    }
    
    // بناء عنوان URL للبحث مع معلمات الموقع
    const rootPath = document.querySelector('meta[name="root-path"]')?.getAttribute('content') || '';
    let searchUrl = `${rootPath}customer/search.php?`;
    
    if (searchValue) {
        searchUrl += `search=${encodeURIComponent(searchValue)}&`;
    }
    
    searchUrl += `view=${viewType}&`;
    searchUrl += `lat=${latitude}&`;
    searchUrl += `lng=${longitude}&`;
    searchUrl += `location=1`;
    
    // الانتقال إلى صفحة البحث
    window.location.href = searchUrl;
}

// وظيفة إظهار مؤشر التحميل
function showLocationLoading() {
    // التحقق من وجود مؤشر التحميل
    let loadingIndicator = document.getElementById('locationLoadingIndicator');
    
    // إنشاء مؤشر التحميل إذا لم يكن موجوداً
    if (!loadingIndicator) {
        loadingIndicator = document.createElement('div');
        loadingIndicator.id = 'locationLoadingIndicator';
        loadingIndicator.className = 'location-loading';
        loadingIndicator.innerHTML = `
            <div class="loading-spinner"></div>
            <p>جاري تحديد موقعك...</p>
        `;
        document.body.appendChild(loadingIndicator);
        
        // إضافة التنسيقات اللازمة
        const style = document.createElement('style');
        style.textContent = `
            .location-loading {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.7);
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                color: white;
                font-size: 1.2rem;
            }
            
            .loading-spinner {
                width: 50px;
                height: 50px;
                border: 5px solid #f3f3f3;
                border-top: 5px solid #FF7A00;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin-bottom: 20px;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    }
    
    // إظهار مؤشر التحميل
    loadingIndicator.style.display = 'flex';
}

// وظيفة إخفاء مؤشر التحميل
function hideLocationLoading() {
    const loadingIndicator = document.getElementById('locationLoadingIndicator');
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }
}

// وظيفة إظهار رسالة خطأ
function showLocationError(message) {
    // التحقق من وجود مربع الخطأ
    let errorBox = document.getElementById('locationErrorBox');
    
    // إنشاء مربع الخطأ إذا لم يكن موجوداً
    if (!errorBox) {
        errorBox = document.createElement('div');
        errorBox.id = 'locationErrorBox';
        errorBox.className = 'location-error';
        document.body.appendChild(errorBox);
        
        // إضافة التنسيقات اللازمة
        const style = document.createElement('style');
        style.textContent = `
            .location-error {
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background-color: #f44336;
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                z-index: 9999;
                max-width: 80%;
                text-align: center;
                animation: fadeIn 0.3s ease;
            }
            
            .location-error-close {
                position: absolute;
                top: 5px;
                left: 10px;
                cursor: pointer;
                font-size: 20px;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translate(-50%, -20px); }
                to { opacity: 1; transform: translate(-50%, 0); }
            }
        `;
        document.head.appendChild(style);
    }
    
    // تعيين محتوى مربع الخطأ
    errorBox.innerHTML = `
        <span class="location-error-close" onclick="this.parentElement.style.display='none'">&times;</span>
        <div>${message}</div>
    `;
    
    // إظهار مربع الخطأ
    errorBox.style.display = 'block';
    
    // إخفاء مربع الخطأ بعد 5 ثوانٍ
    setTimeout(function() {
        errorBox.style.display = 'none';
    }, 5000);
}

// وظيفة تعيين ملف تعريف الارتباط
function setCookie(name, value, days) {
    let expires = '';
    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = '; expires=' + date.toUTCString();
    }
    document.cookie = name + '=' + value + expires + '; path=/';
}

// وظيفة الحصول على قيمة ملف تعريف الارتباط
function getCookie(name) {
    const nameEQ = name + '=';
    const ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// استعادة موقع المستخدم من ملفات تعريف الارتباط عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // إضافة عنصر meta لتخزين مسار الجذر
    const rootPath = document.querySelector('meta[name="root-path"]');
    if (!rootPath) {
        const meta = document.createElement('meta');
        meta.name = 'root-path';
        meta.content = window.location.pathname.includes('/customer/') ? '../' : '';
        document.head.appendChild(meta);
    }
});
