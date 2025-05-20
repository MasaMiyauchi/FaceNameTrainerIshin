<?php
/**
 * FaceNameTrainerIshin Environment Setup Script
 * 
 * This script sets up the necessary environment for the FaceNameTrainerIshin application.
 */

function colorText($text, $color) {
    $colors = [
        'red' => "\033[0;31m",
        'green' => "\033[0;32m",
        'yellow' => "\033[0;33m",
        'reset' => "\033[0m"
    ];
    
    return $colors[$color] . $text . $colors['reset'];
}

echo colorText("FaceNameTrainerIshin Environment Setup", 'green') . PHP_EOL;
echo "==================================================" . PHP_EOL;
echo PHP_EOL;

$repoRoot = dirname(__FILE__);
echo "Repository root: " . colorText($repoRoot, 'yellow') . PHP_EOL;
echo PHP_EOL;

function createDirectory($dir, $perm = 0755, $owner = 'www-data:www-data') {
    echo "Creating directory $dir... ";
    
    if (is_dir($dir)) {
        echo colorText("Already exists", 'yellow') . PHP_EOL;
    } else {
        if (mkdir($dir, $perm, true)) {
            echo colorText("Success", 'green') . PHP_EOL;
        } else {
            echo colorText("Failed", 'red') . PHP_EOL;
            echo "  Try running with sudo or manually create the directory:" . PHP_EOL;
            echo "  sudo mkdir -p $dir" . PHP_EOL;
            return false;
        }
    }
    
    echo "Setting permissions on $dir... ";
    if (chmod($dir, $perm)) {
        echo colorText("Success", 'green') . PHP_EOL;
    } else {
        echo colorText("Failed", 'red') . PHP_EOL;
        echo "  Try running with sudo or manually set permissions:" . PHP_EOL;
        echo "  sudo chmod " . decoct($perm) . " $dir" . PHP_EOL;
        return false;
    }
    
    echo "Setting owner to $owner on $dir... ";
    $ownerParts = explode(':', $owner);
    $user = $ownerParts[0];
    $group = isset($ownerParts[1]) ? $ownerParts[1] : $user;
    
    if (function_exists('posix_getuid') && posix_getuid() === 0) {
        if (chown($dir, $user) && chgrp($dir, $group)) {
            echo colorText("Success", 'green') . PHP_EOL;
        } else {
            echo colorText("Warning: Could not set owner to $owner", 'yellow') . PHP_EOL;
            echo "  Try running with sudo or manually set owner:" . PHP_EOL;
            echo "  sudo chown $owner $dir" . PHP_EOL;
        }
    } else {
        echo colorText("Warning: Not running as root, skipping owner change", 'yellow') . PHP_EOL;
        echo "  Try running with sudo or manually set owner:" . PHP_EOL;
        echo "  sudo chown $owner $dir" . PHP_EOL;
    }
    
    return true;
}

function checkPhpExtension($extension, $message) {
    echo "Checking for PHP extension $extension... ";
    
    if (extension_loaded($extension)) {
        echo colorText("Installed", 'green') . PHP_EOL;
        return true;
    } else {
        echo colorText("Not found", 'red') . PHP_EOL;
        echo "  $message" . PHP_EOL;
        return false;
    }
}

function detectOs() {
    if (PHP_OS === 'Linux') {
        if (file_exists('/etc/debian_version')) {
            return 'debian';
        } elseif (file_exists('/etc/redhat-release')) {
            return 'rhel';
        } else {
            return 'linux';
        }
    } elseif (PHP_OS === 'Darwin') {
        return 'macos';
    } elseif (strpos(PHP_OS, 'WIN') !== false) {
        return 'windows';
    } else {
        return 'unknown';
    }
}

function installPhpExtension($extension, $osType) {
    echo "Installing PHP extension $extension... " . PHP_EOL;
    
    $commands = [];
    
    switch ($osType) {
        case 'debian':
            $commands[] = "apt-get update";
            $commands[] = "apt-get install -y php-$extension";
            break;
        case 'rhel':
            $commands[] = "yum install -y php-$extension";
            break;
        case 'macos':
            $commands[] = "brew install php";
            $commands[] = "brew install $extension";
            break;
        case 'windows':
            echo colorText("For Windows, please enable the extension in your php.ini file:", 'yellow') . PHP_EOL;
            echo "extension=$extension" . PHP_EOL;
            return false;
        default:
            echo colorText("Unsupported OS type", 'red') . PHP_EOL;
            return false;
    }
    
    foreach ($commands as $command) {
        echo "Running: $command" . PHP_EOL;
        $output = [];
        $returnVar = 0;
        
        if (function_exists('exec')) {
            exec("sudo $command 2>&1", $output, $returnVar);
            echo implode(PHP_EOL, $output) . PHP_EOL;
            
            if ($returnVar !== 0) {
                echo colorText("Command failed with error code $returnVar", 'red') . PHP_EOL;
                return false;
            }
        } else {
            echo colorText("exec function is disabled. Cannot run command.", 'red') . PHP_EOL;
            return false;
        }
    }
    
    echo colorText("Installation completed. You may need to restart your web server.", 'green') . PHP_EOL;
    return true;
}

