<?php
/**
 * 顔画像データベースクラス
 * 
 * 生成された顔画像のメタデータを管理します
 */
class FaceDatabase {
    private $db;
    private $rootDir;
    
    public function __construct() {
        $this->rootDir = dirname(__DIR__, 2);
        $this->initDatabase();
    }
    
    /**
     * データベースの初期化
     */
    private function initDatabase() {
        $dbPath = $this->rootDir . '/assets/data/faces.db';
        $isNewDb = !file_exists($dbPath);
        
        try {
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            if ($isNewDb) {
                $this->createTables();
            }
        } catch (PDOException $e) {
            throw new Exception('データベース接続エラー: ' . $e->getMessage());
        }
    }
    
    /**
     * 必要なテーブルを作成
     */
    private function createTables() {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS faces (
                id TEXT PRIMARY KEY,
                image_uri TEXT NOT NULL,
                age INTEGER NOT NULL,
                gender TEXT NOT NULL,
                family_name TEXT NOT NULL,
                given_name TEXT NOT NULL,
                ethnicity TEXT NOT NULL,
                seed BIGINT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');
    }
    
    /**
     * 顔画像メタデータを保存
     * 
     * @param array $faceData 顔画像データ
     * @return string 保存されたレコードのID
     */
    public function saveFace($faceData) {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO faces (id, image_uri, age, gender, family_name, given_name, ethnicity, seed, created_at)
                VALUES (:id, :image_uri, :age, :gender, :family_name, :given_name, :ethnicity, :seed, :created_at)
            ');
            
            $stmt->execute([
                ':id' => $faceData['id'],
                ':image_uri' => $faceData['image_uri'],
                ':age' => $faceData['age'],
                ':gender' => $faceData['gender'],
                ':family_name' => $faceData['family_name'],
                ':given_name' => $faceData['given_name'],
                ':ethnicity' => $faceData['ethnicity'] ?? 'japanese',
                ':seed' => $faceData['seed'] ?? null,
                ':created_at' => $faceData['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            
            return $faceData['id'];
        } catch (PDOException $e) {
            throw new Exception('顔画像データの保存に失敗しました: ' . $e->getMessage());
        }
    }
    
    /**
     * IDから顔画像データを取得
     * 
     * @param string $id 顔画像ID
     * @return array|null 顔画像データ
     */
    public function getFaceById($id) {
        try {
            $stmt = $this->db->prepare('SELECT * FROM faces WHERE id = :id');
            $stmt->execute([':id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result : null;
        } catch (PDOException $e) {
            throw new Exception('顔画像データの取得に失敗しました: ' . $e->getMessage());
        }
    }
    
    /**
     * 条件に合う顔画像データをランダムに取得
     * 
     * @param array $conditions 検索条件
     * @param int $limit 取得する最大数
     * @return array 顔画像データの配列
     */
    public function getRandomFaces($conditions = [], $limit = 5) {
        try {
            $query = 'SELECT * FROM faces';
            $params = [];
            
            if (!empty($conditions)) {
                $whereClause = [];
                
                if (isset($conditions['age'])) {
                    $whereClause[] = 'age = :age';
                    $params[':age'] = $conditions['age'];
                }
                
                if (isset($conditions['gender'])) {
                    $whereClause[] = 'gender = :gender';
                    $params[':gender'] = $conditions['gender'];
                }
                
                if (!empty($whereClause)) {
                    $query .= ' WHERE ' . implode(' AND ', $whereClause);
                }
            }
            
            $query .= ' ORDER BY RANDOM() LIMIT :limit';
            $params[':limit'] = $limit;
            
            $stmt = $this->db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('顔画像データの取得に失敗しました: ' . $e->getMessage());
        }
    }
}
