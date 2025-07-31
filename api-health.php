<?php
// api-health.php - Simple API health check script
header('Content-Type: application/json');

// Define API endpoints to check
$endpoints = [
    'direct' => 'http://198.46.85.193:8888/api/health',
    'proxy' => '/search-proxy.php',
    'nginx' => '/proxy-api/health'
];

$results = [];

// Check each endpoint
foreach ($endpoints as $name => $url) {
    $start_time = microtime(true);
    $status = 'unknown';
    $error = null;
    $response_time = 0;
    
    try {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode('anyone:superstrongpassword99')
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        $end_time = microtime(true);
        $response_time = round(($end_time - $start_time) * 1000, 2); // in ms
        
        if (!empty($curl_error)) {
            $status = 'error';
            $error = $curl_error;
        } else if ($http_code >= 200 && $http_code < 300) {
            $status = 'ok';
        } else {
            $status = 'error';
            $error = "HTTP code: $http_code";
        }
    } catch (Exception $e) {
        $status = 'error';
        $error = $e->getMessage();
    }
    
    $results[$name] = [
        'status' => $status,
        'response_time_ms' => $response_time,
        'error' => $error
    ];
}

// Return the health check results
echo json_encode([
    'timestamp' => date('c'),
    'overall_status' => in_array('ok', array_column($results, 'status')) ? 'degraded' : 'down',
    'endpoints' => $results
]);
?>