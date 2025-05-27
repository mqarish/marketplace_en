<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

echo "<h2>Merging Users and Customers Tables</h2>";

try {
    $conn->begin_transaction();

    // 1. First get all customers
    $customers_query = "SELECT * FROM customers";
    $customers_result = $conn->query($customers_query);
    $migrated_count = 0;
    $errors = [];

    if ($customers_result) {
        while ($customer = $customers_result->fetch_assoc()) {
            // Check if user already exists in users table
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->bind_param("s", $customer['email']);
            $check_stmt->execute();
            $existing_user = $check_stmt->get_result()->fetch_assoc();
            
            if ($existing_user) {
                // Update existing user
                $update_stmt = $conn->prepare("
                    UPDATE users 
                    SET username = ?,
                        role = 'customer',
                        status = 'active'
                    WHERE email = ?
                ");
                $update_stmt->bind_param("ss", $customer['name'], $customer['email']);
                $update_stmt->execute();
            } else {
                // Create new user
                $insert_stmt = $conn->prepare("
                    INSERT INTO users (username, email, password, role, status, created_at)
                    VALUES (?, ?, ?, 'customer', 'active', NOW())
                ");
                // Use a default hashed password that can be reset later
                $default_password = password_hash($customer['email'], PASSWORD_DEFAULT);
                $insert_stmt->bind_param("sss", $customer['name'], $customer['email'], $default_password);
                $insert_stmt->execute();
            }
            $migrated_count++;
        }
    }

    // 2. Ensure all users table has correct structure
    $alter_queries = [
        "ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'pending') DEFAULT 'pending'",
        "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'customer') DEFAULT 'customer'"
    ];

    foreach ($alter_queries as $query) {
        $conn->query($query);
    }

    $conn->commit();
    echo "<div style='color: green; margin: 10px 0;'>Successfully migrated $migrated_count customers to users table.</div>";

} catch (Exception $e) {
    $conn->rollback();
    echo "<div style='color: red; margin: 10px 0;'>Error: " . $e->getMessage() . "</div>";
}

// Now update the login system to handle both tables
echo "<h2>Updating Login System</h2>";

try {
    // Create a view that combines both tables
    $create_view_sql = "
    CREATE OR REPLACE VIEW customer_view AS
    SELECT 
        u.id,
        u.username as name,
        u.email,
        u.password,
        u.status,
        u.role
    FROM users u
    WHERE u.role = 'customer'
    ";
    
    $conn->query($create_view_sql);
    echo "<div style='color: green; margin: 10px 0;'>Successfully created customer view.</div>";

} catch (Exception $e) {
    echo "<div style='color: red; margin: 10px 0;'>Error creating view: " . $e->getMessage() . "</div>";
}
?>
