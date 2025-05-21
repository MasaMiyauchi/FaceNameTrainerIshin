<?php
require_once __DIR__ . '/../utils/NameGenerator.php';
require_once __DIR__ . '/../models/FaceDatabase.php';
require_once __DIR__ . '/../../monitoring/generateImage/php/ImageGenerator.php';
require_once __DIR__ . '/../../monitoring/generateImage/php/ImageMonitor.php';

/**
 * 顔と名前の生成サービス
 * 
 * 顔画像生成と名前生成を統合し、トレーニングに必要なデータを提供します
 */
class FaceNameService {
    private $imageGenerator;
    private $imageMonitor;
    private $nameGenerator;
    private $faceDatabase;
    
    public function __construct() {
        $apiKey = getenv('STABILITY_API_KEY');
        if (empty($apiKey)) {
            throw new Exception('STABILITY_API_KEYが設定されていません');
        }
        
        $this->imageGenerator = new ImageGenerator($apiKey);
        $this->imageMonitor = new ImageMonitor();
        $this->nameGenerator = new NameGenerator();
        $this->faceDatabase = new FaceDatabase();
    }
    
    /**
     * 顔画像と名前のペアを生成
     * 
     * @param int|null $age 年齢（null の場合はランダム）
     * @param string|null $gender 性別（null の場合はランダム）
     * @return array 生成された顔画像と名前のデータ
     */
    public function generateFaceNamePair($age = null, $gender = null) {
        $result = $this->imageMonitor->monitorImageGeneration($this->imageGenerator, [
            'age' => $age,
            'gender' => $gender
        ]);
        
        $faceMetadata = $result['metadata'];
        
        $nameData = $this->nameGenerator->generateName($faceMetadata['age'], $faceMetadata['gender']);
        
        $faceNameData = [
            'id' => $faceMetadata['id'],
            'image_uri' => $faceMetadata['image_uri'],
            'age' => $faceMetadata['age'],
            'gender' => $faceMetadata['gender'],
            'family_name' => $nameData['family_name'],
            'given_name' => $nameData['given_name'],
            'ethnicity' => 'japanese',
            'seed' => $faceMetadata['seed'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->faceDatabase->saveFace($faceNameData);
        
        return $faceNameData;
    }
    
    /**
     * 複数の顔画像と名前のペアを生成
     * 
     * @param int $count 生成する数
     * @param array $options オプション（年齢・性別の指定など）
     * @return array 生成された顔画像と名前のデータの配列
     */
    public function generateMultiplePairs($count, $options = []) {
        $results = [];
        
        for ($i = 0; $i < $count; $i++) {
            $age = isset($options['age']) ? $options['age'] : null;
            $gender = isset($options['gender']) ? $options['gender'] : null;
            
            $results[] = $this->generateFaceNamePair($age, $gender);
        }
        
        return $results;
    }
    
    /**
     * 既存の顔画像からランダムに選択
     * 
     * @param int $count 取得する数
     * @param array $conditions 条件（年齢・性別の指定など）
     * @return array 顔画像と名前のデータの配列
     */
    public function getRandomFaceNamePairs($count, $conditions = []) {
        $faces = $this->faceDatabase->getRandomFaces($conditions, $count);
        
        if (count($faces) < $count) {
            $needToGenerate = $count - count($faces);
            $newFaces = $this->generateMultiplePairs($needToGenerate, $conditions);
            $faces = array_merge($faces, $newFaces);
        }
        
        return $faces;
    }
}
