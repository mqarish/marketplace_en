<?php
include '../includes/connect.php';
include '../includes/functions.php';
ensure_admin_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die(json_encode(['success' => false, 'error' => 'Invalid request method']));
}

$subscription_id = $_POST['subscription_id'] ?? 0;
$status = $_POST['status'] ?? '';

if (!in_array($status, ['paid', 'pending', 'cancelled'])) {
    die(json_encode(['success' => false, 'error' => 'Invalid status']));
}

$stmt = $conn->prepare("UPDATE subscriptions SET payment_status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $subscription_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>
