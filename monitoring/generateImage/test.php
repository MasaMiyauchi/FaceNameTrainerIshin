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

$requiredExtensions = [
    'pdo' => '必須のPDO拡張モジュールがインストールされていません。',
    'pdo_sqlite' => 'SQLiteデータベースドライバがインストールされていません。',
    'sqlite3' => 'SQLite3拡張モジュールがインストールされていません。'
];

$missingExtensions = [];
foreach ($requiredExtensions as $ext => $message) {
    if (!extension_loaded($ext)) {
        $missingExtensions[$ext] = $message;
    }
}

if (!empty($missingExtensions)) {
    echo "<h1>モニタリングツール - 依存関係エラー</h1>";
    echo "<div style='color: red; font-weight: bold; margin-bottom: 20px;'>";
    echo "以下の必要な依存関係が見つかりません：";
    echo "</div>";
    
    echo "<ul>";
    foreach ($missingExtensions as $ext => $message) {
        echo "<li><strong>{$ext}</strong>: {$message}</li>";
    }
    echo "</ul>";
    
    echo "<h2>インストール方法</h2>";
    echo "<h3>Ubuntu/Debian系:</h3>";
    echo "<pre>sudo apt-get update\nsudo apt-get install php-sqlite3 php-pdo</pre>";
    
    echo "<h3>CentOS/RHEL系:</h3>";
    echo "<pre>sudo yum install php-pdo php-sqlite</pre>";
    
    echo "<h3>macOS (Homebrew):</h3>";
    echo "<pre>brew install php\nbrew install sqlite3</pre>";
    
    echo "<h3>Windows:</h3>";
    echo "<p>php.iniファイルで以下の行のコメントを解除してください：</p>";
    echo "<pre>;extension=pdo_sqlite\n;extension=sqlite3</pre>";
    echo "<p>コメントを解除後：</p>";
    echo "<pre>extension=pdo_sqlite\nextension=sqlite3</pre>";
    
    echo "<h2>詳細情報</h2>";
    echo "<p>このモニタリングツールはSQLiteデータベースを使用してモニタリングデータを保存します。</p>";
    echo "<p>必要な拡張モジュールをインストールした後、Webサーバーを再起動してください。</p>";
    
    exit(1);
}

if (empty($apiKey)) {
    echo "<h1>モニタリングツール - 設定エラー</h1>";
    echo "<div style='color: red; font-weight: bold; margin-bottom: 20px;'>";
    echo "STABILITY_API_KEY環境変数が設定されていません。";
    echo "</div>";
    
    echo "<h2>解決方法</h2>";
    echo "<p>プロジェクトのルートディレクトリに.envファイルを作成し、以下の内容を追加してください：</p>";
    echo "<pre>STABILITY_API_KEY=あなたのAPIキー</pre>";
    
    exit(1);
}

