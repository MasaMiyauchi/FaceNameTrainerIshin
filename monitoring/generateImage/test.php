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
    echo "<pre>sudo apt-get update\nsudo apt-get install php-sqlite3</pre>";
    echo "<p>注: PDOはPHPのコアモジュールの一部として提供されています。特定のバージョンを使用している場合は、以下のようにインストールしてください：</p>";
    echo "<pre>sudo apt-get install php-sqlite3 php8.1-common</pre>";
    echo "<p>（8.1の部分は、使用しているPHPのバージョンに合わせて変更してください）</p>";
    
    echo "<h3>CentOS/RHEL系:</h3>";
    echo "<pre>sudo yum install php-pdo php-sqlite</pre>";
    
    echo "<h3>macOS (Homebrew):</h3>";
    echo "<pre>brew install php\nbrew install sqlite3</pre>";
    
    echo "<h3>Windows:</h3>";
    echo "<p>php.iniファイルで以下の行のコメントを解除してください：</p>";
    echo "<pre>;extension=pdo_sqlite\n;extension=sqlite3</pre>";
    echo "<p>コメントを解除後：</p>";
    echo "<pre>extension=pdo_sqlite\nextension=sqlite3</pre>";
    
    echo "<h2>インストール後の確認方法</h2>";
    echo "<h3>1. PHPの設定情報を確認</h3>";
    echo "<p>以下のコマンドを実行して、PHPの設定情報とロードされている拡張モジュールを確認できます：</p>";
    echo "<pre>php -i | grep -i sqlite</pre>";
    echo "<p>または、以下のPHPスクリプトを作成して実行することでも確認できます：</p>";
    echo "<pre>&lt;?php phpinfo(); ?&gt;</pre>";
    
    echo "<h3>2. php.iniの場所を確認</h3>";
    echo "<p>以下のコマンドでPHPが使用しているphp.iniファイルの場所を確認できます：</p>";
    echo "<pre>php -i | grep 'Loaded Configuration File'</pre>";
    
    echo "<h3>3. 拡張モジュールの手動有効化</h3>";
    echo "<p>php.iniファイルを見つけたら、以下の行が含まれていることを確認し、コメントアウトされていないことを確認してください：</p>";
    echo "<pre>extension=pdo_sqlite\nextension=sqlite3</pre>";
    echo "<p>行が見つからない場合は、追加してください。変更後、Webサーバーを再起動してください：</p>";
    echo "<pre>sudo systemctl restart apache2</pre>";
    echo "<p>または</p>";
    echo "<pre>sudo systemctl restart nginx\nsudo systemctl restart php-fpm</pre>";
    
    echo "<h3>4. PHPバージョンの確認</h3>";
    echo "<p>使用しているPHPのバージョンを確認します：</p>";
    echo "<pre>php -v</pre>";
    echo "<p>バージョンに合わせたパッケージをインストールしてください。例えば、PHP 7.4の場合：</p>";
    echo "<pre>sudo apt-get install php7.4-sqlite3</pre>";
    
    echo "<h3>5. 拡張モジュールのディレクトリを確認</h3>";
    echo "<p>PHPの拡張モジュールディレクトリを確認します：</p>";
    echo "<pre>php -i | grep extension_dir</pre>";
    echo "<p>このディレクトリに pdo_sqlite.so と sqlite3.so ファイルが存在するか確認してください。</p>";
    
    echo "<h2>詳細情報</h2>";
    echo "<p>このモニタリングツールはSQLiteデータベースを使用してモニタリングデータを保存します。</p>";
    echo "<p>必要な拡張モジュールをインストールした後、Webサーバーを再起動してください。</p>";
    echo "<p>Webサーバーの再起動は非常に重要です。インストール後に再起動しないと、新しい拡張モジュールが読み込まれません。</p>";
    
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

