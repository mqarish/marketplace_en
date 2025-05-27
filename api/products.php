<?php
require_once 'config.php';

// التعامل مع طلبات المنتجات
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getProductById($_GET['id']);
        } else {
            getProducts();
        }
        break;
    case 'POST':
        // التحقق من المصادقة
        $user = authenticate();
        if ($user['user_type'] !== 'store') {
            sendResponse(403, ['error' => 'غير مصرح به: يجب أن تكون متجرًا']);
        }
        addProduct();
        break;
    case 'PUT':
        // التحقق من المصادقة
        $user = authenticate();
        if ($user['user_type'] !== 'store') {
            sendResponse(403, ['error' => 'غير مصرح به: يجب أن تكون متجرًا']);
        }
        updateProduct();
        break;
    case 'DELETE':
        // التحقق من المصادقة
        $user = authenticate();
        if ($user['user_type'] !== 'store') {
            sendResponse(403, ['error' => 'غير مصرح به: يجب أن تكون متجرًا']);
        }
        deleteProduct();
        break;
    default:
        sendResponse(405, ['error' => 'طريقة غير مسموح بها']);
        break;
}

// دالة للحصول على قائمة المنتجات
function getProducts() {
    $conn = getDbConnection();
    
    // إعداد الاستعلام الأساسي
    $query = "SELECT p.*, s.name as store_name, c.name as category_name, 
              (SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id) as avg_rating,
              (SELECT COUNT(*) FROM likes l WHERE l.product_id = p.id) as likes_count
              FROM products p
              JOIN stores s ON p.store_id = s.id
              JOIN categories c ON p.category_id = c.id
              WHERE 1=1";
    
    // إضافة معايير البحث والتصفية
    $params = [];
    $types = "";
    
    // البحث حسب الاسم
    if (isset($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $query .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }
    
    // التصفية حسب الفئة
    if (isset($_GET['category'])) {
        $query .= " AND p.category_id = ?";
        $params[] = $_GET['category'];
        $types .= "i";
    }
    
    // التصفية حسب المتجر
    if (isset($_GET['store'])) {
        $query .= " AND p.store_id = ?";
        $params[] = $_GET['store'];
        $types .= "i";
    }
    
    // التصفية حسب السعر
    if (isset($_GET['min_price'])) {
        $query .= " AND p.price >= ?";
        $params[] = $_GET['min_price'];
        $types .= "d";
    }
    
    if (isset($_GET['max_price'])) {
        $query .= " AND p.price <= ?";
        $params[] = $_GET['max_price'];
        $types .= "d";
    }
    
    // الترتيب
    $orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'id';
    $orderDir = isset($_GET['order_dir']) ? $_GET['order_dir'] : 'DESC';
    
    // التحقق من صحة معايير الترتيب
    $allowedOrderBy = ['id', 'name', 'price', 'avg_rating', 'likes_count', 'created_at'];
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
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // تحويل مسارات الصور إلى URLs كاملة
        $row['image'] = getFullImageUrl($row['image']);
        $products[] = $row;
    }
    
    // الحصول على العدد الإجمالي للمنتجات (للصفحات)
    $countQuery = str_replace("SELECT p.*, s.name as store_name, c.name as category_name", "SELECT COUNT(*) as total", $query);
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
        'products' => $products,
        'pagination' => [
            'total' => (int)$totalCount,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalCount / $limit)
        ]
    ];
    
    sendResponse(200, $response);
}

// دالة للحصول على منتج بواسطة المعرف
function getProductById($id) {
    $conn = getDbConnection();
    
    // الاستعلام الأساسي للمنتج
    $query = "SELECT p.*, s.name as store_name, c.name as category_name,
              (SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id) as avg_rating,
              (SELECT COUNT(*) FROM likes l WHERE l.product_id = p.id) as likes_count
              FROM products p
              JOIN stores s ON p.store_id = s.id
              JOIN categories c ON p.category_id = c.id
              WHERE p.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(404, ['error' => 'المنتج غير موجود']);
    }
    
    $product = $result->fetch_assoc();
    
    // تحويل مسار الصورة إلى URL كامل
    $product['image'] = getFullImageUrl($product['image']);
    
    // الحصول على صور إضافية للمنتج
    $imagesQuery = "SELECT * FROM product_images WHERE product_id = ?";
    $imagesStmt = $conn->prepare($imagesQuery);
    $imagesStmt->bind_param("i", $id);
    $imagesStmt->execute();
    $imagesResult = $imagesStmt->get_result();
    
    $images = [];
    while ($image = $imagesResult->fetch_assoc()) {
        $image['image_url'] = getFullImageUrl($image['image_url']);
        $images[] = $image;
    }
    
    $product['additional_images'] = $images;
    
    // الحصول على المراجعات
    $reviewsQuery = "SELECT r.*, c.name as customer_name
                    FROM reviews r
                    JOIN customers c ON r.customer_id = c.id
                    WHERE r.product_id = ?
                    ORDER BY r.created_at DESC";
    
    $reviewsStmt = $conn->prepare($reviewsQuery);
    $reviewsStmt->bind_param("i", $id);
    $reviewsStmt->execute();
    $reviewsResult = $reviewsStmt->get_result();
    
    $reviews = [];
    while ($review = $reviewsResult->fetch_assoc()) {
        $reviews[] = $review;
    }
    
    $product['reviews'] = $reviews;
    
    sendResponse(200, $product);
}

