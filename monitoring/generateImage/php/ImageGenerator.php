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
        $requestData = [
            'width' => $this->imageWidth,
            'height' => $this->imageHeight,
            'seed' => $seed,
            'cfg_scale' => 7.5,
            'samples' => 1,
            'text_prompts' => [
                [
                    'text' => $prompt,
                    'weight' => 1.0
                ]
            ]
        ];
        
        $attempts = 0;
        $maxAttempts = 3;
        $lastError = null;
        
        while ($attempts < $maxAttempts) {
            try {
                $ch = curl_init($this->apiEndpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $this->apiKey
                ]);
                
                $response = curl_exec($ch);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($statusCode !== 200) {
                    $errorData = json_decode($response, true) ?: [];
                    throw new Exception("API error: {$statusCode} - " . json_encode($errorData));
                }
                
                return json_decode($response, true);
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
        if (empty($response['artifacts']) || count($response['artifacts']) === 0) {
            throw new Exception('No image data in API response');
        }
        
        $artifact = $response['artifacts'][0];
        $base64Data = $artifact['base64'];
        $finishReason = $artifact['finishReason'];
        
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
        $imageData = base64_decode($base64Data);
        $directory = dirname(__DIR__, 3) . '/assets/faces';
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $filePath = $directory . '/' . $filename;
        file_put_contents($filePath, $imageData);
        
        return 'assets/faces/' . $filename;
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
