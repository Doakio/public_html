<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log something
error_log("Basic PHP script executed");

// Try to test localhost connection
$connection = @fsockopen("127.0.0.1", 8088);
if ($connection) {
    error_log("Connection to localhost:8088 successful");
    fclose($connection);
} else {
    error_log("Connection to localhost:8088 failed: " . error_get_last()['message']);
}

echo "PHP test completed. Check error logs.";
?>
