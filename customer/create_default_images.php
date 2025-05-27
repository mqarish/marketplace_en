<?php
// ملف لإنشاء الصور الافتراضية للعروض

// التأكد من وجود المجلدات
$assets_dir = '../assets/images/';
$uploads_dir = '../uploads/banners/';

if (!file_exists($assets_dir)) {
    mkdir($assets_dir, 0777, true);
    echo "تم إنشاء مجلد assets/images<br>";
}

if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
    echo "تم إنشاء مجلد uploads/banners<br>";
}

// دالة لإنشاء صورة بخلفية متدرجة وكتابة نص عليها
function createGradientImage($width, $height, $text, $filename, $gradient_start = [41, 128, 185], $gradient_end = [109, 213, 250]) {
    $image = imagecreatetruecolor($width, $height);
    
    // إنشاء تدرج لوني
    $steps = $height;
    for ($i = 0; $i < $steps; $i++) {
        $r = $gradient_start[0] + ($gradient_end[0] - $gradient_start[0]) * ($i / $steps);
        $g = $gradient_start[1] + ($gradient_end[1] - $gradient_start[1]) * ($i / $steps);
        $b = $gradient_start[2] + ($gradient_end[2] - $gradient_start[2]) * ($i / $steps);
        
        $color = imagecolorallocate($image, $r, $g, $b);
        imagefilledrectangle($image, 0, $i, $width, $i, $color);
    }
    
    // إضافة طبقة شفافة للتأكد من وضوح النص
    $overlay = imagecreatetruecolor($width, $height);
    $black = imagecolorallocate($overlay, 0, 0, 0);
    imagefilledrectangle($overlay, 0, 0, $width, $height, $black);
    imagecopymerge($image, $overlay, 0, 0, 0, 0, $width, $height, 30);
    
    // إضافة النص
    $text_color = imagecolorallocate($image, 255, 255, 255);
    $font_size = 5;
    $text_width = imagefontwidth($font_size) * mb_strlen($text);
    $text_height = imagefontheight($font_size);
    
    // وضع النص في وسط الصورة
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2;
    
    // كتابة النص
    imagestring($image, $font_size, $x, $y, $text, $text_color);
    
    // حفظ الصورة
    imagejpeg($image, $filename, 90);
    imagedestroy($image);
    imagedestroy($overlay);
    
    echo "تم إنشاء صورة: " . basename($filename) . "<br>";
}

// إنشاء صور العروض الافتراضية
// العرض الرئيسي - عروض نهاية الأسبوع
createGradientImage(1200, 400, "عروض نهاية الأسبوع - خصومات تصل إلى 50% على جميع المنتجات", $uploads_dir . "main-offer.jpg", [231, 76, 60], [241, 196, 15]);

// العرض الثانوي 1 - منتجات جديدة
createGradientImage(600, 300, "منتجات جديدة - اكتشف أحدث المنتجات", $uploads_dir . "offer1.jpg", [46, 204, 113], [26, 188, 156]);

// العرض الثانوي 2 - توصيل مجاني
createGradientImage(600, 300, "توصيل مجاني - للطلبات أكثر من 200 ريال", $uploads_dir . "offer2.jpg", [155, 89, 182], [142, 68, 173]);

// إنشاء صور افتراضية للاستخدام في حالة عدم وجود الصور الأصلية
createGradientImage(1200, 400, "صورة العرض الافتراضية", $assets_dir . "default-banner.jpg", [52, 152, 219], [41, 128, 185]);
createGradientImage(600, 300, "صورة العرض الصغيرة الافتراضية", $assets_dir . "default-small-banner.jpg", [52, 152, 219], [41, 128, 185]);

echo "<p>تم إنشاء جميع الصور بنجاح!</p>";
echo "<p><a href='index.php'>العودة إلى الصفحة الرئيسية</a></p>";
?>