try {
    $generator = new ImageGenerator($apiKey);
    
    try {
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
        
        if (strpos($errorMsg, 'ディレクトリ作成時の権限エラー') !== false || 
            strpos($errorMsg, 'Permission denied') !== false && strpos($errorMsg, 'mkdir') !== false) {
            
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
        
        echo "<h2>エラーの詳細</h2>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        
        exit(1);
    }
} catch (Exception $e) {
    echo "<h1>モニタリングツール - エラー発生</h1>";
    echo "<div style='color: red; font-weight: bold; margin-bottom: 20px;'>";
    echo "エラー: " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    echo "<h2>エラーの詳細</h2>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    
    if (strpos($e->getMessage(), 'could not find driver') !== false) {
        echo "<h2>SQLiteドライバエラーの解決方法</h2>";
        echo "<p>このエラーはPHPのSQLiteドライバが見つからないことを示しています。以下の手順でインストールしてください：</p>";
        
        echo "<h3>Ubuntu/Debian系:</h3>";
        echo "<pre>sudo apt-get update\nsudo apt-get install php-sqlite3</pre>";
        echo "<p>注: PDOはPHPのコアモジュールの一部として提供されています。</p>";
        
        echo "<h3>CentOS/RHEL系:</h3>";
        echo "<pre>sudo yum install php-pdo php-sqlite</pre>";
        
        echo "<h3>macOS (Homebrew):</h3>";
        echo "<pre>brew install php\nbrew install sqlite3</pre>";
        
        echo "<h3>Windows:</h3>";
        echo "<p>php.iniファイルで以下の行のコメントを解除してください：</p>";
        echo "<pre>;extension=pdo_sqlite\n;extension=sqlite3</pre>";
        
        echo "<h2>インストール後のトラブルシューティング</h2>";
        echo "<p>パッケージをインストールしても問題が解決しない場合は、以下の手順を試してください：</p>";
        
        echo "<h3>1. PHPの設定を確認</h3>";
        echo "<p>以下のコマンドを実行して、SQLite関連の拡張モジュールが正しく読み込まれているか確認します：</p>";
        echo "<pre>php -m | grep -i sqlite</pre>";
        echo "<p>または、以下のPHPスクリプトを作成して実行することでも確認できます：</p>";
        echo "<pre>&lt;?php\necho \"インストールされているPHP拡張モジュール：\\n\";\nprint_r(get_loaded_extensions());\necho \"\\nSQLite PDOドライバ：\\n\";\nprint_r(PDO::getAvailableDrivers());\n?&gt;</pre>";
        
        echo "<h3>2. Webサーバーの再起動</h3>";
        echo "<p>拡張モジュールをインストールした後、必ずWebサーバーを再起動してください：</p>";
        echo "<pre>sudo systemctl restart apache2</pre>";
        echo "<p>または</p>";
        echo "<pre>sudo systemctl restart nginx\nsudo systemctl restart php-fpm</pre>";
        
        echo "<h3>3. PHPのバージョンを確認</h3>";
        echo "<p>CLIとWebサーバーで異なるPHPバージョンが使用されている可能性があります。以下のコマンドで確認してください：</p>";
        echo "<pre>php -v</pre>";
        echo "<p>Webサーバー用に以下のPHPスクリプトを作成して実行することでも確認できます：</p>";
        echo "<pre>&lt;?php echo phpversion(); ?&gt;</pre>";
        
        echo "<h3>4. php.iniファイルの確認</h3>";
        echo "<p>CLIとWebサーバーで異なるphp.iniファイルが使用されている可能性があります。以下のコマンドで確認してください：</p>";
        echo "<pre>php -i | grep 'Loaded Configuration File'</pre>";
        echo "<p>Webサーバー用に以下のPHPスクリプトを作成して実行することでも確認できます：</p>";
        echo "<pre>&lt;?php echo php_ini_loaded_file(); ?&gt;</pre>";
    } elseif (strpos($e->getMessage(), 'API key') !== false) {
        echo "<h2>API Keyエラーの解決方法</h2>";
        echo "<p>Stability AI APIキーが正しく設定されていることを確認してください。</p>";
        echo "<p>プロジェクトのルートディレクトリに.envファイルを作成し、以下の内容を追加してください：</p>";
        echo "<pre>STABILITY_API_KEY=あなたのAPIキー</pre>";
    } else {
        echo "<h2>一般的なトラブルシューティング</h2>";
        echo "<ul>";
        echo "<li>PHPのバージョンが7.4以上であることを確認してください</li>";
        echo "<li>必要な拡張モジュールがすべてインストールされていることを確認してください</li>";
        echo "<li>ファイルの権限が正しく設定されていることを確認してください</li>";
        echo "<li>ネットワーク接続が正常であることを確認してください</li>";
        echo "</ul>";
    }
}
