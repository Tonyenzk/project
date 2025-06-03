<?php
/**
 * Image Cache Handler
 * 
 * This script serves images with proper cache headers to improve loading times
 * Usage: image_cache.php?src=path/to/image.jpg
 */

// Cache settings
$cache_time = 60 * 60 * 24 * 7; // 1 week in seconds
$cache_folder = 'cache/images/';

// Get the requested image path
$image_path = isset($_GET['src']) ? $_GET['src'] : '';

// Exit if no image path provided
if (empty($image_path)) {
    header("HTTP/1.0 400 Bad Request");
    exit("Error: No image specified");
}

// Sanitize image path to prevent directory traversal
$image_path = str_replace('../', '', $image_path);
$full_path = $image_path;

// Ensure the image exists
if (!file_exists($full_path)) {
    header("HTTP/1.0 404 Not Found");
    exit("Error: Image not found");
}

// Create cache directory if it doesn't exist
if (!is_dir($cache_folder)) {
    mkdir($cache_folder, 0755, true);
}

// Generate a cache filename based on the image path
$cache_key = md5($image_path);
$cache_path = $cache_folder . $cache_key;

// Get image information
$image_info = getimagesize($full_path);
$mime_type = $image_info['mime'];
$file_modified_time = filemtime($full_path);

// Set caching headers
header("Content-Type: $mime_type");
header("Cache-Control: public, max-age=$cache_time");
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $cache_time) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $file_modified_time) . " GMT");

// Check if the browser has a cached version
if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $if_modified_since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($if_modified_since >= $file_modified_time) {
        // Return 304 Not Modified if the image hasn't changed
        header("HTTP/1.0 304 Not Modified");
        exit;
    }
}

// Output the image file
readfile($full_path);
exit;
