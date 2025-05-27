<?php
/**
 * صفحة عرض قائمة المتاجر
 * تعرض جميع المتاجر المتاحة مع إمكانية التصفية والبحث
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once('../includes/config.php');
require_once('../includes/functions.php');

// تعيين عنوان الصفحة
$page_title = "تصفح المتاجر";
$root_path = "../";

// تحديد عدد المتاجر في كل صفحة
$stores_per_page = 12;

// تحديد رقم الصفحة الحالية
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max(1, $page);
$offset = ($page - 1) * $stores_per_page;

// بناء استعلام المتاجر بنفس الطريقة المستخدمة في index.php
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

// إضافة شروط البحث والتصفية
$where_conditions = ["s.status = 'active'"];
$params = [];
$param_types = "";

// البحث حسب الاسم
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $where_conditions[] = "(s.name LIKE ? OR s.city LIKE ? OR p.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= "sss";
}

// التصفية حسب المدينة
if (isset($_GET['city']) && !empty($_GET['city'])) {
    $city = $_GET['city'];
    $where_conditions[] = "s.city = ?";
    $params[] = $city;
    $param_types .= "s";
}

// التصفية حسب التقييم - معلقة لأن عمود rating غير موجود
/*
if (isset($_GET['rating']) && !empty($_GET['rating'])) {
    $rating = intval($_GET['rating']);
    $where_conditions[] = "s.rating >= ?";
    $params[] = $rating;
    $param_types .= "i";
}
*/

// بناء جملة WHERE
$where_clause = implode(" AND ", $where_conditions);
$stores_sql .= " WHERE " . $where_clause;

// إضافة GROUP BY والترتيب
$stores_sql .= " GROUP BY s.id";

// تحديد الترتيب
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'rating';

switch ($sort) {
    case 'name_asc':
        $stores_sql .= " ORDER BY s.name ASC";
        break;
    case 'name_desc':
        $stores_sql .= " ORDER BY s.name DESC";
        break;
    case 'newest':
        $stores_sql .= " ORDER BY s.created_at DESC";
        break;
    case 'rating':
    default:
        $stores_sql .= " ORDER BY offers_count DESC, s.created_at DESC";
        break;
}

// إضافة حدود الصفحة
$stores_sql .= " LIMIT ? OFFSET ?";

// تحضير الاستعلام
$stores_stmt = $conn->prepare($stores_sql);

if ($stores_stmt === false) {
    die('خطأ في إعداد استعلام المتاجر: ' . $conn->error);
}

// إضافة معاملات الحدود والإزاحة
$params[] = $stores_per_page;
$params[] = $offset;
$param_types .= "ii";

// ربط المعاملات
$stores_stmt->bind_param($param_types, ...$params);

// تنفيذ الاستعلام
if (!$stores_stmt->execute()) {
    die('خطأ في تنفيذ استعلام المتاجر: ' . $stores_stmt->error);
}

// الحصول على النتائج
$stores_result = $stores_stmt->get_result();

// حساب إجمالي عدد المتاجر للترقيم
$count_sql = "SELECT COUNT(DISTINCT s.id) as total FROM stores s LEFT JOIN products p ON s.id = p.store_id WHERE " . $where_clause;
$count_stmt = $conn->prepare($count_sql);

if ($count_stmt === false) {
    die('خطأ في إعداد استعلام عدد المتاجر: ' . $conn->error);
}

// ربط معاملات البحث والتصفية فقط (بدون معاملات الحدود والإزاحة)
$count_params = array_slice($params, 0, -2);
$count_types = substr($param_types, 0, -2);

if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}

// تنفيذ استعلام العدد
if (!$count_stmt->execute()) {
    die('خطأ في تنفيذ استعلام عدد المتاجر: ' . $count_stmt->error);
}

$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_stores = $count_row['total'];
$total_pages = ceil($total_stores / $stores_per_page);

// تحويل نتائج المتاجر إلى مصفوفة
$stores = [];
while ($store = $stores_result->fetch_assoc()) {
    $stores[] = $store;
}

// استعلام لجلب الفئات المتاحة للمتاجر
$categories_query = "SELECT DISTINCT category FROM stores WHERE status = 'active' ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result !== false && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        if (!empty($row['category'])) {
            $categories[] = $row['category'];
        }
    }
}

// استعلام لجلب المدن المتاحة
$cities_query = "SELECT DISTINCT city FROM stores WHERE status = 'active' ORDER BY city";
$cities_result = $conn->query($cities_query);
$cities = [];

if ($cities_result !== false && $cities_result->num_rows > 0) {
    while ($row = $cities_result->fetch_assoc()) {
        if (!empty($row['city'])) {
            $cities[] = $row['city'];
        }
    }
}

// تضمين ملف الهيدر الداكن كما في صفحة index.php
$root_path = '../';
include_once('../includes/dark_header.php');
?>

