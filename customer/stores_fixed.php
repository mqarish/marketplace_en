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

// التصفية حسب الفئة
if (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $category_id = intval($_GET['category_id']);
    $where_conditions[] = "s.category_id = ?";
    $params[] = $category_id;
    $param_types .= "i";
}

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

// استعلام لجلب الفئات المتاحة للمتاجر - تم تعديل هذا الاستعلام ليعمل مع هيكل قاعدة البيانات
try {
    $categories_query = "SELECT DISTINCT c.id, c.name FROM stores s JOIN categories c ON s.category_id = c.id WHERE s.status = 'active' ORDER BY c.name";
    $categories_result = $conn->query($categories_query);
    $categories = [];

    if ($categories_result !== false && $categories_result->num_rows > 0) {
        while ($row = $categories_result->fetch_assoc()) {
            if (!empty($row['name'])) {
                $categories[] = ['id' => $row['id'], 'name' => $row['name']];
            }
        }
    }
} catch (Exception $e) {
    // في حالة حدوث خطأ، نستمر بدون فئات
    $categories = [];
}

// استعلام لجلب المدن المتاحة
try {
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
} catch (Exception $e) {
    // في حالة حدوث خطأ، نستمر بدون مدن
    $cities = [];
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
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .stores-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: #333;
    }
    
    .stores-description {
        font-size: 1.1rem;
        color: #666;
        max-width: 700px;
        margin: 0 auto;
    }
    
    .store-card {
        background-color: #fff;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .store-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    }
    
    .store-card-header {
        padding: 20px;
        text-align: center;
        background-color: #f8f9fa;
        border-bottom: 1px solid #eee;
    }
    
    .store-logo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .store-card-body {
        padding: 20px;
        flex-grow: 1;
    }
    
    .store-name {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: #333;
    }
    
    .store-description {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 15px;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .store-meta {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        margin-left: 15px;
        margin-bottom: 5px;
        font-size: 0.85rem;
        color: #666;
    }
    
    .meta-item i {
        margin-left: 5px;
        color: #FF7A00;
    }
    
    .store-rating i {
        color: #FFD700;
    }
    
    .store-card-footer {
        padding: 15px 20px;
        border-top: 1px solid #eee;
        background-color: #f8f9fa;
    }
    
    .store-action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        background-color: #FF7A00;
        border-color: #FF7A00;
    }
    
    .store-action-btn:hover {
        background-color: #E56E00;
        border-color: #E56E00;
    }
    
    .no-stores {
        text-align: center;
        padding: 50px 20px;
        background-color: #f8f9fa;
        border-radius: 10px;
        margin-top: 30px;
    }
    
    .no-stores-icon {
        font-size: 3rem;
        color: #ccc;
        margin-bottom: 20px;
    }
    
    .pagination-container {
        margin-top: 30px;
        margin-bottom: 50px;
    }
    
    .pagination .page-link {
        color: #FF7A00;
        border-color: #dee2e6;
    }
    
    .pagination .page-item.active .page-link {
        background-color: #FF7A00;
        border-color: #FF7A00;
        color: #fff;
    }
    
    .filter-sidebar {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
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
                                        زيارة المتجر
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination-container">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['city']) ? '&city=' . urlencode($_GET['city']) : ''; ?><?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : ''; ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['city']) ? '&city=' . urlencode($_GET['city']) : ''; ?><?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?><?php echo isset($_GET['city']) ? '&city=' . urlencode($_GET['city']) : ''; ?><?php echo isset($_GET['sort']) ? '&sort=' . $_GET['sort'] : ''; ?>" aria-label="Next">
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
                    <h3>لا توجد متاجر متاحة</h3>
                    <p>لم يتم العثور على متاجر مطابقة لمعايير البحث الحالية.</p>
                    <a href="stores.php" class="btn btn-primary mt-3">عرض جميع المتاجر</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// تضمين ملف الفوتر
include_once('../includes/footer.php');
?>
