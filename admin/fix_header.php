<?php
// Read the original file
$file_path = 'store_details.php';
$content = file_get_contents($file_path);

// Find the position where we need to make changes
$start_pos = strpos($content, "// بدء محتوى الصفحة");
$end_pos = strpos($content, "<div class=\"container-fluid\">");

if ($start_pos !== false && $end_pos !== false) {
    // Replace the header section with proper HTML structure
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
    
    // Create the new content
    $new_content = substr($content, 0, $start_pos) . $new_header . substr($content, $end_pos + strlen("<div class=\"container-fluid\">"));
    
    // Replace the footer section
    $footer_pos = strpos($new_content, "<?php include 'admin_footer.php'; ?>");
    if ($footer_pos !== false) {
        $new_footer = "<!-- Bootstrap Bundle with Popper -->
<script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>";
        $new_content = substr($new_content, 0, $footer_pos) . $new_footer;
    }
    
    // Write the new content back to the file
    file_put_contents($file_path, $new_content);
    echo "File updated successfully!";
} else {
    echo "Could not find the sections to update in the file.";
}
?>
