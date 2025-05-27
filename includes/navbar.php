<?php
require_once __DIR__ . '/init.php';
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['customer_id']) || isset($_SESSION['store_id']);
$is_store = isset($_SESSION['store_id']);
$is_customer = isset($_SESSION['customer_id']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>">السوق الإلكتروني</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>">الرئيسية <i class="fas fa-home"></i></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>stores.php">المتاجر <i class="fas fa-store"></i></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>categories.php">التصنيفات <i class="fas fa-th-list"></i></a>
                </li>
            </ul>
            
            <?php if ($is_customer): ?>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>customer/logout.php">
                            تسجيل خروج <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                if ($user && isset($user['username'])): ?>
                    <div class="user-section">
                        <span class="username">
                            <?php echo htmlspecialchars($user['username']); ?> <i class="fas fa-user"></i>
                        </span>
                        <?php
                        // التحقق مما إذا كنا في مجلد customer
                        $current_path = $_SERVER['PHP_SELF'];
                        $is_in_customer = strpos($current_path, '/marketplace/customer/') !== false;
                        
                        // تعيين المسار المناسب
                        $profile_path = $is_in_customer ? 'profile.php' : 'customer/profile.php';
                        $logout_path = $is_in_customer ? 'logout.php' : 'customer/logout.php';
                        ?>
                        <a href="<?php echo BASE_URL . $profile_path; ?>" class="btn btn-light btn-sm">
                            الملف الشخصي <i class="fas fa-user"></i>
                        </a>
                        <a href="<?php echo BASE_URL . $logout_path; ?>" class="btn btn-danger btn-sm">
                            تسجيل خروج <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
.navbar {
    background: #2c5aa0 !important;
    padding: 0.5rem 0;
}

.user-section {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.username {
    color: white;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.username i {
    font-size: 1rem;
}

.user-section .btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.85rem;
}

.user-section .btn-danger {
    background-color: #dc3545;
    border: none;
}

.user-section .btn-light {
    background-color: #f8f9fa;
    border: none;
}

@media (max-width: 768px) {
    .user-section {
        flex-direction: column;
        gap: 0.5rem;
        margin: 1rem 0;
    }
    
    .user-section .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- إضافة Font Awesome لأيقونات القائمة -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
