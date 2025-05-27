<?php
// Define the file path
$file_path = 'store_details.php';

// Read the original file content
$content = file_get_contents($file_path);

// Define the new header structure
$old_header = "// بدء محتوى الصفحة
include 'admin_header.php';
include 'admin_navbar.php';
?>

<div class=\"container-fluid\">";

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

// Define the new footer structure
$old_footer = "<?php include 'admin_footer.php'; ?>";
$new_footer = "    <!-- Bootstrap Bundle with Popper -->
    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\"></script>
</body>
</html>";

// Replace the header and footer
$content = str_replace($old_header, $new_header, $content);
$content = str_replace($old_footer, $new_footer, $content);

// Write the modified content back to the file
file_put_contents($file_path, $content);

echo "File structure has been fixed successfully!";
?>
