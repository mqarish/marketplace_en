<?php
// Ensure no output before starting the session
ob_start();

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set page encoding
header('Content-Type: text/html; charset=utf-8');

// Include configuration file if not already included
if (!isset($conn)) {
    require_once __DIR__ . '/config.php';
}

// Define base URL
if (!defined('BASE_URL')) {
    // Check current environment (local or production)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    // If local environment
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        define('BASE_URL', '/marketplace_en');
    } 
    // If production environment
    else {
        define('BASE_URL', '');
    }
}

// Define file paths
if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', dirname(__DIR__) . '/uploads');
}

// Include translations
require_once __DIR__ . '/translations.php';

// Include helper functions
require_once __DIR__ . '/functions.php';

// Check user status if logged in
if (isset($_SESSION['customer_id'])) {
    // First check if the customers table exists to avoid errors during initial setup
    $table_exists = false;
    $check_table = $conn->query("SHOW TABLES LIKE 'customers'");
    if ($check_table) {
        $table_exists = ($check_table->num_rows > 0);
    }
    
    if ($table_exists) {
        $customer_id = $_SESSION['customer_id'];
        $stmt = $conn->prepare("SELECT status FROM customers WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $customer = $result->fetch_assoc();
            $stmt->close();

            // If account is suspended or inactive
            if ($customer && $customer['status'] !== 'active') {
                session_destroy();
                $_SESSION['error'] = "Sorry, your account is not active. Please wait until your account is approved by the administration.";
                header('Location: ' . BASE_URL . '/customer/login.php');
                exit();
            }
        }
    }
}
