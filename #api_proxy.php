<?php
// API Proxy for LetGodBeTrue3 Python API
// This script forwards requests to the local Python HTTP server

// Allow cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Enable error logging
error_log("[API-PROXY] Starting request processing");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Get the requested API endpoint from the URL path
$path_info = $_SERVER['PATH_INFO'] ?? '';
if (empty($path_info)) {
    $path_info = '/';
}

// IMPORTANT: Use the correct protocol and port
$target_url = 'http://localhost:8088/api' . $path_info;

// Forward query parameters
if (!empty($_SERVER['QUERY_STRING'])) {
    $target_url .= '?' . $_SERVER['QUERY_STRING'];
}

error_log("[API-PROXY] Connecting to: $target_url");

// Forward the request to the Python server
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $target_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Forward the request method
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);

// Forward request headers
$headers = [];
foreach (getallheaders() as $key => $value) {
    if (strtolower($key) !== 'host') {
        $headers[] = "$key: $value";
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Forward request body for POST/PUT requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

// Enable verbose debugging
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

// Execute the request
$response = curl_exec($ch);
$info = curl_getinfo($ch);
$error = curl_error($ch);

// Log verbose output
rewind($verbose);
$verboseLog = stream_get_contents($verbose);
error_log("[API-PROXY] cURL verbose: " . $verboseLog);

curl_close($ch);

// Handle errors
if ($error) {
    error_log("[API-PROXY] cURL error: $error");
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => $error, 'url' => $target_url]);
    exit;
}

// Forward response status code
http_response_code($info['http_code']);

// Forward content type if available
if (isset($info['content_type'])) {
    header('Content-Type: ' . $info['content_type']);
} else {
    header('Content-Type: application/json');
}

// Output the response
echo $response;
error_log("[API-PROXY] Request processed successfully");