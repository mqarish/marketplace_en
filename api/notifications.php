<?php
/**
 * API للإشعارات
 * يتيح هذا الملف إدارة إشعارات المستخدمين
 */

// استيراد ملف الإعدادات
require_once 'config.php';

// التحقق من طريقة الطلب
$method = $_SERVER['REQUEST_METHOD'];

// الحصول على الإجراء المطلوب
$action = isset($_GET['action']) ? $_GET['action'] : '';

// التعامل مع الطلب بناءً على الإجراء
switch ($action) {
    case 'get':
        // الحصول على إشعارات المستخدم
        handleGetNotifications();
        break;
    case 'markAsRead':
        // تحديث حالة الإشعار إلى "مقروء"
        handleMarkAsRead();
        break;
    case 'delete':
        // حذف إشعار
        handleDeleteNotification();
        break;
    case 'unreadCount':
        // الحصول على عدد الإشعارات غير المقروءة
        handleUnreadCount();
        break;
    case 'updateToken':
        // تحديث رمز الإشعارات (FCM token)
        handleUpdateToken();
        break;
    default:
        // إجراء غير معروف
        sendResponse(400, ['error' => 'الإجراء غير معروف']);
        break;
}

/**
 * دالة للحصول على إشعارات المستخدم
 */
function handleGetNotifications() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }

    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم
    $userId = $user['id'];
    
    // استعلام للحصول على إشعارات المستخدم
    $query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'message' => $row['message'],
            'type' => $row['type'],
            'read' => (bool)$row['is_read'],
            'created_at' => $row['created_at'],
            'link' => $row['link']
        ];
    }
    
    sendResponse(200, ['notifications' => $notifications]);
}

/**
 * دالة لتحديث حالة الإشعار إلى "مقروء"
 */
function handleMarkAsRead() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }
    
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من البيانات المطلوبة
    if (!isset($data['notification_id'])) {
        sendResponse(400, ['error' => 'البيانات غير مكتملة']);
        return;
    }
    
    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم ومعرف الإشعار
    $userId = $user['id'];
    $notificationId = $data['notification_id'];
    
    // التحقق من أن الإشعار ينتمي للمستخدم
    $query = "SELECT id FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(404, ['error' => 'الإشعار غير موجود']);
        return;
    }
    
    // تحديث حالة الإشعار إلى "مقروء"
    $query = "UPDATE notifications SET is_read = 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notificationId);
    $success = $stmt->execute();
    
    if ($success) {
        sendResponse(200, ['success' => true]);
    } else {
        sendResponse(500, ['error' => 'حدث خطأ أثناء تحديث الإشعار']);
    }
}

/**
 * دالة لحذف إشعار
 */
function handleDeleteNotification() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }
    
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من البيانات المطلوبة
    if (!isset($data['notification_id'])) {
        sendResponse(400, ['error' => 'البيانات غير مكتملة']);
        return;
    }
    
    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم ومعرف الإشعار
    $userId = $user['id'];
    $notificationId = $data['notification_id'];
    
    // التحقق من أن الإشعار ينتمي للمستخدم
    $query = "SELECT id FROM notifications WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $notificationId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(404, ['error' => 'الإشعار غير موجود']);
        return;
    }
    
    // حذف الإشعار
    $query = "DELETE FROM notifications WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notificationId);
    $success = $stmt->execute();
    
    if ($success) {
        sendResponse(200, ['success' => true]);
    } else {
        sendResponse(500, ['error' => 'حدث خطأ أثناء حذف الإشعار']);
    }
}

/**
 * دالة للحصول على عدد الإشعارات غير المقروءة
 */
function handleUnreadCount() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }
    
    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم
    $userId = $user['id'];
    
    // استعلام للحصول على عدد الإشعارات غير المقروءة
    $query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    sendResponse(200, ['count' => (int)$row['count']]);
}

/**
 * دالة لتحديث رمز الإشعارات (FCM token)
 */
function handleUpdateToken() {
    // التحقق من تسجيل دخول المستخدم
    $user = validateToken();
    if (!$user) {
        sendResponse(401, ['error' => 'غير مصرح']);
        return;
    }
    
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من البيانات المطلوبة
    if (!isset($data['token'])) {
        sendResponse(400, ['error' => 'البيانات غير مكتملة']);
        return;
    }
    
    $conn = getDbConnection();
    
    // الحصول على معرف المستخدم ورمز الإشعارات
    $userId = $user['id'];
    $token = $data['token'];
    
    // تحديث رمز الإشعارات للمستخدم
    $query = "UPDATE users SET fcm_token = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $token, $userId);
    $success = $stmt->execute();
    
    if ($success) {
        sendResponse(200, ['success' => true]);
    } else {
        sendResponse(500, ['error' => 'حدث خطأ أثناء تحديث رمز الإشعارات']);
    }
}

/**
 * دالة لإرسال إشعار لمستخدم
 * ملاحظة: هذه الدالة للاستخدام الداخلي فقط وليست جزءًا من واجهة API
 */
function sendNotification($userId, $title, $message, $type, $link = null) {
    $conn = getDbConnection();
    
    // إضافة الإشعار إلى قاعدة البيانات
    $query = "INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issss", $userId, $title, $message, $type, $link);
    $success = $stmt->execute();
    
    if ($success) {
        // الحصول على رمز FCM للمستخدم
        $query = "SELECT fcm_token FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row && !empty($row['fcm_token'])) {
            // إرسال إشعار FCM (يتطلب تكامل مع Firebase)
            // هذا الكود تجريبي ويحتاج إلى تكامل مع Firebase Cloud Messaging
            $fcmToken = $row['fcm_token'];
            // sendFCMNotification($fcmToken, $title, $message, $type, $link);
        }
        
        return true;
    }
    
    return false;
}
?>
