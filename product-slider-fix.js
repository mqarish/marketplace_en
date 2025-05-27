/**
 * ملف JavaScript مخصص لإصلاح مشكلة تقليب الصور في صفحة المنتجات
 */

// تنفيذ الكود عند تحميل الصفحة
window.addEventListener('load', function() {
    console.log('تم تحميل صفحة المنتجات');
    
    // تهيئة جميع عناصر الكاروسيل
    try {
        var carousels = document.querySelectorAll('.carousel');
        console.log('عدد عناصر الكاروسيل: ' + carousels.length);
        
        carousels.forEach(function(carousel, index) {
            console.log('تهيئة كاروسيل رقم: ' + index);
            try {
                var carouselId = carousel.id;
                console.log('معرف الكاروسيل: ' + carouselId);
                
                // تهيئة الكاروسيل باستخدام Bootstrap API
                var carouselInstance = new bootstrap.Carousel(carousel, {
                    interval: false,  // عدم التدوير التلقائي
                    wrap: true        // التفاف للبداية بعد آخر عنصر
                });
                console.log('تم تهيئة الكاروسيل بنجاح');
                
                // العثور على أزرار التنقل للكاروسيل الحالي
                var prevButton = carousel.querySelector('.carousel-control-prev');
                var nextButton = carousel.querySelector('.carousel-control-next');
                
                // إضافة معالج حدث للزر السابق
                if (prevButton) {
                    prevButton.addEventListener('click', function(event) {
                        console.log('تم النقر على زر السابق');
                        event.preventDefault();
                        event.stopPropagation();
                        carouselInstance.prev();
                    });
                }
                
                // إضافة معالج حدث للزر التالي
                if (nextButton) {
                    nextButton.addEventListener('click', function(event) {
                        console.log('تم النقر على زر التالي');
                        event.preventDefault();
                        event.stopPropagation();
                        carouselInstance.next();
                    });
                }
            } catch (err) {
                console.error('خطأ في تهيئة الكاروسيل: ', err);
            }
        });
    } catch (error) {
        console.error('خطأ في العثور على عناصر الكاروسيل: ', error);
    }
});
