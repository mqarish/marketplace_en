<?php
require_once '../includes/init.php';

$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin' AND role = 'admin'");
$stmt->bind_param("s", $hashed_password);

if ($stmt->execute()) {
    echo "تم تحديث كلمة المرور بنجاح!";
    echo "<br>Password Hash: " . $hashed_password;
} else {
    echo "حدث خطأ أثناء تحديث كلمة المرور: " . $conn->error;
}
?>
