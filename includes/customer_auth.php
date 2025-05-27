<?php
// التحقق من تسجيل دخول العميل
function check_customer_auth() {
    if (!isset($_SESSION['customer_id'])) {
        // حفظ الصفحة الحالية للعودة إليها بعد تسجيل الدخول
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: " . BASE_URL . "/customer/login.php");
        exit();
    }
}

// التحقق من حالة العميل
function check_customer_status($conn, $customer_id) {
    $stmt = $conn->prepare("SELECT status FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $customer = $result->fetch_assoc();
        if ($customer['status'] !== 'active') {
            session_destroy();
            header("Location: " . BASE_URL . "/customer/login.php?error=account_inactive");
            exit();
        }
    } else {
        session_destroy();
        header("Location: " . BASE_URL . "/customer/login.php?error=invalid_account");
        exit();
    }
}
