<?php
/**
 * Setup Index Page for English Marketplace
 * This page provides links to setup tools and instructions
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - English Marketplace</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .setup-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }
        .setup-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #000;
        }
        .btn-setup {
            background-color: #000;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .btn-setup:hover {
            background-color: #ff8c00;
            color: #fff;
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background-color: #000;
            color: #fff;
            border-radius: 50%;
            text-align: center;
            line-height: 30px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="header">
            <h1><i class="bi bi-shop me-2"></i> E-Marketplace</h1>
            <p class="lead">English Version Setup</p>
        </div>
        
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i> Welcome to the setup page for the English version of the Marketplace. Follow the steps below to set up your database and configure your site.
        </div>
        
        <h2 class="mb-4"><span class="step-number">1</span> Database Setup</h2>
        <div class="row">
            <div class="col-md-6">
                <div class="card setup-card h-100">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="bi bi-database"></i>
                        </div>
                        <h5 class="card-title">Import Database Structure</h5>
                        <p class="card-text">Import the database structure from the original Arabic marketplace database.</p>
                        <a href="import_database_structure.php" class="btn btn-setup">Import Structure</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card setup-card h-100">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="bi bi-table"></i>
                        </div>
                        <h5 class="card-title">Create Database Tables</h5>
                        <p class="card-text">Create all necessary database tables if you're starting from scratch.</p>
                        <a href="setup_database.php" class="btn btn-setup">Create Tables</a>
                    </div>
                </div>
            </div>
        </div>
        
        <h2 class="my-4"><span class="step-number">2</span> Configuration</h2>
        <div class="card setup-card">
            <div class="card-body">
                <h5 class="card-title">Configuration Files</h5>
                <p>The following configuration files have been set up for you:</p>
                <ul>
                    <li><code>includes/config.php</code> - Database connection settings</li>
                    <li><code>includes/init.php</code> - Application initialization</li>
                    <li><code>includes/translations.php</code> - English translations</li>
                </ul>
                <p>If you need to modify these files, you can do so manually.</p>
            </div>
        </div>
        
        <h2 class="my-4"><span class="step-number">3</span> Go to Site</h2>
        <div class="row">
            <div class="col-md-6">
                <div class="card setup-card h-100">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="bi bi-house"></i>
                        </div>
                        <h5 class="card-title">Go to Homepage</h5>
                        <p class="card-text">Visit the homepage of your English marketplace.</p>
                        <a href="customer/index.php" class="btn btn-setup">Homepage</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card setup-card h-100">
                    <div class="card-body text-center">
                        <div class="card-icon">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h5 class="card-title">Admin Panel</h5>
                        <p class="card-text">Access the admin panel to manage your marketplace.</p>
                        <a href="admin/login.php" class="btn btn-setup">Admin Panel</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-5 text-center">
            <p><strong>Need help?</strong> Refer to the <a href="README.md">README.md</a> file for more information.</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
