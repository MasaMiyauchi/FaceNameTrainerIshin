<?php
/**
 * Image Generator Class
 * 
 * Provides functions for generating face images using Stability AI's API
 * and monitoring the generation process.
 */
class ImageGenerator {
    private $apiEndpoint = 'https://api.stability.ai/v2beta/stable-image/generate/core';
    private $imageWidth = 512;
    private $imageHeight = 512;
    
    private $ageDistribution = [
        '20' => 0.25,  // 25%
        '30' => 0.25,  // 25%
        '40' => 0.20,  // 20%
        '50' => 0.15,  // 15%
        '60' => 0.10,  // 10%
        '70' => 0.05   // 5%
    ];
    
    private $genderDistribution = [
        'male' => 0.5,    // 50%
        'female' => 0.5   // 50%
    ];
    
    private $apiKey;
    
    /**
     * Constructor
     * 
     * @param string $apiKey The Stability AI API key
     */
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?: getenv('STABILITY_API_KEY');
        
        if (!$this->apiKey) {
            throw new Exception('STABILITY_API_KEY is not set in environment variables');
        }
    }
    
    /**
     * Generate a face image using Stability AI API
     * 
     * @param array $params Generation parameters
     * @return array Generated image data and metadata
     */
    public function generateFaceImage($params) {
        $startTime = microtime(true);
        
        try {
            $age = $this->validateAge($params['age'] ?? null);
            $gender = $this->validateGender($params['gender'] ?? null);
            $seed = $params['seed'] ?? mt_rand(0, 1000000000);
            
            $prompt = "{$age}-year-old {$gender} japanese wearing a suit, photorealistic";
            
            $response = $this->callStabilityAPI($prompt, $seed);
            
            $imageData = $this->processAPIResponse($response, $age, $gender, $seed);
            
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // in milliseconds
            
            return array_merge($imageData, [
                'performance' => [
                    'responseTime' => $responseTime,
                    'timestamp' => date('c')
                ]
            ]);
        } catch (Exception $error) {
            error_log('Error generating face image: ' . $error->getMessage());
            throw new Exception('Face image generation failed: ' . $error->getMessage());
        }
    }

    /**
     * Call the Stability AI API to generate an image
     * 
     * @param string $prompt Text prompt for image generation
     * @param int $seed Random seed for reproducible generations
     * @return array API response data
     */
    private function callStabilityAPI($prompt, $seed) {
        $attempts = 0;
        $maxAttempts = 3;
        $lastError = null;
        
        while ($attempts < $maxAttempts) {
            try {
                $boundary = uniqid();
                $delimiter = '-------------' . $boundary;
                
                $postFields = [];
                
                // Add text parameters
                $postFields[] = [
                    'name' => 'width',
                    'content' => $this->imageWidth
                ];
                
                $postFields[] = [
                    'name' => 'height',
                    'content' => $this->imageHeight
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
                
                $ch = curl_init($this->apiEndpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: multipart/form-data; boundary=' . $delimiter,
                    'Accept: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ]);
                
                $response = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                error_log("API Response Status: " . $statusCode);
                error_log("API Response Body: " . $response);
                
                if ($statusCode !== 200) {
                    $errorData = json_decode($response, true) ?: [];
                    throw new Exception("API error: {$statusCode} - " . json_encode($errorData));
                }
                
                $responseData = json_decode($response, true);
                error_log("Decoded Response: " . json_encode($responseData));
                
                return $responseData;
            } catch (Exception $error) {
                $lastError = $error;
                $attempts++;
                
                if ($attempts >= $maxAttempts) {
                    break;
                }
                
                $delay = pow(2, $attempts - 1) * 1000000; // in microseconds
                usleep($delay);
            }
        }
        
        throw new Exception("Failed after {$maxAttempts} attempts: " . $lastError->getMessage());
    }

    /**
     * Process the API response and extract image data
     * 
     * @param array $response API response data
     * @param int $age Age parameter used for generation
     * @param string $gender Gender parameter used for generation
     * @param int $seed Seed used for generation
     * @return array Processed image data and metadata
     */
    private function processAPIResponse($response, $age, $gender, $seed) {
        error_log("Processing API response: " . json_encode($response));
        
        $base64Data = null;
        $finishReason = 'SUCCESS';
        
        if (!empty($response['base64'])) {
            $base64Data = $response['base64'];
            $finishReason = $response['finish_reason'] ?? 'SUCCESS';
            error_log("Found base64 data in direct response");
        } 
        else if (!empty($response['artifacts']) && count($response['artifacts']) > 0) {
            $artifact = $response['artifacts'][0];
            if (isset($artifact['base64'])) {
                $base64Data = $artifact['base64'];
                $finishReason = $artifact['finishReason'] ?? $artifact['finish_reason'] ?? 'SUCCESS';
                error_log("Found base64 data in artifacts array");
            }
        }
        
        if (!$base64Data) {
            $base64Data = $this->findBase64($response);
            if ($base64Data) {
                error_log("Found base64 data using recursive search");
            }
        }
        
        if (!$base64Data) {
            $base64Data = $this->findLongString($response);
            if ($base64Data) {
                error_log("Found potential base64 data as long string");
            }
        }
        
        if (!$base64Data) {
            throw new Exception('No image data in API response: ' . json_encode($response));
        }
        
        $randomId = str_pad(mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
        $filename = "{$age}-{$gender}-{$randomId}-face.jpeg";
        
        $imagePath = $this->saveImageToFileSystem($base64Data, $filename);
        
        $metadata = [
            'id' => 'img_' . time() . '_' . $randomId,
            'filename' => $filename,
            'image_uri' => $imagePath,
            'age' => $age,
            'gender' => $gender,
            'ethnicity' => 'japanese',
            'seed' => $seed,
            'finishReason' => $finishReason,
            'created_at' => date('c')
        ];
        
        return [
            'imageData' => $base64Data,
            'metadata' => $metadata
        ];
    }
    
    /**
     * Save image to file system
     * 
     * @param string $base64Data Base64 encoded image data
     * @param string $filename Filename to save as
     * @return string Path to saved image
     */
    private function saveImageToFileSystem($base64Data, $filename) {
        $directory = dirname(__DIR__, 3) . '/assets/faces';
        
        if (!is_dir($directory)) {
            error_log("Creating directory: " . $directory);
            mkdir($directory, 0777, true);
            chmod($directory, 0777);
        }
        
        if (strpos($base64Data, ',') !== false) {
            $base64Data = explode(',', $base64Data)[1];
        }
        
        $imageData = base64_decode($base64Data, true);
        error_log("Base64 decode result: " . ($imageData === false ? 'Failed' : 'Success'));
        error_log("Base64 data length: " . strlen($base64Data));
        error_log("Decoded data length: " . ($imageData === false ? 0 : strlen($imageData)));
        
        $filePath = $directory . '/' . $filename;
        error_log("Saving image to: " . $filePath);
        
        if ($imageData !== false) {
            $result = file_put_contents($filePath, $imageData);
            error_log("Method 1 result: " . ($result === false ? 'Failed' : 'Success (' . $result . ' bytes)'));
            
            if ($result === false) {
                $fp = fopen($filePath, 'wb');
                if ($fp) {
                    $result = fwrite($fp, $imageData);
                    fclose($fp);
                    error_log("Method 2 result: " . ($result === false ? 'Failed' : 'Success (' . $result . ' bytes)'));
                }
            }
        } else {
            $debugPath = $directory . '/debug_' . $filename . '.txt';
            file_put_contents($debugPath, $base64Data);
            error_log("Saved raw base64 data to: " . $debugPath);
            
            $result = file_put_contents($filePath, $base64Data);
            error_log("Raw data save result: " . ($result === false ? 'Failed' : 'Success (' . $result . ' bytes)'));
        }
        
        error_log("File exists: " . (file_exists($filePath) ? 'Yes' : 'No'));
        if (file_exists($filePath)) {
            error_log("File size: " . filesize($filePath) . " bytes");
            chmod($filePath, 0777);
        }
        
        return 'assets/faces/' . $filename;
    }
    
    /**
     * Recursively search for base64 data in an array
     * 
     * @param array $array Array to search in
     * @param string $key Key to search for
     * @return string|null Base64 data if found, null otherwise
     */
    private function findBase64($array, $key = 'base64') {
        foreach ($array as $k => $v) {
            if ($k === $key && is_string($v) && strlen($v) > 100) {
                return $v;
            } else if (is_array($v)) {
                $result = $this->findBase64($v, $key);
                if ($result) {
                    return $result;
                }
            }
        }
        return null;
    }
    
    /**
     * Find any long string that might be base64 encoded
     * 
     * @param array $array Array to search in
     * @return string|null Base64 data if found, null otherwise
     */
    private function findLongString($array) {
        foreach ($array as $k => $v) {
            if (is_string($v) && strlen($v) > 1000 && preg_match('/^[a-zA-Z0-9\/\+\=]+$/', $v)) {
                return $v;
            } else if (is_array($v)) {
                $result = $this->findLongString($v);
                if ($result) {
                    return $result;
                }
            }
        }
        return null;
    }
    
    /**
     * Validate age parameter
     * 
     * @param mixed $age Age to validate
     * @return int Validated age
     */
    private function validateAge($age) {
        $validAges = array_keys($this->ageDistribution);
        $parsedAge = (int)$age;
        
        if (!in_array((string)$parsedAge, $validAges)) {
            throw new Exception("Invalid age: {$age}. Must be one of: " . implode(', ', $validAges));
        }
        
        return $parsedAge;
    }
    
    /**
     * Validate gender parameter
     * 
     * @param string $gender Gender to validate
     * @return string Validated gender
     */
    private function validateGender($gender) {
        $validGenders = array_keys($this->genderDistribution);
        
        if (!in_array($gender, $validGenders)) {
            throw new Exception("Invalid gender: {$gender}. Must be one of: " . implode(', ', $validGenders));
        }
        
        return $gender;
    }
}
