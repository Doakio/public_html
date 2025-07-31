<?php
// search-proxy.php - Enhanced proxy for search API requests with better error handling

// Prevent direct access to this file
if (!defined('ABSPATH') && !isset($_SERVER['HTTP_HOST'])) {
    exit('Direct access not allowed.');
}

// Enable error logging
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Enhanced CORS headers for better browser compatibility
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');  // 24 hours cache

// Create log file with timestamp
$log_file = __DIR__ . '/wp-content/uploads/search-proxy.log';
$log_enabled = true;

// Custom logging function
function log_debug($message) {
    global $log_file, $log_enabled;
    if ($log_enabled) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    }
}

// Function to convert ScoredPoint objects to serializable format
function sanitize_result_data($data) {
    // If the data is already an array, return it
    if (is_array($data)) {
        return $data;
    }
    
    // If the data is a JSON string, decode it
    if (is_string($data)) {
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    
    // Extract data from ScoredPoint-like objects
    if (is_object($data) && isset($data->payload) && isset($data->score)) {
        $result = [
            'score' => (float)$data->score
        ];
        
        // Add payload fields
        if (is_object($data->payload) || is_array($data->payload)) {
            $payload = (array)$data->payload;
            foreach ($payload as $key => $value) {
                $result[$key] = $value;
            }
        }
        
        // Add id if available
        if (isset($data->id)) {
            $result['id'] = $data->id;
        }
        
        return $result;
    }
    
    // Return object as array if possible
    if (is_object($data)) {
        return (array)$data;
    }
    
    // Return as is if we can't convert
    return $data;
}

// Function to fix ScoredPoint serialization issues
function fix_scored_point_serialization($response) {
    // Check if we have the ScoredPoint error
    if (strpos($response, 'ScoredPoint is not JSON serializable') !== false) {
        log_debug("Detected ScoredPoint serialization error, attempting to fix");
        
        // Attempt to extract relevant data
        if (preg_match('/"success":\s*true/', $response)) {
            // Create a generic response with empty results
            $fixed_response = [
                'success' => true,
                'message' => 'Search results processed by proxy (ScoredPoint serialization issue detected)',
                'results' => [],
                'proxy_fixed' => true
            ];
            
            log_debug("Created proxy-fixed response structure");
            return json_encode($fixed_response);
        }
    }
    
    // If not a ScoredPoint error or we can't fix it, return original
    return $response;
}

// Start a new log entry
log_debug("Search proxy started - " . $_SERVER['REQUEST_METHOD'] . " from " . $_SERVER['REMOTE_ADDR']);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    log_debug("Handling OPTIONS preflight request");
    http_response_code(200);
    exit;
}

// Only allow POST for search
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_debug("Rejected " . $_SERVER['REQUEST_METHOD'] . " request");
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed',
        'error' => 'Only POST method is allowed for this endpoint'
    ]);
    exit;
}

// Get request body
try {
    $request_body = file_get_contents('php://input');
    log_debug("Request body received: " . substr($request_body, 0, 200));
} catch (Exception $e) {
    log_debug("Error reading request body: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Request error',
        'error' => 'Could not read request body'
    ]);
    exit;
}

// Validate JSON
try {
    $data = json_decode($request_body, true);
    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception(json_last_error_msg());
    }
} catch (Exception $e) {
    log_debug("Invalid JSON in request: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request format',
        'error' => 'Invalid JSON: ' . $e->getMessage()
    ]);
    exit;
}

// Check for query parameter
if (!isset($data['query']) || empty($data['query'])) {
    log_debug("Missing query parameter");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing parameter',
        'error' => 'Query parameter is required'
    ]);
    exit;
}

// Log the search query
log_debug("Processing search query: " . $data['query']);

// Check if this is a direct mode request
$use_direct_mode = isset($data['use_direct']) && $data['use_direct'] === true;
if ($use_direct_mode) {
    log_debug("Using direct mode as requested by client");
}

// Set up API URL - use the direct API URL always since the proxy URL doesn't work
$api_url = 'http://198.46.85.193:8888/api/search';
log_debug("Using API URL: " . $api_url);

// Prepare request body - modify to add webpage_search flag
if (isset($data['use_direct'])) {
    unset($data['use_direct']);
}

// Add webpage search flag
$data['webpage_search'] = true;

// Check if this is from the top search box
$from_top_search = isset($data['from_top_search']) && $data['from_top_search'] === true;

