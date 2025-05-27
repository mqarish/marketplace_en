<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// التأكد من أن المستخدم مسؤول
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// التأكد من وجود مجلد النسخ الاحتياطية
$backup_dir = '../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// تحديد الإجراء المطلوب
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// إعداد مصفوفة الاستجابة
$response = [
    'success' => false,
    'message' => 'لم يتم تحديد إجراء صالح'
];

switch ($action) {
    case 'create':
        // إنشاء نسخة احتياطية جديدة
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$timestamp}.sql";
        $backup_file = $backup_dir . $filename;
        
        // إنشاء أمر النسخ الاحتياطي
        $command = "mysqldump --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} > {$backup_file}";
        
        // تنفيذ الأمر
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            $response = [
                'success' => true,
                'message' => 'تم إنشاء النسخة الاحتياطية بنجاح',
                'filename' => $filename
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'فشل إنشاء النسخة الاحتياطية'
            ];
        }
        break;
        
    case 'restore':
        // استعادة من نسخة احتياطية
        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $temp_file = $_FILES['backup_file']['tmp_name'];
            $filename = $_FILES['backup_file']['name'];
            
            // التحقق من امتداد الملف
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
                // استعادة قاعدة البيانات
                $command = "mysql --user={$db_user} --password={$db_pass} --host={$db_host} {$db_name} < {$temp_file}";
                
                exec($command, $output, $return_var);
                
                if ($return_var === 0) {
                    $_SESSION['success'] = 'تم استعادة قاعدة البيانات بنجاح';
                    header("Location: settings.php");
                    exit;
                } else {
                    $_SESSION['error'] = 'فشل استعادة قاعدة البيانات';
                    header("Location: settings.php");
                    exit;
                }
            } else {
                $_SESSION['error'] = 'الملف المرفوع ليس ملف SQL صالح';
                header("Location: settings.php");
                exit;
            }
        } else {
            $_SESSION['error'] = 'لم يتم تحديد ملف أو حدث خطأ أثناء الرفع';
            header("Location: settings.php");
            exit;
        }
        break;
        
    case 'delete':
        // حذف نسخة احتياطية
        if (isset($_GET['file'])) {
            $filename = basename($_GET['file']);
            $file_path = $backup_dir . $filename;
            
            // التأكد من أن الملف موجود وأنه ملف SQL
            if (file_exists($file_path) && pathinfo($filename, PATHINFO_EXTENSION) === 'sql') {
                if (unlink($file_path)) {
                    $response = [
                        'success' => true,
                        'message' => 'تم حذف النسخة الاحتياطية بنجاح'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'فشل حذف النسخة الاحتياطية'
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'الملف غير موجود أو غير صالح'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'لم يتم تحديد اسم الملف'
            ];
        }
        break;
        
    default:
        $response = [
            'success' => false,
            'message' => 'إجراء غير معروف'
        ];
        break;
}

// إرسال الاستجابة كـ JSON
header('Content-Type: application/json');
echo json_encode($response);
