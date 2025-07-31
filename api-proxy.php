<?php
// Start session for rate limiting
session_start();

// Write to a custom log file
$log_file = __DIR__ . '/proxy-debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Script started\n", FILE_APPEND);

// Always return JSON, even for errors
header('Content-Type: application/json');

// CORS headers to avoid issues
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Get the requested path and extract endpoint
    $request_uri = $_SERVER['REQUEST_URI'];
    $path_parts = explode('/', trim($request_uri, '/'));
    
    // Check if the request is to /proxy-api/ or similar
    $api_index = array_search('proxy-api', $path_parts);
    
    if ($api_index !== false && isset($path_parts[$api_index + 1])) {
        // Extract endpoint from the URL path
        $endpoint = $path_parts[$api_index + 1];
    } else {
        // Fallback to query parameter or default to health
        $endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : 'health';
    }
    
    // Add this right after getting the endpoint
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - DEBUG: Full Request URI: {$_SERVER['REQUEST_URI']}\n", FILE_APPEND);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - DEBUG: Path parts: " . json_encode($path_parts) . "\n", FILE_APPEND);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - DEBUG: API index: {$api_index}\n", FILE_APPEND);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - DEBUG: Processing endpoint: {$endpoint}\n", FILE_APPEND);
    
    // Special handling for chat endpoint - implement request deduplication
    if ($endpoint === 'chat') {
        // Extract information from the request to identify the conversation
        $post_data = file_get_contents('php://input');
        $request_data = json_decode($post_data, true);
        
        if ($request_data) {
            $conversation_id = $request_data['conversation_id'] ?? null;
            $is_first_message = $request_data['first_message'] ?? false;
            $client_info = $request_data['client'] ?? [];
            $request_id = $client_info['request_id'] ?? null;
            $attempt = $client_info['attempt'] ?? 1;
            
            // Generate a unique request hash for deduplication
            $request_hash = md5($post_data);
            
            // Log the request details
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Chat request: hash={$request_hash}, conversation_id=" . 
                ($conversation_id ?? 'null') . ", first_message=" . ($is_first_message ? 'true' : 'false') . 
                ", request_id=" . ($request_id ?? 'null') . ", attempt={$attempt}\n", FILE_APPEND);
            
            // Check for duplicate requests using the session
            $cache_key = "request_cache_{$request_hash}";
            if (isset($_SESSION[$cache_key]) && $attempt > 1) {
                // We've seen this exact request before, return the cached response
                $cached_response = $_SESSION[$cache_key];
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Using cached response for hash: {$request_hash}\n", FILE_APPEND);
                
                echo $cached_response;
                exit;
            }
        }
        
        // Rate limit based on conversation or IP if needed
        $rate_limit_key = 'api_conv_' . ($conversation_id ?? $_SERVER['REMOTE_ADDR']);
        $last_request_time = 'api_last_' . ($conversation_id ?? $_SERVER['REMOTE_ADDR']);
        
        // Determine minimum interval based on message type
        // First messages take longer to process due to RAG pipeline
        if ($is_first_message) {
            $min_interval = 2; // Allow first messages to start right away
        } else {
            $min_interval = 1; // Almost no delay for subsequent messages
        }
        
        // Check when the last request was made
        $current_time = time();
        $last_time = $_SESSION[$last_request_time] ?? 0;
        $time_since_last = $current_time - $last_time;
        
        // Log the timing information
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Time since last request: {$time_since_last}s " .
            "(required: {$min_interval}s)\n", FILE_APPEND);
        
        // If the request is coming too quickly, enforce rate limit
        if ($time_since_last < $min_interval) {
            // Only rate limit first attempt, not retries
            if ($attempt <= 1) {
                $wait_time = $min_interval - $time_since_last;
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Rate limiting enforced: " . 
                    "Need to wait {$wait_time} more seconds\n", FILE_APPEND);
                
                http_response_code(429);
                $response = json_encode([
                    'success' => false,
                    'message' => 'Rate limit exceeded',
                    'error' => "Please wait {$wait_time} seconds before trying again",
                    'retry_after' => $wait_time
                ]);
                echo $response;
                exit;
            } else {
                // For retries, log but don't enforce rate limit
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Rate limit bypassed for retry attempt {$attempt}\n", FILE_APPEND);
            }
        }
        
        // Update last request time
        $_SESSION[$last_request_time] = $current_time;
    }
    
    // Special handling for vote endpoint
    else if ($endpoint === 'vote') {
        // Set proper headers
        header('Content-Type: application/json');
        
        // Extract data from request
        $post_data = file_get_contents('php://input');
        $vote_data = json_decode($post_data, true);
        
        // Log vote data for debugging
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Vote data received: " . json_encode($vote_data) . "\n", FILE_APPEND);
        
        // Process vote data 
        $conversation_id = $vote_data['conversation_id'] ?? '';
        $server_conversation_id = $vote_data['server_conversation_id'] ?? '';
        $vote_value = $vote_data['vote'] ?? '';
        $is_final = $vote_data['is_final'] ?? false;
        $timestamp = $vote_data['timestamp'] ?? date('Y-m-d H:i:s');
        
        // Get query_id directly from the request
        $query_id = $vote_data['query_id'] ?? null;
        
        // If we don't have a query_id, attempt to infer it
        if (empty($query_id)) {
            // Check if we have server_conversation_id, which might be more reliable
            if (!empty($server_conversation_id)) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Attempting to use server_conversation_id for query_id mapping\n", FILE_APPEND);
                
                // Try to extract a query_id format from server_conversation_id
                if (strpos($server_conversation_id, 'query_') !== false) {
                    $parts = explode('query_', $server_conversation_id);
                    if (count($parts) > 1) {
                        $query_id = 'query_' . $parts[1];
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Extracted query_id from server_conversation_id: {$query_id}\n", FILE_APPEND);
                    }
                }
            }
            
            // If still no query_id, try with conversation_id
            if (empty($query_id) && !empty($conversation_id)) {
                // Extract potential query_id from conversation_id if it follows a pattern
                if (strpos($conversation_id, 'query_') !== false) {
                    $parts = explode('query_', $conversation_id);
                    if (count($parts) > 1) {
                        $query_id = 'query_' . $parts[1];
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Extracted query_id from conversation_id: {$query_id}\n", FILE_APPEND);
                    }
                }
                
                // If conversation_id starts with conv_, convert to query_id format
                if (empty($query_id) && strpos($conversation_id, 'conv_') === 0) {
                    $query_id = 'query_' . substr($conversation_id, 5);
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Created query_id from conv_ format: {$query_id}\n", FILE_APPEND);
                }
                
                // If still no query_id, use conversation_id as query_id
                if (empty($query_id)) {
                    $query_id = $conversation_id;
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Using conversation_id as query_id: {$query_id}\n", FILE_APPEND);
                }
            }
        } else {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Using provided query_id: {$query_id}\n", FILE_APPEND);
        }
        
        // Save locally to file system
        $votes_dir = __DIR__ . '/data/votes';
        if (!is_dir($votes_dir)) {
            mkdir($votes_dir, 0755, true);
        }
        
        // Use conversation_id for the filename to avoid conflicts with multiple votes for same conversation
        $vote_file = $votes_dir . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $conversation_id) . '.json';
        
        // Create enhanced vote data with query_id included
        $enhanced_vote_data = [
            'conversation_id' => $conversation_id,  // Client-side ID
            'server_conversation_id' => $server_conversation_id, // Server-side ID
            'query_id' => $query_id,
            'vote' => $vote_value,
            'timestamp' => $timestamp,
            'is_final' => $is_final,
            'client_ip' => $_SERVER['REMOTE_ADDR']
        ];
        
        // Save the vote data
        file_put_contents($vote_file, json_encode($enhanced_vote_data));
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Vote saved to file: {$vote_file}\n", FILE_APPEND);
        
        // Return simple success response
        echo json_encode([
            'success' => true,
            'message' => 'Vote recorded successfully',
            'data' => [
                'conversation_id' => $conversation_id,
                'server_conversation_id' => $server_conversation_id,
                'query_id' => $query_id,
                'vote' => $vote_value,
                'is_final' => $is_final
            ]
        ]);
        
        // Exit to prevent further processing
        exit;
    }
    
    // Define API URL with correct port
    $api_url = "http://198.46.85.193:8888/api/" . $endpoint;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - API URL: $api_url\n", FILE_APPEND);

    // Initialize cURL with updated options
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 60,  // Increased timeout to 60 seconds
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode('anyone:superstrongpassword99')
        ]
    ]);
    
    // If this is a POST request, pass along the data
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $post_data = isset($post_data) ? $post_data : file_get_contents('php://input');
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - POST data length: " . strlen($post_data) . "\n", FILE_APPEND);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    
    // Execute request
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Executing cURL request\n", FILE_APPEND);
    $response = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - cURL error: $error\n", FILE_APPEND);
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Proxy error: cURL failed',
            'error' => $error
        ]);
        curl_close($ch);
        exit;
    }
    
    // Get HTTP status code
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Response status: $status\n", FILE_APPEND);
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Response length: " . strlen($response) . "\n", FILE_APPEND);
    
    // Close cURL
    curl_close($ch);
    
    // Check if response is valid JSON
    json_decode($response);
    $is_json = (json_last_error() == JSON_ERROR_NONE);
    
    // If the response is not valid JSON, convert it to a JSON error
    if (!$is_json) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Warning: Backend returned non-JSON response\n", FILE_APPEND);
        
        // Create a valid JSON response
        $json_response = json_encode([
            'success' => false,
            'message' => 'Backend returned invalid response',
            'error' => 'The backend server returned a non-JSON response. Please try again later.',
            'status_code' => $status
        ]);
        
        // If this is a chat request and we have a request hash, cache this error response too
        if ($endpoint === 'chat' && isset($request_hash)) {
            $_SESSION["request_cache_{$request_hash}"] = $json_response;
            $_SESSION["request_cache_{$request_hash}_time"] = time();
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Cached error response for hash: {$request_hash}\n", FILE_APPEND);
        }
        
        // Return the JSON error
        http_response_code(500);
        echo $json_response;
        exit;
    }
    
    // For chat endpoint, cache the response
    if ($endpoint === 'chat' && isset($request_hash)) {
        // Store in session for deduplication
        $_SESSION["request_cache_{$request_hash}"] = $response;
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Cached response for hash: {$request_hash}\n", FILE_APPEND);
        
        // Limit cache size by cleaning up old entries (keep only 10 most recent)
        $cache_keys = array_filter(array_keys($_SESSION), function($key) {
            return strpos($key, 'request_cache_') === 0;
        });
        
        if (count($cache_keys) > 10) {
            // Sort by time (newest first)
            usort($cache_keys, function($a, $b) {
                $time_a = $_SESSION[$a . '_time'] ?? 0;
                $time_b = $_SESSION[$b . '_time'] ?? 0;
                return $time_b - $time_a;
            });
            
            // Remove oldest entries
            foreach (array_slice($cache_keys, 10) as $old_key) {
                unset($_SESSION[$old_key]);
                unset($_SESSION[$old_key . '_time']);
            }
        }
        
        // Store timestamp
        $_SESSION["request_cache_{$request_hash}_time"] = time();
    }
    
    // Set the same status code
    http_response_code($status);
    
    // Output the response
    echo $response;
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Script completed successfully\n", FILE_APPEND);
    
} catch (Exception $e) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Exception: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Always return JSON for errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Proxy error',
        'error' => $e->getMessage()
    ]);
}