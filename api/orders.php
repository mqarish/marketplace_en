<?php
/**
 * API للطلبات
 * يتيح هذا الملف إدارة طلبات المستخدمين
 */

// استيراد ملف الإعدادات
require_once 'config.php';

// التحقق من طريقة الطلب
$method = $_SERVER['REQUEST_METHOD'];

// الحصول على الإجراء المطلوب
$action = isset($_GET['action']) ? $_GET['action'] : '';

// التعامل مع الطلب بناءً على الإجراء
switch ($action) {
    case 'create':
        // إنشاء طلب جديد
        handleCreateOrder();
        break;
    case 'get':
        // الحصول على تفاصيل طلب
        handleGetOrder();
        break;
    case 'list':
        // الحصول على قائمة الطلبات
        handleListOrders();
        break;
    case 'cancel':
        // إلغاء طلب
        handleCancelOrder();
        break;
    default:
        // إجراء غير معروف
        sendResponse(400, ['error' => 'الإجراء غير معروف']);
        break;
}

/**
 * دالة لإنشاء طلب جديد
 */
function handleCreateOrder() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }

    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من البيانات المطلوبة
    if (!isset($data['items']) || !isset($data['shipping_address']) || !isset($data['payment_method'])) {
        sendResponse(400, ['error' => 'البيانات غير مكتملة']);
        return;
    }
    
    $conn = getDbConnection();
    
    // بدء المعاملة
    $conn->begin_transaction();
    
    try {
        // الحصول على معرف المستخدم
        $userId = $user['id'];
        
        // إنشاء الطلب
        $query = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status, created_at) 
                  VALUES (?, ?, ?, ?, 'pending', NOW())";
        $stmt = $conn->prepare($query);
        $totalAmount = calculateTotalAmount($data['items']);
        $shippingAddress = json_encode($data['shipping_address']);
        $paymentMethod = $data['payment_method'];
        $stmt->bind_param("idss", $userId, $totalAmount, $shippingAddress, $paymentMethod);
        $stmt->execute();
        
        // الحصول على معرف الطلب
        $orderId = $conn->insert_id;
        
        // إضافة عناصر الطلب
        foreach ($data['items'] as $item) {
            $query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $productId = $item['product_id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $stmt->bind_param("iiid", $orderId, $productId, $quantity, $price);
            $stmt->execute();
            
            // تحديث المخزون
            $query = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $quantity, $productId);
            $stmt->execute();
        }
        
        // إرسال إشعار للمستخدم
        $notificationTitle = 'تم استلام طلبك';
        $notificationMessage = "تم استلام طلبك رقم #$orderId بنجاح وسيتم معالجته قريباً.";
        $notificationType = 'order';
        $notificationLink = "/order/$orderId";
        sendNotification($userId, $notificationTitle, $notificationMessage, $notificationType, $notificationLink);
        
        // إتمام المعاملة
        $conn->commit();
        
        sendResponse(200, [
            'success' => true,
            'order_id' => $orderId,
            'total_amount' => $totalAmount
        ]);
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        $conn->rollback();
        sendResponse(500, ['error' => 'حدث خطأ أثناء إنشاء الطلب: ' . $e->getMessage()]);
    }
}

/**
 * دالة للحصول على تفاصيل طلب
 */
function handleGetOrder() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }
    
    // الحصول على معرف الطلب
    $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($orderId === 0) {
        sendResponse(400, ['error' => 'معرف الطلب غير صالح']);
        return;
    }
    
    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم
    $userId = $user['id'];
    
    // التحقق من أن الطلب ينتمي للمستخدم
    $query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(404, ['error' => 'الطلب غير موجود']);
        return;
    }
    
    $order = $result->fetch_assoc();
    
    // الحصول على عناصر الطلب
    $query = "SELECT oi.*, p.name, p.image 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => (int)$row['id'],
            'product_id' => (int)$row['product_id'],
            'product_name' => $row['name'],
            'product_image' => $row['image'],
            'quantity' => (int)$row['quantity'],
            'price' => (float)$row['price'],
            'subtotal' => (float)($row['quantity'] * $row['price'])
        ];
    }
    
    // تحويل عنوان الشحن من JSON إلى مصفوفة
    $shippingAddress = json_decode($order['shipping_address'], true);
    
    $orderDetails = [
        'id' => (int)$order['id'],
        'total_amount' => (float)$order['total_amount'],
        'shipping_address' => $shippingAddress,
        'payment_method' => $order['payment_method'],
        'status' => $order['status'],
        'created_at' => $order['created_at'],
        'items' => $items
    ];
    
    sendResponse(200, ['order' => $orderDetails]);
}

