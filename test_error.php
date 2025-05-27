<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PHP Version: " . PHP_VERSION . "\n";
echo "Error reporting level: " . error_reporting() . "\n";
echo "Display errors: " . ini_get('display_errors') . "\n";
?>
