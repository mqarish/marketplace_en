<?php
// التأكد من أن هذا الملف لا يمكن الوصول إليه مباشرة
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// عرض رسائل النجاح
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-check-circle me-1"></i> ' . htmlspecialchars($_SESSION['success']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['success']);
}

// عرض رسائل الخطأ
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-exclamation-circle me-1"></i> ' . htmlspecialchars($_SESSION['error']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['error']);
}

// عرض رسائل التحذير
if (isset($_SESSION['warning'])) {
    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-exclamation-triangle me-1"></i> ' . htmlspecialchars($_SESSION['warning']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['warning']);
}

// عرض رسائل المعلومات
if (isset($_SESSION['info'])) {
    echo '<div class="alert alert-info alert-dismissible fade show" role="alert">';
    echo '<i class="fas fa-info-circle me-1"></i> ' . htmlspecialchars($_SESSION['info']);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['info']);
}
?>
