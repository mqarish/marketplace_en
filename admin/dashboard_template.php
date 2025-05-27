<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'لوحة التحكم';
$page_icon = 'fa-dashboard';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php include 'admin_header.php'; ?>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <!-- إحصائيات سريعة -->
        <div class="row g-4 mb-4">
            <!-- إحصائيات المتاجر -->
            <div class="col-md-3">
                <div class="card h-100 border-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-store fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-title mb-0">المتاجر</h6>
                            </div>
                        </div>
                        <?php
                        $stores_count = $conn->query("SELECT COUNT(*) as count FROM stores")->fetch_assoc()['count'];
                        $active_stores = $conn->query("SELECT COUNT(*) as count FROM stores WHERE status = 'active'")->fetch_assoc()['count'];
                        ?>
                        <h3 class="mb-2"><?php echo number_format($stores_count); ?></h3>
                        <p class="card-text text-success mb-0">
                            <i class="fas fa-check-circle"></i>
                            <?php echo number_format($active_stores); ?> متجر نشط
                        </p>
                    </div>
                </div>
            </div>

            <!-- إحصائيات العملاء -->
            <div class="col-md-3">
                <div class="card h-100 border-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-users fa-2x text-success"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-title mb-0">العملاء</h6>
                            </div>
                        </div>
                        <?php
                        $customers_count = $conn->query("SELECT COUNT(*) as count FROM customers")->fetch_assoc()['count'];
                        $active_customers = $conn->query("SELECT COUNT(*) as count FROM customers WHERE status = 'active'")->fetch_assoc()['count'];
                        ?>
                        <h3 class="mb-2"><?php echo number_format($customers_count); ?></h3>
                        <p class="card-text text-success mb-0">
                            <i class="fas fa-check-circle"></i>
                            <?php echo number_format($active_customers); ?> عميل نشط
                        </p>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الطلبات -->
            <div class="col-md-3">
                <div class="card h-100 border-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-shopping-cart fa-2x text-info"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-title mb-0">الطلبات</h6>
                            </div>
                        </div>
                        <?php
                        $orders_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
                        $completed_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")->fetch_assoc()['count'];
                        ?>
                        <h3 class="mb-2"><?php echo number_format($orders_count); ?></h3>
                        <p class="card-text text-success mb-0">
                            <i class="fas fa-check-circle"></i>
                            <?php echo number_format($completed_orders); ?> طلب مكتمل
                        </p>
                    </div>
                </div>
            </div>

            <!-- إجمالي المبيعات -->
            <div class="col-md-3">
                <div class="card h-100 border-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-money-bill-wave fa-2x text-warning"></i>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="card-title mb-0">إجمالي المبيعات</h6>
                            </div>
                        </div>
                        <?php
                        $total_sales = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'completed'")->fetch_assoc()['total'];
                        ?>
                        <h3 class="mb-2"><?php echo number_format($total_sales, 2); ?> ر.س</h3>
                        <p class="card-text text-success mb-0">
                            <i class="fas fa-chart-line"></i>
                            تحديث فوري
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- آخر الطلبات -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">آخر الطلبات</h5>
                <a href="orders.php" class="btn btn-sm btn-primary">
                    عرض كل الطلبات
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>العميل</th>
                            <th>المتجر</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_orders = $conn->query("
                            SELECT o.*, c.name as customer_name, s.name as store_name 
                            FROM orders o 
                            LEFT JOIN customers c ON o.customer_id = c.id 
                            LEFT JOIN stores s ON o.store_id = s.id 
                            ORDER BY o.created_at DESC 
                            LIMIT 5
                        ");

                        while ($order = $recent_orders->fetch_assoc()):
                            $status_class = '';
                            switch ($order['status']) {
                                case 'pending':
                                    $status_class = 'bg-warning';
                                    $status_text = 'قيد الانتظار';
                                    break;
                                case 'processing':
                                    $status_class = 'bg-info';
                                    $status_text = 'قيد التنفيذ';
                                    break;
                                case 'completed':
                                    $status_class = 'bg-success';
                                    $status_text = 'مكتمل';
                                    break;
                                case 'cancelled':
                                    $status_class = 'bg-danger';
                                    $status_text = 'ملغي';
                                    break;
                            }
                        ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['store_name']); ?></td>
                            <td><?php echo number_format($order['total_amount'], 2); ?> ر.س</td>
                            <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td><?php echo date('Y/m/d', strtotime($order['created_at'])); ?></td>
                            <td>
                                <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- آخر المتاجر المسجلة -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">آخر المتاجر المسجلة</h5>
                <a href="stores.php" class="btn btn-sm btn-primary">
                    عرض كل المتاجر
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>المتجر</th>
                            <th>صاحب المتجر</th>
                            <th>الحالة</th>
                            <th>تاريخ التسجيل</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $latest_stores_query = "SELECT s.*, u.username as owner_name FROM stores s LEFT JOIN users u ON s.user_id = u.id ORDER BY s.created_at DESC LIMIT 5";
                        $latest_stores_result = $conn->query($latest_stores_query);
                        $latest_stores = [];

                        if ($latest_stores_result) {
                            while ($row = $latest_stores_result->fetch_assoc()) {
                                // التأكد من تنظيف البيانات قبل عرضها
                                $row['name'] = isset($row['name']) ? $row['name'] : '';
                                $row['owner_name'] = isset($row['owner_name']) ? $row['owner_name'] : '';
                                $row['logo'] = isset($row['logo']) ? $row['logo'] : '';
                                $row['status'] = isset($row['status']) ? $row['status'] : '';
                                $latest_stores[] = $row;
                            }
                        }

                        foreach ($latest_stores as $store):
                            $status_class = $store['status'] === 'active' ? 'bg-success' : 'bg-warning';
                            $status_text = $store['status'] === 'active' ? 'نشط' : 'قيد المراجعة';
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($store['logo'])): ?>
                                        <img src="../uploads/stores/<?php echo htmlspecialchars($store['logo']); ?>" 
                                             alt="<?php echo !empty($store['name']) ? htmlspecialchars($store['name']) : ''; ?>" 
                                             class="rounded-circle me-2" 
                                             width="32" height="32">
                                    <?php else: ?>
                                        <i class="fas fa-store fa-2x me-2 text-muted"></i>
                                    <?php endif; ?>
                                    <?php echo !empty($store['name']) ? htmlspecialchars($store['name']) : ''; ?>
                                </div>
                            </td>
                            <td><?php echo !empty($store['owner_name']) ? htmlspecialchars($store['owner_name']) : ''; ?></td>
                            <td><span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td><?php echo date('Y/m/d', strtotime($store['created_at'])); ?></td>
                            <td>
                                <a href="store_details.php?id=<?php echo $store['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
