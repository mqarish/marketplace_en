<?php
require_once '../includes/init.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تصنيفات المتاجر - السوق الإلكتروني</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('../assets/images/pattern.png');
            opacity: 0.1;
        }
        
        .page-header .container {
            position: relative;
            z-index: 1;
        }
        
        .page-header h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-header .lead {
            font-size: 1.25rem;
            opacity: 0.9;
        }
        
        .category-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.4s ease;
            height: 100%;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: none;
        }
        
        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.12);
        }
        
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #0d6efd, #0099ff);
            transition: height 0.3s ease;
        }
        
        .category-card:hover::before {
            height: 7px;
        }
        
        .category-icon {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(13, 110, 253, 0.1);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            transition: all 0.4s ease;
        }
        
        .category-icon i {
            font-size: 2.5rem;
            color: #0d6efd;
            transition: all 0.4s ease;
        }
        
        .category-card:hover .category-icon {
            transform: scale(1.1) rotate(5deg);
            background: rgba(13, 110, 253, 0.15);
        }
        
        .category-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .category-description {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .store-count {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1.25rem;
            background-color: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            border-radius: 50px;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .store-count i {
            margin-left: 0.5rem;
            font-size: 1.1rem;
        }
        
        .category-card:hover .store-count {
            background-color: #0d6efd;
            color: white;
        }
        
        @media (max-width: 768px) {
            .page-header {
                padding: 3rem 0;
            }
            
            .page-header h1 {
                font-size: 2.5rem;
            }
            
            .category-card {
                margin-bottom: 1rem;
                padding: 2rem 1.5rem;
            }
            
            .category-icon {
                width: 70px;
                height: 70px;
            }
            
            .category-icon i {
                font-size: 2rem;
            }
            
            .category-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/customer_navbar.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <h1>تصنيفات المتاجر</h1>
            <p class="lead">اكتشف مجموعة متنوعة من المتاجر المصنفة حسب احتياجاتك</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row g-4">
            <?php
            // مصفوفة التصنيفات
            $categories = [
                [
                    'name' => 'مواد غذائية',
                    'icon' => 'cart4',
                    'description' => 'تسوق أفضل المنتجات الغذائية والمشروبات من متاجرنا المعتمدة',
                    'stores' => 15
                ],
                [
                    'name' => 'مواد تجميلية',
                    'icon' => 'heart',
                    'description' => 'اكتشفي أحدث مستحضرات وأدوات التجميل من أشهر الماركات العالمية',
                    'stores' => 8
                ],
                [
                    'name' => 'ملابس',
                    'icon' => 'handbag',
                    'description' => 'تسوق أحدث صيحات الموضة والأزياء من أفضل المتاجر المحلية والعالمية',
                    'stores' => 12
                ],
                [
                    'name' => 'أكسسوارات',
                    'icon' => 'gem',
                    'description' => 'مجموعة متنوعة من الإكسسوارات والحلي لتكملة إطلالتك',
                    'stores' => 6
                ],
                [
                    'name' => 'مواد بناء',
                    'icon' => 'tools',
                    'description' => 'كل ما تحتاجه من مواد البناء والأدوات من موردين موثوقين',
                    'stores' => 4
                ],
                [
                    'name' => 'الكترونيات',
                    'icon' => 'laptop',
                    'description' => 'أحدث الأجهزة والمعدات الإلكترونية بأفضل الأسعار',
                    'stores' => 10
                ],
                [
                    'name' => 'صيدليات',
                    'icon' => 'capsule',
                    'description' => 'منتجات صيدلانية وطبية من صيدليات معتمدة وموثوقة',
                    'stores' => 7
                ],
                [
                    'name' => 'هدايا',
                    'icon' => 'gift',
                    'description' => 'اختر من تشكيلة واسعة من الهدايا المميزة لجميع المناسبات',
                    'stores' => 5
                ]
            ];

            foreach ($categories as $category) {
                echo '<div class="col-md-6 col-lg-4">
                    <div class="category-card">
                        <div class="category-icon">
                            <i class="bi bi-' . $category['icon'] . '"></i>
                        </div>
                        <h3 class="category-title">' . $category['name'] . '</h3>
                        <p class="category-description">' . $category['description'] . '</p>
                        <span class="store-count">
                            <i class="bi bi-shop"></i>
                            ' . $category['stores'] . ' ' . ($category['stores'] == 1 ? 'متجر' : 'متاجر') . '
                        </span>
                    </div>
                </div>';
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
