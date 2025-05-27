<?php
require_once '../includes/init.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Database connection successful\n";

// Check if tables exist
$tables = ['subscription_packages', 'subscriptions', 'stores', 'users'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "$table table exists\n";
        
        // Show table structure
        $structure = $conn->query("DESCRIBE $table");
        echo "Table structure:\n";
        while ($row = $structure->fetch_assoc()) {
            echo "- {$row['Field']}: {$row['Type']}\n";
        }
    } else {
        echo "$table table does not exist\n";
    }
    echo "\n";
}

// Check for any existing packages
$result = $conn->query("SELECT * FROM subscription_packages");
if ($result) {
    echo "Number of packages: " . $result->num_rows . "\n";
    while ($row = $result->fetch_assoc()) {
        echo "Package: {$row['name']} (Type: {$row['type']})\n";
    }
} else {
    echo "Error checking packages: " . $conn->error . "\n";
}
