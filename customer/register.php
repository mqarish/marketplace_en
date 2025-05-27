<?php
require_once '../includes/init.php';
// Start session only if it doesn't exist
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit();
}

// Process registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $errors = [];

    // Validate data
    if (empty($name)) {
        $errors[] = "Please enter your name";
    }
    if (empty($email)) {
        $errors[] = "Please enter your email address";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address";
    }
    if (empty($phone)) {
        $errors[] = "Please enter your phone number";
    } elseif (!preg_match('/^[0-9]{9}$/', $phone)) {
        $errors[] = "Invalid phone number, must be exactly 9 digits";
    }
    if (empty($password)) {
        $errors[] = "Please enter a password";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email already exists
    if (empty($errors)) {
        // First check if the customers table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'customers'");
        if ($table_check === false) {
            $errors[] = "Database error: " . $conn->error;
        } elseif ($table_check->num_rows == 0) {
            // Table doesn't exist yet, so no need to check for duplicate email
            // We'll create the table later
        } else {
            // Table exists, check for duplicate email
            $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
            if ($stmt === false) {
                $errors[] = "Database error: " . $conn->error;
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $errors[] = "Email address is already in use";
                }
                $stmt->close();
            }
        }
    }

    // If no errors, create the account
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Check if customers table exists
            $check_table = $conn->query("SHOW TABLES LIKE 'customers'");
            if ($check_table === false) {
                throw new Exception("Error checking if table exists: " . $conn->error);
            }
            
            if ($check_table->num_rows == 0) {
                // Create customers table if it doesn't exist
                // Instead of creating the table here, let's redirect to the setup page
                throw new Exception("The database tables are not set up properly. Please run the database setup process first by visiting <a href='/marketplace_en/setup_index.php'>setup page</a>.");
                
                $create_result = $conn->query($create_table);
                if ($create_result === false) {
                    throw new Exception("Error creating customers table: " . $conn->error);
                }
                
                // Verify table was created
                $verify_table = $conn->query("SHOW TABLES LIKE 'customers'");
                if ($verify_table->num_rows == 0) {
                    throw new Exception("Failed to create customers table. Please run the database setup process first.");
                }
            }
            
            // Now insert the new customer
            $stmt = $conn->prepare("INSERT INTO `customers` (`name`, `email`, `phone`, `password`, `status`, `created_at`) VALUES (?, ?, ?, ?, 'active', NOW())");
            if ($stmt === false) {
                throw new Exception("Error preparing query: " . $conn->error);
            }
            
            $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
            
            if (!$stmt->execute()) {
                throw new Exception("Error executing query: " . $stmt->error);
            }
            
            // Show success message
            $_SESSION['success_message'] = "Your account has been created successfully. You can now log in to your account.";
            header('Location: login.php');
            exit();
            
        } catch (Exception $e) {
            $errors[] = "An error occurred while creating your account: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Account - E-Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.6)), url('../assets/images/customer-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-color: #000;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            z-index: 2;
        }

        .brand-logo {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 3;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 15px 25px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .register-container {
            width: 100%;
            max-width: 560px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 2.8rem;
            border-radius: 24px;
            box-shadow: 
                0 8px 32px 0 rgba(31, 38, 135, 0.37),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            animation: fadeIn 0.6s ease-out;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h2 {
            color: #fff;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .register-header p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .form-floating > .form-control {
            padding: 1rem 0.75rem;
            height: calc(3.5rem + 2px);
            line-height: 1.25;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
        }

        .btn-primary {
            background-color: #0056b3;
            border-color: #0056b3;
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #004494;
            border-color: #004494;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            body {
                background-size: cover;
                background-position: center;
            }
            
            .brand-logo {
                position: relative;
                top: 0;
                left: 0;
                margin: 1rem auto;
                width: fit-content;
            }

            .main-content {
                padding: 1rem;
            }

            .register-container {
                padding: 2rem;
                margin: 0 1rem;
            }

            .register-header h2 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="brand-logo">
        <i class="bi bi-shop"></i>
        <span style="color: #fff; font-size: 1.5rem;">E-Marketplace</span>
    </div>

    <div class="main-content">
        <div class="register-container">
            <div class="register-header">
                <h2>Register New Account</h2>
                <p>Create your account to access all features</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="register.php" method="post" class="needs-validation" novalidate>
                <?php
                // Create array to store fields with errors
                $error_fields = [];
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        if (strpos($error, 'name') !== false) {
                            $error_fields[] = 'name';
                        }
                        if (strpos($error, 'email') !== false) {
                            $error_fields[] = 'email';
                        }
                        if (strpos($error, 'phone') !== false) {
                            $error_fields[] = 'phone';
                        }
                        if (strpos($error, 'password') !== false) {
                            $error_fields[] = 'password';
                            $error_fields[] = 'confirm_password';
                        }
                    }
                }
                ?>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control <?php echo in_array('name', $error_fields) ? 'is-invalid' : ''; ?>" 
                           id="name" name="name" placeholder="Name" 
                           value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                    <label for="name">Name</label>
                    <?php if (in_array('name', $error_fields)): ?>
                        <div class="invalid-feedback">Please enter a valid name</div>
                    <?php endif; ?>
                </div>

                <div class="form-floating mb-3">
                    <input type="email" class="form-control <?php echo in_array('email', $error_fields) ? 'is-invalid' : ''; ?>" 
                           id="email" name="email" placeholder="Email address" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    <label for="email">Email address</label>
                    <div class="<?php echo in_array('email', $error_fields) ? 'invalid-feedback' : 'form-text text-light'; ?>">
                        <?php if (in_array('email', $error_fields)): ?>
                            Please enter a valid email address
                        <?php else: ?>
                            Please enter a valid email address (example: example@domain.com)
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-floating mb-3">
                    <input type="tel" class="form-control <?php echo in_array('phone', $error_fields) ? 'is-invalid' : ''; ?>" 
                           id="phone" name="phone" placeholder="Phone number" 
                           pattern="[0-9]{9}" title="Phone number must be exactly 9 digits" 
                           value="<?php echo htmlspecialchars($phone ?? ''); ?>" required>
                    <label for="phone">Phone number</label>
                    <div class="<?php echo in_array('phone', $error_fields) ? 'invalid-feedback' : 'form-text text-light'; ?>">
                        <?php if (in_array('phone', $error_fields)): ?>
                            Phone number must be exactly 9 digits
                        <?php else: ?>
                            Phone number must be exactly 9 digits
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control <?php echo in_array('password', $error_fields) ? 'is-invalid' : ''; ?>" 
                           id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                    <?php if (in_array('password', $error_fields)): ?>
                        <div class="invalid-feedback">Password must be at least 6 characters long</div>
                    <?php endif; ?>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control <?php echo in_array('confirm_password', $error_fields) ? 'is-invalid' : ''; ?>" 
                           id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                    <label for="confirm_password">Confirm password</label>
                    <?php if (in_array('confirm_password', $error_fields)): ?>
                        <div class="invalid-feedback">Passwords do not match</div>
                    <?php endif; ?>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Register</button>
                </div>

                <div class="text-center mt-4">
                    <p style="color: #fff;">
                        Already have an account?
                        <a href="login.php" class="text-white text-decoration-underline">Login</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>