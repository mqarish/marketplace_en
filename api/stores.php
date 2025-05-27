<?php
require_once 'config.php';

// التعامل مع طلبات المتاجر
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getStoreById($_GET['id']);
        } else {
            getStores();
        }
        break;
    case 'PUT':
        // التحقق من المصادقة
        $user = authenticate();
        if ($user['user_type'] !== 'store') {
            sendResponse(403, ['error' => 'غير مصرح به: يجب أن تكون متجرًا']);
        }
        updateStore();
        break;
    default:
        sendResponse(405, ['error' => 'طريقة غير مسموح بها']);
        break;
}

// دالة للحصول على قائمة المتاجر
function getStores() {
    $conn = getDbConnection();
    
    // إعداد الاستعلام الأساسي
    $query = "SELECT s.*, 
              (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id) as products_count,
              (SELECT AVG(r.rating) FROM reviews r JOIN products p ON r.product_id = p.id WHERE p.store_id = s.id) as avg_rating
              FROM stores s
              WHERE s.is_active = 1";
    
    // إضافة معايير البحث والتصفية
    $params = [];
    $types = "";
    
    // البحث حسب الاسم
    if (isset($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $query .= " AND (s.name LIKE ? OR s.description LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    
    // التصفية حسب الموقع
    if (isset($_GET['location'])) {
        $location = '%' . $_GET['location'] . '%';
        $query .= " AND s.location LIKE ?";
        $params[] = $location;
        $types .= "s";
    }
    
    // الترتيب
    $orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'id';
    $orderDir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'DESC';
    
    // التحقق من صحة معايير الترتيب
    $allowedOrderBy = ['id', 'name', 'products_count', 'avg_rating', 'created_at'];
    $allowedOrderDir = ['ASC', 'DESC'];
    
    if (!in_array($orderBy, $allowedOrderBy)) {
        $orderBy = 'id';
    }
    
    if (!in_array(strtoupper($orderDir), $allowedOrderDir)) {
        $orderDir = 'DESC';
    }
    
    $query .= " ORDER BY $orderBy $orderDir";
    
    // الصفحات
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > 100) $limit = 10;
    
    $offset = ($page - 1) * $limit;
    
    $query .= " LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $limit;
    $types .= "ii";
    
    // تنفيذ الاستعلام
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stores = [];
    while ($row = $result->fetch_assoc()) {
        // تحويل مسارات الصور إلى URLs كاملة
        $row['logo'] = getFullImageUrl($row['logo']);
        
        // إزالة معلومات حساسة
        unset($row['password']);
        unset($row['email_verification_token']);
        
        $stores[] = $row;
    }
    
    // الحصول على العدد الإجمالي للمتاجر (للصفحات)
    $countQuery = str_replace("SELECT s.*", "SELECT COUNT(*) as total", $query);
    $countQuery = preg_replace('/LIMIT \?, \?$/', '', $countQuery);
    
    $countStmt = $conn->prepare($countQuery);
    
    // إزالة معلمات LIMIT من المعلمات
    if (count($params) >= 2) {
        array_pop($params);
        array_pop($params);
        $types = substr($types, 0, -2);
    }
    
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalCount = $countResult->fetch_assoc()['total'];
    
    // إعداد الاستجابة
    $response = [
        'stores' => $stores,
        'pagination' => [
            'total' => (int)$totalCount,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalCount / $limit)
        ]
    ];
    
    sendResponse(200, $response);
}

// دالة للحصول على متجر بواسطة المعرف
function getStoreById($id) {
    $conn = getDbConnection();
    
    // الاستعلام الأساسي للمتجر
    $query = "SELECT s.*, 
              (SELECT COUNT(*) FROM products p WHERE p.store_id = s.id) as products_count,
              (SELECT AVG(r.rating) FROM reviews r JOIN products p ON r.product_id = p.id WHERE p.store_id = s.id) as avg_rating
              FROM stores s
              WHERE s.id = ? AND s.is_active = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(404, ['error' => 'المتجر غير موجود']);
    }
    
    $store = $result->fetch_assoc();
    
    // تحويل مسار الشعار إلى URL كامل
    $store['logo'] = getFullImageUrl($store['logo']);
    
    // إزالة معلومات حساسة
    unset($store['password']);
    unset($store['email_verification_token']);
    
    // الحصول على منتجات المتجر
    $productsQuery = "SELECT p.*, c.name as category_name,
                     (SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id) as avg_rating,
                     (SELECT COUNT(*) FROM likes l WHERE l.product_id = p.id) as likes_count
                     FROM products p
                     JOIN categories c ON p.category_id = c.id
                     WHERE p.store_id = ?
                     ORDER BY p.created_at DESC
                     LIMIT 10";
    
    $productsStmt = $conn->prepare($productsQuery);
    $productsStmt->bind_param("i", $id);
    $productsStmt->execute();
    $productsResult = $productsStmt->get_result();
    
    $products = [];
    while ($product = $productsResult->fetch_assoc()) {
        // تحويل مسار الصورة إلى URL كامل
        $product['image'] = getFullImageUrl($product['image']);
        $products[] = $product;
    }
    
    $store['products'] = $products;
    
    sendResponse(200, $store);
}

// دالة لتحديث معلومات المتجر
function updateStore() {
    // الحصول على بيانات المستخدم المصادق
    $user = authenticate();
    $storeId = $user['user_id'];
    
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // اتصال بقاعدة البيانات
    $conn = getDbConnection();
    
    // بناء استعلام التحديث
    $updateFields = [];
    $params = [];
    $types = "";
    
    if (isset($data['name'])) {
        $updateFields[] = "name = ?";
        $params[] = $data['name'];
        $types .= "s";
    }
    
    if (isset($data['description'])) {
        $updateFields[] = "description = ?";
        $params[] = $data['description'];
        $types .= "s";
    }
    
    if (isset($data['location'])) {
        $updateFields[] = "location = ?";
        $params[] = $data['location'];
        $types .= "s";
    }
    
    if (isset($data['phone'])) {
        $updateFields[] = "phone = ?";
        $params[] = $data['phone'];
        $types .= "s";
    }
    
    if (empty($updateFields)) {
        sendResponse(400, ['error' => 'لا توجد حقول للتحديث']);
    }
    
    // إضافة معرف المتجر إلى المعلمات
    $params[] = $storeId;
    $types .= "i";
    
    // تنفيذ استعلام التحديث
    $updateQuery = "UPDATE stores SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        sendResponse(500, ['error' => 'فشل في تحديث المتجر']);
    }
    
    sendResponse(200, ['message' => 'تم تحديث المتجر بنجاح']);
}

// دالة مساعدة للحصول على URL كامل للصورة
function getFullImageUrl($imagePath) {
    if (empty($imagePath)) {
        return null;
    }
    
    // التحقق مما إذا كان المسار يبدأ بـ http أو https
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return $imagePath;
    }
    
    // إنشاء URL كامل
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // التعامل مع المسارات النسبية
    if (strpos($imagePath, '/') !== 0) {
        $imagePath = '/' . $imagePath;
    }
    
    return $protocol . $host . $imagePath;
}
?>
