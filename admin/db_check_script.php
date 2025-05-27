<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

echo "<h2>Database Structure Check</h2>";

// Check users table structure
$result = $conn->query("DESCRIBE users");
if ($result) {
    echo "<h3>Users Table Structure:</h3>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
}

// Check for pending users
$result = $conn->query("SELECT * FROM users WHERE role = 'customer' AND status = 'pending'");
if ($result) {
    echo "<h3>Pending Customers:</h3>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
}

// Check if customers table exists and show its structure
$result = $conn->query("SHOW TABLES LIKE 'customers'");
if ($result->num_rows > 0) {
    $result = $conn->query("DESCRIBE customers");
    echo "<h3>Customers Table Structure (if exists):</h3>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
}
?>
