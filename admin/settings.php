<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// Define BASEPATH for included files
define('BASEPATH', true);

$page_title = 'System Settings';
$page_icon = 'fa-cog';

// Check if settings table exists
$check_table = $conn->query("SHOW TABLES LIKE 'settings'");
if ($check_table->num_rows == 0) {
    // Create settings table if it doesn't exist
    $create_table = "CREATE TABLE settings (
        id INT(11) NOT NULL AUTO_INCREMENT,
        setting_key VARCHAR(191) NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($create_table)) {
        die("Error creating settings table: " . $conn->error);
    }
    
    // Add default settings
    $default_settings = [
        ['site_name', 'E-Marketplace'],
        ['site_description', 'Integrated E-Commerce Platform'],
        ['site_email', 'info@marketplace.com'],
        ['site_phone', '+966500000000'],
        ['site_address', 'Riyadh, Saudi Arabia'],
        ['maintenance_mode', '0'],
        ['currency', 'SAR'],
        ['currency_symbol', 'SAR'],
        ['paypal_email', ''],
        ['paypal_enabled', '0'],
        ['stripe_key', ''],
        ['stripe_secret', ''],
        ['stripe_enabled', '0'],
        ['smtp_host', ''],
        ['smtp_port', '587'],
        ['smtp_username', ''],
        ['smtp_password', ''],
        ['smtp_encryption', 'tls'],
        ['smtp_enabled', '0'],
        ['facebook_url', ''],
        ['twitter_url', ''],
        ['instagram_url', ''],
        ['youtube_url', ''],
        ['linkedin_url', '']
    ];
    
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($default_settings as $setting) {
        $stmt->bind_param("ss", $setting[0], $setting[1]);
        $stmt->execute();
    }
}

// Load current settings
$settings = [];
$query = "SELECT * FROM settings";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Process settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check the type of submitted form
    $form_type = $_POST['form_type'] ?? '';
    
    if ($form_type == 'general_settings') {
        // Update general settings
        $site_name = sanitize_input($_POST['site_name']);
        $site_description = sanitize_input($_POST['site_description']);
        $site_email = sanitize_input($_POST['site_email']);
        $site_phone = sanitize_input($_POST['site_phone']);
        $site_address = sanitize_input($_POST['site_address']);
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        
        // تحديث قاعدة البيانات
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        $settings_to_update = [
            ['site_name', $site_name],
            ['site_description', $site_description],
            ['site_email', $site_email],
            ['site_phone', $site_phone],
            ['site_address', $site_address],
            ['maintenance_mode', $maintenance_mode]
        ];
        
        foreach ($settings_to_update as $setting) {
            $stmt->bind_param("ss", $setting[0], $setting[1]);
            $stmt->execute();
        }
        
        $_SESSION['success'] = "General settings updated successfully";
        
    } elseif ($form_type == 'payment_settings') {
        // Update payment settings
        $currency = sanitize_input($_POST['currency']);
        $currency_symbol = sanitize_input($_POST['currency_symbol']);
        $paypal_email = sanitize_input($_POST['paypal_email']);
        $paypal_enabled = isset($_POST['paypal_enabled']) ? 1 : 0;
        $stripe_key = sanitize_input($_POST['stripe_key']);
        $stripe_secret = sanitize_input($_POST['stripe_secret']);
        $stripe_enabled = isset($_POST['stripe_enabled']) ? 1 : 0;
        
        // تحديث قاعدة البيانات
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        $settings_to_update = [
            ['currency', $currency],
            ['currency_symbol', $currency_symbol],
            ['paypal_email', $paypal_email],
            ['paypal_enabled', $paypal_enabled],
            ['stripe_key', $stripe_key],
            ['stripe_secret', $stripe_secret],
            ['stripe_enabled', $stripe_enabled]
        ];
        
        foreach ($settings_to_update as $setting) {
            $stmt->bind_param("ss", $setting[0], $setting[1]);
            $stmt->execute();
        }
        
        $_SESSION['success'] = "Payment settings updated successfully";
        
    } elseif ($form_type == 'email_settings') {
        // Update email settings
        $smtp_host = sanitize_input($_POST['smtp_host']);
        $smtp_port = sanitize_input($_POST['smtp_port']);
        $smtp_username = sanitize_input($_POST['smtp_username']);
        $smtp_password = sanitize_input($_POST['smtp_password']);
        $smtp_encryption = sanitize_input($_POST['smtp_encryption']);
        $smtp_enabled = isset($_POST['smtp_enabled']) ? 1 : 0;
        
        // تحديث قاعدة البيانات
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        $settings_to_update = [
            ['smtp_host', $smtp_host],
            ['smtp_port', $smtp_port],
            ['smtp_username', $smtp_username],
            ['smtp_password', $smtp_password],
            ['smtp_encryption', $smtp_encryption],
            ['smtp_enabled', $smtp_enabled]
        ];
        
        foreach ($settings_to_update as $setting) {
            $stmt->bind_param("ss", $setting[0], $setting[1]);
            $stmt->execute();
        }
        
        $_SESSION['success'] = "Email settings updated successfully";
        
    } elseif ($form_type == 'social_settings') {
        // Update social media settings
        $facebook_url = sanitize_input($_POST['facebook_url']);
        $twitter_url = sanitize_input($_POST['twitter_url']);
        $instagram_url = sanitize_input($_POST['instagram_url']);
        $youtube_url = sanitize_input($_POST['youtube_url']);
        $linkedin_url = sanitize_input($_POST['linkedin_url']);
        
        // تحديث قاعدة البيانات
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        $settings_to_update = [
            ['facebook_url', $facebook_url],
            ['twitter_url', $twitter_url],
            ['instagram_url', $instagram_url],
            ['youtube_url', $youtube_url],
            ['linkedin_url', $linkedin_url]
        ];
        
        foreach ($settings_to_update as $setting) {
            $stmt->bind_param("ss", $setting[0], $setting[1]);
            $stmt->execute();
        }
        
        $_SESSION['success'] = "Social media settings updated successfully";
    }
    
    // Reload settings after update
    $result = $conn->query("SELECT * FROM settings");
    $settings = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: settings.php");
    exit;
}

