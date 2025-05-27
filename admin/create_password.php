<?php
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "Password Hash: " . $hashed_password;
?>