<!-- أنماط CSS مخصصة للصفحة -->
<style>
    .stores-header {
        background-color: #f8f9fa;
        padding: 30px 0;
        margin-bottom: 30px;
        border-radius: 10px;
    }
    
    .stores-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 15px;
        color: #333;
    }
    
    .stores-description {
        color: #666;
        margin-bottom: 0;
    }
    
    .filter-sidebar {
        background-color: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 20px;
    }
    
    .filter-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 20px;
        color: #333;
        position: relative;
        padding-right: 15px;
    }
    
    .filter-title::before {
        content: '';
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 20px;
        background-color: #FF7A00;
        border-radius: 5px;
    }
    
    .filter-section {
        margin-bottom: 25px;
        border-bottom: 1px solid #eee;
        padding-bottom: 20px;
    }
    
    .filter-section:last-child {
        margin-bottom: 0;
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .filter-label {
        font-weight: 600;
        margin-bottom: 10px;
        color: #555;
    }
    
    .custom-checkbox {
        margin-bottom: 10px;
    }
    
    .custom-checkbox .form-check-input:checked {
        background-color: #FF7A00;
        border-color: #FF7A00;
    }
    
    .custom-radio .form-check-input:checked {
        background-color: #FF7A00;
        border-color: #FF7A00;
    }
    
    .store-card {
        background-color: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        margin-bottom: 30px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .store-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .store-card-header {
        position: relative;
        height: 200px;
        overflow: hidden;
    }
    
    .store-logo {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background-color: #f8f9fa;
    }
    
    .store-card-body {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    
    .store-name {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: #333;
    }
    
    .store-description {
        color: #666;
        margin-bottom: 15px;
        font-size: 0.9rem;
        flex-grow: 1;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }
    
    .store-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #666;
        font-size: 0.9rem;
    }
    
    .store-rating {
        color: #ffc107;
    }
    
    .store-card-footer {
        padding: 15px 20px;
        border-top: 1px solid #eee;
        background-color: #f9f9f9;
    }
    
    .store-action-btn {
        width: 100%;
        padding: 8px;
        border-radius: 5px;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        transition: all 0.3s ease;
    }
    
    .pagination-container {
        margin-top: 30px;
    }
    
    .page-link {
        color: #FF7A00;
        border-color: #eee;
    }
    
    .page-item.active .page-link {
        background-color: #FF7A00;
        border-color: #FF7A00;
    }
    
    .sort-dropdown .dropdown-item.active,
    .sort-dropdown .dropdown-item:active {
        background-color: #FF7A00;
    }
    
    .no-stores {
        background-color: #fff;
        border-radius: 10px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .no-stores-icon {
        font-size: 3rem;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .no-stores-text {
        font-size: 1.2rem;
        color: #666;
        margin-bottom: 20px;
    }
    
    @media (max-width: 768px) {
        .filter-sidebar {
            margin-bottom: 30px;
            position: static;
        }
    }
</style>

<!-- محتوى الصفحة الرئيسي -->
<div class="container mt-4">
    <!-- رأس الصفحة -->
    <div class="stores-header text-center p-4">
        <h1 class="stores-title">تصفح المتاجر</h1>
        <p class="stores-description">اكتشف مجموعة متنوعة من المتاجر المميزة وتسوق بثقة وأمان</p>
    </div>
    
    <div class="row">
        <!-- قائمة المتاجر -->
        <div class="col-md-12">
            <?php if (count($stores) > 0): ?>
                <div class="row">
                    <?php foreach ($stores as $store): ?>
                        <div class="col-md-4 col-sm-6 mb-4">
                            <div class="store-card">
                                <div class="store-card-header">
                                    <img src="<?php echo !empty($store['logo']) ? '../uploads/stores/'.$store['logo'] : 'images/default-store.png'; ?>" alt="<?php echo $store['name']; ?>" class="store-logo">
                                </div>
                                <div class="store-card-body">
                                    <h3 class="store-name"><?php echo $store['name']; ?></h3>
                                    <p class="store-description"><?php echo $store['description']; ?></p>
                                    <div class="store-meta">
                                        <div class="meta-item store-rating">
                                            <i class="bi bi-star-fill"></i>
                                            <span><?php echo isset($store['rating']) ? number_format($store['rating'], 1) : '0.0'; ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="bi bi-box-seam"></i>
                                            <span><?php echo $store['products_count'] ?? 0; ?> منتج</span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="bi bi-geo-alt"></i>
                                            <span><?php echo $store['city']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="store-card-footer">
                                    <a href="store-page.php?id=<?php echo $store['id']; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" class="btn btn-primary store-action-btn">
                                        <i class="bi bi-shop"></i>
                                        <span>زيارة المتجر</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- الترقيم -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['city']) ? '&city='.urlencode($_GET['city']) : ''; ?><?php echo isset($_GET['rating']) ? '&rating='.urlencode($_GET['rating']) : ''; ?><?php echo isset($_GET['sort']) ? '&sort='.urlencode($_GET['sort']) : ''; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['city']) ? '&city='.urlencode($_GET['city']) : ''; ?><?php echo isset($_GET['rating']) ? '&rating='.urlencode($_GET['rating']) : ''; ?><?php echo isset($_GET['sort']) ? '&sort='.urlencode($_GET['sort']) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['category']) ? '&category='.urlencode($_GET['category']) : ''; ?><?php echo isset($_GET['city']) ? '&city='.urlencode($_GET['city']) : ''; ?><?php echo isset($_GET['rating']) ? '&rating='.urlencode($_GET['rating']) : ''; ?><?php echo isset($_GET['sort']) ? '&sort='.urlencode($_GET['sort']) : ''; ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-stores">
                    <div class="no-stores-icon">
                        <i class="bi bi-shop"></i>
                    </div>
                    <h3 class="no-stores-text">لا توجد متاجر متاحة حالياً</h3>
                    <p>حاول تغيير معايير البحث أو التصفية</p>
                    <a href="stores.php" class="btn btn-primary">عرض جميع المتاجر</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// تضمين ملف الفوتر
include_once('../includes/footer.php');
?>
