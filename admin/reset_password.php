<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

if (isset($_POST['email']) && isset($_POST['new_password'])) {
    $email = $_POST['email'];
    $new_password = $_POST['new_password'];
    
    try {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update the password in users table
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        
        if ($stmt->execute()) {
            echo "<div style='color: green;'>Password updated successfully!</div>";
        } else {
            echo "<div style='color: red;'>Error updating password: " . $stmt->error . "</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="password"] { padding: 8px; width: 300px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Reset User Password</h2>
    <form method="POST">
        <div class="form-group">
            <label>Email:</label>
            <input type="text" name="email" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>" required>
        </div>
        <div class="form-group">
            <label>New Password:</label>
            <input type="password" name="new_password" required>
        </div>
        <button type="submit">Reset Password</button>
    </form>
    
    <div style="margin-top: 20px;">
        <h3>Check Current User Data:</h3>
        <?php
        if (isset($_GET['email']) || isset($_POST['email'])) {
            $check_email = isset($_GET['email']) ? $_GET['email'] : $_POST['email'];
            
            // Check users table
            $stmt = $conn->prepare("SELECT id, username, email, role, status FROM users WHERE email = ?");
            $stmt->bind_param("s", $check_email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                echo "<pre>User found in users table:\n";
                print_r($user);
                echo "</pre>";
            } else {
                echo "<p>User not found in users table.</p>";
            }
            
            // Check customers table
            $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
            $stmt->bind_param("s", $check_email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $customer = $result->fetch_assoc();
                echo "<pre>Customer found in customers table:\n";
                print_r($customer);
                echo "</pre>";
            } else {
                echo "<p>Customer not found in customers table.</p>";
            }
        }
        ?>
    </div>
</body>
</html>
