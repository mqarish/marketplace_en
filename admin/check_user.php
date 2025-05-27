<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

if (isset($_GET['email'])) {
    $email = $_GET['email'];
    
    echo "<h2>User Information Check</h2>";
    
    // Check user in users table
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<h3>User Found:</h3>";
        echo "<pre>";
        // Remove password from display
        $user['password'] = '[HIDDEN]';
        print_r($user);
        echo "</pre>";
        
        // Check password hash format
        $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $hash = $result->fetch_assoc()['password'];
        
        echo "<h3>Password Hash Information:</h3>";
        echo "Hash Length: " . strlen($hash) . "<br>";
        echo "Hash Algorithm: " . password_get_info($hash)['algoName'] . "<br>";
        
    } else {
        echo "<p>No user found with email: " . htmlspecialchars($email) . "</p>";
    }
    
    $stmt->close();
} else {
    echo "<p>Please provide an email address in the URL parameter.</p>";
}
?>
