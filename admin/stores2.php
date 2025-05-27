<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'إدارة المتاجر';
$page_icon = 'fa-store';

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $store_id = isset($_POST['store_id']) ? (int)$_POST['store_id'] : 0;
    
    if ($store_id > 0) {
        switch ($_POST['action']) {
            case 'change_status':
                $status = $_POST['status'];
                
                // بدء المعاملة
                $conn->begin_transaction();
                
                try {
                    // تحديث حالة المتجر في جدول stores
                    $stmt = $conn->prepare("UPDATE stores SET status = ? WHERE id = ?");
                    $stmt->bind_param("si", $status, $store_id);
                    $stmt->execute();
                    
                    // تحديث حالة المستخدم في جدول users
                    $stmt2 = $conn->prepare("UPDATE users u 
                                           JOIN stores s ON u.id = s.user_id 
                                           SET u.status = ? 
                                           WHERE s.id = ?");
                    $stmt2->bind_param("si", $status, $store_id);
                    $stmt2->execute();
                    
                    // تأكيد المعاملة
                    $conn->commit();
                    $_SESSION['success'] = "تم تحديث حالة المتجر بنجاح";
                } catch (Exception $e) {
                    // التراجع عن المعاملة في حالة حدوث خطأ
                    $conn->rollback();
                    $_SESSION['error'] = "حدث خطأ أثناء تحديث حالة المتجر: " . $e->getMessage();
                }
                break;

            case 'reset_password':
                // الحصول على user_id للمتجر
                $stmt = $conn->prepare("SELECT user_id FROM stores WHERE id = ?");
                $stmt->bind_param("i", $store_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $store = $result->fetch_assoc();
                $stmt->close();
                
                if ($store && $store['user_id']) {
                    // إنشاء كلمة مرور جديدة عشوائية
                    $new_password = bin2hex(random_bytes(4)); // 8 characters
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->bind_param("si", $hashed_password, $store['user_id']);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "تم إعادة تعيين كلمة المرور بنجاح. كلمة المرور الجديدة هي: " . $new_password;
                    } else {
                        $_SESSION['error'] = "حدث خطأ أثناء إعادة تعيين كلمة المرور";
                    }
                    $stmt->close();
                }
                break;
        }
    }
    
    header("Location: stores.php");
    exit;
}

try {
    // جلب بيانات المتاجر مع معلومات المستخدم
    $query = "SELECT s.*, u.email, u.status as user_status, s.status as store_status 
              FROM stores s 
              LEFT JOIN users u ON s.user_id = u.id 
              ORDER BY s.created_at DESC";
    
    $result = $conn->query($query);
    $stores = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // تنظيف البيانات
            foreach ($row as $key => $value) {
                $row[$key] = $value !== null ? htmlspecialchars($value) : '';
            }
            $row['id'] = (int)$row['id'];
            $stores[] = $row;
        }
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $stores = [];
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .store-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .store-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: #f8f9fa;
        }
        .store-logo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .btn-group {
            display: flex;
            gap: 5px;
        }
        .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid py-4">
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

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">قائمة المتاجر</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الشعار</th>
                                <th>اسم المتجر</th>
                                <th>البريد الإلكتروني</th>
                                <th>رقم الهاتف</th>
                                <th>العنوان</th>
                                <th>الوصف</th>
                                <th>تاريخ التسجيل</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stores)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">لا يوجد متاجر</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stores as $index => $store): 
                                    $status = $store['store_status'];
                                    $status_class = $status === 'active' ? 'bg-success' : 'bg-warning';
                                    $status_text = $status === 'active' ? 'نشط' : 'غير نشط';
                                    $logo_path = !empty($store['logo']) ? '../uploads/stores/' . $store['logo'] : '../assets/images/default-store.png';
                                ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <img src="<?php echo $logo_path; ?>" 
                                                 alt="<?php echo $store['name']; ?>" 
                                                 class="store-logo">
                                        </td>
                                        <td>
                                            <div class="store-info">
                                                <div>
                                                    <?php echo $store['name']; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $store['email']; ?></td>
                                        <td><?php echo $store['phone']; ?></td>
                                        <td><?php echo $store['address']; ?></td>
                                        <td><?php echo $store['description']; ?></td>
                                        <td><?php echo date('Y/m/d', strtotime($store['created_at'])); ?></td>
                                        <td>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="edit_store.php?id=<?php echo $store['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> تعديل
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm <?php echo $status === 'active' ? 'btn-success' : 'btn-warning'; ?>" 
                                                        onclick="changeStatus(<?php echo $store['id']; ?>, '<?php echo $status === 'active' ? 'inactive' : 'active'; ?>')">
                                                    <?php echo $status === 'active' ? 'تعطيل' : 'تفعيل'; ?>
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-info" 
                                                        onclick="resetPassword(<?php echo $store['id']; ?>)">
                                                    إعادة تعيين كلمة المرور
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        onclick="confirmDelete(<?php echo $store['id']; ?>, '<?php echo $store['name']; ?>')">
                                                    <i class="fas fa-trash-alt"></i> حذف
                                                </button>
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

    <!-- Modal تأكيد الحذف -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">تأكيد الحذف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    هل أنت متأكد من حذف المتجر "<span id="storeName"></span>"؟
                    <br>
                    <strong class="text-danger">تحذير: سيتم حذف جميع البيانات المرتبطة بالمتجر بما في ذلك المنتجات والطلبات والعروض.</strong>
                </div>
                <div class="modal-footer">
                    <form action="delete_store.php" method="POST">
                        <input type="hidden" name="store_id" id="deleteStoreId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function changeStatus(storeId, newStatus) {
        const action = newStatus === 'active' ? 'تنشيط' : 'تعطيل';
        if (confirm(`هل أنت متأكد من ${action} هذا المتجر؟`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="change_status">
                <input type="hidden" name="store_id" value="${storeId}">
                <input type="hidden" name="status" value="${newStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function resetPassword(storeId) {
        if (confirm('هل أنت متأكد من إعادة تعيين كلمة المرور لهذا المتجر؟')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="store_id" value="${storeId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function confirmDelete(storeId, storeName) {
        document.getElementById('deleteStoreId').value = storeId;
        document.getElementById('storeName').textContent = storeName;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
</body>
</html>
