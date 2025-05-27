<?php
/**
 * Address Management Page - Add, edit, and delete shipping addresses for the customer
 */

session_start();
require_once '../includes/init.php';
require_once '../includes/functions.php';
require_once '../includes/customer_auth.php';

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: ' . BASE_URL . '/customer/login.php');
    exit();
}

$customer_id = $_SESSION['customer_id'];
$success_message = '';
$error_message = '';

// Delete address
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $address_id = (int)$_GET['delete'];
    
    // Verify that the address belongs to the current customer
    $check_stmt = $conn->prepare("SELECT id FROM customer_addresses WHERE id = ? AND customer_id = ?");
    if ($check_stmt === false) {
        $error_message = "Database query error: " . $conn->error;
    } else {
        $check_stmt->bind_param("ii", $address_id, $customer_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
    }
    
    if ($check_result->num_rows > 0) {
        $delete_stmt = $conn->prepare("DELETE FROM customer_addresses WHERE id = ?");
        if ($delete_stmt === false) {
            $error_message = "Database query error: " . $conn->error;
        } else {
            $delete_stmt->bind_param("i", $address_id);
        }
        
        if ($delete_stmt->execute()) {
            $success_message = "Address deleted successfully";
        } else {
            $error_message = "An error occurred while deleting the address";
        }
    } else {
        $error_message = "Address not found or you cannot delete it";
    }
}

