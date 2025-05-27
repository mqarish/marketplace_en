<?php
require_once '../includes/init.php';

// If admin is already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$username = '';

// Process login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        try {
            // Verify admin credentials
            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND status = 'active'");
            if ($stmt === false) {
                throw new Exception('Database connection error');
            }
            
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($admin = $result->fetch_assoc()) {
                // Verify password using MD5
                if (md5($password) === $admin['password']) {
                    // Login successful
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['name'];
                    
                    // Update last login time
                    $update = $conn->prepare("UPDATE admins SET created_at = NOW() WHERE id = ?");
                    $update->bind_param("i", $admin['id']);
                    $update->execute();
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Incorrect password';
                    error_log('Password verification failed for admin: ' . $username);
                }
            } else {
                $error = 'Username does not exist or account is inactive';
            }
        } catch (Exception $e) {
            $error = 'An error occurred during login: ' . $e->getMessage();
            error_log('Login error: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Control Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
                        url('../assets/images/admin-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .admin-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .admin-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .login-title {
            color: white;
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .login-subtitle {
            color: rgba(255, 255, 255, 0.8);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .form-floating > label {
            color: rgba(255, 255, 255, 0.8);
            padding-right: 1rem;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            height: 3.2rem;
            padding: 1rem;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: none;
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group-text {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.8);
        }

        .btn-login {
            background: linear-gradient(45deg, #2193b0, #6dd5ed);
            border: none;
            color: white;
            padding: 0.8rem;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: linear-gradient(45deg, #1c7a94, #5ab9cc);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .error-message {
            background: rgba(220, 53, 69, 0.2);
            color: #ff6b6b;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .password-toggle {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            z-index: 10;
            padding: 0.5rem;
        }

        @media (max-width: 576px) {
            .login-container {
                padding: 2rem;
            }
            .admin-icon {
                width: 60px;
                height: 60px;
            }
            .admin-icon i {
                font-size: 2rem;
            }
            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="admin-icon">
            <i class="fas fa-user-shield"></i>
        </div>
        <h1 class="login-title">Admin Login</h1>
        <p class="login-subtitle">Welcome to the Admin Control Panel</p>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" 
                       placeholder="Username" required value="<?php echo htmlspecialchars($username); ?>">
                <label for="username">Username</label>
            </div>

            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Password" required>
                <label for="password">Password</label>
                <span class="password-toggle" onclick="togglePassword()">
                    <i class="fas fa-eye" id="toggleIcon"></i>
                </span>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>
                Login
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>