/**
 * دالة للحصول على قائمة الطلبات
 */
function handleListOrders() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }
    
    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم
    $userId = $user['id'];
    
    // الحصول على رقم الصفحة وعدد العناصر في الصفحة
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    
    // استعلام للحصول على عدد الطلبات
    $query = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalOrders = (int)$row['count'];
    
    // استعلام للحصول على الطلبات
    $query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $userId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // الحصول على عدد العناصر في الطلب
        $query = "SELECT COUNT(*) as count FROM order_items WHERE order_id = ?";
        $stmtItems = $conn->prepare($query);
        $orderId = $row['id'];
        $stmtItems->bind_param("i", $orderId);
        $stmtItems->execute();
        $resultItems = $stmtItems->get_result();
        $rowItems = $resultItems->fetch_assoc();
        $itemsCount = (int)$rowItems['count'];
        
        $orders[] = [
            'id' => (int)$row['id'],
            'total_amount' => (float)$row['total_amount'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'items_count' => $itemsCount
        ];
    }
    
    sendResponse(200, [
        'orders' => $orders,
        'total' => $totalOrders,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($totalOrders / $limit)
    ]);
}

/**
 * دالة لإلغاء طلب
 */
function handleCancelOrder() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }
    
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من البيانات المطلوبة
    if (!isset($data['order_id'])) {
        sendResponse(400, ['error' => 'البيانات غير مكتملة']);
        return;
    }
    
    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم ومعرف الطلب
    $userId = $user['id'];
    $orderId = $data['order_id'];
    
    // التحقق من أن الطلب ينتمي للمستخدم
    $query = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(404, ['error' => 'الطلب غير موجود']);
        return;
    }
    
    $order = $result->fetch_assoc();
    
    // التحقق من أن الطلب يمكن إلغاؤه
    if ($order['status'] !== 'pending' && $order['status'] !== 'processing') {
        sendResponse(400, ['error' => 'لا يمكن إلغاء الطلب في هذه الحالة']);
        return;
    }
    
    // بدء المعاملة
    $conn->begin_transaction();
    
    try {
        // تحديث حالة الطلب إلى "ملغي"
        $query = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        
        // استعادة المخزون
        $query = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $query = "UPDATE products SET stock = stock + ? WHERE id = ?";
            $stmtUpdate = $conn->prepare($query);
            $productId = $row['product_id'];
            $quantity = $row['quantity'];
            $stmtUpdate->bind_param("ii", $quantity, $productId);
            $stmtUpdate->execute();
        }
        
        // إرسال إشعار للمستخدم
        $notificationTitle = 'تم إلغاء طلبك';
        $notificationMessage = "تم إلغاء طلبك رقم #$orderId بنجاح.";
        $notificationType = 'order';
        $notificationLink = "/order/$orderId";
        sendNotification($userId, $notificationTitle, $notificationMessage, $notificationType, $notificationLink);
        
        // إتمام المعاملة
        $conn->commit();
        
        sendResponse(200, ['success' => true]);
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث خطأ
        $conn->rollback();
        sendResponse(500, ['error' => 'حدث خطأ أثناء إلغاء الطلب: ' . $e->getMessage()]);
    }
}

/**
 * دالة لحساب إجمالي المبلغ للطلب
 */
function calculateTotalAmount($items) {
    $conn = getDbConnection();
    $totalAmount = 0;
    
    foreach ($items as $item) {
        $query = "SELECT price FROM products WHERE id = ?";
        $stmt = $conn->prepare($query);
        $productId = $item['product_id'];
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row) {
            $price = $row['price'];
            $quantity = $item['quantity'];
            $totalAmount += $price * $quantity;
        }
    }
    
    return $totalAmount;
}

/**
 * دالة لإرسال إشعار
 * ملاحظة: هذه الدالة تستدعي دالة sendNotification من ملف notifications.php
 */
function sendNotification($userId, $title, $message, $type, $link = null) {
    // التحقق من وجود الدالة
    if (!function_exists('sendNotification')) {
        require_once 'notifications.php';
    }
    
    return sendNotification($userId, $title, $message, $type, $link);
}
?>
