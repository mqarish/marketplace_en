<?php
// ملف لإنشاء جدول العروض الخاصة بالموقع
require_once '../includes/init.php';

// إنشاء جدول العروض الخاصة بالموقع إذا لم يكن موجوداً
$sql = "CREATE TABLE IF NOT EXISTS site_offers (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    position INT(11) NOT NULL DEFAULT 4,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "تم إنشاء جدول العروض الخاصة بالموقع بنجاح";
} else {
    echo "خطأ في إنشاء الجدول: " . $conn->error;
}

// إنشاء مجلد الصور إذا لم يكن موجوداً
$upload_dir = '../uploads/banners/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    echo "<br>تم إنشاء مجلد الصور بنجاح";
} else {
    echo "<br>مجلد الصور موجود بالفعل";
}

// إنشاء صور افتراضية
$default_images_dir = '../assets/images/';
if (!file_exists($default_images_dir)) {
    mkdir($default_images_dir, 0777, true);
}

// إنشاء صورة افتراضية للبانر الرئيسي
$default_banner = $default_images_dir . 'default-banner.jpg';
if (!file_exists($default_banner)) {
    // إنشاء صورة بيضاء بحجم 1200x400
    $image = imagecreatetruecolor(1200, 400);
    $bg_color = imagecolorallocate($image, 240, 240, 240);
    $text_color = imagecolorallocate($image, 100, 100, 100);
    imagefill($image, 0, 0, $bg_color);
    
    // إضافة نص
    $text = 'صورة العرض الافتراضية';
    $font = 5; // حجم الخط
    $text_width = imagefontwidth($font) * strlen($text);
    $text_height = imagefontheight($font);
    
    // وضع النص في وسط الصورة
    imagestring($image, $font, (1200 - $text_width) / 2, (400 - $text_height) / 2, $text, $text_color);
    
    // حفظ الصورة
    imagejpeg($image, $default_banner, 90);
    imagedestroy($image);
    
    echo "<br>تم إنشاء صورة البانر الافتراضية";
}

// إنشاء صورة افتراضية للبانرات الصغيرة
$default_small_banner = $default_images_dir . 'default-small-banner.jpg';
if (!file_exists($default_small_banner)) {
    // إنشاء صورة بيضاء بحجم 600x300
    $image = imagecreatetruecolor(600, 300);
    $bg_color = imagecolorallocate($image, 240, 240, 240);
    $text_color = imagecolorallocate($image, 100, 100, 100);
    imagefill($image, 0, 0, $bg_color);
    
    // إضافة نص
    $text = 'صورة العرض الصغيرة الافتراضية';
    $font = 5; // حجم الخط
    $text_width = imagefontwidth($font) * strlen($text);
    $text_height = imagefontheight($font);
    
    // وضع النص في وسط الصورة
    imagestring($image, $font, (600 - $text_width) / 2, (300 - $text_height) / 2, $text, $text_color);
    
    // حفظ الصورة
    imagejpeg($image, $default_small_banner, 90);
    imagedestroy($image);
    
    echo "<br>تم إنشاء صورة البانر الصغيرة الافتراضية";
}

// إضافة بعض العروض الافتراضية
$offers = [
    [
        'title' => 'عروض نهاية الأسبوع',
        'description' => 'خصومات تصل إلى 50% على جميع المنتجات',
        'image_url' => 'assets/images/default-banner.jpg',
        'link' => 'offer.php?id=1',
        'position' => 1,
        'active' => 1
    ],
    [
        'title' => 'منتجات جديدة',
        'description' => 'اكتشف أحدث المنتجات',
        'image_url' => 'assets/images/default-small-banner.jpg',
        'link' => 'offer.php?id=2',
        'position' => 2,
        'active' => 1
    ],
    [
        'title' => 'توصيل مجاني',
        'description' => 'للطلبات أكثر من 200 ريال',
        'image_url' => 'assets/images/default-small-banner.jpg',
        'link' => 'offer.php?id=3',
        'position' => 3,
        'active' => 1
    ]
];

// التحقق من وجود عروض في الجدول
$check_sql = "SELECT COUNT(*) as count FROM site_offers";
$result = $conn->query($check_sql);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // إضافة العروض الافتراضية
    foreach ($offers as $offer) {
        $sql = "INSERT INTO site_offers (title, description, image_url, link, position, active) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $offer['title'], $offer['description'], $offer['image_url'], $offer['link'], $offer['position'], $offer['active']);
        $stmt->execute();
    }
    
    echo "<br>تم إضافة العروض الافتراضية بنجاح";
} else {
    echo "<br>العروض موجودة بالفعل في قاعدة البيانات";
}

echo "<br><br><a href='manage-site-offers.php'>الذهاب إلى صفحة إدارة العروض</a>";
?>
