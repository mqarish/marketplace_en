<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// التحقق من وجود معرف العميل
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    // جلب معلومات العميل
    $query = "SELECT u.*, c.id as customer_id, c.address 
             FROM users u 
             LEFT JOIN customers c ON u.email = c.email 
             WHERE u.id = ? AND u.role = 'customer'";
    
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("خطأ في إعداد الاستعلام: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("خطأ في تنفيذ الاستعلام: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();
    
    if (!$customer) {
        throw new Exception("العميل غير موجود");
    }

    // جلب إحصائيات الطلبات
    $stats_query = "SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_spent
    FROM orders 
    WHERE customer_id = ?";
    
    $stmt = $conn->prepare($stats_query);
    $stmt->bind_param("i", $customer['customer_id']);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // جلب آخر الطلبات
    $orders_query = "SELECT o.*, s.name as store_name
                    FROM orders o
                    JOIN stores s ON o.store_id = s.id
                    WHERE o.customer_id = ?
                    ORDER BY o.created_at DESC
                    LIMIT 10";
    
    $stmt = $conn->prepare($orders_query);
    $stmt->bind_param("i", $customer['customer_id']);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: users.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل العميل - لوحة التحكم</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>تفاصيل العميل</h2>
            <a href="users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right ml-1"></i>
                عودة للقائمة
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- معلومات العميل -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 text-center">
                        <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                    </div>
                    <div class="col-md-5">
                        <h4><?php echo htmlspecialchars($customer['username']); ?></h4>
                        <p class="text-muted mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo htmlspecialchars($customer['email']); ?>
                        </p>
                        <?php if (!empty($customer['phone'])): ?>
                            <p class="text-muted mb-2">
                                <i class="fas fa-phone me-2"></i>
                                <?php echo htmlspecialchars($customer['phone']); ?>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($customer['address'])): ?>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo htmlspecialchars($customer['address']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-5">
                        <p class="mb-2">
                            <strong>تاريخ التسجيل:</strong>
                            <?php echo date('Y/m/d', strtotime($customer['created_at'])); ?>
                        </p>
                        <p class="mb-2">
                            <strong>الحالة:</strong>
                            <?php
                            $status_class = '';
                            $status_text = '';
                            switch ($customer['status']) {
                                case 'active':
                                    $status_class = 'bg-success';
                                    $status_text = 'نشط';
                                    break;
                                case 'pending':
                                    $status_class = 'bg-warning';
                                    $status_text = 'قيد المراجعة';
                                    break;
                                case 'blocked':
                                    $status_class = 'bg-danger';
                                    $status_text = 'محظور';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </p>
                        <?php if ($customer['status'] !== 'blocked'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="change_status">
                                <input type="hidden" name="user_id" value="<?php echo $customer['id']; ?>">
                                <input type="hidden" name="status" value="blocked">
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-ban me-1"></i>
                                    حظر العميل
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="action" value="change_status">
                                <input type="hidden" name="user_id" value="<?php echo $customer['id']; ?>">
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-check-circle me-1"></i>
                                    تنشيط العميل
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات العميل -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white stats-card">
                    <div class="card-body">
                        <h6 class="card-title">إجمالي الطلبات</h6>
                        <h3><?php echo number_format($stats['total_orders']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white stats-card">
                    <div class="card-body">
                        <h6 class="card-title">الطلبات المكتملة</h6>
                        <h3><?php echo number_format($stats['completed_orders']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white stats-card">
                    <div class="card-body">
                        <h6 class="card-title">الطلبات المعلقة</h6>
                        <h3><?php echo number_format($stats['pending_orders']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white stats-card">
                    <div class="card-body">
                        <h6 class="card-title">إجمالي المشتريات</h6>
                        <h3><?php echo number_format($stats['total_spent'], 2); ?> ر.س</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- آخر الطلبات -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">آخر الطلبات</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>المتجر</th>
                                <th>التاريخ</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">لا توجد طلبات</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['store_name']); ?></td>
                                        <td><?php echo date('Y/m/d', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo number_format($order['total_amount'], 2); ?> ر.س</td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch ($order['status']) {
                                                case 'completed':
                                                    $status_class = 'bg-success';
                                                    $status_text = 'مكتمل';
                                                    break;
                                                case 'pending':
                                                    $status_class = 'bg-warning';
                                                    $status_text = 'قيد المعالجة';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'bg-danger';
                                                    $status_text = 'ملغي';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
