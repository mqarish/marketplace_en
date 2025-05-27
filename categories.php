<?php
require_once 'includes/init.php';

// جلب جميع التصنيفات
$categories_sql = "SELECT 
    categories.*,
    COUNT(stores.id) as stores_count
FROM categories 
LEFT JOIN stores ON categories.id = stores.category_id 
    AND stores.status = 'active'
GROUP BY categories.id
ORDER BY categories.name";

$categories = $conn->query($categories_sql);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التصنيفات - السوق الإلكتروني</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .category-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }
        
        .category-icon {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }
        
        .category-name {
            color: var(--primary);
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .stores-count {
            color: var(--secondary);
            font-size: 0.9rem;
        }
        
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>

    <div class="container py-5">
        <h1 class="text-center mb-5">تصنيفات المتاجر</h1>

        <div class="row g-4">
            <?php while ($category = $categories->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <a href="index.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                        <div class="card category-card h-100">
                            <div class="card-body text-center">
                                <div class="category-icon">
                                    <?php
                                    $icons = [
                                        'مواد غذائية' => 'bi-cart4',
                                        'ملابس' => 'bi-handbag',
                                        'مواد بناء' => 'bi-building',
                                        'مستحضرات تجميل' => 'bi-gem',
                                        'إلكترونيات' => 'bi-phone',
                                        'أثاث منزلي' => 'bi-house',
                                        'أدوات منزلية' => 'bi-house-door',
                                        'هدايا وإكسسوارات' => 'bi-gift'
                                    ];
                                    $icon = $icons[$category['name']] ?? 'bi-shop';
                                    ?>
                                    <i class="bi <?php echo $icon; ?>"></i>
                                </div>
                                <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                                <p class="stores-count mb-0">
                                    <?php echo $category['stores_count']; ?> متجر
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
