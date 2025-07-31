<?php
header('Content-Type: text/plain');

// Test various locations
$test_paths = [
    __DIR__,
    __DIR__ . '/logs',
    sys_get_temp_dir(),
    '/tmp',
    dirname(__DIR__) . '/logs',
    dirname(__DIR__) . '/tmp'
];

foreach ($test_paths as $path) {
    echo "Testing path: $path\n";
    echo "Exists: " . (file_exists($path) ? 'Yes' : 'No') . "\n";
    echo "Writable: " . (is_writable($path) ? 'Yes' : 'No') . "\n";
    echo "\n";
    
    // Try to write a test file
    $test_file = $path . '/test_' . time() . '.txt';
    $result = @file_put_contents($test_file, 'test');
    echo "Write test: " . ($result !== false ? 'Success' : 'Failed') . "\n";
    if ($result !== false) {
        unlink($test_file);
        echo "Test file cleaned up\n";
    }
    echo "----------------------------------------\n";
}

// Show PHP info about error logging
echo "\nPHP Error Log Settings:\n";
echo "error_log setting: " . ini_get('error_log') . "\n";
echo "log_errors setting: " . ini_get('log_errors') . "\n";
echo "error_reporting setting: " . ini_get('error_reporting') . "\n";
echo "display_errors setting: " . ini_get('display_errors') . "\n";

// Create logs directory if it doesn't exist
$logs_dir = __DIR__ . '/logs';
if (!file_exists($logs_dir)) {
    echo "\nTrying to create logs directory...\n";
    $created = @mkdir($logs_dir, 0777, true);
    echo "Create logs directory: " . ($created ? 'Success' : 'Failed') . "\n";
    if ($created) {
        chmod($logs_dir, 0777);
        echo "Set logs directory permissions\n";
    }
}

echo "\nScript user: " . exec('whoami') . "\n";
echo "Script path: " . __FILE__ . "\n";
