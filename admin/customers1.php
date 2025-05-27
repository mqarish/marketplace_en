<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'إدارة العملاء';
$page_icon = 'fa-users';

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    switch ($_POST['action']) {
        case 'change_status':
            $status = $_POST['status'];
            if ($user_id != $_SESSION['user_id']) {
                $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'customer'");
                $stmt->bind_param("si", $status, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "تم تحديث حالة العميل بنجاح";
                } else {
                    $_SESSION['error'] = "حدث خطأ أثناء تحديث حالة العميل";
                }
                $stmt->close();
            }
            break;
    }
    
    header("Location: customers.php");
    exit;
}

// جلب العملاء مع معلوماتهم
try {
    $query = "SELECT u.*, c.address 
              FROM users u 
              LEFT JOIN customers c ON u.email = c.email 
              WHERE u.role = 'customer'
              ORDER BY u.created_at DESC";
    
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("خطأ في إعداد الاستعلام: " . $conn->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("خطأ في تنفيذ الاستعلام: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $customers = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $customers = [];
}

// Get statistics
$total_customers = count($customers);
$active_customers = 0;
$inactive_customers = 0;
$new_customers_this_month = 0;

$current_month = date('Y-m');
foreach ($customers as $customer) {
    if ($customer['status'] === 'active') {
        $active_customers++;
    } else {
        $inactive_customers++;
    }
    
    // Count customers registered this month
    $customer_month = date('Y-m', strtotime($customer['created_at']));
    if ($customer_month === $current_month) {
        $new_customers_this_month++;
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php include 'admin_header.php'; ?>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">إجمالي العملاء</h5>
                        <p class="card-text display-6"><?php echo number_format($total_customers); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">العملاء النشطين</h5>
                        <p class="card-text display-6"><?php echo number_format($active_customers); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">العملاء غير النشطين</h5>
                        <p class="card-text display-6"><?php echo number_format($inactive_customers); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">عملاء جدد هذا الشهر</h5>
                        <p class="card-text display-6"><?php echo number_format($new_customers_this_month); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">قائمة العملاء</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>اسم المستخدم</th>
                                <th>البريد الإلكتروني</th>
                                <th>العنوان</th>
                                <th>تاريخ التسجيل</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">لا يوجد عملاء</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle fa-2x me-2"></i>
                                                <span><?php echo htmlspecialchars($customer['username']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['address'] ?? 'غير محدد'); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($customer['created_at'])); ?></td>
                                        <td>
                                            <?php 
                                            $status = isset($customer['status']) ? $customer['status'] : 'inactive';
                                            $badge_class = $status === 'active' ? 'success' : 'danger';
                                            $status_text = $status === 'active' ? 'نشط' : 'غير نشط';
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="customer_details.php?id=<?php echo $customer['id']; ?>" 
                                                   class="btn btn-sm btn-info" title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (isset($_SESSION['user_id']) && isset($customer['id']) && $customer['id'] != $_SESSION['user_id']): ?>
                                                    <button type="button" 
                                                            class="btn btn-sm <?php echo $status === 'active' ? 'btn-warning' : 'btn-success'; ?>"
                                                            onclick="changeStatus(<?php echo $customer['id']; ?>, '<?php echo $status === 'active' ? 'inactive' : 'active'; ?>')"
                                                            title="<?php echo $status === 'active' ? 'تعطيل' : 'تفعيل'; ?>">
                                                        <i class="fas fa-<?php echo $status === 'active' ? 'ban' : 'check'; ?>"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
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
    <script>
    function changeStatus(userId, newStatus) {
        if (confirm('هل أنت متأكد من تغيير حالة هذا العميل؟')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="change_status">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="status" value="${newStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
</body>
</html>
