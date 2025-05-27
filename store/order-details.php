<?php
session_start();
require_once '../includes/init.php';

// التحقق من تسجيل الدخول كمتجر
if (!isset($_SESSION['store_id'])) {
    header("Location: login.php");
    exit();
}

$store_id = $_SESSION['store_id'];

// التحقق من وجود معرف الطلب
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int)$_GET['id'];

// التحقق من وجود جدول الطلبات
$table_exists_query = "SHOW TABLES LIKE 'orders'";
$table_exists_result = $conn->query($table_exists_query);
$orders_table_exists = ($table_exists_result && $table_exists_result->num_rows > 0);

// تهيئة المتغيرات
$order = null;
$order_items = [];
$order_history = [];

// جلب بيانات الطلب
if ($orders_table_exists) {
    // جلب بيانات الطلب
    $order_query = "
        SELECT * FROM orders 
        WHERE id = ? AND store_id = ?
    ";
    
    $order_stmt = $conn->prepare($order_query);
    $order_stmt->bind_param("ii", $order_id, $store_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        // الطلب غير موجود أو لا ينتمي لهذا المتجر
        header("Location: orders.php");
        exit();
    }
    
    $order = $order_result->fetch_assoc();
    
    // جلب منتجات الطلب
    $items_query = "
        SELECT 
            oi.*,
            p.name as product_name,
            p.image_url as product_image
        FROM 
            order_items oi
        LEFT JOIN 
            products p ON oi.product_id = p.id
        WHERE 
            oi.order_id = ?
    ";
    
    $items_stmt = $conn->prepare($items_query);
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    while ($item = $items_result->fetch_assoc()) {
        $order_items[] = $item;
    }
    
    // جلب سجل حالة الطلب
    $history_query = "
        SELECT * FROM order_history
        WHERE order_id = ?
        ORDER BY created_at DESC
    ";
    
    $history_stmt = $conn->prepare($history_query);
    $history_stmt->bind_param("i", $order_id);
    $history_stmt->execute();
    $history_result = $history_stmt->get_result();
    
    while ($history = $history_result->fetch_assoc()) {
        $order_history[] = $history;
    }
}

