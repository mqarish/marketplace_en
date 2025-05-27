<?php
/**
 * Database Setup Script for English Marketplace
 * This script will create all necessary tables for the English version of the marketplace
 */

// Include database configuration
require_once 'includes/config.php';

// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to execute SQL query
function executeQuery($conn, $sql, $message) {
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>✓ $message</p>";
        return true;
    } else {
        echo "<p style='color:red;'>✗ Error: " . $conn->error . "</p>";
        return false;
    }
}

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database - English Marketplace</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Setting up English Marketplace Database</h1>
        
        <?php
        // Check connection
        if ($conn->connect_error) {
            die("<p class='error'>Connection failed: " . $conn->connect_error . "</p>");
        }
        
        echo "<p class='success'>Connected to database successfully!</p>";
        
        // Create users table
        $sql_users = "CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `email` varchar(100) NOT NULL,
            `password` varchar(255) NOT NULL,
            `role` enum('admin','store','customer') NOT NULL DEFAULT 'customer',
            `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_users, "Users table created successfully");
        
        // Create customers table
        $sql_customers = "CREATE TABLE IF NOT EXISTS `customers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `name` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `password` varchar(255) DEFAULT NULL,
            `address` text,
            `city` varchar(50) DEFAULT NULL,
            `status` enum('active','inactive','suspended','pending','blocked') NOT NULL DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `email` (`email`(100)),
            KEY `user_id` (`user_id`),
            CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_customers, "Customers table created successfully");
        
        // Create stores table
        $sql_stores = "CREATE TABLE IF NOT EXISTS `stores` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `name` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL,
            `phone` varchar(20) DEFAULT NULL,
            `address` text,
            `city` varchar(50) DEFAULT NULL,
            `description` text,
            `logo` varchar(255) DEFAULT NULL,
            `status` enum('active','pending','suspended') NOT NULL DEFAULT 'pending',
            `password` varchar(255) DEFAULT NULL,
            `facebook_url` varchar(255) DEFAULT NULL,
            `twitter_url` varchar(255) DEFAULT NULL,
            `instagram_url` varchar(255) DEFAULT NULL,
            `whatsapp` varchar(20) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `stores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_stores, "Stores table created successfully");
        
        // Create categories table
        $sql_categories = "CREATE TABLE IF NOT EXISTS `categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `description` text,
            `icon` varchar(50) DEFAULT NULL,
            `parent_id` int(11) DEFAULT NULL,
            `status` enum('active','inactive') NOT NULL DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `parent_id` (`parent_id`),
            CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_categories, "Categories table created successfully");
        
        // Create store_categories table
        $sql_store_categories = "CREATE TABLE IF NOT EXISTS `store_categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `store_id` int(11) NOT NULL,
            `category_id` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `store_category` (`store_id`,`category_id`),
            KEY `category_id` (`category_id`),
            CONSTRAINT `store_categories_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
            CONSTRAINT `store_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_store_categories, "Store Categories table created successfully");
        
        // Create products table
        $sql_products = "CREATE TABLE IF NOT EXISTS `products` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `store_id` int(11) NOT NULL,
            `category_id` int(11) DEFAULT NULL,
            `name` varchar(100) NOT NULL,
            `description` text,
            `price` decimal(10,2) NOT NULL,
            `discount_price` decimal(10,2) DEFAULT NULL,
            `quantity` int(11) NOT NULL DEFAULT '0',
            `image` varchar(255) DEFAULT NULL,
            `image_url` varchar(255) DEFAULT NULL,
            `status` enum('active','inactive','out_of_stock') NOT NULL DEFAULT 'active',
            `hide_price` tinyint(1) NOT NULL DEFAULT '0',
            `featured` tinyint(1) NOT NULL DEFAULT '0',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `store_id` (`store_id`),
            KEY `category_id` (`category_id`),
            CONSTRAINT `products_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
            CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_products, "Products table created successfully");
        
        // Create product_images table
        $sql_product_images = "CREATE TABLE IF NOT EXISTS `product_images` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `image` varchar(255) NOT NULL,
            `sort_order` int(11) NOT NULL DEFAULT '0',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `product_id` (`product_id`),
            CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_product_images, "Product Images table created successfully");
        
        // Create orders table
        $sql_orders = "CREATE TABLE IF NOT EXISTS `orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `total_amount` decimal(10,2) NOT NULL,
            `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
            `shipping_address` text NOT NULL,
            `shipping_city` varchar(50) NOT NULL,
            `shipping_phone` varchar(20) NOT NULL,
            `payment_method` enum('cash_on_delivery','credit_card','bank_transfer') NOT NULL DEFAULT 'cash_on_delivery',
            `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
            `notes` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `customer_id` (`customer_id`),
            CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_orders, "Orders table created successfully");
        
        // Create order_items table
        $sql_order_items = "CREATE TABLE IF NOT EXISTS `order_items` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `product_id` int(11) NOT NULL,
            `store_id` int(11) NOT NULL,
            `quantity` int(11) NOT NULL,
            `price` decimal(10,2) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `order_id` (`order_id`),
            KEY `product_id` (`product_id`),
            KEY `store_id` (`store_id`),
            CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
            CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
            CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_order_items, "Order Items table created successfully");
        
        // Create reviews table
        $sql_reviews = "CREATE TABLE IF NOT EXISTS `reviews` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `customer_id` int(11) NOT NULL,
            `rating` int(11) NOT NULL,
            `comment` text,
            `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `product_id` (`product_id`),
            KEY `customer_id` (`customer_id`),
            CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
            CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_reviews, "Reviews table created successfully");
        
        // Create customer_addresses table
        $sql_customer_addresses = "CREATE TABLE IF NOT EXISTS `customer_addresses` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `title` varchar(100) NOT NULL,
            `recipient_name` varchar(100) NOT NULL,
            `phone` varchar(20) NOT NULL,
            `city` varchar(50) NOT NULL,
            `area` varchar(100) DEFAULT NULL,
            `street` varchar(100) NOT NULL,
            `building` varchar(100) DEFAULT NULL,
            `apartment` varchar(50) DEFAULT NULL,
            `notes` text,
            `is_default` tinyint(1) NOT NULL DEFAULT '0',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `customer_id` (`customer_id`),
            CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_customer_addresses, "Customer Addresses table created successfully");
        
        // Create product_likes table
        $sql_product_likes = "CREATE TABLE IF NOT EXISTS `product_likes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `customer_id` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `product_customer` (`product_id`,`customer_id`),
            KEY `customer_id` (`customer_id`),
            CONSTRAINT `product_likes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
            CONSTRAINT `product_likes_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_product_likes, "Product Likes table created successfully");
        
        // Create offers table
        $sql_offers = "CREATE TABLE IF NOT EXISTS `offers` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(100) NOT NULL,
            `description` text,
            `discount_percentage` decimal(5,2) NOT NULL,
            `start_date` datetime NOT NULL,
            `end_date` datetime NOT NULL,
            `status` enum('active','inactive','expired') NOT NULL DEFAULT 'active',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_offers, "Offers table created successfully");
        
        // Create offer_store_products table
        $sql_offer_store_products = "CREATE TABLE IF NOT EXISTS `offer_store_products` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `offer_id` int(11) NOT NULL,
            `store_id` int(11) NOT NULL,
            `product_id` int(11) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `offer_id` (`offer_id`),
            KEY `store_id` (`store_id`),
            KEY `product_id` (`product_id`),
            CONSTRAINT `offer_store_products_ibfk_1` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE,
            CONSTRAINT `offer_store_products_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
            CONSTRAINT `offer_store_products_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        executeQuery($conn, $sql_offer_store_products, "Offer Store Products table created successfully");
        
        // Create admin user
        $admin_username = "admin";
        $admin_email = "admin@example.com";
        $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
        
        $check_admin = $conn->query("SELECT id FROM users WHERE email = 'admin@example.com' AND role = 'admin'");
        
        if ($check_admin->num_rows == 0) {
            $sql_admin = "INSERT INTO users (username, email, password, role, status) VALUES ('$admin_username', '$admin_email', '$admin_password', 'admin', 'active')";
            executeQuery($conn, $sql_admin, "Admin user created successfully (Email: admin@example.com, Password: admin123)");
        } else {
            echo "<p>Admin user already exists.</p>";
        }
        
        // Close connection
        $conn->close();
        ?>
        
        <div style="margin-top: 20px;">
            <p class="success">Database setup completed successfully!</p>
            <p>You can now <a href="index.php">go to the homepage</a> or <a href="admin/login.php">login to admin panel</a>.</p>
        </div>
    </div>
</body>
</html>
