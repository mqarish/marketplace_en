<?php
require_once 'config.php';

// دالة لتنظيف المدخلات
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(trim($input));
}

// دالة للتحقق من تسجيل الدخول
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// دالة للتحقق من صلاحيات المستخدم
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// دالة للتحقق من صلاحيات المدير
function isAdmin() {
    return hasRole('admin');
}

// دالة للتحقق من صلاحيات صاحب المتجر
function isStore() {
    return hasRole('store');
}

// دالة للتحقق من صلاحيات العميل
function isCustomer() {
    return hasRole('customer');
}

// دالة لتنسيق السعر
function formatPrice($price) {
    return number_format($price, 2) . ' ريال';
}

// دالة لتحويل التاريخ إلى صيغة مناسبة
function formatDate($date) {
    return date('Y-m-d h:i A', strtotime($date));
}

// دالة للتحقق من امتداد الصورة
function isValidImageType($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    return in_array($file['type'], $allowed_types);
}

// Function to upload an image
function uploadImage($file, $upload_path) {
    // Modified function to match the expected return format in edit-offer.php
    $result = [
        'status' => 'success',
        'message' => '',
        'path' => ''
    ];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $result['status'] = 'error';
        $result['message'] = 'Error occurred during file upload';
        return $result;
    }

    // Check file type
    if (!isValidImageType($file)) {
        $result['status'] = 'error';
        $result['message'] = 'File type not supported. Please choose JPG, PNG, or GIF image';
        return $result;
    }

    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $result['status'] = 'error';
        $result['message'] = 'File size is too large. Maximum size is 5MB';
        return $result;
    }

    // Create unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . '.' . $file_extension;
    $target_path = $upload_path . $file_name;

    // Ensure upload directory exists
    if (!file_exists($upload_path)) {
        if (!mkdir($upload_path, 0777, true)) {
            $result['status'] = 'error';
            $result['message'] = 'Failed to create upload directory';
            return $result;
        }
    }

    // Move uploaded file to target directory
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        $result['status'] = 'error';
        $result['message'] = 'Failed to upload file';
        return $result;
    }

    // Set file permissions
    chmod($target_path, 0777);

    // Set relative path (without ../)
    $relative_path = str_replace('../', '', $upload_path) . $file_name;
    $result['path'] = $relative_path;
    
    return $result;
}

// دالة لحذف الصورة
function deleteImage($filename, $type = 'stores') {
    if (empty($filename)) {
        return;
    }

    $file_path = __DIR__ . '/../uploads/' . $type . '/' . $filename;
    if (file_exists($file_path)) {
        unlink($file_path);
    }
}

// دالة للتحقق من وجود المتجر
function getStoreId() {
    if (!isset($_SESSION['store_id'])) {
        return false;
    }
    return $_SESSION['store_id'];
}

// دالة لجلب تفاصيل المتجر
function getStoreDetails($store_id) {
    global $conn;
    
    $sql = "SELECT * FROM stores WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// دالة للتحقق من امتداد الصورة
function is_valid_image($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    return in_array($file['type'], $allowed_types);
}

// دالة لرفع الصورة
function upload_image($file, $target_dir) {
    if (!is_valid_image($file)) {
        return false;
    }
    
    $target_file = $target_dir . basename($file["name"]);
    return move_uploaded_file($file["tmp_name"], $target_file) ? basename($file["name"]) : false;
}

// دالة لتنظيف المدخلات
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// دالة لإنشاء رسالة نجاح
function success_message($message) {
    return "<div class='alert alert-success'>" . $message . "</div>";
}

// دالة لإنشاء رسالة خطأ
function error_message($message) {
    return "<div class='alert alert-danger'>" . $message . "</div>";
}

// دالة للتحقق من تسجيل دخول المشرف
function ensure_admin_login() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: /marketplace/admin/login.php');
        exit();
    }
}

// دالة للتحقق من تسجيل دخول المتجر
function ensure_store_login() {
    if (!isset($_SESSION['store_id'])) {
        header('Location: /marketplace/store/login.php');
        exit();
    }
}

// دالة للتحقق من تسجيل دخول العميل
function ensure_customer_login() {
    if (!isset($_SESSION['customer_id'])) {
        header('Location: /marketplace/customer/login.php');
        exit();
    }
}

// دالة للتحقق من تسجيل دخول المستخدم
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// دالة للتحقق من وجود الصورة وإرجاع المسار الصحيح
function get_image_path($image_name, $type = 'stores') {
    if (empty($image_name)) {
        return '';
    }

    $file_path = __DIR__ . '/../uploads/' . $type . '/' . $image_name;
    if (file_exists($file_path)) {
        return 'uploads/' . $type . '/' . $image_name;
    }

    return '';
}

// دالة لحفظ الصورة المرفوعة
function save_uploaded_image($file, $type = 'stores') {
    $upload_path = __DIR__ . '/../uploads/' . $type . '/';
    
    try {
        $result = uploadImage($file, $upload_path);
        if ($result['success']) {
            return $result['filename'];
        } else {
            return false;
        }
    } catch (Exception $e) {
        return false;
    }
}

// دالة للحصول على لون حالة الطلب
function get_order_status_color($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'processing':
            return 'info';
        case 'shipped':
            return 'primary';
        case 'delivered':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}

// دالة للحصول على نص حالة الطلب
function get_order_status_text($status) {
    switch ($status) {
        case 'pending':
            return 'قيد الانتظار';
        case 'processing':
            return 'قيد المعالجة';
        case 'completed':
            return 'مكتمل';
        case 'cancelled':
            return 'ملغي';
        default:
            return 'غير معروف';
    }
}

// دالة للحصول على لون دور المستخدم
function get_role_color($role) {
    switch ($role) {
        case 'admin':
            return 'danger';
        case 'store':
            return 'success';
        case 'customer':
            return 'primary';
        default:
            return 'secondary';
    }
}

// دالة للحصول على اسم دور المستخدم بالعربية
function get_role_name($role) {
    switch ($role) {
        case 'admin':
            return 'مدير';
        case 'store':
            return 'متجر';
        case 'customer':
            return 'عميل';
        default:
            return 'غير معروف';
    }
}

// دالة لإنشاء رسالة تنبيه
function setAlert($message, $type = 'success') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// دالة لعرض رسالة التنبيه
function showAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        
        $type_class = $alert['type'] == 'success' ? 'alert-success' : 'alert-danger';
        echo '<div class="alert ' . $type_class . ' alert-dismissible fade show" role="alert">';
        echo $alert['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
}

?>
