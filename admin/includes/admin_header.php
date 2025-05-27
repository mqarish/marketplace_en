<?php
// التأكد من تعيين عنوان الصفحة
if (!isset($page_title)) {
    $page_title = 'لوحة التحكم';
}

// التأكد من تعيين الأيقونة
if (!isset($page_icon)) {
    $page_icon = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - السوق الإلكتروني</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <style>
        .page-header {
            background-color: #f8f9fa;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .page-header h2 {
            margin: 0;
            font-size: 1.75rem;
            color: #212529;
        }
        
        .page-header .breadcrumb {
            margin: 0;
            background: transparent;
            padding: 0;
        }
        
        .page-header .icon-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .page-header .icon-title i {
            font-size: 1.5rem;
            color: #0d6efd;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .table-responsive {
            margin-top: 1rem;
        }
        
        .alert {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>
    
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="icon-title">
                    <i class="fas fa-<?php echo $page_icon; ?>"></i>
                    <h2><?php echo htmlspecialchars($page_title); ?></h2>
                </div>
                <?php if (isset($header_buttons)): ?>
                    <div class="action-buttons">
                        <?php echo $header_buttons; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (isset($breadcrumb)): ?>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">الرئيسية</a></li>
                        <?php echo $breadcrumb; ?>
                    </ol>
                </nav>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container py-4">
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
