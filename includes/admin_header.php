<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'لوحة التحكم'; ?> - السوق الإلكتروني</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom Admin Styles -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
            border: none;
            margin-bottom: 1rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,.125);
            padding: 1rem;
        }
        
        .btn-icon {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stats-card {
            transition: transform 0.2s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .table th {
            font-weight: 600;
        }
        
        .status-badge {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }
        
        /* Custom Colors */
        .bg-primary {
            background-color: #0d6efd !important;
        }
        
        .bg-success {
            background-color: #198754 !important;
        }
        
        .bg-warning {
            background-color: #ffc107 !important;
        }
        
        .bg-danger {
            background-color: #dc3545 !important;
        }
    </style>
    
    <?php if (isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
</head>
