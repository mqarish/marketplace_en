<?php
session_start();
require_once '../includes/init.php';
require_once '../includes/functions.php';
require_once '../includes/customer_auth.php';

// Verify customer login
check_customer_auth();
check_customer_status($conn, $_SESSION['customer_id']);

// Get user information
$customer_id = $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
if (!$stmt) {
    die("خطأ في إعداد الاستعلام: " . $conn->error);
}

$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header('Location: login.php');
    exit;
}

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $errors = [];

    // Validate data
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email is invalid";
    }

    // Check for duplicate email
    if (!empty($email) && $email !== $user['email']) {
        $check_email = $conn->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
        if (!$check_email) {
            die("Error in query preparation: " . $conn->error);
        }
        $check_email->bind_param("si", $email, $customer_id);
        $check_email->execute();
        $email_result = $check_email->get_result();
        if ($email_result->num_rows > 0) {
            $errors[] = "Email is already in use";
        }
        $check_email->close();
    }

    // Check for errors
    if (empty($errors)) {
        $update_stmt = $conn->prepare("UPDATE customers SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        if (!$update_stmt) {
            die("Error in query preparation: " . $conn->error);
        }
        
        $update_stmt->bind_param("ssssi", $name, $email, $phone, $address, $customer_id);
        
        if ($update_stmt->execute()) {
            $success_message = "Profile updated successfully";
            // Update user data in the variable
            $user['name'] = $name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['address'] = $address;
        } else {
            $errors[] = "An error occurred while updating data: " . $update_stmt->error;
        }
        
        $update_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($user['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #000000 0%, #222222 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 1rem;
            border: 3px solid rgba(255, 255, 255, 0.5);
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #344767;
        }
        .btn-primary {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
        }
        .member-since {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .profile-header {
                padding: 2rem 0;
            }
            .profile-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Include the new dark header -->
    <?php 
    $root_path = '../';
    include '../includes/dark_header.php'; 
    ?>

    <div class="profile-header text-center">
        <div class="container">
            <div class="profile-avatar">
                <i class="bi bi-person"></i>
            </div>
            <h2 class="mb-2"><?php echo htmlspecialchars($user['name']); ?></h2>
            <span class="member-since">
                <i class="bi bi-calendar3 me-2"></i>
                Member since <?php echo date('Y/m/d', strtotime($user['created_at'])); ?>
            </span>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="profile-card">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   placeholder="05xxxxxxxx" dir="ltr">
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" 
                                      rows="3" placeholder="Enter your full address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
