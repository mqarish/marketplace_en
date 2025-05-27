/**
 * ملف جافاسكريبت للتحكم في كاروسيل المنتجات
 */
document.addEventListener('DOMContentLoaded', function() {
    // تفعيل الكاروسيل
    const carousels = document.querySelectorAll('.carousel');
    carousels.forEach(carousel => {
        const instance = new bootstrap.Carousel(carousel, {
            interval: false, // لا تقوم بالتدوير تلقائياً
            wrap: true
        });
    });

    // منع انتشار النقر على أزرار التنقل
    document.querySelectorAll('.carousel-control-prev, .carousel-control-next').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // إضافة تأثير تكبير الصورة عند التحويم
    document.querySelectorAll('.product-image').forEach(img => {
        img.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        img.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // إضافة معالجة للنقر على أزرار الإعجاب
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            // يمكن إضافة كود للتفاعل مع الإعجابات هنا
        });
    });

    // إضافة معالجة للنقر على زر إضافة للسلة
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            // يمكن إضافة كود لإضافة المنتج للسلة هنا
        });
    });
});
