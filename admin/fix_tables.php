<?php
require_once '../includes/init.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Fixing Database Tables</h2>";

// Fix users table
$conn->query("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending'");
$conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'store', 'customer') NOT NULL");
$conn->query("ALTER TABLE users ADD UNIQUE INDEX email_unique (email)");

// Fix customers table if needed
$conn->query("ALTER TABLE customers MODIFY COLUMN status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending'");

// Update specific user
$email = 'rqarish120@gmail.com';
$conn->query("UPDATE users SET status = 'active', role = 'customer' WHERE email = '$email'");

echo "<h3>Tables Updated</h3>";
echo "Please try logging in again.";
?>
