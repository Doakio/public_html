<?php
// Super simple test
echo "PHP is working!<br>";
$result = file_get_contents("http://localhost:8088/api/health");
echo "API Response: " . $result;
?>
