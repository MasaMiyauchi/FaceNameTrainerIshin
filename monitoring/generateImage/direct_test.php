<?php
/**
 * Direct test script for image generation and saving
 * 
 * This script directly tests the API call and image saving functionality.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/load_env.php';

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

$apiEndpoint = 'https://api.stability.ai/v2beta/stable-image/generate/core';
$imageWidth = 512;
$imageHeight = 512;
$seed = mt_rand(0, 1000000000);
$prompt = "30-year-old female japanese wearing a suit, photorealistic";

$boundary = uniqid();
$delimiter = '-------------' . $boundary;

$postFields = [];

$postFields[] = [
    'name' => 'width',
    'content' => $imageWidth
];

$postFields[] = [
    'name' => 'height',
    'content' => $imageHeight
];

$postFields[] = [
    'name' => 'seed',
    'content' => $seed
];

$postFields[] = [
    'name' => 'cfg_scale',
    'content' => 7.5
];

$postFields[] = [
    'name' => 'samples',
    'content' => 1
];

$postFields[] = [
    'name' => 'prompt',
    'content' => $prompt
];

$postFields[] = [
    'name' => 'text_prompts[0][text]',
    'content' => $prompt
];

$postFields[] = [
    'name' => 'text_prompts[0][weight]',
    'content' => 1.0
];

$body = '';
foreach ($postFields as $field) {
    $body .= "--" . $delimiter . "\r\n";
    $body .= 'Content-Disposition: form-data; name="' . $field['name'] . '"';
    $body .= "\r\n\r\n" . $field['content'] . "\r\n";
}
$body .= "--" . $delimiter . "--\r\n";

echo "Making API request...\n";
$ch = curl_init($apiEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: multipart/form-data; boundary=' . $delimiter,
    'Accept: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "API Response Status: " . $statusCode . "\n";

if ($statusCode !== 200) {
    echo "API Error: " . $response . "\n";
    die("API request failed with status code: $statusCode\n");
}

$responseData = json_decode($response, true);
if (!$responseData) {
    echo "Failed to decode JSON response\n";
    echo "Raw response: " . substr($response, 0, 100) . "...\n";
    die("JSON decode error\n");
}

echo "Response structure: " . print_r(array_keys($responseData), true) . "\n";

$base64Data = null;

echo "Full response structure:\n";
print_r($responseData);

if (isset($responseData['artifacts']) && is_array($responseData['artifacts']) && count($responseData['artifacts']) > 0) {
    $artifact = $responseData['artifacts'][0];
    if (isset($artifact['base64'])) {
        $base64Data = $artifact['base64'];
        echo "Found base64 data in artifacts array\n";
    }
} 

if (!$base64Data && isset($responseData['base64'])) {
    $base64Data = $responseData['base64'];
    echo "Found base64 data in direct response\n";
}

if (!$base64Data) {
    function findBase64($array, $key = 'base64') {
        foreach ($array as $k => $v) {
            if ($k === $key && is_string($v) && strlen($v) > 100) {
                return $v;
            } else if (is_array($v)) {
                $result = findBase64($v, $key);
                if ($result) {
                    return $result;
                }
            }
        }
        return null;
    }
    
    $base64Data = findBase64($responseData);
    if ($base64Data) {
        echo "Found base64 data using recursive search\n";
    }
}

if (!$base64Data) {
    function findLongString($array) {
        foreach ($array as $k => $v) {
            if (is_string($v) && strlen($v) > 1000 && preg_match('/^[a-zA-Z0-9\/\+\=]+$/', $v)) {
                return $v;
            } else if (is_array($v)) {
                $result = findLongString($v);
                if ($result) {
                    return $result;
                }
            }
        }
        return null;
    }
    
    $base64Data = findLongString($responseData);
    if ($base64Data) {
        echo "Found potential base64 data as long string\n";
    }
}

if (!$base64Data) {
    echo "No image data found in response\n";
    echo "Response keys: " . implode(', ', array_keys($responseData)) . "\n";
    die("No image data in API response\n");
}

$filename = "direct_test_" . time() . ".jpg";
$filePath = $assetsDir . '/' . $filename;

echo "Saving image to: $filePath\n";
$imageData = base64_decode($base64Data);
if ($imageData === false) {
    echo "Failed to decode base64 data\n";
    echo "Base64 data length: " . strlen($base64Data) . "\n";
    echo "Base64 data sample: " . substr($base64Data, 0, 50) . "...\n";
    die("Base64 decode failed\n");
}

echo "Trying file_put_contents...\n";
$result = file_put_contents($filePath, $imageData);
if ($result === false) {
    echo "file_put_contents failed\n";
    
    echo "Trying fopen/fwrite...\n";
    $fp = fopen($filePath, 'wb');
    if ($fp) {
        $result = fwrite($fp, $imageData);
        fclose($fp);
        echo "fwrite result: " . ($result === false ? "Failed" : "Success ($result bytes)") . "\n";
    } else {
        echo "fopen failed\n";
    }
} else {
    echo "file_put_contents result: Success ($result bytes)\n";
}

if (file_exists($filePath)) {
    echo "File exists: Yes\n";
    echo "File size: " . filesize($filePath) . " bytes\n";
    chmod($filePath, 0777);
    echo "Image saved successfully to: $filePath\n";
} else {
    echo "File exists: No\n";
    echo "Failed to save image\n";
}

echo "Test completed!\n";
