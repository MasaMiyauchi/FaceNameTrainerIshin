<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../services/FaceNameService.php';

try {
    $service = new FaceNameService();
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch ($action) {
        case 'generate_pairs':
            $count = isset($_GET['count']) ? intval($_GET['count']) : 5;
            $age = isset($_GET['age']) ? intval($_GET['age']) : null;
            $gender = isset($_GET['gender']) ? $_GET['gender'] : null;
            
            $options = [];
            if ($age !== null) $options['age'] = $age;
            if ($gender !== null) $options['gender'] = $gender;
            
            $result = $service->generateMultiplePairs($count, $options);
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'get_random_pairs':
            $count = isset($_GET['count']) ? intval($_GET['count']) : 5;
            
            $conditions = [];
            if (isset($_GET['age'])) $conditions['age'] = intval($_GET['age']);
            if (isset($_GET['gender'])) $conditions['gender'] = $_GET['gender'];
            
            $result = $service->getRandomFaceNamePairs($count, $conditions);
            echo json_encode(['success' => true, 'data' => $result]);
            break;
            
        case 'get_pair_by_id':
            $id = isset($_GET['id']) ? $_GET['id'] : '';
            if (empty($id)) {
                throw new Exception('IDが指定されていません');
            }
            
            $faceDatabase = new FaceDatabase();
            $result = $faceDatabase->getFaceById($id);
            
            if ($result) {
                echo json_encode(['success' => true, 'data' => $result]);
            } else {
                echo json_encode(['success' => false, 'error' => '指定されたIDの顔データが見つかりません']);
            }
            break;
            
        default:
            throw new Exception('不明なアクション: ' . $action);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
