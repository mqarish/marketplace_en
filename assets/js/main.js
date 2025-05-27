document.addEventListener('DOMContentLoaded', function() {
    // إدارة مؤشر التحميل
    const loading = document.getElementById('loading');
    if (loading) {
        window.addEventListener('load', function() {
            loading.style.display = 'none';
        });

        document.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                loading.style.display = 'flex';
            });
        });

        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', () => {
                loading.style.display = 'flex';
            });
        });
    }

    // تحسين تحميل الصور
    const storeLogos = document.querySelectorAll('.store-logo');
    storeLogos.forEach(img => {
        img.addEventListener('error', function() {
            this.src = 'uploads/stores/default-store.png';
        });

        // تحميل مسبق للصورة الافتراضية
        const defaultImg = new Image();
        defaultImg.src = 'uploads/stores/default-store.png';
    });

    // تحسين أداء النموذج
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        const formInputs = filterForm.querySelectorAll('select, input');
        let timer;

        formInputs.forEach(input => {
            input.addEventListener('change', function() {
                clearTimeout(timer);
                loading.style.display = 'flex';
                timer = setTimeout(() => {
                    filterForm.submit();
                }, 500);
            });
        });

        const latInput = filterForm.querySelector('input[name="latitude"]');
        const lngInput = filterForm.querySelector('input[name="longitude"]');
        const locationBtn = document.createElement('button');
        locationBtn.type = 'button';
        locationBtn.className = 'btn btn-outline-primary';
        locationBtn.innerHTML = '<i class="bi bi-geo-alt"></i> استخدام موقعي';
        
        // إضافة زر الموقع بعد حقول الإحداثيات
        lngInput.parentNode.insertBefore(locationBtn, lngInput.nextSibling);
        
        // إخفاء حقول الإحداثيات افتراضياً
        latInput.style.display = 'none';
        lngInput.style.display = 'none';
        
        locationBtn.addEventListener('click', () => {
            if (navigator.geolocation) {
                loading.style.display = 'flex';
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        latInput.value = position.coords.latitude;
                        lngInput.value = position.coords.longitude;
                        filterForm.submit();
                    },
                    (error) => {
                        loading.style.display = 'none';
                        alert('لم نتمكن من تحديد موقعك. الرجاء المحاولة مرة أخرى.');
                        console.error('خطأ في تحديد الموقع:', error);
                    }
                );
            } else {
                alert('متصفحك لا يدعم تحديد الموقع.');
            }
        });
    }
});
