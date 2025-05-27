<?php
// تكوين API وإعدادات قاعدة البيانات
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'marketplace');

// اتصال قاعدة البيانات
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // التحقق من الاتصال
    if ($conn->connect_error) {
        sendResponse(500, ['error' => 'فشل الاتصال بقاعدة البيانات: ' . $conn->connect_error]);
        exit;
    }
    
    // ضبط الترميز
    $conn->set_charset("utf8");
    
    return $conn;
}

// دالة إرسال الاستجابة
function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// دالة للتحقق من المصادقة
function authenticate() {
    // التحقق من وجود رأس التفويض
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        sendResponse(401, ['error' => 'غير مصرح به: رمز المصادقة مفقود']);
    }
    
    // استخراج الرمز
    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);
    
    // التحقق من صحة الرمز (يجب تنفيذ منطق JWT هنا)
    // هذا مثال بسيط، في الإنتاج يجب استخدام مكتبة JWT
    
    // للتبسيط، نفترض أن الرمز صالح ونعيد معرف المستخدم
    // في التطبيق الفعلي، يجب التحقق من صحة الرمز وفك تشفيره
    return [
        'user_id' => 1, // هذا مجرد مثال
        'user_type' => 'customer' // أو 'store' أو 'admin'
    ];
}
?>
