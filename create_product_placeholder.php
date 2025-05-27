<?php
// ملف لإنشاء صورة افتراضية للمنتجات

// التأكد من وجود المجلد
$assets_dir = 'assets/images/';
if (!file_exists($assets_dir)) {
    mkdir($assets_dir, 0777, true);
    echo "تم إنشاء مجلد assets/images<br>";
}

// إنشاء صورة بخلفية متدرجة
$width = 400;
$height = 400;
$image = imagecreatetruecolor($width, $height);

// تعريف الألوان للتدرج
$start_color = [52, 152, 219]; // #3498db - أزرق فاتح
$end_color = [41, 128, 185];   // #2980b9 - أزرق غامق

// إنشاء تدرج لوني
$steps = $height;
for ($i = 0; $i < $steps; $i++) {
    $r = $start_color[0] + ($end_color[0] - $start_color[0]) * ($i / $steps);
    $g = $start_color[1] + ($end_color[1] - $start_color[1]) * ($i / $steps);
    $b = $start_color[2] + ($end_color[2] - $start_color[2]) * ($i / $steps);
    
    $color = imagecolorallocate($image, $r, $g, $b);
    imagefilledrectangle($image, 0, $i, $width, $i, $color);
}

// إضافة أيقونة بسيطة في الوسط
$white = imagecolorallocate($image, 255, 255, 255);
$icon_size = 80;
$icon_x = ($width - $icon_size) / 2;
$icon_y = ($height - $icon_size) / 2;

// رسم دائرة
imagefilledellipse($image, $width/2, $height/2, $icon_size, $icon_size, $white);

// رسم مربع داخل الدائرة
$box_size = $icon_size * 0.6;
$box_x = ($width - $box_size) / 2;
$box_y = ($height - $box_size) / 2;
$blue = imagecolorallocate($image, 41, 128, 185);
imagefilledrectangle($image, $box_x, $box_y, $box_x + $box_size, $box_y + $box_size, $blue);

// إضافة نص
$text = "صورة المنتج";
$font_size = 5;
$text_width = imagefontwidth($font_size) * mb_strlen($text);
$text_x = ($width - $text_width) / 2;
$text_y = $height/2 + $icon_size/2 + 20;
imagestring($image, $font_size, $text_x, $text_y, $text, $white);

// حفظ الصورة
$filename = $assets_dir . 'product-placeholder.jpg';
imagejpeg($image, $filename, 90);
imagedestroy($image);

echo "تم إنشاء صورة المنتج الافتراضية بنجاح!<br>";
echo "<img src='$filename' style='max-width: 300px; border: 1px solid #ddd; border-radius: 4px;'><br>";
echo "<a href='index.php' class='btn btn-primary mt-3'>العودة إلى الصفحة الرئيسية</a>";
?>