// Handle categories based on source
if ($from_top_search) {
    log_debug("Search is from top search box");
    
    // If no categories are selected or empty array, use default Website and Proverbs
    if (!isset($data['categories']) || empty($data['categories'])) {
        $data['categories'] = ['Website', 'Proverbs'];
        log_debug("Setting default categories for top search: Website, Proverbs");
    }
    
    // Remove the flag so it doesn't get sent to the API
    unset($data['from_top_search']);
} 
// Handle optional category filters normally
else if (isset($data['categories']) && is_array($data['categories']) && count($data['categories']) > 0) {
    $data['categories'] = array_map('trim', $data['categories']); // Clean up inputs
    log_debug("Category filters applied: " . implode(', ', $data['categories']));
} else {
    // No categories selected and not from top search - use empty array
    $data['categories'] = []; 
    log_debug("No category filters applied");
}

// Add request ID for tracking
$request_id = uniqid('req_');
$data['request_id'] = $request_id;
log_debug("Assigned request ID: " . $request_id);

// Add special parameter to indicate we need sanitized results
$data['serialize_results'] = true;
log_debug("Added serialize_results flag to request");

// Re-encode the request body
$request_body = json_encode($data);
log_debug("Modified request body for API: " . $request_body);

// Make sure cURL is available
if (!function_exists('curl_init')) {
    log_debug("cURL is not available on this server");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server configuration error',
        'error' => 'cURL is required but not available'
    ]);
    exit;
}

// Add retry functionality
$max_retries = 2;
$retry_count = 0;
$success = false;

