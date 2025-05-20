#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}FaceNameTrainerIshin Environment Setup${NC}"
echo "=================================================="
echo ""

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo -e "Repository root: ${YELLOW}$REPO_ROOT${NC}"
echo ""

check_root() {
    if [ "$EUID" -ne 0 ]; then
        echo -e "${YELLOW}Notice: Not running as root. Some operations might fail.${NC}"
        echo "Consider running with sudo if you encounter permission errors."
        echo ""
    fi
}

create_directory() {
    local dir=$1
    local perm=${2:-755}
    local owner=${3:-"www-data:www-data"}
    
    echo -n "Creating directory $dir... "
    
    if [ -d "$dir" ]; then
        echo -e "${YELLOW}Already exists${NC}"
    else
        mkdir -p "$dir"
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}Success${NC}"
        else
            echo -e "${RED}Failed${NC}"
            echo "  Try running with sudo or manually create the directory:"
            echo "  sudo mkdir -p $dir"
            return 1
        fi
    fi
    
    echo -n "Setting permissions on $dir... "
    chmod $perm "$dir" 2>/dev/null
    if [ $? -ne 0 ]; then
        echo -e "${RED}Failed${NC}"
        echo "  Try running with sudo or manually set permissions:"
        echo "  sudo chmod $perm $dir"
        return 1
    fi
    
    echo -n "Setting owner to $owner on $dir... "
    chown $owner "$dir" 2>/dev/null
    if [ $? -ne 0 ]; then
        echo -e "${YELLOW}Warning: Could not set owner to $owner${NC}"
        echo "  Try running with sudo or manually set owner:"
        echo "  sudo chown $owner $dir"
    else
        echo -e "${GREEN}Success${NC}"
    fi
    
    return 0
}

check_php_extension() {
    local extension=$1
    local message=$2
    
    echo -n "Checking for PHP extension $extension... "
    
    if php -m | grep -q $extension; then
        echo -e "${GREEN}Installed${NC}"
        return 0
    else
        echo -e "${RED}Not found${NC}"
        echo "  $message"
        return 1
    fi
}

install_php_extension() {
    local extension=$1
    local package=$2
    local os_type=$3
    
    echo -n "Installing PHP extension $extension... "
    
    case $os_type in
        debian)
            apt-get update -qq && apt-get install -y $package
            ;;
        rhel)
            yum install -y $package
            ;;
        macos)
            brew install $package
            ;;
        *)
            echo -e "${RED}Unsupported OS type${NC}"
            return 1
            ;;
    esac
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}Success${NC}"
        return 0
    else
        echo -e "${RED}Failed${NC}"
        return 1
    fi
}

detect_os() {
    if [ -f /etc/debian_version ]; then
        echo "debian"
    elif [ -f /etc/redhat-release ]; then
        echo "rhel"
    elif [ "$(uname)" == "Darwin" ]; then
        echo "macos"
    else
        echo "unknown"
    fi
}

setup_env_file() {
    local env_file="$REPO_ROOT/.env"
    local env_example="$REPO_ROOT/.env.example"
    
    echo -n "Setting up .env file... "
    
    if [ -f "$env_file" ]; then
        echo -e "${YELLOW}Already exists${NC}"
    else
        if [ -f "$env_example" ]; then
            cp "$env_example" "$env_file"
            echo -e "${GREEN}Created from example${NC}"
        else
            echo -e "${RED}Failed: .env.example not found${NC}"
            return 1
        fi
    fi
    
    echo ""
    echo -e "${YELLOW}Important:${NC} You need to edit the .env file and set your Stability AI API key:"
    echo "STABILITY_API_KEY=your_api_key_here"
    echo ""
    echo "You can edit it with the following command:"
    echo "nano $env_file"
    
    return 0
}

check_root

echo "Step 1: Creating necessary directories"
echo "-------------------------------------"
create_directory "$REPO_ROOT/assets/data" 755 "www-data:www-data" || true
create_directory "$REPO_ROOT/assets/faces" 755 "www-data:www-data" || true
echo ""

