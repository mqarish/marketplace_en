<?php
/**
 * صفحة المنتجات والمتاجر الرئيسية - الصفحة الرئيسية للسوق الإلكتروني
 * 
 * هذه الصفحة تعرض المنتجات أو المتاجر بناءً على اختيار المستخدم مع إمكانية التصفية والبحث
 */

session_start();

// تضمين الملفات المطلوبة
require_once '../includes/init.php';
require_once '../includes/functions.php';

// ===== التحقق من المستخدم =====

// التأكد من تسجيل الدخول
if (!isset($_SESSION['customer_id'])) {
    header('Location: /marketplace/customer/login.php');
    exit();
}

// التحقق من حالة العميل
$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT status FROM customers WHERE id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $customer = $result->fetch_assoc();
    if ($customer['status'] !== 'active') {
        session_destroy();
        header('Location: /marketplace/customer/login.php?error=inactive');
        exit();
    }
} else {
    session_destroy();
    header('Location: /marketplace/customer/login.php?error=invalid');
    exit();
}

// ===== معالجة المعايير =====

// الحصول على معايير البحث والتصفية
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : '';
$current_location = isset($_SESSION['current_location']) ? $_SESSION['current_location'] : '';
$view_type = isset($_GET['view']) ? $_GET['view'] : 'products';

// ===== جلب التصنيفات =====
$categories_sql = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_sql);

// ===== استعلام المنتجات مع العروض =====

/**
 * استعلام المنتجات مع العروض النشطة
 * يقوم باسترجاع المنتجات مع معلومات المتاجر والتصنيفات والعروض السارية
 * ويحسب السعر النهائي بعد الخصم إذا كان هناك عرض نشط
 */
$products_sql = "SELECT 
    p.*, 
    s.name as store_name, 
    s.address as store_address, 
    s.city as store_city,
    c.name as category_name,
    o.id as offer_id, 
    o.title as offer_title, 
    o.discount_percentage, 
    o.start_date,
    o.end_date,
    o.status as offer_status,
    oi.name as offer_item_name,
    oi.price as offer_item_price,
    oi.image_url as offer_item_image,
    CASE 
        WHEN o.id IS NOT NULL 
        AND o.status = 'active'
        AND o.start_date <= CURRENT_DATE()
        AND o.end_date >= CURRENT_DATE()
        THEN ROUND(COALESCE(oi.price, p.price) - (COALESCE(oi.price, p.price) * o.discount_percentage / 100), 2)
        ELSE p.price 
    END as final_price
FROM 
    products p
    INNER JOIN stores s ON p.store_id = s.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN offer_items oi ON p.id = oi.product_id
    LEFT JOIN offers o ON oi.offer_id = o.id 
        AND o.store_id = p.store_id 
        AND o.status = 'active'
        AND o.start_date <= CURRENT_DATE()
        AND o.end_date >= CURRENT_DATE()
WHERE 
    p.status = 'active' 
    AND s.status = 'active'";

// إضافة شروط البحث إذا وجدت
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = '%' . $_GET['search'] . '%';
    $products_sql .= " AND (p.name LIKE ? OR s.name LIKE ? OR c.name LIKE ?)";
}

// إضافة شروط التصنيف إذا وجدت
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $products_sql .= " AND p.category_id = ?";
}

// ترتيب النتائج
$products_sql .= " ORDER BY 
    CASE 
        WHEN o.id IS NOT NULL 
        AND o.status = 'active'
        AND o.start_date <= CURRENT_DATE()
        AND o.end_date >= CURRENT_DATE()
        THEN 0 
        ELSE 1 
    END, 
    p.created_at DESC";

// تحضير وتنفيذ الاستعلام
$stmt = $conn->prepare($products_sql);

if (isset($_GET['search']) && !empty($_GET['search'])) {
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $stmt->bind_param("sssi", $search_term, $search_term, $search_term, $_GET['category']);
    } else {
        $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    }
} elseif (isset($_GET['category']) && !empty($_GET['category'])) {
    $stmt->bind_param("i", $_GET['category']);
}

$stmt->execute();
$products_result = $stmt->get_result();

// ===== استعلام المتاجر =====

/**
 * استعلام المتاجر النشطة مع إحصائيات المنتجات والعروض
 * يقوم باسترجاع المتاجر مع عدد المنتجات وعدد العروض النشطة
 */
$stores_sql = "SELECT 
    s.*, 
    COUNT(DISTINCT p.id) as products_count,
    COUNT(DISTINCT CASE 
        WHEN o.id IS NOT NULL 
        AND o.start_date <= CURDATE()
        AND o.end_date >= CURDATE()
        AND o.status = 'active'
        THEN o.id
    END) as offers_count
