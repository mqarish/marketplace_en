<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Enable error display for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Site constants
define('SITE_URL', 'http://localhost/marketplace_en');

// Database connection information
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'marketplace_en';

// First, try to connect to the MySQL server without specifying a database
try {
    $conn_server = new mysqli($db_host, $db_user, $db_pass);
    
    // Check if connection to server was successful
    if ($conn_server->connect_error) {
        die("MySQL server connection failed: " . $conn_server->connect_error);
    }
    
    // Check if database exists, create it if it doesn't
    $db_exists = $conn_server->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'")->num_rows > 0;
    
    if (!$db_exists) {
        // Create the database with proper character set
        if (!$conn_server->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            die("Error creating database: " . $conn_server->error);
        }
        
        // Log database creation
        error_log("Created new database: $db_name");
    }
    
    // Close the server connection
    $conn_server->close();
    
    // Now connect to the specific database
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Set connection character set
    mysqli_set_charset($conn, "utf8mb4");
    
    // Check connection to database
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('Asia/Riyadh');

// Helper functions
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $random_string;
}

function generate_slug($text) {
    // Convert text to lowercase
    $text = mb_strtolower($text, 'UTF-8');
    
    // Replace spaces with hyphens
    $text = str_replace(' ', '-', $text);
    
    // Remove special characters
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);
    
    // Remove duplicate hyphens
    $text = preg_replace('/-+/', '-', $text);
    
    // Remove hyphens from beginning and end
    $text = trim($text, '-');
    
    return $text;
}

function format_price($price) {
    return number_format($price, 2) . ' SAR';
}

function get_time_ago($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    
    $seconds = $time_difference;
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "seconds ago";
    } else if ($minutes <= 60) {
        if ($minutes == 1) {
            return "1 minute ago";
        } else {
            return "$minutes minutes ago";
        }
    } else if ($hours <= 24) {
        if ($hours == 1) {
            return "1 hour ago";
        } else {
            return "$hours hours ago";
        }
    } else if ($days <= 7) {
        if ($days == 1) {
            return "1 day ago";
        } else {
            return "$days days ago";
        }
    } else if ($weeks <= 4.3) {
        if ($weeks == 1) {
            return "1 week ago";
        } else {
            return "$weeks weeks ago";
        }
    } else if ($months <= 12) {
        if ($months == 1) {
            return "1 month ago";
        } else {
            return "$months months ago";
        }
    } else {
        if ($years == 1) {
            return "1 year ago";
        } else {
            return "$years years ago";
        }
    }
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($sql)) {
    die("Error creating database: " . $conn->error);
}

// Select database
$conn->select_db($db_name);

// Create admin user if it doesn't exist
$admin_check = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if (!$admin_check || $admin_check->num_rows == 0) {
    // Make sure users table exists
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'store', 'customer') NOT NULL,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create admin account
    $admin_username = 'admin';
    $admin_email = 'admin@example.com';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_role = 'admin';

    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssss", $admin_username, $admin_email, $admin_password, $admin_role);
    $stmt->execute();
}

// Site configuration
define('UPLOADS_PATH', __DIR__ . '/../uploads');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0777, true);
    mkdir(UPLOADS_PATH . '/stores', 0777, true);
    mkdir(UPLOADS_PATH . '/products', 0777, true);
}
?>
