<?php
require_once '../includes/init.php';
// Start session only if it doesn't exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is already logged in
if (isset($_SESSION['store_id'])) {
    header('Location: index.php');
    exit();
}

// Process registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $description = $_POST['description'] ?? '';
    $selected_categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    $errors = [];

    // Validate data
    if (empty($name)) {
        $errors[] = "Please enter the store name";
    }
    if (empty($email)) {
        $errors[] = "Please enter your email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address";
    }
    if (empty($password)) {
        $errors[] = "Please enter your password";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    if (empty($phone)) {
        $errors[] = "Please enter your phone number";
    }
    if (empty($address)) {
        $errors[] = "Please enter the store address";
    }
    if (empty($city)) {
        $errors[] = "Please enter the city";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM stores WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email is already in use";
        }
        $stmt->close();
    }

    // If there are no errors, create the store
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // التحقق من وجود جدول stores
            $check_table = $conn->query("SHOW TABLES LIKE 'stores'");
            if ($check_table->num_rows == 0) {
                // إنشاء جدول stores إذا لم يكن موجوداً
                $create_table = "CREATE TABLE stores (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    address TEXT NOT NULL,
                    description TEXT,
                    status ENUM('pending', 'active', 'blocked') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $conn->query($create_table);
            }
            
            // Handle logo upload if selected
            $logo_name = null;
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                $upload_dir = '../uploads/stores/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $logo_name = uniqid('store_') . '.' . $file_extension;
                $upload_path = $upload_dir . $logo_name;
                
                // Check file type
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array(strtolower($file_extension), $allowed_extensions)) {
                    $errors[] = "Unsupported file format. Supported formats are: " . implode(', ', $allowed_extensions);
                }
                
                // Check file size (5MB maximum)
                if ($_FILES['logo']['size'] > 5 * 1024 * 1024) {
                    $errors[] = "File size is too large. Maximum size is 5MB";
                }
                
                if (empty($errors)) {
                    if (!move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                        $errors[] = "An error occurred while uploading the logo";
                        $logo_name = null;
                    }
                }
            }
            
            // التحقق من وجود جدول stores
            $check_table = $conn->query("SHOW TABLES LIKE 'stores'");
            if ($check_table->num_rows == 0) {
                // إنشاء جدول stores إذا لم يكن موجوداً
                $create_table = "CREATE TABLE stores (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    phone VARCHAR(20) NOT NULL,
                    address TEXT NOT NULL,
                    city VARCHAR(100) NOT NULL,
                    description TEXT,
                    logo VARCHAR(255),
                    status ENUM('pending', 'active', 'blocked') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                $conn->query($create_table);
                
                // Create store categories table if it doesn't exist
                $create_store_categories = "CREATE TABLE IF NOT EXISTS store_categories (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    store_id INT NOT NULL,
                    category_id INT NOT NULL,
                    UNIQUE KEY store_category (store_id, category_id)
                )";
                $conn->query($create_store_categories);
            }
            
            $stmt = $conn->prepare("INSERT INTO stores (name, email, password, phone, address, city, description, logo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
            if (!$stmt) {
                throw new Exception("Error in query preparation: " . $conn->error);
            }
            
            $stmt->bind_param("ssssssss", $name, $email, $hashed_password, $phone, $address, $city, $description, $logo_name);
            
            if (!$stmt->execute()) {
                throw new Exception("Error in query execution: " . $stmt->error);
            }
            
            // Add store categories
            $store_id = $conn->insert_id;
            if (!empty($selected_categories)) {
                $category_stmt = $conn->prepare("INSERT INTO store_categories (store_id, category_id) VALUES (?, ?)");
                foreach ($selected_categories as $category_id) {
                    $category_stmt->bind_param("ii", $store_id, $category_id);
                    $category_stmt->execute();
                }
                $category_stmt->close();
            }
            
            // Display success message
            $_SESSION['success_message'] = "Your store has been created successfully. Please wait for your store to be approved by the administration.";
            header('Location: login.php');
            exit();
            
        } catch (Exception $e) {
            $errors[] = "An error occurred while creating the store: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Create New Store - Electronic Marketplace</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
        }
        
        .form-label {
            font-weight: 600;
            color: white;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        
        @media (max-width: 576px) {
            .form-label {
                font-size: 0.8rem;
                margin-bottom: 0.15rem;
            }
        }
        
        .form-check-label {
            color: white;
            font-size: 0.8rem;
        }
        
        @media (max-width: 576px) {
            .form-check-label {
                font-size: 0.75rem;
            }
        }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body {
            background-image: url('store.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(0, 0, 0, 0.6) 0%, 
                rgba(0, 0, 0, 0.5) 50%, 
                rgba(0, 0, 0, 0.4) 100%
            );
            z-index: 1;
        }
        
        .container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1200px;
            padding: 0 15px;
        }
        
        .register-container {
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            margin: 10px 0;
            color: white;
            max-height: 85vh;
            overflow-y: auto;
            font-size: 0.85rem;
        }
        
        @media (max-width: 576px) {
            .register-container {
                padding: 12px;
                margin: 5px 0;
                max-height: 80vh;
                font-size: 0.8rem;
            }
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="register-container">
                    <h2 class="text-center mb-2" style="font-size: 1.3rem;">Create New Store</h2>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="row g-2" enctype="multipart/form-data">
                        <div class="col-6">
                            <label for="name" class="form-label">Store Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-6">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('#password', '#togglePassword')">
                                    <i class="bi bi-eye" id="togglePassword"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-6">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('#confirm_password', '#toggleConfirmPassword')">
                                    <i class="bi bi-eye" id="toggleConfirmPassword"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-6">
                            <label for="address" class="form-label">Store Address</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($address ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-6">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo htmlspecialchars($city ?? ''); ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Store Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        </div>
                        
                        <div class="col-12 mb-1">
                            <label for="logo" class="form-label">Store Logo</label>
                            <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                            <div class="form-text" style="font-size: 0.8rem;">Optional - Preferably PNG or JPG format with an appropriate size</div>
                        </div>
                        
                        <div class="col-12 mb-1">
                            <label class="form-label">Store Categories</label>
                            <div class="form-text mb-1" style="font-size: 0.8rem;">Choose categories that suit your store (you can edit them later)</div>
                            <div class="row">
                                <?php
                                // Get all categories if they exist
                                $categories_query = "SELECT * FROM categories ORDER BY name";
                                $categories_result = $conn->query($categories_query);
                                if ($categories_result && $categories_result->num_rows > 0) {
                                    while ($category = $categories_result->fetch_assoc()) {
                                        echo '<div class="col-6 mb-1">';
                                        echo '<div class="form-check">';
                                        echo '<input class="form-check-input" type="checkbox" name="categories[]" value="' . $category['id'] . '" id="category_' . $category['id'] . '">';
                                        echo '<label class="form-check-label" for="category_' . $category['id'] . '">';
                                        if (!empty($category['icon'])) {
                                            echo '<i class="bi bi-' . htmlspecialchars($category['icon']) . ' me-2"></i>';
                                        }
                                        echo htmlspecialchars($category['name']);
                                        echo '</label>';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                } else {
                                    echo '<div class="col-12"><div class="alert alert-info">Categories will be assigned later by the administration</div></div>';
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-2">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-sm py-1">Create Store</button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="text-center mt-2">
                        <p style="font-size: 0.8rem;">Already have a store? <a href="login.php">Login</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.querySelector(inputId);
            const icon = document.querySelector(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
