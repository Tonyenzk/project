<?php
// Set JSON content type immediately - this is crucial for error handling
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Turn off error display to prevent HTML errors from mixing with JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Ensure all errors are logged instead
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

// Check if config file exists before requiring it
if (!file_exists('../config/config.php')) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Configuration not found',
        'message' => 'The configuration file does not exist'
    ]);
    exit;
}

// Include only the config file - removing functions.php dependency
try {
    require_once '../config/config.php';
    // We don't need functions.php for this API endpoint
} catch (Throwable $e) {
    // Log the error and return a clean JSON response
    error_log('API include error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error', 'message' => 'Failed to load configuration']);
    exit;
}

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Check if search query is provided
if (!isset($_GET['q']) || empty($_GET['q'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Search query is required']);
    exit;
}

$searchQuery = $_GET['q'];

// Get API key - with better error handling
if (!defined('YOUTUBE_API_KEY')) {
    // The API key constant isn't defined - provide a more descriptive error
    http_response_code(500);
    echo json_encode([
        'error' => 'API configuration missing',
        'message' => 'YouTube API key constant is not defined in config.php'
    ]);
    exit;
}

$apiKey = YOUTUBE_API_KEY;

// Validate API key
if (empty($apiKey) || $apiKey === 'YOUR_YOUTUBE_API_KEY') {
    http_response_code(500);
    echo json_encode([
        'error' => 'Invalid API key',
        'message' => 'YouTube API key is not properly configured in config.php'
    ]);
    exit;
}

// For debugging - log the search query
error_log('Searching YouTube for: ' . $searchQuery . ' with API key: ' . substr($apiKey, 0, 5) . '...');

// YouTube API endpoint
$url = "https://www.googleapis.com/youtube/v3/search?" . http_build_query([
    'part' => 'snippet',
    'q' => $searchQuery,
    'type' => 'video',
    'videoCategoryId' => '10', // Music category
    'maxResults' => 10,
    'key' => $apiKey
]);

// Create a simple mock response for testing if we're in development mode
// This helps when the YouTube API is not accessible or the key is invalid
// For production, you should remove this mock data and use the actual API
$useMockData = false; // Using mock data to bypass YouTube API issues

if ($useMockData) {
    // Use mock data instead of making a real API call
    $mockData = [
        'kind' => 'youtube#searchListResponse',
        'etag' => 'mock_etag',
        'nextPageToken' => 'mock_next_page',
        'regionCode' => 'US',
        'pageInfo' => [
            'totalResults' => 3,
            'resultsPerPage' => 3
        ],
        'items' => [
            [
                'kind' => 'youtube#searchResult',
                'etag' => 'mock_etag_1',
                'id' => [
                    'kind' => 'youtube#video',
                    'videoId' => 'dQw4w9WgXcQ'
                ],
                'snippet' => [
                    'publishedAt' => '2009-10-25T06:57:33Z',
                    'channelId' => 'UCuAXFkgsw1L7xaCfnd5JJOw',
                    'title' => 'Rick Astley - Never Gonna Give You Up',
                    'description' => 'Official music video for Rick Astley - Never Gonna Give You Up',
                    'thumbnails' => [
                        'default' => [
                            'url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/default.jpg',
                            'width' => 120,
                            'height' => 90
                        ]
                    ],
                    'channelTitle' => 'Rick Astley',
                    'liveBroadcastContent' => 'none',
                    'publishTime' => '2009-10-25T06:57:33Z'
                ]
            ],
            [
                'kind' => 'youtube#searchResult',
                'etag' => 'mock_etag_2',
                'id' => [
                    'kind' => 'youtube#video',
                    'videoId' => 'qHBVnMf2t7w'
                ],
                'snippet' => [
                    'publishedAt' => '2018-05-02T15:39:17Z',
                    'channelId' => 'UCuqfA7xF0nO-ZpOTBKYtHHw',
                    'title' => 'Test Song - Music Test',
                    'description' => 'This is a mock test song description',
                    'thumbnails' => [
                        'default' => [
                            'url' => 'https://i.ytimg.com/vi/qHBVnMf2t7w/default.jpg',
                            'width' => 120,
                            'height' => 90
                        ]
                    ],
                    'channelTitle' => 'Test Artist',
                    'liveBroadcastContent' => 'none',
                    'publishTime' => '2018-05-02T15:39:17Z'
                ]
            ],
            [
                'kind' => 'youtube#searchResult',
                'etag' => 'mock_etag_3',
                'id' => [
                    'kind' => 'youtube#video',
                    'videoId' => 'xyz12345678'
                ],
                'snippet' => [
                    'publishedAt' => '2021-01-15T10:00:00Z',
                    'channelId' => 'UCmock123456',
                    'title' => $searchQuery . ' - Sample Music',
                    'description' => 'A sample result based on your search query: ' . $searchQuery,
                    'thumbnails' => [
                        'default' => [
                            'url' => 'https://picsum.photos/120/90',
                            'width' => 120,
                            'height' => 90
                        ]
                    ],
                    'channelTitle' => 'Sample Artist',
                    'liveBroadcastContent' => 'none',
                    'publishTime' => '2021-01-15T10:00:00Z'
                ]
            ]
        ]
    ];
    
    echo json_encode($mockData);
    exit;
}

try {
    // Initialize cURL session
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development only
    curl_setopt($ch, CURLOPT_HEADER, false);
    // Add a user agent
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.99 Safari/537.36');
    // Add timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Execute cURL request
    $response = curl_exec($ch);
    
    // Get HTTP status code
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        throw new Exception('cURL Error: ' . curl_error($ch));
    }
    
    // Close cURL session
    curl_close($ch);
    
    // Check if response is empty
    if (empty($response)) {
        throw new Exception('Empty response from YouTube API');
    }
    
    // Check for non-200 HTTP status
    if ($httpCode !== 200) {
        error_log('YouTube API HTTP error: ' . $httpCode . ' - Response: ' . $response);
        throw new Exception('YouTube API returned HTTP ' . $httpCode);
    }
    
    // Try to decode JSON response
    $data = json_decode($response, true);
    
    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('JSON decode error: ' . json_last_error_msg() . ' - Response: ' . $response);
        throw new Exception('Failed to parse YouTube API response: ' . json_last_error_msg());
    }
    
    // Check for API errors
    if (isset($data['error'])) {
        $errorMessage = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown API error';
        throw new Exception('YouTube API Error: ' . $errorMessage);
    }
    
    // Return successful response
    echo json_encode($data);
    
} catch (Throwable $e) {
    // Log the error
    error_log('Music search error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to search music',
        'message' => $e->getMessage(),
        'debug' => true
    ]);
}