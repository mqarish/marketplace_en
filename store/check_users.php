<?php
require_once '../includes/config.php';

// التحقق من جدول المستخدمين
$query = "SELECT * FROM users WHERE role = 'store'";
$result = $conn->query($query);

echo "<h2>المستخدمون:</h2>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "<br>";
        echo "Username: " . $row['username'] . "<br>";
        echo "Email: " . $row['email'] . "<br>";
        echo "Role: " . $row['role'] . "<br>";
        echo "Status: " . $row['status'] . "<br>";
        echo "Phone: " . $row['phone'] . "<br>";
        echo "<hr>";
    }
} else {
    echo "لا يوجد مستخدمين من نوع 'store'";
}

// التحقق من جدول المتاجر
$query = "SELECT * FROM stores";
$result = $conn->query($query);

echo "<h2>المتاجر:</h2>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "<br>";
        echo "User ID: " . $row['user_id'] . "<br>";
        echo "Name: " . $row['name'] . "<br>";
        echo "Status: " . $row['status'] . "<br>";
        echo "<hr>";
    }
} else {
    echo "لا يوجد متاجر";
}
?>
