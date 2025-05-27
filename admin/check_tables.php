<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

echo "<h2>Database Structure Check</h2>";

// Check users table
echo "<h3>Users Table Structure:</h3>";
$result = $conn->query("SHOW CREATE TABLE users");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
}

// Check customers table
echo "<h3>Customers Table Structure:</h3>";
$result = $conn->query("SHOW CREATE TABLE customers");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<pre>" . htmlspecialchars($row['Create Table']) . "</pre>";
}

// Show sample data from both tables
echo "<h3>Sample Users:</h3>";
$result = $conn->query("SELECT id, username, email, role, status FROM users LIMIT 5");
if ($result) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
}

echo "<h3>Sample Customers:</h3>";
$result = $conn->query("SELECT * FROM customers LIMIT 5");
if ($result) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
}

// Check for the specific email
if (isset($_GET['email'])) {
    $email = $_GET['email'];
    echo "<h3>Checking specific email: " . htmlspecialchars($email) . "</h3>";
    
    // Check in users table
    $stmt = $conn->prepare("SELECT id, username, email, role, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "<h4>Found in users table:</h4>";
        echo "<pre>";
        print_r($result->fetch_assoc());
        echo "</pre>";
    } else {
        echo "<p>Not found in users table</p>";
    }
    
    // Check in customers table
    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo "<h4>Found in customers table:</h4>";
        echo "<pre>";
        print_r($result->fetch_assoc());
        echo "</pre>";
    } else {
        echo "<p>Not found in customers table</p>";
    }
}
?>
