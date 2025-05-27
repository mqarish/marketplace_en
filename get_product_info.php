<?php
// بدء الجلسة
session_start();

// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db_connection.php';

// التحقق من وجود معرف المنتج
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'invalid_id']);
    exit;
}

$product_id = intval($_GET['id']);

// استعلام للحصول على معلومات المنتج
$product_sql = "SELECT p.*, s.id as store_id, s.name as store_name 
                FROM products p 
                JOIN stores s ON p.store_id = s.id 
                WHERE p.id = ?";

$stmt = $conn->prepare($product_sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'product_not_found']);
    exit;
}

$product = $result->fetch_assoc();

// تحضير البيانات للإرجاع
$response = [
    'success' => true,
    'id' => $product['id'],
    'name' => $product['name'],
    'description' => $product['description'] ?? '',
    'price' => $product['price'],
    'final_price' => isset($product['final_price']) ? $product['final_price'] : $product['price'],
    'store_id' => $product['store_id'],
    'store_name' => $product['store_name'],
    'image_url' => $product['image_url'] ?? '',
    'category_id' => $product['category_id'] ?? null
];

// إضافة معلومات التقييم إذا كانت متوفرة
$rating_sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as rating_count 
               FROM product_ratings 
               WHERE product_id = ?";

$stmt = $conn->prepare($rating_sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$rating_result = $stmt->get_result();
$rating_data = $rating_result->fetch_assoc();

if ($rating_data) {
    $response['avg_rating'] = round($rating_data['avg_rating'], 1);
    $response['rating_count'] = $rating_data['rating_count'];
}

// التحقق مما إذا كان المستخدم قد قام بالإعجاب بالمنتج
if (isset($_SESSION['customer_id'])) {
    $like_sql = "SELECT * FROM product_likes WHERE product_id = ? AND customer_id = ?";
    $stmt = $conn->prepare($like_sql);
    $stmt->bind_param("ii", $product_id, $_SESSION['customer_id']);
    $stmt->execute();
    $like_result = $stmt->get_result();
    
    $response['is_liked'] = ($like_result->num_rows > 0);
}

// إرجاع البيانات بتنسيق JSON
header('Content-Type: application/json');
echo json_encode($response);
