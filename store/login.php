<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If the user is already logged in, redirect to the home page
if (isset($_SESSION['store_id'])) {
    header('Location: index.php');
    exit();
}

require_once '../includes/init.php';

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate input data
    if (empty($email)) {
        $errors[] = "Please enter your email";
    }
    if (empty($password)) {
        $errors[] = "Please enter your password";
    }

    // If there are no errors, verify login credentials
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, name, email, password, status FROM stores WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $store = $result->fetch_assoc();
            
            if (password_verify($password, $store['password'])) {
                // Check store status
                if ($store['status'] === 'pending') {
                    $errors[] = "Sorry, your store is pending approval from administration";
                } elseif ($store['status'] === 'blocked' || $store['status'] === 'inactive') {
                    $errors[] = "Sorry, your store has been disabled. Please contact site administration for more information";
                } else {
                    // Log in
                    $_SESSION['store_id'] = $store['id'];
                    $_SESSION['store_name'] = $store['name'];
                    $_SESSION['store_email'] = $store['email'];
                    
                    // Redirect to home page
                    header('Location: index.php');
                    exit();
                }
            } else {
                $errors[] = "البريد الإلكتروني أو كلمة المرور غير صحيحة";
            }
        } else {
            $errors[] = "البريد الإلكتروني أو كلمة المرور غير صحيحة";
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Store</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
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
        
        .main-content {
            position: relative;
            min-height: 100vh;
            width: 100%;
            display: flex;
            align-items: center;
            z-index: 2;
        }
        
        .brand-logo {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 3;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 8px 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .brand-logo i {
            font-size: 1.5rem;
            color: white;
        }
        
        .brand-logo span {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        @media (max-width: 576px) {
            .brand-logo i {
                font-size: 1.2rem;
            }
            
            .brand-logo span {
                font-size: 1rem;
            }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            margin: 1rem auto;
            max-width: 450px;
            width: 90%;
            animation: fadeIn 0.5s ease-out;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h2 {
            color: #ffffff;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            font-size: 1.5rem;
        }
        
        @media (max-width: 576px) {
            .login-header h2 {
                font-size: 1.2rem;
            }
        }
        
        .login-header p {
            color: rgba(255,255,255,0.9);
            margin-bottom: 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.6rem 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: white;
            height: auto;
            font-size: 0.95rem;
        }
        
        @media (max-width: 576px) {
            .form-control {
                padding: 0.5rem 0.7rem;
                font-size: 0.9rem;
            }
        }
        
        .form-control::placeholder {
            color: rgba(255,255,255,0.7);
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .input-group-text {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.6rem 0.8rem;
        }
        
        @media (max-width: 576px) {
            .input-group-text {
                padding: 0.5rem 0.7rem;
            }
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        @media (max-width: 576px) {
            .btn-primary {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
        
        .btn-primary:hover {
            background: rgba(37, 99, 235, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
        }
        
        .password-toggle {
            cursor: pointer;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .form-label {
            color: rgba(255,255,255,0.9);
            font-weight: 500;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .alert {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            color: white;
            border-radius: 8px;
        }
        
        .register-link {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .register-link:hover {
            color: white;
            text-shadow: 0 0 10px rgba(255,255,255,0.5);
        }
        
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: rgba(255,255,255,0.7);
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .divider span {
            padding: 0 1rem;
            color: rgba(255,255,255,0.7);
            font-size: 0.875rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            color: #ffffff;
        }
        
        .remember-me label {
            margin-left: 0.5rem;
            margin-bottom: 0;
        }
        
        .form-check-input {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .brand-logo {
                position: relative;
                top: 0;
                right: 0;
                margin: 1rem auto;
                justify-content: center;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Site Logo -->
    <div class="brand-logo">
        <i class="bi bi-shop"></i>
        <span>Store Portal</span>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="login-container">
                        <div class="login-header">
                            <h2>Welcome to the Store Portal</h2>
                            <p>Log in to manage your store</p>
                        </div>
                        
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php 
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                        value="<?php echo htmlspecialchars($email); ?>" required 
                                        placeholder="Enter your email">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" 
                                        id="password" name="password" required 
                                        placeholder="Enter your password">
                                    <button class="input-group-text password-toggle" type="button" 
                                        onclick="togglePasswordVisibility('#password', '#togglePassword')">
                                        <i class="bi bi-eye" id="togglePassword"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="remember-me">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                                <a href="forgot-password.php" class="ms-auto register-link">Forgot password?</a>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Login
                                </button>
                            </div>
                        </form>
                        
                        <div class="divider">
                            <span>OR</span>
                        </div>
                        
                        <div class="text-center">
                            <p class="text-white-50 mb-0">Don't have a store? 
                                <a href="register.php" class="register-link">Create a new store</a>
                            </p>
                        </div>
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