// Retrieve settings values from the array
$site_name = $settings['site_name'] ?? 'E-Marketplace';
$site_description = $settings['site_description'] ?? 'Integrated E-Commerce Platform';
$site_email = $settings['site_email'] ?? 'info@marketplace.com';
$site_phone = $settings['site_phone'] ?? '+966500000000';
$site_address = $settings['site_address'] ?? 'Riyadh, Saudi Arabia';
$maintenance_mode = $settings['maintenance_mode'] ?? '0';

$currency = $settings['currency'] ?? 'SAR';
$currency_symbol = $settings['currency_symbol'] ?? 'SAR';
$paypal_email = $settings['paypal_email'] ?? '';
$paypal_enabled = $settings['paypal_enabled'] ?? '0';
$stripe_key = $settings['stripe_key'] ?? '';
$stripe_secret = $settings['stripe_secret'] ?? '';
$stripe_enabled = $settings['stripe_enabled'] ?? '0';

$smtp_host = $settings['smtp_host'] ?? '';
$smtp_port = $settings['smtp_port'] ?? '587';
$smtp_username = $settings['smtp_username'] ?? '';
$smtp_password = $settings['smtp_password'] ?? '';
$smtp_encryption = $settings['smtp_encryption'] ?? 'tls';
$smtp_enabled = $settings['smtp_enabled'] ?? '0';

