<?php
/**
 * Image Helper Functions
 * Provides functions for optimal image loading with browser caching
 */

/**
 * Get a cacheable image URL
 * 
 * @param string $image_url The original image URL
 * @return string The URL with proper cache handling
 */
function get_cached_image_url($image_url) {
    // Skip for external URLs or non-existent files
    if (empty($image_url) || strpos($image_url, 'http://') === 0 || strpos($image_url, 'https://') === 0) {
        return $image_url;
    }
    
    // Use placeholder for empty images
    if (empty($image_url)) {
        return 'images/profile_placeholder.webp';
    }
    
    // Check if file exists
    $image_path = $image_url;
    if (!file_exists($image_path)) {
        return $image_url; // Return original if file doesn't exist
    }
    
    // Use file's last modified time as version parameter for cache busting only when file changes
    $file_modified = filemtime($image_path);
    
    // Return URL with cache parameter based on file's last modified time
    return "image_cache.php?src=" . urlencode($image_url) . "&v=" . $file_modified;
}
