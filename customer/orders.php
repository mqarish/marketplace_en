<?php
session_start();
require_once '../includes/init.php';
require_once '../includes/functions.php';
require_once '../includes/customer_auth.php';

// التحقق من تسجيل دخول العميل
if (!isset($_SESSION['customer_id'])) {
    header('Location: /marketplace/customer/login.php');
    exit();
}

// جلب طلبات العميل
$customer_id = $_SESSION['customer_id'];
$orders_sql = "SELECT o.*, s.name as store_name 
               FROM orders o
               LEFT JOIN stores s ON o.store_id = s.id
               WHERE o.customer_id = ?
               ORDER BY o.created_at DESC";

$stmt = $conn->prepare($orders_sql);
if ($stmt === false) {
    die('خطأ في إعداد الاستعلام: ' . $conn->error);
}

$stmt->bind_param("i", $customer_id);
$stmt->execute();
$orders_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلباتي - السوق الإلكتروني</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .order-card {
            transition: transform 0.2s;
            margin-bottom: 1rem;
        }
        .order-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .status-pending { background-color: #ffc107; }
        .status-processing { background-color: #17a2b8; }
        .status-completed { background-color: #28a745; }
        .status-cancelled { background-color: #dc3545; }
    </style>
</head>
<body>
    <!-- استدعاء الهيدر الداكن الجديد -->
    <?php 
    $root_path = '../';
    include '../includes/dark_header.php'; 
    ?>

    <div class="container py-5">
        <h1 class="mb-4">طلباتي</h1>

        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
            <div class="row">
                <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <div class="col-12">
                        <div class="card order-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="card-title">طلب رقم: #<?php echo $order['id']; ?></h5>
                                        <p class="card-text mb-2">
                                            <strong>المتجر:</strong> <?php echo htmlspecialchars($order['store_name']); ?>
                                        </p>
                                        <p class="card-text mb-2">
                                            <strong>المبلغ الإجمالي:</strong> <?php echo number_format($order['total_amount'], 2); ?> ريال
                                        </p>
                                        <p class="card-text">
                                            <strong>تاريخ الطلب:</strong> <?php echo date('Y/m/d h:i A', strtotime($order['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="badge status-<?php echo $order['status']; ?> status-badge">
                                            <?php echo get_order_status_text($order['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                لا توجد طلبات حتى الآن.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
