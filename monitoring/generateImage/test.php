<?php
/**
 * Test script for image generation and monitoring
 * 
 * This script tests the image generation and monitoring functionality.
 */

require_once __DIR__ . '/php/ImageGenerator.php';
require_once __DIR__ . '/php/ImageMonitor.php';

$apiKey = getenv('STABILITY_API_KEY');

try {
    $generator = new ImageGenerator($apiKey);
    
    $monitor = new ImageMonitor();
    
    echo "Testing single image generation...\n";
    $result = $monitor->monitorImageGeneration($generator, [
        'age' => 30,
        'gender' => 'female'
    ]);
    
    echo "Image generated successfully!\n";
    echo "Image ID: " . $result['metadata']['id'] . "\n";
    echo "Response time: " . $result['monitoring']['performance']['responseTime'] . "ms\n";
    
    echo "Test completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