FROM 
    stores s
    LEFT JOIN products p ON s.id = p.store_id
    LEFT JOIN offers o ON s.id = o.store_id
        AND o.start_date <= CURDATE()
        AND o.end_date >= CURDATE()
        AND o.status = 'active'";

if (!empty($search)) {
    $stores_sql .= " WHERE s.status = 'active' AND (s.name LIKE ? OR s.city LIKE ? OR p.name LIKE ?)";
} else {
    $stores_sql .= " WHERE s.status = 'active'";
}

$stores_sql .= " GROUP BY s.id ORDER BY offers_count DESC, s.created_at DESC";

$stores_stmt = $conn->prepare($stores_sql);

if ($stores_stmt === false) {
    die('خطأ في إعداد استعلام المتاجر: ' . $conn->error);
}

// تحضير المعاملات لاستعلام المتاجر
$store_params = [];
$store_types = '';

if (!empty($search)) {
    $store_params[] = "%$search%";
    $store_params[] = "%$search%";
    $store_params[] = "%$search%";
    $store_types .= 'sss';
}

if (!empty($store_params)) {
    $stores_stmt->bind_param($store_types, ...$store_params);
}

// تنفيذ الاستعلام
if (!$stores_stmt->execute()) {
    die('خطأ في تنفيذ استعلام المتاجر: ' . $stores_stmt->error);
}

$stores_result = $stores_stmt->get_result();

if ($stores_result === false) {
    die('خطأ في الحصول على نتائج المتاجر: ' . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="السوق الإلكتروني - تسوق أفضل المنتجات من مختلف المتاجر">
    <title>السوق الإلكتروني | استكشف آلاف المنتجات والمتاجر</title>
    
    <!-- Bootstrap RTL & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    
    <!-- Google Fonts - Tajawal -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles/marketplace-modern.css">
    <link rel="stylesheet" href="styles/shopify-header.css">
    
    <style>
    :root {
        --primary: #008060;
        --primary-hover: #004c3f;
        --secondary: #3d3d3d;
        --secondary-light: #6d7175;
        --light-bg: #f6f6f7;
        --border-color: #e1e3e5;
        --text-color: #212326;
        --text-light: #6d7175;
    }
    
    body {
        font-family: 'Tajawal', sans-serif;
        color: var(--text-color);
        background-color: #f9f9f9;
    }
    
    /* صفحة المنتجات */
    .page-header {
        background-color: var(--light-bg);
        padding: 2rem 0 1rem;
        margin-bottom: 2rem;
        border-bottom: 1px solid var(--border-color);
    }
    
    .search-form {
        max-width: 800px;
        margin: 0 auto 1.5rem;
    }
    
    .search-input {
        border-top-right-radius: var(--shopify-radius) !important;
        border-bottom-right-radius: var(--shopify-radius) !important;
        border: 1px solid var(--border-color);
        padding: 0.75rem 1rem;
        box-shadow: none !important;
    }
    
    .search-select {
        border: 1px solid var(--border-color);
        border-right: none;
        border-left: none;
        padding: 0.75rem 1rem;
        box-shadow: none !important;
    }
    
    .search-button {
        border-top-left-radius: var(--shopify-radius) !important;
        border-bottom-left-radius: var(--shopify-radius) !important;
        background-color: var(--primary);
        border: 1px solid var(--primary);
        color: white;
        padding: 0.75rem 1.5rem;
    }
    
    .search-button:hover {
        background-color: var(--primary-hover);
        border-color: var(--primary-hover);
        color: white;
    }
    
    .location-button {
        display: inline-block;
        margin-top: 0.5rem;
        color: var(--text-light);
    }
    
    .location-button i {
        margin-left: 0.3rem;
    }
    
    .product-card {
        height: 100%;
        border-radius: var(--shopify-radius);
        border: 1px solid var(--border-color);
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: var(--shopify-shadow);
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shopify-shadow-hover);
    }
    
    .store-card {
        height: 100%;
        border-radius: var(--shopify-radius);
        border: 1px solid var(--border-color);
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: var(--shopify-shadow);
    }
    
    .store-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shopify-shadow-hover);
    }
    
    .card-img-top {
        height: 200px;
        object-fit: cover;
    }
    
    .offer-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        z-index: 2;
    }
    
    .section-title {
        position: relative;
        margin-bottom: 2rem;
        padding-bottom: 0.5rem;
        font-weight: 700;
        color: var(--text-color);
    }
    
    .section-title:after {
        content: '';
        position: absolute;
        bottom: 0;
        right: 0;
        width: 50px;
        height: 3px;
        background-color: var(--primary);
    }
    
    .badge-primary {
        background-color: var(--primary);
        color: white;
    }
    
    @media (max-width: 768px) {
        .page-header {
            padding: 1rem 0;
        }
    }
    </style>
    <!-- إضافة ملف CSS الخارجي -->
    <link rel="stylesheet" href="styles/marketplace-modern.css">
