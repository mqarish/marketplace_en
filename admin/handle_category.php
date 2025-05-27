<?php
/**
 * معالج التصنيفات - يتعامل مع إضافة وتعديل وحذف وعرض التصنيفات
 */

// تمكين الإبلاغ عن الأخطاء للتصحيح
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/init.php';
require_once 'check_admin.php';

// تأكد من أن الطلب من نوع AJAX
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// التحقق من وجود إجراء
if (!isset($_REQUEST['action'])) {
    echo json_encode(['success' => false, 'message' => 'لم يتم تحديد الإجراء']);
    exit;
}

$action = $_REQUEST['action'];

// معالجة الإجراءات المختلفة
switch ($action) {
    case 'add':
        // إضافة تصنيف جديد
        addCategory();
        break;
    case 'update':
        // تحديث تصنيف موجود
        updateCategory();
        break;
    case 'delete':
        // حذف تصنيف
        deleteCategory();
        break;
    case 'get':
        // الحصول على بيانات تصنيف
        getCategory();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'إجراء غير صالح']);
        exit;
}

/**
 * إضافة تصنيف جديد
 */
function addCategory() {
    global $conn;
    
    // للتصحيح والتحقق
    error_log("Attempting to add category with data: " . print_r($_POST, true));
    
    // التحقق من البيانات المطلوبة
    if (!isset($_POST['name']) || empty($_POST['name'])) {
        error_log("Category name is missing");
        echo json_encode(['success' => false, 'message' => 'اسم التصنيف مطلوب']);
        exit;
    }
    
    $name = trim($_POST['name']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $icon = isset($_POST['icon']) ? trim($_POST['icon']) : '';
    $image_url = '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'active';
    
    // معالجة رفع الصورة
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
        $upload_dir = '../uploads/categories/';
        
        // إنشاء المجلد إذا لم يكن موجودًا
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_info = pathinfo($_FILES['category_image']['name']);
        $file_extension = strtolower($file_info['extension']);
        
        // التحقق من نوع الملف
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_extension, $allowed_extensions)) {
            // إنشاء اسم فريد للملف
            $new_file_name = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($_FILES['category_image']['tmp_name'], $upload_path)) {
                $image_url = $new_file_name;
            } else {
                error_log("Failed to move uploaded file");
            }
        } else {
            error_log("Invalid file extension: " . $file_extension);
        }
    }
    
    try {
        // التحقق من عدم وجود تصنيف بنفس الاسم
        $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        if (!$check_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $check_stmt->bind_param("s", $name);
        if (!$check_stmt->execute()) {
            throw new Exception("Execute failed: " . $check_stmt->error);
        }
        
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'يوجد تصنيف بنفس الاسم بالفعل']);
            exit;
        }
        
        // إضافة التصنيف الجديد
        $stmt = $conn->prepare("INSERT INTO categories (name, description, icon, image_url) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssss", $name, $description, $icon, $image_url);
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            error_log("Category added successfully with ID: " . $new_id);
            echo json_encode(['success' => true, 'message' => 'تم إضافة التصنيف بنجاح', 'id' => $new_id]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        error_log("Error adding category: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إضافة التصنيف: ' . $e->getMessage()]);
    }
}

/**
 * تحديث تصنيف موجود
 */
