<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات المتجر
if (!isLoggedIn() || !isStore()) {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

// التحقق من حالة المتجر
$user_id = $_SESSION['user_id'];
$sql = "SELECT status FROM stores WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$store = $result->fetch_assoc();

if (!$store || $store['status'] != 'active') {
    header("Location: pending.php");
    exit;
}
