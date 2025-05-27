<?php
require_once '../includes/init.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Setting up Database Structure</h2>";

try {
    // Create users table
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'store', 'customer') NOT NULL,
        status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY email_unique (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Users table created/verified<br>";

    // Create customers table
    $conn->query("CREATE TABLE IF NOT EXISTS customers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Customers table created/verified<br>";

    // Create stores table
    $conn->query("CREATE TABLE IF NOT EXISTS stores (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        description TEXT,
        logo VARCHAR(255),
        status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Stores table created/verified<br>";

    // Create categories table
    $conn->query("CREATE TABLE IF NOT EXISTS categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Categories table created/verified<br>";

    // Create products table
    $conn->query("CREATE TABLE IF NOT EXISTS products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        store_id INT NOT NULL,
        category_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        image VARCHAR(255),
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Products table created/verified<br>";

    // Create orders table
    $conn->query("CREATE TABLE IF NOT EXISTS orders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Orders table created/verified<br>";

    // Create order_items table
    $conn->query("CREATE TABLE IF NOT EXISTS order_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Order items table created/verified<br>";

    // Check if admin exists, if not create default admin
    $result = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if ($result->num_rows == 0) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (username, email, password, role, status) 
                     VALUES ('admin', 'admin@example.com', '$admin_password', 'admin', 'active')");
        echo "✓ Default admin account created<br>";
    }

    echo "<h3>Database Setup Complete!</h3>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Login as admin with:<br>Email: admin@example.com<br>Password: admin123</li>";
    echo "<li>Register a new customer account</li>";
    echo "<li>Register a new store account</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
}

// Show current tables
echo "<h3>Current Database Tables:</h3>";
$result = $conn->query("SHOW TABLES");
echo "<ul>";
while ($row = $result->fetch_row()) {
    $table = $row[0];
    echo "<li>$table</li>";
    
    // Show table structure
    $structure = $conn->query("SHOW CREATE TABLE $table");
    if ($structure) {
        $row = $structure->fetch_assoc();
        echo "<pre style='margin-left: 20px; font-size: 0.9em;'>" . htmlspecialchars($row['Create Table']) . "</pre>";
    }
}
echo "</ul>";
?>
