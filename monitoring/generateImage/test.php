<?php
/**
 * Test script for image generation and monitoring
 * 
 * This script tests the image generation and monitoring functionality.
 */

function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
    
    return true;
}

$rootDir = dirname(__DIR__, 2);
$envFile = $rootDir . '/.env';
loadEnv($envFile);

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
