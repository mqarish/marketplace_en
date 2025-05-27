<?php
/**
 * Main entry point for the English Marketplace
 * Redirects to the setup page if database is not set up yet
 * Otherwise redirects to the customer homepage
 */

// Check if we need to set up the database
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'marketplace_en';

// Connect to the database
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Check if tables exist
    $tables_exist = false;
    $check_tables = $conn->query("SHOW TABLES LIKE 'customers'");
    if ($check_tables) {
        $tables_exist = ($check_tables->num_rows > 0);
    }
    
    // Close connection
    $conn->close();
    
    if (!$tables_exist) {
        // Database exists but tables don't, redirect to setup
        header('Location: setup_index.php');
        exit();
    } else {
        // Database and tables exist, redirect to customer homepage
        header('Location: customer/index.php');
        exit();
    }
} catch (Exception $e) {
    // Database connection failed, redirect to setup
    header('Location: setup_index.php');
    exit();
}
?>