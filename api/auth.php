<?php
require_once 'config.php';

// التعامل مع طلبات المصادقة
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($method) {
    case 'POST':
        if ($action == 'login') {
            handleLogin();
        } elseif ($action == 'register') {
            handleRegister();
        } else {
            sendResponse(400, ['error' => 'إجراء غير صالح']);
        }
        break;
    default:
        sendResponse(405, ['error' => 'طريقة غير مسموح بها']);
        break;
}

// دالة تسجيل الدخول
function handleLogin() {
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من البيانات المطلوبة
    if (!isset($data['email']) || !isset($data['password']) || !isset($data['user_type'])) {
        sendResponse(400, ['error' => 'البيانات غير مكتملة']);
    }
    
    $email = $data['email'];
    $password = $data['password'];
    $userType = $data['user_type']; // 'customer', 'store', or 'admin'
    
    // اتصال بقاعدة البيانات
    $conn = getDbConnection();
    
    // تحديد الجدول بناءً على نوع المستخدم
    $table = '';
    switch ($userType) {
        case 'customer':
            $table = 'customers';
            break;
        case 'store':
            $table = 'stores';
            break;
        case 'admin':
            $table = 'admins';
            break;
        default:
            sendResponse(400, ['error' => 'نوع مستخدم غير صالح']);
            break;
    }
    
    // استعلام للتحقق من بيانات الاعتماد
    $stmt = $conn->prepare("SELECT * FROM $table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(401, ['error' => 'بيانات الاعتماد غير صالحة']);
    }
    
    $user = $result->fetch_assoc();
    
    // التحقق من كلمة المرور
    if (!password_verify($password, $user['password'])) {
        sendResponse(401, ['error' => 'بيانات الاعتماد غير صالحة']);
    }
    
    // إنشاء رمز JWT (في الإنتاج، استخدم مكتبة JWT)
    // هذا مجرد مثال بسيط
    $token = bin2hex(random_bytes(32));
    
    // إعداد استجابة المستخدم
    $response = [
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'user_type' => $userType
        ]
    ];
    
    sendResponse(200, $response);
}

// دالة التسجيل
function handleRegister() {
    // الحصول على بيانات JSON من الطلب
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من البيانات المطلوبة
    if (!isset($data['name']) || !isset($data['email']) || !isset($data['password']) || !isset($data['user_type'])) {
        sendResponse(400, ['error' => 'البيانات غير مكتملة']);
    }
    
    $name = $data['name'];
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $userType = $data['user_type']; // 'customer', 'store', or 'admin'
    
    // اتصال بقاعدة البيانات
    $conn = getDbConnection();
    
    // تحديد الجدول بناءً على نوع المستخدم
    $table = '';
    switch ($userType) {
        case 'customer':
            $table = 'customers';
            break;
        case 'store':
            $table = 'stores';
            break;
        default:
            sendResponse(400, ['error' => 'نوع مستخدم غير صالح']);
            break;
    }
    
    // التحقق من وجود البريد الإلكتروني
    $stmt = $conn->prepare("SELECT id FROM $table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        sendResponse(409, ['error' => 'البريد الإلكتروني مستخدم بالفعل']);
    }
    
    // إدراج المستخدم الجديد
    $stmt = $conn->prepare("INSERT INTO $table (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);
    
    if (!$stmt->execute()) {
        sendResponse(500, ['error' => 'فشل في إنشاء الحساب']);
    }
    
    // إنشاء رمز JWT (في الإنتاج، استخدم مكتبة JWT)
    // هذا مجرد مثال بسيط
    $token = bin2hex(random_bytes(32));
    
    // إعداد استجابة المستخدم
    $response = [
        'token' => $token,
        'user' => [
            'id' => $conn->insert_id,
            'name' => $name,
            'email' => $email,
            'user_type' => $userType
        ]
    ];
    
    sendResponse(201, $response);
}
?>
