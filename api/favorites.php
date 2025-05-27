<?php
/**
 * API للمفضلة
 * يتيح هذا الملف إدارة المنتجات والمتاجر المفضلة للمستخدمين
 */

// استيراد ملف الإعدادات
require_once 'config.php';

// التحقق من طريقة الطلب
$method = $_SERVER['REQUEST_METHOD'];

// الحصول على الإجراء المطلوب
$action = isset($_GET['action']) ? $_GET['action'] : '';

// التعامل مع الطلب بناءً على الإجراء
switch ($action) {
    case 'getProducts':
        // الحصول على المنتجات المفضلة
        handleGetFavoriteProducts();
        break;
    case 'getStores':
        // الحصول على المتاجر المفضلة
        handleGetFavoriteStores();
        break;
    case 'add':
        // إضافة منتج أو متجر إلى المفضلة
        handleAddFavorite();
        break;
    case 'remove':
        // إزالة منتج أو متجر من المفضلة
        handleRemoveFavorite();
        break;
    case 'check':
        // التحقق مما إذا كان المنتج أو المتجر في المفضلة
        handleCheckFavorite();
        break;
    default:
        // إجراء غير معروف
        sendResponse(400, ['error' => 'الإجراء غير معروف']);
        break;
}

/**
 * دالة للحصول على المنتجات المفضلة للمستخدم
 */
function handleGetFavoriteProducts() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }

    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم
    $userId = $user['id'];
    
    // استعلام للحصول على المنتجات المفضلة
    $query = "SELECT p.id, p.name, p.price, p.image, s.name as store_name, p.store_id 
              FROM favorites f 
              JOIN products p ON f.item_id = p.id 
              JOIN stores s ON p.store_id = s.id 
              WHERE f.user_id = ? AND f.type = 'product'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'price' => (float)$row['price'],
            'image' => $row['image'],
            'store_name' => $row['store_name'],
            'store_id' => (int)$row['store_id']
        ];
    }
    
    sendResponse(200, ['products' => $products]);
}

/**
 * دالة للحصول على المتاجر المفضلة للمستخدم
 */
function handleGetFavoriteStores() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }

    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم
    $userId = $user['id'];
    
    // استعلام للحصول على المتاجر المفضلة
    $query = "SELECT s.id, s.name, s.logo, 
              (SELECT COUNT(*) FROM products WHERE store_id = s.id) as products_count,
              (SELECT AVG(rating) FROM store_reviews WHERE store_id = s.id) as rating
              FROM favorites f 
              JOIN stores s ON f.item_id = s.id 
              WHERE f.user_id = ? AND f.type = 'store'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stores = [];
    while ($row = $result->fetch_assoc()) {
        $stores[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'logo' => $row['logo'],
            'products_count' => (int)$row['products_count'],
            'rating' => (float)$row['rating'] ?: 0
        ];
    }
    
    sendResponse(200, ['stores' => $stores]);
}

/**
 * دالة لإضافة منتج أو متجر إلى المفضلة
 */
function handleAddFavorite() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }
    
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من البيانات المطلوبة
    if (!isset($data['type']) || !isset($data['id'])) {
        sendResponse(400, ['error' => 'البيانات غير مكتملة']);
        return;
    }
    
    // التحقق من نوع العنصر
    if ($data['type'] !== 'product' && $data['type'] !== 'store') {
        sendResponse(400, ['error' => 'نوع العنصر غير صالح']);
        return;
    }
    
    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم ومعلومات العنصر
    $userId = $user['id'];
    $itemType = $data['type'];
    $itemId = $data['id'];
    
    // التحقق من وجود العنصر
    $table = ($itemType === 'product') ? 'products' : 'stores';
    $query = "SELECT id FROM $table WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(404, ['error' => 'العنصر غير موجود']);
        return;
    }
    
    // التحقق من عدم وجود العنصر في المفضلة بالفعل
    $query = "SELECT id FROM favorites WHERE user_id = ? AND type = ? AND item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isi", $userId, $itemType, $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        sendResponse(200, ['success' => true, 'message' => 'العنصر موجود بالفعل في المفضلة']);
        return;
    }
    
    // إضافة العنصر إلى المفضلة
    $query = "INSERT INTO favorites (user_id, type, item_id, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isi", $userId, $itemType, $itemId);
    $success = $stmt->execute();
    
    if ($success) {
        sendResponse(200, ['success' => true]);
    } else {
        sendResponse(500, ['error' => 'حدث خطأ أثناء إضافة العنصر إلى المفضلة']);
    }
}

/**
 * دالة لإزالة منتج أو متجر من المفضلة
 */
function handleRemoveFavorite() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }
    
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من البيانات المطلوبة
    if (!isset($data['type']) || !isset($data['id'])) {
        sendResponse(400, ['error' => 'البيانات غير مكتملة']);
        return;
    }
    
    // التحقق من نوع العنصر
    if ($data['type'] !== 'product' && $data['type'] !== 'store') {
        sendResponse(400, ['error' => 'نوع العنصر غير صالح']);
        return;
    }
    
    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم ومعلومات العنصر
    $userId = $user['id'];
    $itemType = $data['type'];
    $itemId = $data['id'];
    
    // حذف العنصر من المفضلة
    $query = "DELETE FROM favorites WHERE user_id = ? AND type = ? AND item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isi", $userId, $itemType, $itemId);
    $success = $stmt->execute();
    
    if ($success) {
        sendResponse(200, ['success' => true]);
    } else {
        sendResponse(500, ['error' => 'حدث خطأ أثناء إزالة العنصر من المفضلة']);
    }
}

/**
 * دالة للتحقق مما إذا كان المنتج أو المتجر في المفضلة
 */
function handleCheckFavorite() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }
    
    // الحصول على المعلمات من الطلب
    $itemType = isset($_GET['type']) ? $_GET['type'] : '';
    $itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    // التحقق من البيانات المطلوبة
    if (empty($itemType) || $itemId === 0) {
        sendResponse(400, ['error' => 'البيانات غير مكتملة']);
        return;
    }
    
    // التحقق من نوع العنصر
    if ($itemType !== 'product' && $itemType !== 'store') {
        sendResponse(400, ['error' => 'نوع العنصر غير صالح']);
        return;
    }
    
    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم
    $userId = $user['id'];
    
    // التحقق من وجود العنصر في المفضلة
    $query = "SELECT id FROM favorites WHERE user_id = ? AND type = ? AND item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isi", $userId, $itemType, $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $isFavorite = $result->num_rows > 0;
    
    sendResponse(200, ['isFavorite' => $isFavorite]);
}
?>
