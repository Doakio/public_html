<?php
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Test endpoint working',
    'server' => $_SERVER,
    'request' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'query' => $_GET,
    ]
]);