// メイン処理
try {
    $generator = new ImageGenerator($apiKey);
    $monitor = new ImageMonitor();
    
    echo "<h1>モニタリングツール - テスト実行中</h1>";
    echo "<div style='color: green; font-weight: bold; margin-bottom: 20px;'>";
    echo "単一画像生成をテストしています...";
    echo "</div>";
    
    $result = $monitor->monitorImageGeneration($generator, [
        'age' => 30,
        'gender' => 'female'
    ]);
    
    echo "<h2>テスト成功！</h2>";
    echo "<ul>";
    echo "<li>画像ID: " . htmlspecialchars($result['metadata']['id']) . "</li>";
    echo "<li>応答時間: " . htmlspecialchars($result['monitoring']['performance']['responseTime']) . "ms</li>";
    echo "</ul>";
    
    echo "<p>すべてのテストが正常に完了しました。</p>";
    
} catch (Exception $e) {
    echo "<h1>モニタリングツール - エラー発生</h1>";
    echo "<div style='color: red; font-weight: bold; margin-bottom: 20px;'>";
    echo "エラー: " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    $errorMsg = $e->getMessage();
    
    // ディレクトリ権限エラーの処理
    if (strpos($errorMsg, 'ディレクトリ作成時の権限エラー') !== false || 
        (strpos($errorMsg, 'Permission denied') !== false && strpos($errorMsg, 'mkdir') !== false)) {
        
        echo "<h2>ディレクトリ権限エラーの詳細</h2>";
        echo "<pre>" . htmlspecialchars($errorMsg) . "</pre>";
        
        echo "<h2>エラーの原因</h2>";
        echo "<p>このエラーはWebサーバーがデータディレクトリを作成する権限がないために発生しています。</p>";
        echo "<p>Webサーバー（Apache/Nginxなど）は通常、制限された権限で実行されており、システムディレクトリに書き込むことができません。</p>";
        
        echo "<h2>解決方法</h2>";
        echo "<ol>";
        echo "<li><strong>ディレクトリを手動で作成する</strong>：上記のコマンドを使用して、必要なディレクトリを作成し、適切な権限を設定します。</li>";
        echo "<li><strong>アプリケーションのデータディレクトリを変更する</strong>：Webサーバーが書き込み可能な場所にデータディレクトリを変更することも検討してください。</li>";
        echo "<li><strong>Webサーバーの設定を確認する</strong>：Apacheの場合、VirtualHostの設定でディレクトリへのアクセス権を付与することができます。</li>";
        echo "</ol>";
        
        exit(1);
    }
    
    // データベース接続エラーの処理
    if (strpos($errorMsg, 'データベースファイルを開けません') !== false || 
        $e instanceof PDOException || 
        strpos($errorMsg, 'unable to open database file') !== false) {
        
        echo "<h2>データベース接続エラーの詳細</h2>";
        echo "<pre>" . htmlspecialchars($errorMsg) . "</pre>";
        
        if (!strpos($errorMsg, 'データベースファイルを開けません')) {
            echo "<h2>解決方法</h2>";
            echo "<p>1. データディレクトリの権限を確認してください：</p>";
            echo "<pre>sudo mkdir -p " . htmlspecialchars(dirname(__DIR__, 2) . '/data') . "\n";
            echo "sudo chown www-data:www-data " . htmlspecialchars(dirname(__DIR__, 2) . '/data') . "\n";
            echo "sudo chmod 755 " . htmlspecialchars(dirname(__DIR__, 2) . '/data') . "</pre>";
            
            echo "<p>2. Webサーバーユーザー（www-dataなど）にデータディレクトリへの書き込み権限があることを確認してください。</p>";
        }
        
        exit(1);
    }
    
    // SQLiteドライバエラーの処理
    if (strpos($errorMsg, 'could not find driver') !== false) {
        echo "<h2>SQLiteドライバエラーの解決方法</h2>";
        echo "<p>このエラーはPHPのSQLiteドライバが見つからないことを示しています。以下の手順でインストールしてください：</p>";
        
        echo "<h3>Ubuntu/Debian系:</h3>";
        echo "<pre>sudo apt-get update\nsudo apt-get install php-sqlite3 php-pdo</pre>";
        
        echo "<h3>CentOS/RHEL系:</h3>";
        echo "<pre>sudo yum install php-pdo php-sqlite</pre>";
        
        echo "<h3>macOS (Homebrew):</h3>";
        echo "<pre>brew install php\nbrew install sqlite3</pre>";
        
        echo "<h3>Windows:</h3>";
        echo "<p>php.iniファイルで以下の行のコメントを解除してください：</p>";
        echo "<pre>;extension=pdo_sqlite\n;extension=sqlite3</pre>";
        
        exit(1);
    }
    
    // API Keyエラーの処理
    if (strpos($errorMsg, 'API key') !== false) {
        echo "<h2>API Keyエラーの解決方法</h2>";
        echo "<p>Stability AI APIキーが正しく設定されていることを確認してください。</p>";
        echo "<p>プロジェクトのルートディレクトリに.envファイルを作成し、以下の内容を追加してください：</p>";
        echo "<pre>STABILITY_API_KEY=あなたのAPIキー</pre>";
        
        exit(1);
    }
    
    // その他のエラーの処理
    echo "<h2>エラーの詳細</h2>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    
    echo "<h2>一般的なトラブルシューティング</h2>";
    echo "<ul>";
    echo "<li>PHPのバージョンが7.4以上であることを確認してください</li>";
    echo "<li>必要な拡張モジュールがすべてインストールされていることを確認してください</li>";
    echo "<li>ファイルの権限が正しく設定されていることを確認してください</li>";
    echo "<li>ネットワーク接続が正常であることを確認してください</li>";
    echo "</ul>";
    
    exit(1);
}