// دالة لإضافة منتج جديد
function addProduct() {
    // الحصول على بيانات المستخدم المصادق
    $user = authenticate();
    $storeId = $user['user_id'];
    
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من البيانات المطلوبة
    if (!isset($data['name']) || !isset($data['description']) || !isset($data['price']) || !isset($data['category_id'])) {
        sendResponse(400, ['error' => 'البيانات غير مكتملة']);
    }
    
    $name = $data['name'];
    $description = $data['description'];
    $price = $data['price'];
    $categoryId = $data['category_id'];
    $quantity = isset($data['quantity']) ? $data['quantity'] : 0;
    
    // اتصال بقاعدة البيانات
    $conn = getDbConnection();
    
    // إدراج المنتج الجديد
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, category_id, store_id, quantity) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdiid", $name, $description, $price, $categoryId, $storeId, $quantity);
    
    if (!$stmt->execute()) {
        sendResponse(500, ['error' => 'فشل في إضافة المنتج']);
    }
    
    $productId = $conn->insert_id;
    
    // إعداد استجابة المنتج
    $response = [
        'id' => $productId,
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'category_id' => $categoryId,
        'store_id' => $storeId,
        'quantity' => $quantity,
        'message' => 'تمت إضافة المنتج بنجاح'
    ];
    
    sendResponse(201, $response);
}

// دالة لتحديث منتج
function updateProduct() {
    // الحصول على بيانات المستخدم المصادق
    $user = authenticate();
    $storeId = $user['user_id'];
    
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من معرف المنتج
    if (!isset($data['id'])) {
        sendResponse(400, ['error' => 'معرف المنتج مطلوب']);
    }
    
    $productId = $data['id'];
    
    // اتصال بقاعدة البيانات
    $conn = getDbConnection();
    
    // التحقق من وجود المنتج وملكيته
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND store_id = ?");
    $stmt->bind_param("ii", $productId, $storeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(404, ['error' => 'المنتج غير موجود أو ليس لديك إذن لتحديثه']);
    }
    
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
    
    if (isset($data['price'])) {
        $updateFields[] = "price = ?";
        $params[] = $data['price'];
        $types .= "d";
    }
    
    if (isset($data['category_id'])) {
        $updateFields[] = "category_id = ?";
        $params[] = $data['category_id'];
        $types .= "i";
    }
    
    if (isset($data['quantity'])) {
        $updateFields[] = "quantity = ?";
        $params[] = $data['quantity'];
        $types .= "i";
    }
    
    if (empty($updateFields)) {
        sendResponse(400, ['error' => 'لا توجد حقول للتحديث']);
    }
    
    // إضافة معرف المنتج إلى المعلمات
    $params[] = $productId;
    $types .= "i";
    
    // تنفيذ استعلام التحديث
    $updateQuery = "UPDATE products SET " . implode(", ", $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        sendResponse(500, ['error' => 'فشل في تحديث المنتج']);
    }
    
    sendResponse(200, ['message' => 'تم تحديث المنتج بنجاح']);
}

// دالة لحذف منتج
function deleteProduct() {
    // الحصول على بيانات المستخدم المصادق
    $user = authenticate();
    $storeId = $user['user_id'];
    
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من معرف المنتج
    if (!isset($data['id'])) {
        sendResponse(400, ['error' => 'معرف المنتج مطلوب']);
    }
    
    $productId = $data['id'];
    
    // اتصال بقاعدة البيانات
    $conn = getDbConnection();
    
    // التحقق من وجود المنتج وملكيته
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND store_id = ?");
    $stmt->bind_param("ii", $productId, $storeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(404, ['error' => 'المنتج غير موجود أو ليس لديك إذن لحذفه']);
    }
    
    // حذف المنتج
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    
    if (!$stmt->execute()) {
        sendResponse(500, ['error' => 'فشل في حذف المنتج']);
    }
    
    sendResponse(200, ['message' => 'تم حذف المنتج بنجاح']);
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
