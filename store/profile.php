<?php
require_once '../includes/init.php';

// Check if logged in
if (!isset($_SESSION['store_id'])) {
    header('Location: login.php');
    exit();
}

$store_id = $_SESSION['store_id'];

// Get store information
$stmt = $conn->prepare("SELECT * FROM stores WHERE id = ?");
$stmt->bind_param("i", $store_id);
$stmt->execute();
$store = $stmt->get_result()->fetch_assoc();

// Get all categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$all_categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Get current store categories
$store_categories_query = "SELECT category_id FROM store_categories WHERE store_id = ?";
$stmt = $conn->prepare($store_categories_query);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$result = $stmt->get_result();
$store_categories = [];
while ($row = $result->fetch_assoc()) {
    $store_categories[] = $row['category_id'];
}

// Process information update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $description = trim($_POST['description']);
    $city = trim($_POST['city']);
    $facebook_url = trim($_POST['facebook_url']);
    $twitter_url = trim($_POST['twitter_url']);
    $instagram_url = trim($_POST['instagram_url']);
    $whatsapp = trim($_POST['whatsapp']);
    $selected_categories = isset($_POST['categories']) ? $_POST['categories'] : [];

    $errors = [];

    // Validate data
    if (empty($name)) {
        $errors[] = "Store name is required";
    }
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    if (empty($selected_categories)) {
        $errors[] = "You must select at least one category";
    }

    // Process category updates
    if (!empty($selected_categories)) {
        // Delete old categories
        $delete_categories = $conn->prepare("DELETE FROM store_categories WHERE store_id = ?");
        $delete_categories->bind_param("i", $store_id);
        $delete_categories->execute();

        // Add new categories
        $insert_category = $conn->prepare("INSERT INTO store_categories (store_id, category_id) VALUES (?, ?)");
        foreach ($selected_categories as $category_id) {
            $insert_category->bind_param("ii", $store_id, $category_id);
            $insert_category->execute();
        }
    }

    if (empty($errors)) {
        // Start transaction to ensure all data is updated simultaneously
        $conn->begin_transaction();
        
        try {
            // Update store information
            $update_query = "UPDATE stores SET name = ?, email = ?, phone = ?, address = ?, city = ?, description = ?, facebook_url = ?, twitter_url = ?, instagram_url = ?, whatsapp = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssssssssi", $name, $email, $phone, $address, $city, $description, $facebook_url, $twitter_url, $instagram_url, $whatsapp, $store_id);
            $stmt->execute();
            
            // Update email in users table if there is a user_id associated with the store
            $get_user_id = $conn->prepare("SELECT user_id FROM stores WHERE id = ?");
            $get_user_id->bind_param("i", $store_id);
            $get_user_id->execute();
            $user_result = $get_user_id->get_result();
            $user_data = $user_result->fetch_assoc();
            
            if ($user_data && !empty($user_data['user_id'])) {
                $user_id = $user_data['user_id'];
                $update_user = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $update_user->bind_param("si", $email, $user_id);
                $update_user->execute();
            }
            
            // Confirm transaction
            $conn->commit();
            
            // Process logo upload if selected
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $logo_name = $_FILES['logo']['name'];
                $logo_tmp = $_FILES['logo']['tmp_name'];
                $logo_ext = strtolower(pathinfo($logo_name, PATHINFO_EXTENSION));
                
                // Check file type
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($logo_ext, $allowed_types)) {
                    $new_logo_name = uniqid() . '.' . $logo_ext;
                    $upload_path = '../uploads/stores/' . $new_logo_name;
                    
                    if (move_uploaded_file($logo_tmp, $upload_path)) {
                        // Update logo name in database
                        $update_logo = $conn->prepare("UPDATE stores SET logo = ? WHERE id = ?");
                        $update_logo->bind_param("si", $new_logo_name, $store_id);
                        $update_logo->execute();
                    }
                }
            }
            
            $_SESSION['success_message'] = "Store information updated successfully";
            header("Location: profile.php");
            exit();
        } catch (Exception $e) {
            // Rollback transaction in case of error
            $conn->rollback();
            $errors[] = "An error occurred while updating information: " . $e->getMessage();
        
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Profile - Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .store-logo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 1rem;
            border: 3px solid #0d6efd;
        }
        .categories-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
        }
        .category-item {
            background: white;
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        .category-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .category-item .form-check-input:checked + .form-check-label {
            color: #0d6efd;
            font-weight: 600;
        }
        .category-item .bi {
            font-size: 1.2rem;
            vertical-align: middle;
        }
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .category-checkbox {
            margin-left: 10px;
        }
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }
        .category-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
        .category-item:hover {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <?php include '../includes/store_navbar.php'; ?>
    
    <div class="container py-4">
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?php echo !empty($store['logo']) ? '../uploads/stores/' . $store['logo'] : '../assets/images/store-placeholder.jpg'; ?>" 
                             alt="Store Logo" 
                             class="img-fluid store-logo mb-3" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                        <h5 class="card-title"><?php echo htmlspecialchars($store['name']); ?></h5>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($store['email']); ?>
                            </small>
                        </p>
                        <button class="btn btn-outline-primary btn-sm" onclick="copyStoreUrl()">
                            <i class="bi bi-link-45deg"></i> Copy Store URL
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Update Store Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success">
                                <?php 
                                echo $_SESSION['success_message'];
                                unset($_SESSION['success_message']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Store Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($store['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($store['email']); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($store['phone']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" 
                                               value="<?php echo htmlspecialchars($store['address']); ?>"
                                               required>
                                        <div class="form-text">Important to enable customers to reach your store</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="city" name="city" 
                                               value="<?php echo isset($store['city']) ? htmlspecialchars($store['city']) : ''; ?>"
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h5 class="mb-3">Social Media Links</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="facebook_url" class="form-label">
                                                <i class="bi bi-facebook me-2 text-primary"></i>Facebook
                                            </label>
                                            <input type="url" class="form-control" id="facebook_url" name="facebook_url" 
                                                   placeholder="https://facebook.com/your-page"
                                                   value="<?php echo isset($store['facebook_url']) ? htmlspecialchars($store['facebook_url']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="twitter_url" class="form-label">
                                                <i class="bi bi-twitter me-2 text-info"></i>Twitter
                                            </label>
                                            <input type="url" class="form-control" id="twitter_url" name="twitter_url" 
                                                   placeholder="https://twitter.com/your-handle"
                                                   value="<?php echo isset($store['twitter_url']) ? htmlspecialchars($store['twitter_url']) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="instagram_url" class="form-label">
                                                <i class="bi bi-instagram me-2 text-danger"></i>Instagram
                                            </label>
                                            <input type="url" class="form-control" id="instagram_url" name="instagram_url" 
                                                   placeholder="https://instagram.com/your-profile"
                                                   value="<?php echo isset($store['instagram_url']) ? htmlspecialchars($store['instagram_url']) : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="whatsapp" class="form-label">
                                                <i class="bi bi-whatsapp me-2 text-success"></i>WhatsApp
                                            </label>
                                            <input type="text" class="form-control" id="whatsapp" name="whatsapp" 
                                                   placeholder="966xxxxxxxxx"
                                                   value="<?php echo isset($store['whatsapp']) ? htmlspecialchars($store['whatsapp']) : ''; ?>">
                                            <div class="form-text">Enter WhatsApp number without + sign (example: 966xxxxxxxxx)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="categories-section mb-4">
                                <label class="form-label fw-bold mb-3">Store Categories</label>
                                <div class="row g-3">
                                    <?php foreach ($all_categories as $category): ?>
                                        <div class="col-md-4">
                                            <div class="form-check category-item">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="categories[]" 
                                                       value="<?php echo $category['id']; ?>"
                                                       id="category_<?php echo $category['id']; ?>"
                                                       <?php echo in_array($category['id'], $store_categories) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="category_<?php echo $category['id']; ?>">
                                                    <i class="bi bi-<?php echo htmlspecialchars($category['icon']); ?> me-2"></i>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Store Description</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="4" required><?php echo htmlspecialchars($store['description']); ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label for="logo" class="form-label">Store Logo</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                <div class="form-text">Optional - Leave empty to keep current logo</div>
                            </div>

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function copyStoreUrl() {
        var storeSlug = '<?php echo $store['slug']; ?>';
        var storeUrl = window.location.origin + '/marketplace/store-page.php?slug=' + storeSlug;
        
        navigator.clipboard.writeText(storeUrl).then(function() {
            alert('Store URL copied successfully!');
        }).catch(function(err) {
            console.error('Failed to copy URL: ', err);
            alert('An error occurred while copying the URL');
        });
    }
    </script>
</body>
</html>