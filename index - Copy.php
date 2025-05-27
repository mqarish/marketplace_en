<?php
session_start();
require_once 'includes/init.php';

// التحقق من تسجيل دخول المستخدم
if (!isset($_SESSION['customer_id'])) {
    header('Location: customer/login.php');
    exit();
}

// تحسين الأداء: تعيين حد لعدد النتائج
$limit = 12;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// معالجة الفلترة
$where_conditions = ["s.status = 'active'"];
$params = [];
$types = "";

// فلترة حسب التصنيف
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $where_conditions[] = "s.category_id = ?";
    $params[] = $_GET['category'];
    $types .= "i";
}

// فلترة حسب البحث
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = "%" . $_GET['search'] . "%";
    $where_conditions[] = "(s.name LIKE ? OR s.description LIKE ? OR p.name LIKE ? OR p.description LIKE ?)";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
    $types .= "ssss";
}

// بناء جملة WHERE
$where_clause = implode(" AND ", $where_conditions);

// بناء الاستعلام الأساسي
$sql = "
    SELECT DISTINCT 
        s.*, 
        c.name as category_name,
        s.city,
        s.address,
        p.id as product_id,
        p.name as product_name,
        p.price as product_price,
        p.description as product_description,
        p.image_url as product_image
    FROM stores s
    LEFT JOIN categories c ON s.category_id = c.id
    LEFT JOIN products p ON p.store_id = s.id AND p.status = 'active'
    WHERE {$where_clause}
";

// إضافة الترتيب
if (isset($_GET['sort'])) {
    switch ($_GET['sort']) {
        case 'price':
            $sql .= " ORDER BY IFNULL(p.price, 999999999) ASC";
            break;
        default:
            $sql .= " ORDER BY s.name ASC";
    }
} else {
    $sql .= " ORDER BY s.name ASC";
}