function setupEnvFile($repoRoot) {
    $envFile = $repoRoot . '/.env';
    $envExample = $repoRoot . '/.env.example';
    
    echo "Setting up .env file... ";
    
    if (file_exists($envFile)) {
        echo colorText("Already exists", 'yellow') . PHP_EOL;
    } else {
        if (file_exists($envExample)) {
            copy($envExample, $envFile);
            echo colorText("Created from example", 'green') . PHP_EOL;
        } else {
            echo colorText("Failed: .env.example not found", 'red') . PHP_EOL;
            return false;
        }
    }
    
    echo PHP_EOL;
    echo colorText("Important:", 'yellow') . " You need to edit the .env file and set your Stability AI API key:" . PHP_EOL;
    echo "STABILITY_API_KEY=your_api_key_here" . PHP_EOL;
    echo PHP_EOL;
    echo "You can edit it with the following command:" . PHP_EOL;
    echo "nano $envFile" . PHP_EOL;
    
    return true;
}

echo "Step 1: Creating necessary directories" . PHP_EOL;
echo "-------------------------------------" . PHP_EOL;
createDirectory($repoRoot . '/assets/data', 0755);
createDirectory($repoRoot . '/assets/faces', 0755);
echo PHP_EOL;

echo "Step 2: Checking PHP dependencies" . PHP_EOL;
echo "-------------------------------" . PHP_EOL;
$missingExtensions = [];

if (!checkPhpExtension('pdo', 'PDO extension is required for database operations')) {
    $missingExtensions[] = 'pdo';
}
if (!checkPhpExtension('pdo_sqlite', 'PDO SQLite driver is required for database operations')) {
    $missingExtensions[] = 'sqlite3';
}
if (!checkPhpExtension('sqlite3', 'SQLite3 extension is required for database operations')) {
    $missingExtensions[] = 'sqlite3';
}

if (!empty($missingExtensions)) {
    echo PHP_EOL;
    echo colorText("Missing PHP extensions detected.", 'yellow') . PHP_EOL;
    
    $osType = detectOs();
    
    if ($osType !== 'unknown') {
        echo "Would you like to install the missing extensions now? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($line) === 'y') {
            echo "Installing missing extensions..." . PHP_EOL;
            
            foreach ($missingExtensions as $extension) {
                installPhpExtension($extension, $osType);
            }
        } else {
            echo "Skipping installation." . PHP_EOL;
            
            echo PHP_EOL;
            echo "Please install the required extensions manually:" . PHP_EOL;
            echo PHP_EOL;
            echo "Ubuntu/Debian:" . PHP_EOL;
            echo "  sudo apt-get update" . PHP_EOL;
            echo "  sudo apt-get install php-sqlite3" . PHP_EOL;
            echo PHP_EOL;
            echo "CentOS/RHEL:" . PHP_EOL;
            echo "  sudo yum install php-pdo php-sqlite" . PHP_EOL;
            echo PHP_EOL;
            echo "macOS (Homebrew):" . PHP_EOL;
            echo "  brew install php" . PHP_EOL;
            echo "  brew install sqlite3" . PHP_EOL;
            echo PHP_EOL;
            echo "Windows:" . PHP_EOL;
            echo "  Enable extensions in php.ini file:" . PHP_EOL;
            echo "  extension=pdo_sqlite" . PHP_EOL;
            echo "  extension=sqlite3" . PHP_EOL;
        }
    } else {
        echo colorText("Automatic installation not supported for your OS.", 'red') . PHP_EOL;
        echo "Please install the required extensions manually:" . PHP_EOL;
        echo PHP_EOL;
        echo "Ubuntu/Debian:" . PHP_EOL;
        echo "  sudo apt-get update" . PHP_EOL;
        echo "  sudo apt-get install php-sqlite3" . PHP_EOL;
        echo PHP_EOL;
        echo "CentOS/RHEL:" . PHP_EOL;
        echo "  sudo yum install php-pdo php-sqlite" . PHP_EOL;
        echo PHP_EOL;
        echo "macOS (Homebrew):" . PHP_EOL;
        echo "  brew install php" . PHP_EOL;
        echo "  brew install sqlite3" . PHP_EOL;
        echo PHP_EOL;
        echo "Windows:" . PHP_EOL;
        echo "  Enable extensions in php.ini file:" . PHP_EOL;
        echo "  extension=pdo_sqlite" . PHP_EOL;
        echo "  extension=sqlite3" . PHP_EOL;
    }
}
echo PHP_EOL;

echo "Step 3: Setting up environment file" . PHP_EOL;
echo "---------------------------------" . PHP_EOL;
setupEnvFile($repoRoot);
echo PHP_EOL;

echo "Step 4: Verifying setup" . PHP_EOL;
echo "---------------------" . PHP_EOL;
echo "Checking assets/data directory... ";
if (is_dir($repoRoot . '/assets/data')) {
    echo colorText("Exists", 'green') . PHP_EOL;
} else {
    echo colorText("Not found", 'red') . PHP_EOL;
}

echo "Checking assets/faces directory... ";
if (is_dir($repoRoot . '/assets/faces')) {
    echo colorText("Exists", 'green') . PHP_EOL;
} else {
    echo colorText("Not found", 'red') . PHP_EOL;
}

echo "Checking .env file... ";
if (file_exists($repoRoot . '/.env')) {
    echo colorText("Exists", 'green') . PHP_EOL;
} else {
    echo colorText("Not found", 'red') . PHP_EOL;
}
echo PHP_EOL;

echo colorText("Setup complete!", 'green') . PHP_EOL;
echo "You can now run the application. If you encounter any issues," . PHP_EOL;
echo "please refer to the README.md file for troubleshooting." . PHP_EOL;
echo PHP_EOL;

if (!empty($missingExtensions)) {
    echo colorText("Warning:", 'yellow') . " Some PHP extensions are missing. Install them before running the application." . PHP_EOL;
}

echo "To run the monitoring test, access:" . PHP_EOL;
echo "http://localhost/monitoring/generateImage/test.php" . PHP_EOL;
echo PHP_EOL;