while (!$success && $retry_count <= $max_retries) {
    if ($retry_count > 0) {
        log_debug("Retry attempt #" . $retry_count . " for request " . $request_id);
    }
    
    try {
        // Forward to the actual API
        $ch = curl_init($api_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $request_body,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode('anyone:superstrongpassword99'),
                'X-Request-ID: ' . $request_id
            ],
            CURLOPT_TIMEOUT => 120,         // 2 minutes (120 seconds)
            CURLOPT_CONNECTTIMEOUT => 15,   // 15 seconds for connection
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'LetGodBeTrue-SearchProxy/1.0'
        ]);

        // Execute the request
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);

        log_debug("API response status: " . $status_code . " for request " . $request_id);
        
        if (!empty($curl_error)) {
            log_debug("cURL error: " . $curl_error);
        }

        // Check for cURL errors
        if ($curl_errno) {
            throw new Exception($curl_error ?: 'Unknown cURL error: ' . $curl_errno);
        }

        // Better error handling for API responses with status codes
        if ($status_code >= 400) {
            log_debug("API error response with status: " . $status_code);
            
            // Check for ScoredPoint error
            if (strpos($response, 'ScoredPoint is not JSON serializable') !== false) {
                log_debug("Detected ScoredPoint serialization error");
                
                // Create a generic response with empty results
                $response = json_encode([
                    'success' => false,
                    'error' => 'Object of type ScoredPoint is not JSON serializable',
                    'message' => 'API error - known serialization issue',
                    'status_code' => $status_code,
                    'request_id' => $request_id
                ]);
                
                // Return immediately to client
                http_response_code(500);
                echo $response;
                log_debug("Returned ScoredPoint error response to client");
                break;
            }
            
            // Try to extract more meaningful error information
            $error_data = json_decode($response, true);
            if ($error_data && isset($error_data['error'])) {
                $error_message = $error_data['error'];
            } else {
                $error_message = "API returned error with status code " . $status_code;
            }
            
            // If not the last retry, try again
            if ($retry_count < $max_retries) {
                $retry_count++;
                log_debug("Will retry after API error: " . $error_message);
                
                // Add exponential backoff
                $backoff = pow(2, $retry_count) * 500; // 500ms, 1s, 2s
                log_debug("Waiting " . ($backoff/1000) . " seconds before retry");
                usleep($backoff * 1000);
                continue;
            }
            
            // If it's the last retry, return a standardized error response
            http_response_code(500);  // Return 500 to the client regardless of API status
            echo json_encode([
                'success' => false,
                'error' => $error_message,
                'message' => 'API error',
                'status_code' => $status_code,
                'request_id' => $request_id
            ]);
            break;
        }

        // If we have a response, check if it's valid
        if ($response) {
            log_debug("API response length: " . strlen($response));
            log_debug("API response preview: " . substr($response, 0, 200) . "...");
            
            // Apply fix for ScoredPoint serialization if needed
            $fixed_response = fix_scored_point_serialization($response);
            if ($fixed_response !== $response) {
                log_debug("Applied ScoredPoint serialization fix");
                $response = $fixed_response;
            }
            
            // Verify the response is valid JSON
            json_decode($response);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_debug("API returned non-JSON response: " . json_last_error_msg());
                
                // Try to extract error message if it's HTML
                $error_message = "Unknown error";
                if (preg_match('/<title>(.*?)<\/title>/i', $response, $matches)) {
                    $error_message = $matches[1];
                } elseif (preg_match('/<h1>(.*?)<\/h1>/i', $response, $matches)) {
                    $error_message = $matches[1];
                }
                
                // Check for ScoredPoint serialization error
                if (strpos($response, 'ScoredPoint is not JSON serializable') !== false) {
                    log_debug("Detected ScoredPoint serialization error in non-JSON response");
                    
                    // Create a generic response with empty results
                    $response = json_encode([
                        'success' => false,
                        'error' => 'Object of type ScoredPoint is not JSON serializable',
                        'message' => 'API error - known serialization issue',
                        'status_code' => $status_code,
                        'request_id' => $request_id
                    ]);
                    
                    // Return to client
                    http_response_code(500);
                    echo $response;
                    log_debug("Returned ScoredPoint error response to client");
                    break;
                }
                
                // If not valid JSON and we're not on the last retry, try again
                if ($retry_count < $max_retries) {
                    $retry_count++;
                    log_debug("Invalid JSON response, will retry. Error: " . $error_message);
                    
                    // Add exponential backoff
                    $backoff = pow(2, $retry_count) * 500; // 500ms, 1s, 2s
                    log_debug("Waiting " . ($backoff/1000) . " seconds before retry");
                    usleep($backoff * 1000);
                    continue;
                }
                
                // If it's the last retry, return an error
                $response = json_encode([
                    'success' => false,
                    'error' => 'API returned invalid JSON response', 
                    'message' => $error_message,
                    'raw_data' => substr($response, 0, 500),
                    'request_id' => $request_id
                ]);
            } else {
                // Valid JSON response, mark as success
                log_debug("Valid JSON response received for request " . $request_id);
                $success = true;
                
                // Check if we need to enhance the response with additional metadata
                $json_response = json_decode($response, true);
                if (is_array($json_response)) {
                    // If it's an array of results, wrap it with metadata
                    if (!isset($json_response['success']) && !isset($json_response['error'])) {
                        $enhanced_response = [
                            'success' => true,
                            'message' => 'Search completed successfully',
                            'results' => $json_response,
                            'count' => count($json_response),
                            'request_id' => $request_id
                        ];
                        $response = json_encode($enhanced_response);
                        log_debug("Enhanced response with metadata for request " . $request_id);
                    }
                }
            }
            
            http_response_code($status_code);
            echo $response;
            break; // Exit the retry loop
        } else {
            // No response but no error either, should retry
            if ($retry_count < $max_retries) {
                $retry_count++;
                log_debug("Empty response from API, will retry");
                
                // Add exponential backoff
                $backoff = pow(2, $retry_count) * 500; // 500ms, 1s, 2s
                log_debug("Waiting " . ($backoff/1000) . " seconds before retry");
                usleep($backoff * 1000);
                continue;
            }
            
            throw new Exception("Empty response from API");
        }
    } catch (Exception $e) {
        log_debug("Exception during API request: " . $e->getMessage());
        
        // If not on the last retry, try again
        if ($retry_count < $max_retries) {
            $retry_count++;
            log_debug("Will retry after exception");
            
            // Add exponential backoff
            $backoff = pow(2, $retry_count) * 500; // 500ms, 1s, 2s
            log_debug("Waiting " . ($backoff/1000) . " seconds before retry");
            usleep($backoff * 1000);
            continue;
        }
        
        // If it's the last retry, return an error
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'API request failed',
            'error' => $e->getMessage(),
            'request_id' => $request_id
        ]);
        break; // Exit the retry loop
    }
}

// Log completion with metrics
$retry_info = $retry_count > 0 ? " after {$retry_count} retries" : "";
$status_info = $success ? "successfully" : "with errors";
log_debug("Search proxy completed {$status_info} {$retry_info} for request {$request_id}");