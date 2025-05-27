<?php
// Read the original file
$original_file = 'store_details.php';
$content = file_get_contents($original_file);

// Create a backup
file_put_contents('store_details_backup_' . date('Y-m-d_H-i-s') . '.php', $content);

// Find the position where we need to make changes
$header_start_pos = strpos($content, "// بدء محتوى الصفحة");
$container_pos = strpos($content, '<div class="container-fluid">');
$footer_pos = strpos($content, "<?php include 'admin_footer.php'; ?>");

if ($header_start_pos !== false && $container_pos !== false && $footer_pos !== false) {
    // Extract the PHP code before the header
    $php_code = substr($content, 0, $header_start_pos + strlen("// بدء محتوى الصفحة"));
    
    // Create the new header structure
    $new_header = "// بدء محتوى الصفحة
?>

<!DOCTYPE html>
<html lang=\"ar\" dir=\"rtl\">
<head>
    <?php include 'admin_header.php'; ?>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class=\"container-fluid\">";
    
    // Extract the content between container and footer
    $body_content = substr($content, $container_pos + strlen('<div class="container-fluid">'), $footer_pos - ($container_pos + strlen('<div class="container-fluid">')));
    
    // Create the new footer structure
    $new_footer = "    <!-- Bootstrap Bundle with Popper -->
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>";
    
    // Combine everything
    $new_content = $php_code . $new_header . $body_content . $new_footer;
    
    // Write the new content back to the file
    file_put_contents($original_file, $new_content);
    
    echo "Success! The header structure in store_details.php has been fixed to match dashboard.php format.";
} else {
    echo "Error: Could not find the necessary sections in the file.";
}
?>
