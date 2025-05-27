<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول وصلاحيات المتجر
if (!isLoggedIn() || !isStore()) {
    header("Location: ../login.php?error=unauthorized");
    exit;
}

// جلب معلومات المتجر
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM stores WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$store = $result->fetch_assoc();

// إذا لم يتم العثور على متجر، قم بإنشاء متجر جديد
if (!$store) {
    $store = [
        'status' => 'pending',
        'name' => $_SESSION['username'] . ' store'
    ];
    
    // إدراج المتجر في قاعدة البيانات
    $insert_sql = "INSERT INTO stores (user_id, name, status) VALUES (?, ?, 'pending')";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("is", $user_id, $store['name']);
    $insert_stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>متجر قيد المراجعة - السوق الإلكتروني</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card text-center">
                    <div class="card-body py-5">
                        <h1 class="card-title mb-4">
                            <i class="fas fa-store text-primary mb-3 d-block" style="font-size: 3rem;"></i>
                            متجرك قيد المراجعة
                        </h1>
                        
                        <?php if ($store['status'] == 'pending'): ?>
                            <p class="card-text mb-4">
                                شكراً لتسجيلك في السوق الإلكتروني. متجرك حالياً قيد المراجعة من قبل الإدارة.
                                سيتم إخطارك عبر البريد الإلكتروني فور الموافقة على متجرك.
                            </p>
                            <div class="alert alert-info">
                                <strong>حالة المتجر:</strong> قيد المراجعة
                            </div>
                        <?php elseif ($store['status'] == 'rejected'): ?>
                            <p class="card-text mb-4">
                                عذراً، تم رفض طلب تسجيل متجرك. يرجى التواصل مع الإدارة لمزيد من المعلومات.
                            </p>
                            <div class="alert alert-danger">
                                <strong>حالة المتجر:</strong> مرفوض
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="../logout.php" class="btn btn-outline-primary">تسجيل الخروج</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