// إضافة الصفحات
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// تنفيذ الاستعلام
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// الحصول على إجمالي عدد النتائج
$total_results = $result->num_rows;
$total_pages = ceil($total_results / $limit);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>السوق الإلكتروني</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --light: #f8f9fa;
            --dark: #343a40;
        }

        body {
            background-color: var(--light);
        }

        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-form select,
        .filter-form input {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 8px 15px;
            flex: 1;
            min-width: 200px;
        }

        .stores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }

        .store-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }

        .store-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }

        .store-header {
            position: relative;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-align: center;
        }

        .store-logo {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            margin: 0 auto 15px;
            border: 3px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            background-color: white;
            display: block;
        }

        .store-content {
            padding: 20px;
        }

        .store-title {
            font-size: 1.25rem;
            margin-bottom: 10px;
            color: var(--dark);
            text-align: center;
        }

        .store-category {
            display: inline-block;
            padding: 5px 10px;
            background: var(--light);
            border-radius: 5px;
            font-size: 0.9rem;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .store-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-top: 1px solid #eee;
            color: #666;
        }

        .store-stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            color: #666;
        }

        .pagination {
            margin-top: 2rem;
            justify-content: center;
        }

        .pagination .page-link {
            color: var(--primary);
            border-color: var(--primary);
            margin: 0 2px;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .store-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.9);
            color: var(--primary);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            backdrop-filter: blur(5px);
        }

        .search-result {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 15px;
            transition: transform 0.2s;
        }
        
        .search-result:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .store-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .store-location {
            color: #666;
            font-size: 0.9rem;
            margin-right: 10px;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-left: 15px;
        }
        
        .product-details {
            flex-grow: 1;
        }
        
        .product-price {
            font-weight: bold;
            color: #28a745;
            font-size: 1.2rem;
        }
        
        .distance-badge {
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #495057;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="loading" id="loading">
        <div class="loading-spinner"></div>
    </div>

    <div class="container py-5">
        <div class="filter-section">
            <form class="filter-form" method="GET" id="filterForm">
                <select name="category" class="form-select">
                    <option value="">كل التصنيفات</option>
                    <?php
                    $categories = $conn->query("SELECT * FROM categories ORDER BY name");
                    while ($category = $categories->fetch_assoc()):
                    ?>
                    <option value="<?php echo $category['id']; ?>" 
                            <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>

                <select name="sort" class="form-select">
                    <option value="name" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'name') ? 'selected' : ''; ?>>أبجدياً</option>
                    <option value="distance" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'distance') ? 'selected' : ''; ?>>الأقرب</option>
                    <option value="price" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price') ? 'selected' : ''; ?>>السعر: من الأقل للأعلى</option>
                </select>

                <input type="text" name="search" class="form-control" 
                       placeholder="ابحث عن متجر أو منتج..." 
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                
                <input type="number" step="any" name="latitude" class="form-control" 
                       placeholder="خط العرض..." 
                       value="<?php echo isset($_GET['latitude']) ? htmlspecialchars($_GET['latitude']) : ''; ?>">
                
                <input type="number" step="any" name="longitude" class="form-control" 
                       placeholder="خط الطول..." 
                       value="<?php echo isset($_GET['longitude']) ? htmlspecialchars($_GET['longitude']) : ''; ?>">
                
                <button type="submit" class="btn btn-primary">بحث</button>
            </form>
        </div>

        <?php if (isset($_GET['search']) && !empty($_GET['search'])): ?>
            <div class="search-results">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="search-result">
                            <div class="store-info">
                                <h3 class="store-name">
                                    <a href="store.php?id=<?php echo $row['id']; ?>">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </a>
                                </h3>
                                <span class="store-location">
                                    <i class="bi bi-geo-alt"></i>
                                    <?php echo htmlspecialchars($row['city'] . ' - ' . $row['address']); ?>
                                </span>
                            </div>
                            <?php if ($row['product_id']): ?>
                                <div class="product-info">
                                    <?php if ($row['product_image']): ?>
                                        <img src="<?php echo htmlspecialchars($row['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                                             class="product-image">
                                    <?php endif; ?>
                                    <div class="product-details">
                                        <h4><?php echo htmlspecialchars($row['product_name']); ?></h4>
                                        <p><?php echo htmlspecialchars($row['product_description']); ?></p>
                                        <div class="product-price">
                                            <?php echo number_format($row['product_price'], 2); ?> ريال
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        لم يتم العثور على نتائج للبحث عن "<?php echo htmlspecialchars($_GET['search']); ?>"
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- عرض المتاجر بالطريقة العادية -->
            <div class="stores-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while($store = $result->fetch_assoc()): ?>
                        <div class="store-card">
                            <?php if($store['products_count'] > 0): ?>
                                <span class="store-badge">
                                    <i class="fas fa-fire"></i> متجر نشط
                                </span>
                            <?php endif; ?>
                            <div class="store-header">
                                <?php
                                // تحديد مسار الصورة
                                $image_url = $store['image_url'];
                                
                                // التحقق من وجود الصورة
                                if (!empty($image_url) && file_exists($image_url)) {
                                    $display_image = $image_url;
                                } else {
                                    $display_image = 'assets/images/store-placeholder.jpg';
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($display_image); ?>" 
                                     alt="<?php echo htmlspecialchars($store['name']); ?>"
                                     class="store-logo"
                                     onerror="this.src='assets/images/store-placeholder.jpg'"
                                     loading="lazy">
                            </div>
                            <div class="store-content">
                                <h3 class="store-title"><?php echo htmlspecialchars($store['name']); ?></h3>
                                <?php if($store['category_name']): ?>
                                    <div class="text-center">
                                        <span class="store-category">
                                            <i class="fas fa-tag"></i>
                                            <?php echo htmlspecialchars($store['category_name']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="store-stats">
                                    <div class="store-stat">
                                        <i class="fas fa-box"></i>
                                        <span><?php echo $store['products_count']; ?> منتج</span>
                                    </div>
                                    <a href="store.php?id=<?php echo $store['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        عرض المتجر
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-store-slash fa-3x mb-3"></i>
                        <h3>لا توجد متاجر</h3>
                        <p>لم يتم العثور على متاجر تطابق معايير البحث</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- الترقيم -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['category']) ? '&category=' . $_GET['category'] : ''; ?><?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : ''; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['latitude']) ? '&latitude=' . $_GET['latitude'] : ''; ?><?php echo isset($_GET['longitude']) ? '&longitude=' . $_GET['longitude'] : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>