<?php
/**
 * 名前生成クラス
 * 
 * 年齢と性別に基づいて日本人名を生成します
 */
class NameGenerator {
    private $rootDir;
    
    public function __construct() {
        $this->rootDir = dirname(__DIR__, 2);
    }
    
    /**
     * 年齢と性別に基づいて名前を生成する
     * 
     * @param int $age 年齢 (20, 30, 40, 50, 60, 70)
     * @param string $gender 性別 ('male' or 'female')
     * @return array ['family_name' => '姓', 'given_name' => '名']
     */
    public function generateName($age, $gender) {
        $this->validateAge($age);
        $this->validateGender($gender);
        
        $familyName = $this->getRandomFamilyName();
        
        $givenName = $this->getRandomGivenName($age, $gender);
        
        return [
            'family_name' => $familyName,
            'given_name' => $givenName
        ];
    }
    
    /**
     * ランダムな姓を取得
     * 
     * @return string ランダムに選択された姓
     */
    private function getRandomFamilyName() {
        $filePath = $this->rootDir . '/assets/names/familyNames.txt';
        return $this->getRandomLineFromFile($filePath);
    }
    
    /**
     * 年齢と性別に基づいてランダムな名を取得
     * 
     * @param int $age 年齢
     * @param string $gender 性別
     * @return string ランダムに選択された名
     */
    private function getRandomGivenName($age, $gender) {
        $filePath = $this->rootDir . "/assets/names/{$age}-{$gender}-Names.txt";
        return $this->getRandomLineFromFile($filePath);
    }
    
    /**
     * ファイルからランダムな行を取得
     * 
     * @param string $filePath ファイルパス
     * @return string ファイルからランダムに選択された行
     * @throws Exception ファイルが存在しない場合
     */
    private function getRandomLineFromFile($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("名前ファイルが見つかりません: {$filePath}");
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            throw new Exception("名前ファイルが空です: {$filePath}");
        }
        
        return trim($lines[array_rand($lines)]);
    }
    
    /**
     * 年齢パラメータを検証
     * 
     * @param int $age 検証する年齢
     * @throws Exception 無効な年齢の場合
     */
    private function validateAge($age) {
        $validAges = [20, 30, 40, 50, 60, 70];
        if (!in_array($age, $validAges)) {
            throw new Exception("無効な年齢パラメータです: {$age}。有効な値: " . implode(', ', $validAges));
        }
    }
    
    /**
     * 性別パラメータを検証
     * 
     * @param string $gender 検証する性別
     * @throws Exception 無効な性別の場合
     */
    private function validateGender($gender) {
        $validGenders = ['male', 'female'];
        if (!in_array($gender, $validGenders)) {
            throw new Exception("無効な性別パラメータです: {$gender}。有効な値: " . implode(', ', $validGenders));
        }
    }
}