$facebook_url = $settings['facebook_url'] ?? '';
$twitter_url = $settings['twitter_url'] ?? '';
$instagram_url = $settings['instagram_url'] ?? '';
$youtube_url = $settings['youtube_url'] ?? '';
$linkedin_url = $settings['linkedin_url'] ?? '';
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <?php include 'admin_header.php'; ?>
    <style>
        .nav-pills .nav-link {
            color: #495057;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            margin-bottom: 5px;
        }
        .nav-pills .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .settings-icon {
            width: 20px;
            text-align: center;
            margin-left: 8px;
        }
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .card-header {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas <?php echo $page_icon; ?>"></i> <?php echo $page_title; ?></h2>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Settings Sections</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button" role="tab">
                                <i class="fas fa-cog settings-icon"></i> General Settings
                            </button>
                            <button class="nav-link" id="payment-tab" data-bs-toggle="pill" data-bs-target="#payment" type="button" role="tab">
                                <i class="fas fa-credit-card settings-icon"></i> Payment Settings
                            </button>
                            <button class="nav-link" id="email-tab" data-bs-toggle="pill" data-bs-target="#email" type="button" role="tab">
                                <i class="fas fa-envelope settings-icon"></i> Email Settings
                            </button>
                            <button class="nav-link" id="social-tab" data-bs-toggle="pill" data-bs-target="#social" type="button" role="tab">
                                <i class="fas fa-share-alt settings-icon"></i> Social Media
                            </button>
                            <button class="nav-link" id="backup-tab" data-bs-toggle="pill" data-bs-target="#backup" type="button" role="tab">
                                <i class="fas fa-database settings-icon"></i> Backup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <div class="tab-content" id="v-pills-tabContent">
                            <!-- General Settings -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                                <h4 class="mb-4">General Settings</h4>
                                <form method="POST" action="">
                                    <input type="hidden" name="form_type" value="general_settings">
                                    
                                    <div class="mb-3">
                                        <label for="site_name" class="form-label">Site Name</label>
                                        <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_name); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="site_description" class="form-label">Site Description</label>
                                        <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($site_description); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="site_email" class="form-label">Site Email</label>
                                        <input type="email" class="form-control" id="site_email" name="site_email" value="<?php echo htmlspecialchars($site_email); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="site_phone" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="site_phone" name="site_phone" value="<?php echo htmlspecialchars($site_phone); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="site_address" class="form-label">Address</label>
                                        <textarea class="form-control" id="site_address" name="site_address" rows="2"><?php echo htmlspecialchars($site_address); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php echo $maintenance_mode == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Save Settings</button>
                                </form>
                            </div>
                            
                            <!-- Payment Settings -->
                            <div class="tab-pane fade" id="payment" role="tabpanel" aria-labelledby="payment-tab">
                                <h4 class="mb-4">Payment Settings</h4>
                                <form method="POST" action="">
                                    <input type="hidden" name="form_type" value="payment_settings">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="currency" class="form-label">Currency</label>
                                            <select class="form-select" id="currency" name="currency">
                                                <option value="SAR" <?php echo $currency == 'SAR' ? 'selected' : ''; ?>>Saudi Riyal (SAR)</option>
                                                <option value="YER" <?php echo $currency == 'YER' ? 'selected' : ''; ?>>Yemeni Rial (YER)</option>
                                                <option value="USD" <?php echo $currency == 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                                <option value="EUR" <?php echo $currency == 'EUR' ? 'selected' : ''; ?>>Euro (EUR)</option>
                                                <option value="GBP" <?php echo $currency == 'GBP' ? 'selected' : ''; ?>>British Pound (GBP)</option>
                                                <option value="AED" <?php echo $currency == 'AED' ? 'selected' : ''; ?>>UAE Dirham (AED)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="currency_symbol" class="form-label">Currency Symbol</label>
                                            <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?php echo htmlspecialchars($currency_symbol); ?>">
                                        </div>
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <h5 class="mb-3">PayPal</h5>
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="paypal_enabled" name="paypal_enabled" <?php echo $paypal_enabled == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="paypal_enabled">Enable PayPal Payments</label>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="paypal_email" class="form-label">PayPal Account Email</label>
                                        <input type="email" class="form-control" id="paypal_email" name="paypal_email" value="<?php echo htmlspecialchars($paypal_email); ?>">
                                    </div>
                                    
                                    <hr class="my-4">
                                    
                                    <h5 class="mb-3">Stripe</h5>
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="stripe_enabled" name="stripe_enabled" <?php echo $stripe_enabled == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="stripe_enabled">Enable Stripe Payments</label>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="stripe_key" class="form-label">Stripe Publishable Key</label>
                                        <input type="text" class="form-control" id="stripe_key" name="stripe_key" value="<?php echo htmlspecialchars($stripe_key); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="stripe_secret" class="form-label">Stripe Secret Key</label>
                                        <input type="password" class="form-control" id="stripe_secret" name="stripe_secret" value="<?php echo htmlspecialchars($stripe_secret); ?>">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Save Payment Settings</button>
                                </form>
                            </div>
                            
                            <!-- Email Settings -->
                            <div class="tab-pane fade" id="email" role="tabpanel" aria-labelledby="email-tab">
                                <h4 class="mb-4">Email Settings</h4>
                                <form method="POST" action="">
                                    <input type="hidden" name="form_type" value="email_settings">
                                    
                                    <div class="mb-3 form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="smtp_enabled" name="smtp_enabled" <?php echo $smtp_enabled == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="smtp_enabled">Use SMTP for sending emails</label>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="smtp_host" class="form-label">SMTP Server</label>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>" placeholder="Example: smtp.gmail.com">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="smtp_port" class="form-label">SMTP Port</label>
                                        <input type="text" class="form-control" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>" placeholder="Example: 587">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="smtp_username" class="form-label">SMTP Username</label>
                                        <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($smtp_username); ?>" placeholder="Usually your email address">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="smtp_password" class="form-label">SMTP Password</label>
                                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($smtp_password); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="smtp_encryption" class="form-label">SMTP Encryption</label>
                                        <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                            <option value="tls" <?php echo $smtp_encryption == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                            <option value="ssl" <?php echo $smtp_encryption == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                            <option value="" <?php echo $smtp_encryption == '' ? 'selected' : ''; ?>>No Encryption</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Save Email Settings</button>
                                    
                                    <div class="mt-4">
                                        <button type="button" class="btn btn-outline-primary" id="test_email">
                                            <i class="fas fa-paper-plane me-2"></i> Send Test Email
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Social Media -->
                            <div class="tab-pane fade" id="social" role="tabpanel" aria-labelledby="social-tab">
                                <h4 class="mb-4">Social Media</h4>
                                <form method="POST" action="">
                                    <input type="hidden" name="form_type" value="social_settings">
                                    
                                    <div class="mb-3">
                                        <label for="facebook_url" class="form-label">
                                            <i class="fab fa-facebook text-primary me-2"></i> Facebook
                                        </label>
                                        <input type="url" class="form-control" id="facebook_url" name="facebook_url" value="<?php echo htmlspecialchars($facebook_url); ?>" placeholder="https://facebook.com/yourpage">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="twitter_url" class="form-label">
                                            <i class="fab fa-twitter text-info me-2"></i> Twitter
                                        </label>
                                        <input type="url" class="form-control" id="twitter_url" name="twitter_url" value="<?php echo htmlspecialchars($twitter_url); ?>" placeholder="https://twitter.com/youraccount">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="instagram_url" class="form-label">
                                            <i class="fab fa-instagram text-danger me-2"></i> Instagram
                                        </label>
                                        <input type="url" class="form-control" id="instagram_url" name="instagram_url" value="<?php echo htmlspecialchars($instagram_url); ?>" placeholder="https://instagram.com/youraccount">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="youtube_url" class="form-label">
                                            <i class="fab fa-youtube text-danger me-2"></i> YouTube
                                        </label>
                                        <input type="url" class="form-control" id="youtube_url" name="youtube_url" value="<?php echo htmlspecialchars($youtube_url); ?>" placeholder="https://youtube.com/channel/yourchannel">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="linkedin_url" class="form-label">
                                            <i class="fab fa-linkedin text-primary me-2"></i> LinkedIn
                                        </label>
                                        <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" value="<?php echo htmlspecialchars($linkedin_url); ?>" placeholder="https://linkedin.com/company/yourcompany">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Save Social Media Settings</button>
                                </form>
                            </div>
                            
                            <!-- Backup -->
                            <div class="tab-pane fade" id="backup" role="tabpanel" aria-labelledby="backup-tab">
                                <h4 class="mb-4">Backup and Data Recovery</h4>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Create Backup</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Create a complete backup of the database. The file will be saved on the server and you can download it.</p>
                                        <button type="button" id="create_backup" class="btn btn-primary">
                                            <i class="fas fa-download me-2"></i> Create Backup
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Restore Data</h5>
                                    </div>
                                    <div class="card-body">
                                        <p>Restore the database from a previous backup. <strong class="text-danger">Warning: All current data will be replaced!</strong></p>
                                        <form method="POST" action="process_backup.php" enctype="multipart/form-data">
                                            <input type="hidden" name="action" value="restore">
                                            <div class="mb-3">
                                                <label for="backup_file" class="form-label">Backup File (SQL)</label>
                                                <input type="file" class="form-control" id="backup_file" name="backup_file" accept=".sql">
                                            </div>
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-upload me-2"></i> Restore Data
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Saved Backups</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>File Name</th>
                                                        <th>Creation Date</th>
                                                        <th>File Size</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="backup_files">
                                                    <?php
                                                    $backup_dir = '../backups/';
                                                    if (!file_exists($backup_dir)) {
                                                        mkdir($backup_dir, 0755, true);
                                                    }
                                                    
                                                    $backup_files = glob($backup_dir . '*.sql');
                                                    if (!empty($backup_files)) {
                                                        foreach ($backup_files as $file) {
                                                            $filename = basename($file);
                                                            $filesize = filesize($file);
                                                            $filedate = date('Y-m-d H:i:s', filemtime($file));
                                                            
                                                            echo '<tr>';
                                                            echo '<td>' . htmlspecialchars($filename) . '</td>';
                                                            echo '<td>' . $filedate . '</td>';
                                                            echo '<td>' . round($filesize / 1024, 2) . ' KB</td>';
                                                            echo '<td>';
                                                            echo '<a href="download_backup.php?file=' . urlencode($filename) . '" class="btn btn-sm btn-primary me-2"><i class="fas fa-download"></i> Download</a>';
                                                            echo '<button type="button" class="btn btn-sm btn-danger delete-backup" data-file="' . htmlspecialchars($filename) . '"><i class="fas fa-trash"></i> Delete</button>';
                                                            echo '</td>';
                                                            echo '</tr>';
                                                        }
                                                    } else {
                                                        echo '<tr><td colspan="4" class="text-center">No saved backups</td></tr>';
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Send test email
        document.getElementById('test_email').addEventListener('click', function() {
            if (confirm('Do you want to send a test email to verify the settings?')) {
                fetch('send_test_email.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Test email sent successfully!');
                    } else {
                        alert('Failed to send email: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('An error occurred: ' + error);
                });
            }
        });
        
        // Create backup
        document.getElementById('create_backup').addEventListener('click', function() {
            if (confirm('Do you want to create a new database backup?')) {
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';
                
                fetch('process_backup.php?action=create')
                .then(response => response.json())
                .then(data => {
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-download me-2"></i> Create Backup';
                    
                    if (data.success) {
                        alert('Backup created successfully!');
                        location.reload();
                    } else {
                        alert('Failed to create backup: ' + data.message);
                    }
                })
                .catch(error => {
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-download me-2"></i> Create Backup';
                    alert('An error occurred: ' + error);
                });
            }
        });
        
        // Delete backup
        document.querySelectorAll('.delete-backup').forEach(button => {
            button.addEventListener('click', function() {
                const filename = this.getAttribute('data-file');
                if (confirm('Are you sure you want to delete the backup: ' + filename + '?')) {
                    fetch('process_backup.php?action=delete&file=' + encodeURIComponent(filename))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Backup deleted successfully!');
                            location.reload();
                        } else {
                            alert('Failed to delete backup: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('An error occurred: ' + error);
                    });
                }
            });
        });
    </script>
</body>
</html>