echo "Step 2: Checking PHP dependencies"
echo "-------------------------------"
missing_extensions=0
missing_extension_list=""

check_php_extension "pdo" "PDO extension is required for database operations" || { 
    ((missing_extensions++))
    missing_extension_list="$missing_extension_list php-pdo"
}
check_php_extension "pdo_sqlite" "PDO SQLite driver is required for database operations" || {
    ((missing_extensions++))
    missing_extension_list="$missing_extension_list php-sqlite3"
}
check_php_extension "sqlite3" "SQLite3 extension is required for database operations" || {
    ((missing_extensions++))
    missing_extension_list="$missing_extension_list php-sqlite3"
}

if [ $missing_extensions -gt 0 ]; then
    echo ""
    echo -e "${YELLOW}Missing PHP extensions detected.${NC}"
    
    os_type=$(detect_os)
    
    case $os_type in
        debian)
            install_cmd="apt-get update && apt-get install -y $missing_extension_list"
            ;;
        rhel)
            install_cmd="yum install -y $missing_extension_list"
            ;;
        macos)
            install_cmd="brew install php sqlite3"
            ;;
        *)
            echo -e "${RED}Automatic installation not supported for your OS.${NC}"
            echo "Please install the required extensions manually:"
            echo ""
            echo "Ubuntu/Debian:"
            echo "  sudo apt-get update"
            echo "  sudo apt-get install php-sqlite3"
            echo ""
            echo "CentOS/RHEL:"
            echo "  sudo yum install php-pdo php-sqlite"
            echo ""
            echo "macOS (Homebrew):"
            echo "  brew install php"
            echo "  brew install sqlite3"
            echo ""
            echo "Windows:"
            echo "  Enable extensions in php.ini file:"
            echo "  extension=pdo_sqlite"
            echo "  extension=sqlite3"
            ;;
    esac
    
    if [ "$os_type" != "unknown" ]; then
        echo -e "${YELLOW}Would you like to install the missing extensions now? (y/n)${NC}"
        read -r answer
        if [[ "$answer" =~ ^[Yy]$ ]]; then
            echo "Installing missing extensions..."
            if [ "$EUID" -ne 0 ]; then
                echo "Sudo privileges required for installation."
                sudo bash -c "$install_cmd"
            else
                eval "$install_cmd"
            fi
            
            if [ $? -eq 0 ]; then
                echo -e "${GREEN}Installation successful!${NC}"
            else
                echo -e "${RED}Installation failed.${NC}"
                echo "Please try installing the extensions manually."
            fi
        else
            echo "Skipping installation."
        fi
    fi
fi
echo ""

echo "Step 3: Setting up environment file"
echo "---------------------------------"
setup_env_file
echo ""

echo "Step 4: Verifying setup"
echo "---------------------"
echo -n "Checking assets/data directory... "
if [ -d "$REPO_ROOT/assets/data" ]; then
    echo -e "${GREEN}Exists${NC}"
else
    echo -e "${RED}Not found${NC}"
fi

echo -n "Checking assets/faces directory... "
if [ -d "$REPO_ROOT/assets/faces" ]; then
    echo -e "${GREEN}Exists${NC}"
else
    echo -e "${RED}Not found${NC}"
fi

echo -n "Checking .env file... "
if [ -f "$REPO_ROOT/.env" ]; then
    echo -e "${GREEN}Exists${NC}"
else
    echo -e "${RED}Not found${NC}"
fi
echo ""

echo -e "${GREEN}Setup complete!${NC}"
echo "You can now run the application. If you encounter any issues,"
echo "please refer to the README.md file for troubleshooting."
echo ""

if [ $missing_extensions -gt 0 ]; then
    echo -e "${YELLOW}Warning:${NC} Some PHP extensions are missing. Install them before running the application."
fi

echo "To run the monitoring test, access:"
echo "http://localhost/monitoring/generateImage/test.php"
echo ""