// دالة لتحويل حالة الطلب إلى اللغة العربية
function getOrderStatusInArabic($status) {
    $statuses = [
        'pending' => 'قيد الانتظار',
        'processing' => 'قيد التجهيز',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التوصيل',
        'cancelled' => 'ملغي'
    ];
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

// دالة لتحويل طريقة الدفع إلى اللغة العربية
function getPaymentMethodInArabic($method) {
    $methods = [
        'cash_on_delivery' => 'الدفع عند الاستلام',
        'credit_card' => 'بطاقة ائتمان',
        'bank_transfer' => 'تحويل بنكي'
    ];
    
    return isset($methods[$method]) ? $methods[$method] : $method;
}

// دالة لتحويل حالة الدفع إلى اللغة العربية
function getPaymentStatusInArabic($status) {
    $statuses = [
        'pending' => 'قيد الانتظار',
        'paid' => 'تم الدفع',
        'failed' => 'فشل الدفع'
    ];
    
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

// دالة للحصول على لون الحالة
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'processing' => 'info',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    
    return isset($colors[$status]) ? $colors[$status] : 'secondary';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الطلب #<?php echo $order_id; ?> - لوحة تحكم المتجر</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --light-color: #f3f4f6;
            --dark-color: #1f2937;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        
        .page-header {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .breadcrumb {
            margin-bottom: 0;
        }
        
        .dashboard-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 1rem 1.5rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        .order-info-item {
            margin-bottom: 1rem;
        }
        
        .order-info-label {
            font-weight: 600;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .order-info-value {
            font-weight: 500;
        }
        
        .order-product-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-product-item:last-child {
            border-bottom: none;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 0.25rem;
            overflow: hidden;
            margin-left: 1rem;
            flex-shrink: 0;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .product-price {
            color: #666;
        }
        
        .product-quantity {
            font-weight: 600;
            color: var(--primary-color);
            margin-right: 1rem;
        }
        
        .product-total {
            font-weight: 600;
            color: var(--dark-color);
            text-align: left;
            white-space: nowrap;
        }
        
        .timeline {
            position: relative;
            padding-right: 1.5rem;
            margin-right: 1rem;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            right: 0;
            width: 2px;
            background-color: #eee;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-badge {
            position: absolute;
            top: 0;
            right: -1.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background-color: var(--primary-color);
            border: 2px solid #fff;
            z-index: 1;
        }
        
        .timeline-content {
            padding-right: 1rem;
        }
        
        .timeline-date {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .timeline-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .timeline-text {
            color: #666;
        }
        
        .order-summary {
            border-top: 1px solid #eee;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .summary-label {
            color: #666;
        }
        
        .summary-value {
            font-weight: 500;
        }
        
        .summary-total {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--dark-color);
        }
        
        .action-buttons {
            margin-top: 1.5rem;
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>
    
    <!-- الشريط العلوي مع مسار التنقل -->
    <div class="page-header">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">لوحة التحكم</a></li>
                    <li class="breadcrumb-item"><a href="orders.php">إدارة الطلبات</a></li>
                    <li class="breadcrumb-item active" aria-current="page">تفاصيل الطلب #<?php echo $order_id; ?></li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="container py-4">
        <?php if ($orders_table_exists && $order): ?>
            <div class="row">
                <!-- معلومات الطلب -->
                <div class="col-lg-8">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <span>تفاصيل الطلب #<?php echo $order['id']; ?></span>
                            <span class="status-badge bg-<?php echo getStatusColor($order['status']); ?>">
                                <?php echo getOrderStatusInArabic($order['status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="order-info-item">
                                        <div class="order-info-label">تاريخ الطلب</div>
                                        <div class="order-info-value">
                                            <?php echo date('d/m/Y h:i A', strtotime($order['created_at'])); ?>
                                        </div>
                                    </div>
                                    <div class="order-info-item">
                                        <div class="order-info-label">طريقة الدفع</div>
                                        <div class="order-info-value">
                                            <?php echo getPaymentMethodInArabic($order['payment_method']); ?>
                                        </div>
                                    </div>
                                    <div class="order-info-item">
                                        <div class="order-info-label">حالة الدفع</div>
                                        <div class="order-info-value">
                                            <span class="status-badge bg-<?php echo $order['payment_status'] == 'paid' ? 'success' : ($order['payment_status'] == 'failed' ? 'danger' : 'warning'); ?>">
                                                <?php echo getPaymentStatusInArabic($order['payment_status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="order-info-item">
                                        <div class="order-info-label">عنوان الشحن</div>
                                        <div class="order-info-value">
                                            <?php echo htmlspecialchars($order['shipping_address']); ?>
                                        </div>
                                    </div>
                                    <div class="order-info-item">
                                        <div class="order-info-label">رقم الهاتف</div>
                                        <div class="order-info-value">
                                            <?php echo htmlspecialchars($order['phone']); ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($order['notes'])): ?>
                                        <div class="order-info-item">
                                            <div class="order-info-label">ملاحظات</div>
                                            <div class="order-info-value">
                                                <?php echo htmlspecialchars($order['notes']); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <h5 class="mb-3">المنتجات</h5>
                            <?php if (count($order_items) > 0): ?>
                                <div class="order-products">
                                    <?php foreach ($order_items as $item): ?>
                                        <div class="order-product-item">
                                            <div class="product-image">
                                                <?php if (!empty($item['product_image'])): ?>
                                                    <img src="../uploads/products/<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                                <?php else: ?>
                                                    <img src="../assets/img/product-placeholder.png" alt="صورة افتراضية">
                                                <?php endif; ?>
                                            </div>
                                            <div class="product-details">
                                                <div class="product-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                <div class="product-price"><?php echo number_format($item['price'], 2); ?> ريال / القطعة</div>
                                            </div>
                                            <div class="product-quantity">
                                                <?php echo $item['quantity']; ?> قطعة
                                            </div>
                                            <div class="product-total">
                                                <?php echo number_format($item['price'] * $item['quantity'], 2); ?> ريال
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="order-summary">
                                    <div class="summary-row">
                                        <div class="summary-label">المجموع الفرعي</div>
                                        <div class="summary-value"><?php echo number_format($order['total_amount'], 2); ?> ريال</div>
                                    </div>
                                    <div class="summary-row">
                                        <div class="summary-label">رسوم الشحن</div>
                                        <div class="summary-value">0.00 ريال</div>
                                    </div>
                                    <div class="summary-row">
                                        <div class="summary-label summary-total">المجموع الكلي</div>
                                        <div class="summary-value summary-total"><?php echo number_format($order['total_amount'], 2); ?> ريال</div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    لا توجد منتجات مرتبطة بهذا الطلب.
                                </div>
                            <?php endif; ?>
                            
                            <div class="action-buttons">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-order-id="<?php echo $order['id']; ?>" data-order-status="<?php echo $order['status']; ?>">
                                    <i class="bi bi-arrow-repeat"></i> تحديث الحالة
                                </button>
                                <a href="print-order.php?id=<?php echo $order['id']; ?>" target="_blank" class="btn btn-outline-secondary">
                                    <i class="bi bi-printer"></i> طباعة
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- سجل الطلب -->
                <div class="col-lg-4">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <span>سجل الطلب</span>
                        </div>
                        <div class="card-body">
                            <?php if (count($order_history) > 0): ?>
                                <div class="timeline">
                                    <?php foreach ($order_history as $history): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-badge bg-<?php echo getStatusColor($history['status']); ?>"></div>
                                            <div class="timeline-content">
                                                <div class="timeline-date">
                                                    <?php echo date('d/m/Y h:i A', strtotime($history['created_at'])); ?>
                                                </div>
                                                <div class="timeline-title">
                                                    <?php echo getOrderStatusInArabic($history['status']); ?>
                                                </div>
                                                <?php if (!empty($history['notes'])): ?>
                                                    <div class="timeline-text">
                                                        <?php echo htmlspecialchars($history['notes']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-clock-history fs-1 mb-2 d-block"></i>
                                    <p>لا يوجد سجل متاح لهذا الطلب</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="dashboard-card">
                <div class="card-body">
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-exclamation-circle fs-1 mb-2 d-block"></i>
                        <h4>لا يمكن العثور على الطلب</h4>
                        <p>الطلب غير موجود أو تم حذفه.</p>
                        <a href="orders.php" class="btn btn-primary mt-3">العودة إلى قائمة الطلبات</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- نافذة تحديث حالة الطلب -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">تحديث حالة الطلب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="update-order-status.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="orderIdInput" value="<?php echo $order_id; ?>">
                        <div class="mb-3">
                            <label for="orderStatus" class="form-label">الحالة الجديدة</label>
                            <select class="form-select" id="orderStatus" name="status" required>
                                <option value="pending">قيد الانتظار</option>
                                <option value="processing">قيد التجهيز</option>
                                <option value="shipped">تم الشحن</option>
                                <option value="delivered">تم التوصيل</option>
                                <option value="cancelled">ملغي</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="statusNotes" class="form-label">ملاحظات (اختياري)</label>
                            <textarea class="form-control" id="statusNotes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">تحديث الحالة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // تحديث بيانات نافذة تحديث الحالة
        document.addEventListener('DOMContentLoaded', function() {
            const updateStatusModal = document.getElementById('updateStatusModal');
            if (updateStatusModal) {
                updateStatusModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const orderId = button.getAttribute('data-order-id');
                    const orderStatus = button.getAttribute('data-order-status');
                    
                    const orderIdInput = document.getElementById('orderIdInput');
                    const orderStatusSelect = document.getElementById('orderStatus');
                    
                    orderIdInput.value = orderId;
                    orderStatusSelect.value = orderStatus;
                });
            }
        });
    </script>
</body>
</html>
