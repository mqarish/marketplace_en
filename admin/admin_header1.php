<?php
// Ensure this file is included, not accessed directly
defined('BASEPATH') or define('BASEPATH', true);
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $page_title; ?> - لوحة التحكم</title>

<!-- Bootstrap RTL CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<!-- Admin Custom CSS -->
<style>
    body {
        background-color: #f8f9fa;
    }
    
    .navbar {
        box-shadow: 0 2px 4px rgba(0,0,0,.1);
        background-color: #343a40;
        padding: 0.5rem 1rem;
    }
    
    .navbar-brand {
        color: #fff;
        font-size: 1.25rem;
        padding: 0.5rem 1rem;
        margin: 0;
    }
    
    .navbar-nav .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 1rem;
    }
    
    .navbar-nav .nav-link:hover,
    .navbar-nav .nav-link.active {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    .navbar-nav .nav-link i {
        margin-left: 0.5rem;
        width: 20px;
        text-align: center;
    }
    
    .content {
        padding: 20px;
    }
    
    .card {
        margin-bottom: 20px;
        box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        border: 0;
    }
    
    .card-header {
        background-color: transparent;
        border-bottom: 1px solid rgba(0,0,0,.125);
        padding: 0.75rem 1.25rem;
    }
    
    .stats-card {
        transition: all 0.3s ease;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0,0,0,.2);
    }
    
    .user-menu .dropdown-toggle::after {
        display: none;
    }
    
    .user-menu .dropdown-menu {
        left: 0;
        right: auto;
    }
    
    @media (max-width: 768px) {
        .navbar-nav {
            padding-top: 0.5rem;
        }
        
        .navbar-nav .nav-link {
            padding: 0.5rem 1rem;
        }
    }
</style>