// إضافة أو تعديل عنوان
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address_id = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;
    $title = trim($_POST['title']);
    $recipient_name = trim($_POST['recipient_name']);
    $phone = trim($_POST['phone']);
    $city = trim($_POST['city']);
    $area = trim($_POST['area']);
    $street = trim($_POST['street']);
    $building = trim($_POST['building']);
    $apartment = trim($_POST['apartment']);
    $notes = trim($_POST['notes']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // التحقق من البيانات
    if (empty($title) || empty($recipient_name) || empty($phone) || empty($city) || empty($street)) {
        $error_message = "يرجى ملء جميع الحقول المطلوبة";
    } else {
        // إذا تم تحديد هذا العنوان كافتراضي، قم بإلغاء تحديد جميع العناوين الأخرى
        if ($is_default) {
            $update_defaults = $conn->prepare("UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?");
            if ($update_defaults === false) {
                $error_message = "خطأ في استعلام قاعدة البيانات: " . $conn->error;
            } else {
                $update_defaults->bind_param("i", $customer_id);
                $update_defaults->execute();
            }
        }
        
        if ($address_id > 0) {
            // تعديل عنوان موجود
            $check_stmt = $conn->prepare("SELECT id FROM customer_addresses WHERE id = ? AND customer_id = ?");
            if ($check_stmt === false) {
                $error_message = "خطأ في استعلام قاعدة البيانات: " . $conn->error;
            } else {
                $check_stmt->bind_param("ii", $address_id, $customer_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
            
                if ($check_result && $check_result->num_rows > 0) {
                    $update_stmt = $conn->prepare("UPDATE customer_addresses SET 
                        title = ?, recipient_name = ?, phone = ?, city = ?, area = ?, 
                        street = ?, building = ?, apartment = ?, notes = ?, is_default = ? 
                        WHERE id = ?");
                    if ($update_stmt === false) {
                        $error_message = "خطأ في استعلام قاعدة البيانات: " . $conn->error;
                    } else {
                        $update_stmt->bind_param("sssssssssii", $title, $recipient_name, $phone, $city, $area, 
                                                $street, $building, $apartment, $notes, $is_default, $address_id);
                        if (!$update_stmt->execute()) {
                            $error_message = "خطأ في استعلام قاعدة البيانات: " . $conn->error;
                        } else {
                            $success_message = "تم تحديث العنوان بنجاح";
                        }
                    }
                } else {
                    $error_message = "العنوان غير موجود أو لا يمكنك تعديله";
                }
            }
        } else {
            // إضافة عنوان جديد
            $insert_stmt = $conn->prepare("INSERT INTO customer_addresses 
                (customer_id, title, recipient_name, phone, city, area, street, building, apartment, notes, is_default) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($insert_stmt === false) {
                $error_message = "خطأ في استعلام قاعدة البيانات: " . $conn->error;
            } else {
                $insert_stmt->bind_param("isssssssssi", $customer_id, $title, $recipient_name, $phone, $city, 
                                        $area, $street, $building, $apartment, $notes, $is_default);
                if (!$insert_stmt->execute()) {
                    $error_message = "خطأ في استعلام قاعدة البيانات: " . $conn->error;
                } else {
                    $success_message = "تمت إضافة العنوان بنجاح";
                }
            }
        }
    }
}

// جلب عناوين العميل
$addresses_result = false; // تهيئة المتغير لتجنب التحذيرات
$addresses_sql = "SELECT * FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, created_at DESC";
$stmt = $conn->prepare($addresses_sql);
if ($stmt === false) {
    $error_message = "خطأ في استعلام قاعدة البيانات: " . $conn->error;
} else {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $addresses_result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Addresses - E-Marketplace</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #000000 0%, #222222 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .address-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            position: relative;
            padding-top: 25px; /* Add space at the top for the badge */
        }
        .address-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }
        .address-card.default {
            border-right: 4px solid #000000;
        }
        .address-actions {
            position: absolute;
            top: 10px;
            left: 10px;
        }
        .address-actions .btn {
            padding: 0.25rem 0.5rem;
            margin-right: 0.25rem;
        }
        .default-badge {
            display: inline-block;
            background-color: #ff8c00; /* Orange color */
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        /* Add new address button styling */
        .btn-add-address {
            background-color: #000000;
            color: white;
            border: none;
            transition: all 0.3s ease;
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 50px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
            font-size: 1rem;
        }
        
        .btn-add-address:hover, .btn-add-address:focus, .btn-add-address:active {
            background-color: #ff8c00; /* Orange color */
            border-color: #ff8c00;
            color: white;
        }
        .address-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .address-info {
            margin-bottom: 0.25rem;
            color: #6c757d;
        }
        .address-info i {
            width: 20px;
            text-align: center;
            margin-left: 0.5rem;
        }
        .empty-addresses {
            text-align: center;
            padding: 3rem 0;
        }
        .empty-addresses i {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Include the dark header -->
    <?php 
    $root_path = '../';
    include '../includes/dark_header.php'; 
    ?>

    <div class="page-header text-center">
        <div class="container">
            <h1><i class="bi bi-geo-alt me-2"></i> My Addresses</h1>
            <p class="lead">Manage your shipping addresses</p>
        </div>
    </div>

    <div class="container py-4">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">My Addresses</h1>
            <button class="btn btn-add-address" data-bs-toggle="modal" data-bs-target="#addressModal">
                <i class="bi bi-plus me-2"></i> Add New Address
            </button>
        </div>
        
        <?php if ($addresses_result && $addresses_result->num_rows > 0): ?>
            <div class="row">
                <?php while ($address = $addresses_result->fetch_assoc()): ?>
                    <div class="col-md-6">
                        <div class="card address-card <?php echo $address['is_default'] ? 'default' : ''; ?>">
                            <div class="address-actions">
                                <button class="btn btn-sm btn-outline-primary edit-address" 
                                        data-address='<?php echo json_encode($address); ?>'
                                        data-bs-toggle="modal" data-bs-target="#addressModal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="?delete=<?php echo $address['id']; ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this address?');">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                            
                            <div class="card-body">
                                <?php if ($address['is_default']): ?>
                                    <div class="default-badge">
                                        <i class="bi bi-check-circle me-1"></i> Default Address
                                    </div>
                                <?php endif; ?>
                                <h5 class="address-title"><?php echo htmlspecialchars($address['title']); ?></h5>
                                
                                <div class="address-info">
                                    <i class="bi bi-person"></i>
                                    <?php echo htmlspecialchars($address['recipient_name']); ?>
                                </div>
                                
                                <div class="address-info">
                                    <i class="bi bi-telephone"></i>
                                    <?php echo htmlspecialchars($address['phone']); ?>
                                </div>
                                
                                <div class="address-info">
                                    <i class="bi bi-geo-alt"></i>
                                    <?php echo htmlspecialchars($address['city']); ?>
                                    <?php if (!empty($address['area'])): ?>
                                        ، <?php echo htmlspecialchars($address['area']); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="address-info">
                                    <i class="bi bi-signpost"></i>
                                    شارع <?php echo htmlspecialchars($address['street']); ?>
                                    <?php if (!empty($address['building'])): ?>
                                        ، مبنى <?php echo htmlspecialchars($address['building']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($address['apartment'])): ?>
                                        ، شقة <?php echo htmlspecialchars($address['apartment']); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($address['notes'])): ?>
                                    <div class="address-info">
                                        <i class="bi bi-info-circle"></i>
                                        <?php echo htmlspecialchars($address['notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-addresses">
                <i class="bi bi-geo-alt"></i>
                <h3>No addresses found</h3>
                <p class="text-muted">Add a new address to make checkout easier</p>
                <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addressModal">
                    <i class="bi bi-plus-circle me-2"></i> Add Address
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal for Add/Edit Address -->
    <div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addressModalLabel">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addressForm" method="POST" action="">
                        <input type="hidden" id="address_id" name="address_id" value="0">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Address Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required
                                       placeholder="Example: Home, Work, etc.">
                            </div>
                            <div class="col-md-6">
                                <label for="recipient_name" class="form-label">Recipient Name *</label>
                                <input type="text" class="form-control" id="recipient_name" name="recipient_name" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="area" class="form-label">Area/District</label>
                                <input type="text" class="form-control" id="area" name="area">
                            </div>
                            <div class="col-md-6">
                                <label for="street" class="form-label">Street *</label>
                                <input type="text" class="form-control" id="street" name="street" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="building" class="form-label">Building/Villa</label>
                                <input type="text" class="form-control" id="building" name="building">
                            </div>
                            <div class="col-md-6">
                                <label for="apartment" class="form-label">Apartment/Floor</label>
                                <input type="text" class="form-control" id="apartment" name="apartment">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Any additional details to help with delivery..."></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                            <label class="form-check-label" for="is_default">
                                Set as default address
                            </label>
                        </div>
                        
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Address</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Include required JavaScript libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // تعديل العنوان
        const editButtons = document.querySelectorAll('.edit-address');
        const addressForm = document.getElementById('addressForm');
        const modalTitle = document.getElementById('addressModalLabel');
        
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const addressData = JSON.parse(this.getAttribute('data-address'));
                
                // Fill form with data
                document.getElementById('address_id').value = addressData.id;
                document.getElementById('title').value = addressData.title;
                document.getElementById('recipient_name').value = addressData.recipient_name;
                document.getElementById('phone').value = addressData.phone;
                document.getElementById('city').value = addressData.city;
                document.getElementById('area').value = addressData.area;
                document.getElementById('street').value = addressData.street;
                document.getElementById('building').value = addressData.building;
                document.getElementById('apartment').value = addressData.apartment;
                document.getElementById('notes').value = addressData.notes;
                document.getElementById('is_default').checked = addressData.is_default == 1;
                
                // Change form title
                modalTitle.textContent = 'Edit Address';
            });
        });
        
        // Reset form when opening for adding new address
        const addressModal = document.getElementById('addressModal');
        addressModal.addEventListener('hidden.bs.modal', function() {
            addressForm.reset();
            document.getElementById('address_id').value = 0;
            modalTitle.textContent = 'Add New Address';
        });
    });
    </script>
</body>
</html>
