<?php
/**
 * Prompt Check Tool
 * 
 * This script allows testing the Stability AI image generation with custom prompts.
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>画像生成チェックツール</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="/FaceNameTrainerIshin/styles/base/main.css">
    <script src="js/errorDisplay.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>画像生成チェックツール</h1>
            <div class="subtitle">プロンプトを入力して画像を生成</div>
        </header>
        
        <main>
            <?php
            $apiKey = getenv('STABILITY_API_KEY');

            if (empty($apiKey)) {
                echo "<h1>設定エラー</h1>";
                echo "<div style='color: red; font-weight: bold; margin-bottom: 20px;'>";
                echo "STABILITY_API_KEY環境変数が設定されていません。";
                echo "</div>";
                
                echo "<h2>解決方法</h2>";
                echo "<p>プロジェクトのルートディレクトリに.envファイルを作成し、以下の内容を追加してください：</p>";
                echo "<pre>STABILITY_API_KEY=あなたのAPIキー</pre>";
                
                exit(1);
            }

            $imageData = null;
            $errorMessage = null;
            $formPrompt = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt'])) {
                $formPrompt = $_POST['prompt'];
                
                try {
                    $generator = new ImageGenerator($apiKey);
                    
                    $prompt = $formPrompt;
                    $seed = isset($_POST['seed']) && !empty($_POST['seed']) ? intval($_POST['seed']) : mt_rand(0, 1000000000);
                    
                    $response = $generator->callStabilityAPI($prompt, $seed);
                    
                    if (!empty($response['image'])) {
                        $imageData = $response['image'];
                    } else {
                        $errorMessage = "画像データがAPIレスポンスに含まれていません。";
                    }
                    
                } catch (Exception $e) {
                    $errorMessage = $e->getMessage();
                }
            }
            ?>
            
            <!-- プロンプト入力フォーム -->
            <form method="post" action="" class="settings-form">
                <div class="form-group">
                    <label for="prompt">生成プロンプト</label>
                    <textarea id="prompt" name="prompt" rows="4" style="width: 100%; padding: 10px;" required><?php echo htmlspecialchars($formPrompt); ?></textarea>
                    <p class="form-help">例: "a cat wearing a hat, photorealistic"</p>
                </div>
                
                <div class="form-group">
                    <label for="seed">シード値（任意）</label>
                    <input type="number" id="seed" name="seed" min="0" max="2147483647" placeholder="ランダム" style="width: 100%; padding: 10px;">
                    <p class="form-help">同じシード値を使用すると、同じ条件で類似した画像が生成されます。</p>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="primary-btn">画像を生成</button>
                </div>
            </form>
            
            <!-- 結果表示エリア -->
            <?php if ($imageData): ?>
            <div style="margin-top: 30px; text-align: center;">
                <h2>生成結果</h2>
                <div style="margin: 20px 0;">
                    <img src="data:image/jpeg;base64,<?php echo $imageData; ?>" alt="生成された画像" style="max-width: 512px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div>
                    <h3>使用したプロンプト</h3>
                    <p style="background-color: #f5f5f5; padding: 10px; border-radius: 4px; text-align: left;"><?php echo htmlspecialchars($formPrompt); ?></p>
                </div>
            </div>
            <?php elseif ($errorMessage): ?>
            <div style="margin-top: 30px; color: red; font-weight: bold;">
                <h2>エラーが発生しました</h2>
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
                
                <?php if (strpos($errorMessage, 'API error:') !== false): ?>
                <div style="margin-top: 20px;">
                    <button id="toggle-api-response" onclick="toggleElement('api-response')" style="padding: 5px 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 3px; cursor: pointer;">[+] 詳細を表示</button>
                </div>
                
                <div id="api-response" data-toggleable style="display: none; margin-top: 10px; background-color: #f5f5f5; padding: 10px; border-radius: 5px;">
                    <h3>APIエラーの詳細</h3>
                    <p>考えられる原因:</p>
                    <ul>
                        <li>APIキーが無効または期限切れの可能性があります。</li>
                        <li>プロンプトに不適切な内容が含まれている可能性があります。</li>
                        <li>APIサーバーが過負荷またはメンテナンス中の可能性があります。</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
        
        <footer>
            <p>© 2023 顔名前トレーナー - モニタリングツール</p>
        </footer>
    </div>
</body>
</html>