</head>
<body>
    <!-- استدعاء الهيدر الداكن الجديد -->
    <?php 
    $root_path = '../';
    include '../includes/dark_header.php'; 
    ?>

    <!-- أنماط CSS للقسم الرئيسي -->
    <style>
        /* تعديلات عامة */
        .hero-section {
            padding: 50px 0;
            background-color: #000000;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255, 122, 0, 0.2) 0%, rgba(0, 0, 0, 0) 70%);
            pointer-events: none;
        }
        
        .hero-title {
            text-align: center;
            margin-bottom: 40px;
            color: #ffffff;
        }
        
        .hero-title h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(255, 122, 0, 0.3);
        }
        
        .hero-title p {
        }
        
        .slide-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #FF7A00;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(255, 122, 0, 0.3);
        }
        
        .slide-btn:hover {
            background-color: #E56E00;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 122, 0, 0.4);
            color: white;
        }
        
        /* بطاقات المميزات */
        .feature-cards {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .feature-card {
            flex: 1;
            min-width: 300px;
            height: 350px;
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .card-bg {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-size: cover;
            background-position: center;
            filter: brightness(0.7);
            transition: all 0.5s ease;
        }
        
        .feature-card:hover .card-bg {
            transform: scale(1.1);
            filter: brightness(0.6);
        }
        
        .card-1 .card-bg {
            background-image: url('../assets/images/electronics-bg.jpg');
        }
        
        .card-2 .card-bg {
            background-image: url('../assets/images/fashion-bg.jpg');
        }
        
        .card-3 .card-bg {
            background-image: url('../assets/images/home-bg.jpg');
        }
        
        .card-content {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 25px;
            color: white;
            background: linear-gradient(0deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0) 100%);
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            background-color: #FF7A00;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
            box-shadow: 0 4px 12px rgba(255, 122, 0, 0.3);
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .card-description {
            font-size: 0.95rem;
            margin-bottom: 15px;
            opacity: 0.9;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        
        .card-link {
            color: #FF7A00;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .card-link:hover {
            color: #FFA94D;
            gap: 8px;
        }
        
        /* تحسينات للموبايل */
        @media (max-width: 992px) {
            .slide-item {
                height: 350px;
            }
            
            .slide-content {
                padding: 30px;
            }
            
            .slide-title {
                font-size: 2rem;
            }
            
            .feature-cards {
                flex-wrap: wrap;
            }
            
            .feature-card {
                min-width: calc(50% - 10px);
                height: 300px;
            }
            
            .product-card .card-title {
                font-size: 1rem;
            }
            
            .product-card .product-price {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 30px 0;
            }
            
            .slide-item {
                height: 300px;
                padding: 0 10px;
            }
            
            .slide-content {
                padding: 20px;
                background: linear-gradient(90deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.5) 70%, rgba(0,0,0,0.2) 100%);
            }
            
            .slide-title {
                font-size: 1.5rem;
                margin-bottom: 10px;
            }
            
            .slide-description {
                font-size: 0.9rem;
                margin-bottom: 15px;
            }
            
            .slide-btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            
            .feature-card {
                min-width: calc(50% - 10px);
                height: 280px;
                margin-bottom: 15px;
            }
            
            .card-content {
                padding: 15px;
            }
            
            .card-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
                margin-bottom: 10px;
            }
            
            .card-title {
                font-size: 1.2rem;
            }
            
            .card-description {
                font-size: 0.85rem;
                margin-bottom: 10px;
            }
            
            /* تحسين عرض المنتجات */
            .product-card {
                margin-bottom: 15px;
            }
            
            .product-card .card-img-top {
                height: 180px;
            }
        }
        
        @media (max-width: 576px) {
            .hero-section {
                padding: 15px 0;
            }
            
            /* تحسينات خاصة بالموبايل فقط */
            .hero-title h1 {
                font-size: 1.3rem !important;
                line-height: 1.4 !important;
                margin-bottom: 8px !important;
            }
            
            .hero-title p {
                font-size: 0.85rem !important;
                line-height: 1.5 !important;
                margin-bottom: 15px !important;
            }
            
            .feature-card {
                height: 180px !important;
                margin-bottom: 15px !important;
            }
            
            .feature-card .card-content {
                padding: 15px 10px !important;
            }
            
            .feature-card .card-icon {
                font-size: 24px !important;
                margin-bottom: 8px !important;
            }
            
            .feature-card .card-title {
                font-size: 0.95rem !important;
                margin-bottom: 5px !important;
            }
            
            .feature-card .card-text {
                font-size: 0.8rem !important;
                line-height: 1.3 !important;
                margin-bottom: 8px !important;
            }
            
            .feature-card .card-btn {
                font-size: 0.8rem !important;
                padding: 5px 10px !important;
            }
            
            /* تحسينات بطاقات المنتجات */
            .product-card {
                border-radius: 8px !important;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1) !important;
            }
            
            .product-card .card-img-top {
                height: 120px !important;
                object-fit: cover !important;
                border-top-left-radius: 8px !important;
                border-top-right-radius: 8px !important;
            }
            
            .product-card .card-body {
                padding: 10px !important;
            }
            
            .product-card .card-title {
                font-size: 0.9rem !important;
                height: 40px !important;
                overflow: hidden !important;
                display: -webkit-box !important;
                -webkit-line-clamp: 2 !important;
                -webkit-box-orient: vertical !important;
            }
            
            .product-card .btn {
                font-size: 0.75rem !important;
                padding: 4px 8px !important;
                border-radius: 4px !important;
            }
            
            .slide-item {
                height: 180px;
            }
            
            .slide-content {
                padding: 12px;
                width: 100%;
                background: linear-gradient(90deg, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.7) 50%, rgba(0,0,0,0.6) 100%);
            }
            
            .slide-title {
                font-size: 1rem;
                margin-bottom: 4px;
                line-height: 1.3;
                font-weight: 700;
            }
            
            .slide-description {
                font-size: 0.75rem;
                margin-bottom: 8px;
                max-width: 100%;
                line-height: 1.4;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            
            .slide-btn {
                padding: 5px 10px;
                font-size: 0.75rem;
                border-radius: 4px;
            }
            
            .feature-cards {
                gap: 8px;
            }
            
            .feature-card {
                min-width: 100%;
                height: 150px;
                margin-bottom: 8px;
                border-radius: 8px;
            }
            
            .card-icon {
                width: 32px;
                height: 32px;
                font-size: 14px;
                margin-bottom: 6px;
            }
            
            .card-title {
                font-size: 0.9rem;
                margin-bottom: 4px;
                font-weight: 700;
            }
            
            .card-description {
                font-size: 0.7rem;
                margin-bottom: 6px;
                line-height: 1.4;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
            
            /* تحسين عرض المنتجات للموبايل الصغير */
            .product-card .card-img-top {
                height: 120px;
                object-fit: cover;
            }
            
            .product-card .card-body {
                padding: 8px;
            }
            
            .product-card .card-title {
                font-size: 0.8rem;
                margin-bottom: 4px;
                font-weight: 600;
                line-height: 1.3;
                height: 2.6rem;
                overflow: hidden;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }
            
            .product-card .product-price {
                font-size: 0.85rem;
            }
            
            .store-card .store-logo {
                width: 50px;
                height: 50px;
            }
            
            .store-card .store-name {
                font-size: 1rem;
            }
            
            .store-card .store-meta {
                font-size: 0.8rem;
            }
        }
        
        /* تحسينات للشاشات الصغيرة جداً */
        @media (max-width: 400px) {
            .slide-item {
                height: 180px;
            }
            
            .slide-title {
                font-size: 1rem;
            }
            
            .slide-description {
                font-size: 0.75rem;
                margin-bottom: 8px;
            }
            
            .feature-card {
                height: 180px;
            }
            
            .product-card .card-img-top {
                height: 120px;
            }
        }
    </style>

    <!-- قسم العنوان وتحديد الموقع -->
    <section class="hero-section">
        <div class="container">
            <!-- سلايدر متحرك باستخدام Slick Slider -->
            <div class="hero-slider mb-5">
                <div class="slick-hero-slider">
                    <div class="slide-item">
                        <img src="images/products/th (1).jpg" alt="أحدث المنتجات التقنية">
                        <div class="slide-overlay"></div>
                        <div class="slide-caption">
                            <h2>أحدث المنتجات التقنية</h2>
                            <p>اكتشف أحدث الأجهزة والإلكترونيات بأسعار تنافسية وجودة عالية</p>
                            <a href="index.php?view=products&category=electronics" class="btn btn-primary">تسوق الآن</a>
                        </div>
                    </div>
                    <div class="slide-item">
                        <img src="images/products/th (2).jpg" alt="عروض حصرية">
                        <div class="slide-overlay"></div>
                        <div class="slide-caption">
                            <h2>عروض حصرية لفترة محدودة</h2>
                            <p>خصومات تصل إلى 50% على تشكيلة واسعة من المنتجات المميزة</p>
                            <a href="index.php?view=products&sort=offers" class="btn btn-primary">تصفح العروض</a>
                        </div>
                    </div>
                    <div class="slide-item">
                        <img src="images/products/th (3).jpg" alt="تسوق من أفضل المتاجر">
                        <div class="slide-overlay"></div>
                        <div class="slide-caption">
                            <h2>تسوق من أفضل المتاجر</h2>
                            <p>اكتشف مجموعة متنوعة من المتاجر المميزة وتسوق بثقة وأمان</p>
                            <a href="index.php?view=stores" class="btn btn-primary">زيارة المتاجر</a>
                        </div>
                    </div>
                    <div class="slide-item">
                        <img src="images/products/th (4).jpg" alt="توصيل سريع">
                        <div class="slide-overlay"></div>
                        <div class="slide-caption">
                            <h2>توصيل سريع لجميع الطلبات</h2>
                            <p>نوصل طلبك بسرعة وأمان إلى باب منزلك مع إمكانية تتبع الطلب</p>
                            <a href="index.php?view=products" class="btn btn-primary">اطلب الآن</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="hero-title">
                <h1 style="font-size: 1.3rem; line-height: 1.4; margin-bottom: 8px;">السوق الإلكتروني - تجربة تسوق فريدة</h1>
                <p style="font-size: 0.85rem; line-height: 1.5; margin-bottom: 15px;">اكتشف مجموعة متنوعة من المنتجات والمتاجر المميزة بأسعار تنافسية وخدمة ممتازة</p>
            </div>
            
            <div class="feature-cards">
                <div class="feature-card card-1" style="height: 180px; margin-bottom: 15px;">
                    <div class="card-bg"></div>
                    <div class="card-content" style="padding: 15px 10px;">
                        <div class="card-icon" style="font-size: 24px; margin-bottom: 8px;">
                            <i class="bi bi-percent"></i>
                        </div>
                        <h3 class="card-title" style="font-size: 0.95rem; margin-bottom: 5px;">عروض حصرية</h3>
                        <p class="card-text" style="font-size: 0.8rem; line-height: 1.3; margin-bottom: 8px;">تسوق الآن واحصل على خصومات تصل إلى 50% على مجموعة مختارة من المنتجات</p>
                        <a href="index.php?view=products" class="card-btn" style="font-size: 0.8rem; padding: 5px 10px;">تسوق الآن</a>
                    </div>
                </div>
                
                <div class="feature-card card-2" style="height: 180px; margin-bottom: 15px;">
                    <div class="card-bg"></div>
                    <div class="card-content" style="padding: 15px 10px;">
                        <div class="card-icon" style="font-size: 24px; margin-bottom: 8px;">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <h3 class="card-title" style="font-size: 0.95rem; margin-bottom: 5px;">منتجات جديدة</h3>
                        <p class="card-text" style="font-size: 0.8rem; line-height: 1.3; margin-bottom: 8px;">اكتشف أحدث المنتجات في السوق الإلكتروني من مختلف الفئات والماركات</p>
                        <a href="index.php?view=products&sort=newest" class="card-btn" style="font-size: 0.8rem; padding: 5px 10px;">استكشف</a>
                    </div>
                </div>
                
                <div class="feature-card card-3" style="height: 180px; margin-bottom: 15px;">
                    <div class="card-bg"></div>
                    <div class="card-content" style="padding: 15px 10px;">
                        <div class="card-icon" style="font-size: 24px; margin-bottom: 8px;">
                            <i class="bi bi-shop"></i>
                        </div>
                        <h3 class="card-title" style="font-size: 0.95rem; margin-bottom: 5px;">متاجر مميزة</h3>
                        <p class="card-text" style="font-size: 0.8rem; line-height: 1.3; margin-bottom: 8px;">تصفح أفضل المتاجر في السوق الإلكتروني واستمتع بتجربة تسوق فريدة</p>
                        <a href="index.php?view=stores" class="card-btn" style="font-size: 0.8rem; padding: 5px 10px;">زيارة المتاجر</a>
                    </div>
                </div>
                
                <div class="feature-card card-4" style="height: 180px; margin-bottom: 15px;">
                    <div class="card-bg"></div>
                    <div class="card-content" style="padding: 15px 10px;">
                        <div class="card-icon" style="font-size: 24px; margin-bottom: 8px;">
                            <i class="bi bi-truck"></i>
                        </div>
                        <h3 class="card-title" style="font-size: 0.95rem; margin-bottom: 5px;">توصيل سريع</h3>
                        <p class="card-text" style="font-size: 0.8rem; line-height: 1.3; margin-bottom: 8px;">نوصل طلبك بسرعة وأمان إلى باب منزلك مع إمكانية تتبع الطلب</p>
                        <a href="#" class="card-btn" style="font-size: 0.8rem; padding: 5px 10px;">معرفة المزيد</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
   
    </section>
            

    </section>

    <!-- المحتوى الرئيسي -->
    <div class="container py-4">
        <?php if ($view_type === 'products'): ?>
            <!-- عرض المنتجات -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <h2 class="section-title">
                        <?php echo ($view_type === 'products') ? 'المنتجات المتاحة' : 'المتاجر المتاحة'; ?>
                        <?php if (!empty($search)): ?>
                            للبحث "<?php echo htmlspecialchars($search); ?>"
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="d-flex justify-content-lg-end align-items-center">
                        <span class="text-muted ms-2">
                            <i class="bi bi-grid-3x3-gap-fill"></i>
                            <?php echo $products_result->num_rows; ?> منتج
                        </span>
                        <div class="btn-group ms-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary active" id="grid-view">
                                <i class="bi bi-grid-3x3"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="list-view">
                                <i class="bi bi-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($products_result->num_rows > 0): ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4" id="products-grid">
                    <?php while ($product = $products_result->fetch_assoc()): ?>
                        <div class="col mb-4">
                            <div class="card h-100 product-card" style="border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                                <a href="product-details.php?id=<?php echo $product['id']; ?>" class="product-link">
                                    <?php if (!empty($product['offer_id'])): ?>
                                        <div class="offer-badge" style="top: 5px; right: 5px;">
                                            <span class="badge bg-danger" style="font-size: 0.7rem; padding: 3px 6px;">
                                                <?php echo $product['discount_percentage']; ?>% خصم
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                            class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 120px; object-fit: cover; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 120px; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                                            <i class="bi bi-image text-secondary" style="font-size: 2.5rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                
                                <div class="card-body" style="padding: 10px;">
                                    <h5 class="card-title mb-2" style="font-size: 0.9rem; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </h5>
                                    
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <a href="store-page.php?id=<?php echo $product['store_id']; ?>" class="text-decoration-none text-muted small">
                                            <i class="bi bi-shop ms-1"></i> <?php echo htmlspecialchars($product['store_name']); ?>
                                        </a>
                                        
                                        <?php if (!empty($product['category_name'])): ?>
                                            <span class="badge bg-light text-secondary small">
                                                <?php echo htmlspecialchars($product['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-price mb-3">
                                        <?php if (!empty($product['offer_id'])): ?>
                                            <span class="fw-bold text-danger">
                                                <?php echo number_format($product['final_price'], 2); ?> ريال
                                            </span>
                                            <br>
                                            <small class="text-decoration-line-through text-muted">
                                                <?php echo number_format($product['price'], 2); ?> ريال
                                            </small>
                                        <?php else: ?>
                                            <span class="fw-bold">
                                                <?php echo number_format($product['price'], 2); ?> ريال
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary" style="font-size: 0.75rem; padding: 4px 8px; border-radius: 4px;">
                                            <i class="bi bi-eye"></i> عرض
                                        </a>
                                        
                                        <button class="btn btn-sm btn-outline-primary add-to-cart" 
                                                data-product-id="<?php echo $product['id']; ?>" style="font-size: 0.75rem; padding: 4px 8px; border-radius: 4px;">
                                            <i class="bi bi-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- عرض القائمة (سيتم إخفاؤه بشكل افتراضي) -->
                <div class="products-list" id="products-list" style="display: none;">
                    <?php 
                    // إعادة مؤشر النتائج إلى البداية
                    if ($products_result->num_rows > 0) {
                        $products_result->data_seek(0);
                        while ($product = $products_result->fetch_assoc()): 
                    ?>
                        <div class="card mb-3 product-list-item">
                            <div class="row g-0">
                                <div class="col-md-3">
                                    <a href="product-details.php?id=<?php echo $product['id']; ?>">
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                class="img-fluid rounded-start h-100" style="object-fit: cover;" 
                                                alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center h-100" style="min-height: 200px;">
                                                <i class="bi bi-image text-secondary" style="font-size: 3rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                                <div class="col-md-9">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="card-title mb-2">
                                                <a href="product-details.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </a>
                                            </h5>
                                            
                                            <?php if (!empty($product['offer_id'])): ?>
                                                <span class="badge bg-danger ms-2">
                                                    <?php echo $product['discount_percentage']; ?>% خصم
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="d-flex mb-2">
                                            <a href="store-page.php?id=<?php echo $product['store_id']; ?>" class="text-decoration-none text-muted small me-3">
                                                <i class="bi bi-shop ms-1"></i> <?php echo htmlspecialchars($product['store_name']); ?>
                                            </a>
                                            
                                            <?php if (!empty($product['category_name'])): ?>
                                                <span class="badge bg-light text-secondary small">
                                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($product['description'])): ?>
                                            <p class="card-text small mb-3">
                                                <?php echo mb_substr(htmlspecialchars($product['description']), 0, 150) . '...'; ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="product-price">
                                                <?php if (!empty($product['offer_id'])): ?>
                                                    <span class="fw-bold text-danger">
                                                        <?php echo number_format($product['final_price'], 2); ?> ريال
                                                    </span>
                                                    <span class="text-decoration-line-through text-muted ms-2">
                                                        <?php echo number_format($product['price'], 2); ?> ريال
                                                    </span>
                                                <?php else: ?>
                                                    <span class="fw-bold">
                                                        <?php echo number_format($product['price'], 2); ?> ريال
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div>
                                                <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary me-2">
                                                    <i class="bi bi-eye me-1"></i> عرض التفاصيل
                                                </a>
                                                
                                                <button class="btn btn-sm btn-outline-primary add-to-cart" 
                                                        data-product-id="<?php echo $product['id']; ?>">
                                                    <i class="bi bi-cart-plus me-1"></i> إضافة للسلة
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="alert alert-custom py-4 text-center">
                    <i class="bi bi-exclamation-circle fs-1 d-block mb-3 text-muted"></i>
                    <h4 class="alert-heading">لا توجد منتجات متاحة</h4>
                    <p class="mb-0">لم يتم العثور على أي منتجات مطابقة لمعايير البحث الحالية.</p>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-outline-primary">عرض جميع المنتجات</a>
                    </div>
                </div>
                
                <!-- اقتراحات للمستخدم -->
                <div class="suggestions-section mt-5">
                    <h3 class="section-title mb-4">اقتراحات قد تهمك</h3>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">تصفح حسب الفئات</h5>
                                    <p class="card-text">استكشف منتجاتنا من خلال التصنيفات المختلفة.</p>
                                    
                                    <div class="categories-suggestions">
                                        <?php 
                                        // إعادة مؤشر نتائج التصنيفات
                                        if ($categories_result) {
                                            $categories_result->data_seek(0);
                                            $count = 0;
                                            while ($category = $categories_result->fetch_assoc()) {
                                                if ($count++ < 6) { // عرض فقط 6 فئات
                                                    echo '<a href="index.php?category=' . $category['id'] . '&view=products" class="btn btn-sm btn-outline-secondary m-1">' . 
                                                    htmlspecialchars($category['name']) . '</a>';
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">استكشف المتاجر</h5>
                                    <p class="card-text">تعرف على متاجرنا المتنوعة والمنتجات التي يقدمونها.</p>
                                    
                                    <a href="index.php?view=stores" class="btn btn-primary">عرض جميع المتاجر</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
                
        <?php else: ?>
            <!-- عرض المتاجر -->
            <div class="row mb-4">
                <div class="col">
                    <h2 class="section-title">المتاجر المتاحة
                        <?php if (!empty($search)): ?>
                            للبحث "<?php echo htmlspecialchars($search); ?>"
                        <?php endif; ?>
                        <span class="fs-6 text-muted ms-2">(<?php echo $stores_result->num_rows; ?> متجر)</span>
                    </h2>
                </div>
            </div>
            
            <?php if ($stores_result->num_rows > 0): ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php while ($store = $stores_result->fetch_assoc()): ?>
                        <div class="col">
                            <div class="card store-card h-100">
                                <a href="store-page.php?id=<?php echo $store['id']; ?>" class="text-decoration-none">
                                    <?php if (!empty($store['logo'])): ?>
                                        <img src="../uploads/stores/<?php echo htmlspecialchars($store['logo']); ?>" 
                                            class="card-img-top" alt="<?php echo htmlspecialchars($store['name']); ?>"
                                            style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                            style="height: 200px;">
                                            <i class="bi bi-shop text-secondary" style="font-size: 4rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="store-page.php?id=<?php echo $store['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($store['name']); ?>
                                        </a>
                                    </h5>
                                    
                                    <?php if (!empty($store['address']) || !empty($store['city'])): ?>
                                        <p class="card-text text-muted small mb-2">
                                            <i class="bi bi-geo-alt"></i>
                                            <?php 
                                            if (!empty($store['address'])) {
                                                echo htmlspecialchars($store['address']);
                                                if (!empty($store['city'])) {
                                                    echo '، ' . htmlspecialchars($store['city']);
                                                }
                                            } elseif (!empty($store['city'])) {
                                                echo htmlspecialchars($store['city']);
                                            }
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($store['description'])): ?>
                                        <p class="card-text small">
                                            <?php echo mb_substr(htmlspecialchars($store['description']), 0, 120) . '...'; ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <span class="badge bg-light text-primary">
                                                <?php echo $store['products_count']; ?> منتج
                                            </span>
                                            <?php if ($store['offers_count'] > 0): ?>
                                                <span class="badge bg-danger ms-1">
                                                    <?php echo $store['offers_count']; ?> عرض نشط
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="store-page.php?id=<?php echo $store['id']; ?>" 
                                        class="btn btn-sm btn-primary">
                                            <i class="bi bi-shop ms-1"></i> زيارة المتجر
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-custom py-4 text-center">
                    <i class="bi bi-shop-window fs-1 d-block mb-3 text-muted"></i>
                    <h4 class="alert-heading">لا توجد متاجر متاحة</h4>
                    <p class="mb-0">لم يتم العثور على أي متاجر مطابقة لمعايير البحث الحالية.</p>
                    <div class="mt-3">
                        <a href="index.php?view=stores" class="btn btn-outline-primary">عرض جميع المتاجر</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- تضمين مكتبات JavaScript اللازمة -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- مكتبة Slick Slider -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    <!-- ملف JavaScript للهيدر الداكن -->
    <script src="js/dark-header.js"></script>
    
    <script>
    // إزالة أيقونة العربة المكررة
    document.addEventListener('DOMContentLoaded', function() {
        // التحقق من وجود أيقونات العربة المكررة وإزالتها
        const cartIcons = document.querySelectorAll('.cart-btn');
        if (cartIcons.length > 1) {
            // الاحتفاظ بالأيقونة الأولى فقط وإزالة الباقي
            for (let i = 1; i < cartIcons.length; i++) {
                cartIcons[i].remove();
            }
        }
        
        // تفعيل وظائف التبديل بين عرض الشبكة والقائمة
        const gridViewBtn = document.getElementById('grid-view');
        const listViewBtn = document.getElementById('list-view');
        const productsGrid = document.getElementById('products-grid');
        const productsList = document.getElementById('products-list');
        
        if (gridViewBtn && listViewBtn && productsGrid && productsList) {
            // تبديل إلى عرض الشبكة
            gridViewBtn.addEventListener('click', function() {
                productsGrid.style.display = 'flex';
                productsList.style.display = 'none';
                gridViewBtn.classList.add('active');
                listViewBtn.classList.remove('active');
                // حفظ تفضيل المستخدم
                localStorage.setItem('productViewMode', 'grid');
            });
            
            // تبديل إلى عرض القائمة
            listViewBtn.addEventListener('click', function() {
                productsGrid.style.display = 'none';
                productsList.style.display = 'block';
                listViewBtn.classList.add('active');
                gridViewBtn.classList.remove('active');
                // حفظ تفضيل المستخدم
                localStorage.setItem('productViewMode', 'list');
            });
            
            // استرجاع تفضيل المستخدم المحفوظ إن وجد
            const savedViewMode = localStorage.getItem('productViewMode');
            if (savedViewMode === 'list') {
                listViewBtn.click();
            }
        }
        
        // تفعيل أزرار إضافة للسلة
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                addProductToCart(productId);
            });
        });
    });
    
    // إضافة منتج للسلة
    function addProductToCart(productId) {
        // يمكن هنا إضافة AJAX لإضافة المنتج للسلة
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&quantity=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // عرض رسالة نجاح
                alert('تمت إضافة المنتج إلى سلة التسوق بنجاح!');
            } else {
                // عرض رسالة خطأ
                alert(data.message || 'حدث خطأ أثناء إضافة المنتج إلى سلة التسوق');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء إضافة المنتج إلى سلة التسوق');
        });
    }
    
    // تحديد الموقع
    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const location = position.coords.latitude + ',' + position.coords.longitude;
                // تحويل العرض تلقائياً إلى المتاجر عند تحديد الموقع
                window.location.href = 'set_location.php?location=' + encodeURIComponent(location) + '&view=stores';
            }, function(error) {
                alert('عذراً، لم نتمكن من تحديد موقعك. الرجاء المحاولة مرة أخرى.');
            });
        } else {
            alert('عذراً، متصفحك لا يدعم تحديد الموقع.');
        }
    }

    // إلغاء تحديد الموقع
    function clearLocation() {
        window.location.href = 'set_location.php?clear=1';
    }

    // فتح خريطة جوجل بالعنوان
    function openMap(address) {
        if (address) {
            const mapUrl = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(address);
            window.open(mapUrl, '_blank');
        }
    }
    
    // تفعيل سلايدر Slick
    $(document).ready(function(){
        $('.slick-hero-slider').slick({
            rtl: true,
            dots: true,
            infinite: true,
            speed: 500,
            fade: true,
            cssEase: 'linear',
            autoplay: true,
            autoplaySpeed: 5000,
            arrows: true,
            adaptiveHeight: true,
            prevArrow: '<button type="button" class="slick-prev"><i class="fas fa-chevron-right"></i></button>',
            nextArrow: '<button type="button" class="slick-next"><i class="fas fa-chevron-left"></i></button>'
        });
    });
    </script>
</body>
</html>