<?php
require_once 'includes/init.php';

// Create suggestions table without foreign key constraint
$create_table_sql = "CREATE TABLE IF NOT EXISTS suggestions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    customer_id INT(11) DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    suggestion_text TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'implemented', 'rejected') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Check if table exists first
$check_table = $conn->query("SHOW TABLES LIKE 'suggestions'");
if ($check_table->num_rows > 0) {
    // Table exists, drop it
    $conn->query("DROP TABLE suggestions");
    echo "Old suggestions table dropped<br>";
}

// Create new table
if ($conn->query($create_table_sql) === TRUE) {
    echo "Suggestions table successfully created without foreign key constraint";
} else {
    echo "Error creating suggestions table: " . $conn->error;
}
?>
