<?php
require_once '../includes/init.php';
require_once 'check_admin.php';

// التأكد من أن المستخدم مسؤول
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح لك بالوصول'
    ]);
    exit;
}

// تحميل الإعدادات الحالية
$settings = [];
$query = "SELECT * FROM settings";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// استرجاع إعدادات البريد الإلكتروني
$smtp_enabled = $settings['smtp_enabled'] ?? '0';
$smtp_host = $settings['smtp_host'] ?? '';
$smtp_port = $settings['smtp_port'] ?? '587';
$smtp_username = $settings['smtp_username'] ?? '';
$smtp_password = $settings['smtp_password'] ?? '';
$smtp_encryption = $settings['smtp_encryption'] ?? 'tls';
$site_email = $settings['site_email'] ?? 'info@marketplace.com';
$site_name = $settings['site_name'] ?? 'السوق الإلكتروني';

// التحقق من وجود إعدادات SMTP
if ($smtp_enabled == '1' && !empty($smtp_host) && !empty($smtp_port) && !empty($smtp_username)) {
    // استخدام PHPMailer لإرسال البريد الإلكتروني
    // تحقق من وجود مكتبة PHPMailer
    if (!file_exists('../vendor/autoload.php')) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'مكتبة PHPMailer غير موجودة. يرجى تثبيتها باستخدام Composer.'
        ]);
        exit;
    }
    
    require_once '../vendor/autoload.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    $mail = new PHPMailer(true);
    
    try {
        // إعدادات الخادم
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        
        if (!empty($smtp_encryption)) {
            if ($smtp_encryption == 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($smtp_encryption == 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
        }
        
        $mail->Port = $smtp_port;
        $mail->CharSet = 'UTF-8';
        
        // المرسل والمستقبل
        $mail->setFrom($site_email, $site_name);
        $mail->addAddress($smtp_username); // إرسال إلى نفس البريد المستخدم في SMTP
        
        // محتوى البريد الإلكتروني
        $mail->isHTML(true);
        $mail->Subject = 'اختبار إعدادات البريد الإلكتروني';
        $mail->Body = '
            <div dir="rtl" style="text-align: right; font-family: Arial, sans-serif;">
                <h2>اختبار إعدادات البريد الإلكتروني</h2>
                <p>هذا بريد إلكتروني تجريبي للتأكد من صحة إعدادات SMTP.</p>
                <p>إذا وصلك هذا البريد، فهذا يعني أن إعدادات البريد الإلكتروني تعمل بشكل صحيح.</p>
                <hr>
                <p>تم إرساله من: ' . $site_name . '</p>
                <p>تاريخ الإرسال: ' . date('Y-m-d H:i:s') . '</p>
            </div>
        ';
        
        $mail->send();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'تم إرسال البريد الإلكتروني التجريبي بنجاح'
        ]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'فشل إرسال البريد الإلكتروني: ' . $mail->ErrorInfo
        ]);
    }
    
} else {
    // إعدادات SMTP غير مكتملة
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'إعدادات SMTP غير مكتملة أو غير مفعلة'
    ]);
}