function updateCategory() {
    global $conn;
    
    // للتصحيح والتحقق
    error_log("Attempting to update category with data: " . print_r($_POST, true));
    
    // التحقق من البيانات المطلوبة
    if (!isset($_POST['id']) || empty($_POST['id']) || !isset($_POST['name']) || empty($_POST['name'])) {
        error_log("Category ID or name is missing");
        echo json_encode(['success' => false, 'message' => 'المعرف واسم التصنيف مطلوبان']);
        exit;
    }
    
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $icon = isset($_POST['icon']) ? trim($_POST['icon']) : '';
    
    // الحصول على الصورة الحالية للتصنيف
    $current_image_query = $conn->prepare("SELECT image_url FROM categories WHERE id = ?");
    $current_image_query->bind_param("i", $id);
    $current_image_query->execute();
    $current_image_result = $current_image_query->get_result();
    $current_image_data = $current_image_result->fetch_assoc();
    $current_image = $current_image_data ? $current_image_data['image_url'] : '';
    
    // معالجة رفع الصورة الجديدة
    $image_url = $current_image; // الاحتفاظ بالصورة الحالية إذا لم يتم تحميل صورة جديدة
    
    if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] == 0) {
        $upload_dir = '../uploads/categories/';
        
        // إنشاء المجلد إذا لم يكن موجودًا
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_info = pathinfo($_FILES['category_image']['name']);
        $file_extension = strtolower($file_info['extension']);
        
        // التحقق من نوع الملف
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_extension, $allowed_extensions)) {
            // إنشاء اسم فريد للملف
            $new_file_name = 'category_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($_FILES['category_image']['tmp_name'], $upload_path)) {
                // حذف الصورة القديمة إذا كانت موجودة
                if (!empty($current_image) && file_exists($upload_dir . $current_image)) {
                    unlink($upload_dir . $current_image);
                }
                
                $image_url = $new_file_name;
            } else {
                error_log("Failed to move uploaded file");
            }
        } else {
            error_log("Invalid file extension: " . $file_extension);
        }
    }
    
    try {
        // التحقق من عدم وجود تصنيف آخر بنفس الاسم
        $check_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        if (!$check_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $check_stmt->bind_param("si", $name, $id);
        if (!$check_stmt->execute()) {
            throw new Exception("Execute failed: " . $check_stmt->error);
        }
        
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'يوجد تصنيف آخر بنفس الاسم']);
            exit;
        }
        
        // التحقق من وجود التصنيف المراد تحديثه
        $check_exist_stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
        if (!$check_exist_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $check_exist_stmt->bind_param("i", $id);
        if (!$check_exist_stmt->execute()) {
            throw new Exception("Execute failed: " . $check_exist_stmt->error);
        }
        
        $check_exist_result = $check_exist_stmt->get_result();
        
        if ($check_exist_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'التصنيف غير موجود']);
            exit;
        }
        
        // تحديث التصنيف
        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, icon = ?, image_url = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssssi", $name, $description, $icon, $image_url, $id);
        
        if ($stmt->execute()) {
            error_log("Category updated successfully with ID: " . $id);
            echo json_encode(['success' => true, 'message' => 'تم تحديث التصنيف بنجاح']);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        error_log("Error updating category: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء تحديث التصنيف: ' . $e->getMessage()]);
    }
}

/**
 * حذف تصنيف
 */
function deleteCategory() {
    global $conn;
    
    // التحقق من وجود معرف التصنيف
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'معرف التصنيف مطلوب']);
        exit;
    }
    
    $id = (int)$_POST['id'];
    
    // التحقق من عدم وجود منتجات مرتبطة بهذا التصنيف
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $row = $check_result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'لا يمكن حذف هذا التصنيف لأنه مرتبط بـ ' . $row['count'] . ' منتج. قم بتغيير تصنيف هذه المنتجات أولاً.'
        ]);
        exit;
    }
    
    // حذف التصنيف
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم حذف التصنيف بنجاح']);
    } else {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء حذف التصنيف: ' . $stmt->error]);
    }
}

/**
 * الحصول على بيانات تصنيف
 */
function getCategory() {
    global $conn;
    
    // التحقق من وجود معرف التصنيف
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'معرف التصنيف مطلوب']);
        exit;
    }
    
    $id = (int)$_GET['id'];
    
    try {
        // الحصول على بيانات التصنيف
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $category = $result->fetch_assoc();
                // إضافة مسار كامل للصورة إذا كانت موجودة
                if (!empty($category['image_url'])) {
                    $category['image_full_path'] = '../uploads/categories/' . $category['image_url'];
                }
                echo json_encode(['success' => true, 'category' => $category]);
            } else {
                echo json_encode(['success' => false, 'message' => 'لم يتم العثور على التصنيف']);
            }
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
    } catch (Exception $e) {
        error_log("Error getting category: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء الحصول على التصنيف: ' . $e->getMessage()]);
    }
}
