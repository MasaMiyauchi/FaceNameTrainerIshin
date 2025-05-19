<?php
/**
 * Image Generation Monitoring Class
 * 
 * Provides functions for monitoring the image generation process,
 * collecting metrics, and generating reports.
 */
class ImageMonitor {
    private $db;
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection (optional)
     */
    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            $dbPath = dirname(__DIR__, 3) . '/assets/data/monitoring.db';
            $this->initializeDatabase($dbPath);
        }
    }
    
    /**
     * Initialize the SQLite database
     * 
     * @param string $dbPath Path to the database file
     */
    private function initializeDatabase($dbPath) {
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            try {
                if (!mkdir($dbDir, 0755, true)) {
                    throw new Exception("ディレクトリの作成に失敗しました: {$dbDir}");
                }
            } catch (Exception $e) {
                $errorMessage = $e->getMessage();
                $webServerUser = $this->getWebServerUser();
                $currentUser = $this->getCurrentUser();
                
                if (strpos($errorMessage, 'Permission denied') !== false) {
                    throw new Exception(
                        "ディレクトリ作成時の権限エラー: {$dbDir}\n" .
                        "現在の実行ユーザー: {$currentUser}\n" .
                        "Webサーバーユーザー: {$webServerUser}\n" .
                        "このエラーはデータディレクトリへの書き込み権限がないために発生しています。\n" .
                        "以下のコマンドを実行して権限を修正してください:\n\n" .
                        "sudo mkdir -p {$dbDir}\n" .
                        "sudo chown {$webServerUser}:{$webServerUser} {$dbDir}\n" .
                        "sudo chmod 755 {$dbDir}"
                    );
                } else {
                    throw new Exception(
                        "ディレクトリ作成エラー: {$dbDir}\n" .
                        "エラー詳細: {$errorMessage}\n" .
                        "以下のコマンドを実行してディレクトリを手動で作成してください:\n\n" .
                        "sudo mkdir -p {$dbDir}\n" .
                        "sudo chown {$webServerUser}:{$webServerUser} {$dbDir}\n" .
                        "sudo chmod 755 {$dbDir}"
                    );
                }
            }
        }
        
        try {
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->db->exec('
                CREATE TABLE IF NOT EXISTS monitoring_data (
                    id TEXT PRIMARY KEY,
                    timestamp TEXT,
                    request_params TEXT,
                    performance TEXT,
                    quality TEXT,
                    errors TEXT,
                    created_at TEXT
                )
            ');
        } catch (PDOException $e) {
            $errorMessage = $e->getMessage();
            $webServerUser = $this->getWebServerUser();
            
            if (strpos($errorMessage, 'unable to open database file') !== false) {
                throw new Exception(
                    "データベースファイルを開けません: {$dbPath}\n" .
                    "このエラーはデータベースファイルへの書き込み権限がないために発生しています。\n" .
                    "以下のコマンドを実行して権限を修正してください:\n\n" .
                    "sudo touch {$dbPath}\n" .
                    "sudo chown {$webServerUser}:{$webServerUser} {$dbPath}\n" .
                    "sudo chmod 644 {$dbPath}"
                );
            } else {
                throw new Exception(
                    "データベース初期化エラー: {$errorMessage}\n" .
                    "SQLiteデータベースドライバが正しくインストールされているか確認してください。"
                );
            }
        }
    }
    
    /**
     * Get the current web server user
     * 
     * @return string Web server user
     */
    private function getWebServerUser() {
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $user = posix_getpwuid(posix_geteuid());
            return $user['name'] ?? 'unknown';
        }
        
        if (PHP_OS === 'Linux') {
            return 'www-data'; // Debian/Ubuntu default
        } elseif (PHP_OS === 'Darwin') {
            return '_www'; // macOS default
        } elseif (strpos(PHP_OS, 'WIN') === 0) {
            return 'IUSR'; // Windows IIS default
        }
        
        return 'www-data'; // Default fallback
    }
    
    /**
     * Get the current user running the script
     * 
     * @return string Current user
     */
    private function getCurrentUser() {
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $user = posix_getpwuid(posix_geteuid());
            return $user['name'] ?? 'unknown';
        }
        
        if (isset($_SERVER['USER'])) {
            return $_SERVER['USER'];
        } elseif (isset($_SERVER['USERNAME'])) {
            return $_SERVER['USERNAME'];
        }
        
        return 'unknown';
    }
    
    /**
     * Monitor image generation process
     * 
     * @param ImageGenerator $generator The image generator instance
     * @param array $params Parameters to pass to the generation function
     * @return array Monitoring results and generated image data
     */
    public function monitorImageGeneration($generator, $params) {
        $startTime = microtime(true);
        $result = null;
        $error = null;
        
        try {
            $result = $generator->generateFaceImage($params);
            
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // in milliseconds
            
            $monitoringData = [
                'id' => 'mon_' . time() . '_' . mt_rand(0, 999999),
                'timestamp' => date('c'),
                'requestParams' => $params,
                'performance' => [
                    'responseTime' => $responseTime,
                    'success' => true
                ],
                'quality' => $this->calculateQualityMetrics($result),
                'errors' => null
            ];
            
            $this->saveMonitoringData($monitoringData);
            
            return array_merge($result, ['monitoring' => $monitoringData]);
        } catch (Exception $err) {
            $error = $err;
            
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // in milliseconds
            
            $monitoringData = [
                'id' => 'mon_' . time() . '_' . mt_rand(0, 999999),
                'timestamp' => date('c'),
                'requestParams' => $params,
                'performance' => [
                    'responseTime' => $responseTime,
                    'success' => false
                ],
                'quality' => null,
                'errors' => [
                    'message' => $err->getMessage(),
                    'trace' => $err->getTraceAsString()
                ]
            ];
            
            $this->saveMonitoringData($monitoringData);
            
            throw $err;
        }
    }

    /**
     * Calculate quality metrics for generated image
     * 
     * @param array $result The result from image generation
     * @return array Quality metrics
     */
    private function calculateQualityMetrics($result) {
        return [
            'qualityScore' => 0.85 + (mt_rand() / mt_getrandmax() * 0.15), // Simple random score for demo
            'promptCompliance' => 0.9 + (mt_rand() / mt_getrandmax() * 0.1)
        ];
    }
    
    /**
     * Save monitoring data to database
     * 
     * @param array $monitoringData Monitoring data to save
     */
    private function saveMonitoringData($monitoringData) {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO monitoring_data 
                (id, timestamp, request_params, performance, quality, errors, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                $monitoringData['id'],
                $monitoringData['timestamp'],
                json_encode($monitoringData['requestParams']),
                json_encode($monitoringData['performance']),
                json_encode($monitoringData['quality']),
                json_encode($monitoringData['errors']),
                date('c')
            ]);
            
            error_log("Saved monitoring data: {$monitoringData['id']}");
        } catch (Exception $error) {
            error_log('Error saving monitoring data: ' . $error->getMessage());
        }
    }
    
    /**
     * Generate a batch of images with monitoring
     * 
     * @param ImageGenerator $generator The image generator instance
     * @param int $count Number of images to generate
     * @param array $options Generation options
     * @return array Batch results and statistics
     */
    public function monitorBatchGeneration($generator, $count, $options = []) {
        $results = [];
        $errors = [];
        $startTime = microtime(true);
        
        for ($i = 0; $i < $count; $i++) {
            try {
                $params = $this->generateRandomParams($options);
                
                $result = $this->monitorImageGeneration($generator, $params);
                $results[] = $result;
            } catch (Exception $error) {
                $errors[] = $error;
                error_log("Error in batch generation (" . ($i+1) . "/{$count}): " . $error->getMessage());
            }
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // in milliseconds
        $averageResponseTime = count($results) > 0 
            ? array_reduce($results, function($sum, $r) {
                return $sum + $r['monitoring']['performance']['responseTime'];
              }, 0) / count($results) 
            : 0;
        $errorRate = count($errors) / $count;
        
        return [
            'results' => $results,
            'errors' => $errors,
            'stats' => [
                'totalTime' => $totalTime,
                'averageResponseTime' => $averageResponseTime,
                'errorRate' => $errorRate,
                'successCount' => count($results),
                'errorCount' => count($errors)
            ]
        ];
    }

    /**
     * Generate random parameters based on options
     * 
     * @param array $options Options for parameter generation
     * @return array Random parameters for image generation
     */
    private function generateRandomParams($options) {
        $age = isset($options['age']) 
            ? $options['age'] 
            : $this->selectRandomByDistribution($this->getAgeDistribution());
        
        $gender = isset($options['gender']) 
            ? $options['gender'] 
            : $this->selectRandomByDistribution($this->getGenderDistribution());
        
        return ['age' => $age, 'gender' => $gender];
    }
    
    /**
     * Select a random value based on a distribution
     * 
     * @param array $distribution Distribution array with values as probabilities
     * @return string|int Randomly selected key based on distribution
     */
    private function selectRandomByDistribution($distribution) {
        $rand = mt_rand() / mt_getrandmax();
        $sum = 0;
        
        foreach ($distribution as $value => $probability) {
            $sum += $probability;
            if ($rand < $sum) {
                return $value;
            }
        }
        
        return array_keys($distribution)[0]; // Fallback to first value
    }
    
    /**
     * Get the age distribution
     * 
     * @return array Age distribution
     */
    private function getAgeDistribution() {
        return [
            '20' => 0.25,  // 25%
            '30' => 0.25,  // 25%
            '40' => 0.20,  // 20%
            '50' => 0.15,  // 15%
            '60' => 0.10,  // 10%
            '70' => 0.05   // 5%
        ];
    }
    
    /**
     * Get the gender distribution
     * 
     * @return array Gender distribution
     */
    private function getGenderDistribution() {
        return [
            'male' => 0.5,    // 50%
            'female' => 0.5   // 50%
        ];
    }
}
