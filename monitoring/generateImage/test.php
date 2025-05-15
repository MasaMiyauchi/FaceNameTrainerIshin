<?php
/**
 * Test script for image generation and monitoring
 * 
 * This script tests the image generation and monitoring functionality.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
error_log("Starting test script");

require_once __DIR__ . '/load_env.php';
require_once __DIR__ . '/php/ImageGenerator.php';
require_once __DIR__ . '/php/ImageMonitor.php';

$apiKey = getenv('STABILITY_API_KEY');
if (!$apiKey) {
    die("Error: STABILITY_API_KEY environment variable is not set\n");
}

echo "API Key found: " . substr($apiKey, 0, 5) . "...\n";

$assetsDir = dirname(__DIR__, 2) . '/assets/faces';
if (!is_dir($assetsDir)) {
    echo "Creating assets directory: $assetsDir\n";
    mkdir($assetsDir, 0777, true);
} else {
    echo "Assets directory exists: $assetsDir\n";
}
chmod($assetsDir, 0777);
echo "Directory permissions set to 777\n";

$testFile = $assetsDir . '/test_from_script.jpg';
$testData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
$writeResult = file_put_contents($testFile, $testData);
if ($writeResult === false) {
    echo "Error: Failed to write test file\n";
    error_log("Failed to write test file: $testFile");
} else {
    echo "Successfully wrote test file: $testFile ($writeResult bytes)\n";
}

try {
    echo "Initializing ImageGenerator...\n";
    $generator = new ImageGenerator($apiKey);
    
    echo "Initializing ImageMonitor...\n";
    $monitor = new ImageMonitor();
    
    // Test single image generation with monitoring
    echo "Testing single image generation...\n";
    $result = $monitor->monitorImageGeneration($generator, [
        'age' => 30,
        'gender' => 'female'
    ]);
    
    echo "Image generated successfully!\n";
    echo "Image ID: " . $result['metadata']['id'] . "\n";
    echo "Image URI: " . $result['metadata']['image_uri'] . "\n";
    echo "Response time: " . $result['monitoring']['performance']['responseTime'] . "ms\n";
    
    $imagePath = dirname(__DIR__, 2) . '/' . $result['metadata']['image_uri'];
    if (file_exists($imagePath)) {
        $fileSize = filesize($imagePath);
        echo "Image file exists: $imagePath ($fileSize bytes)\n";
    } else {
        echo "Error: Image file does not exist: $imagePath\n";
        
        echo "Attempting to save image directly...\n";
        $base64Data = $result['imageData'];
        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            echo "Error: Failed to decode base64 data\n";
        } else {
            $saveResult = file_put_contents($imagePath, $imageData);
            if ($saveResult === false) {
                echo "Error: Failed to save image directly\n";
            } else {
                echo "Successfully saved image directly: $imagePath ($saveResult bytes)\n";
            }
        }
    }
    
    echo "Test completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    error_log("Test error: " . $e->getMessage());
}
