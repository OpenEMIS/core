#!/bin/bash
set -e

cd "$(dirname "$0")"

echo "=========================================="
echo "Installing All Missing Packages"
echo "=========================================="
echo ""

# Install all missing packages referenced in autoloader
echo "Installing missing packages..."

# Check and install symfony/polyfill-php80
if [ ! -d "vendor/symfony/polyfill-php80" ]; then
    echo "Installing symfony/polyfill-php80..."
    /opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer require symfony/polyfill-php80 --no-interaction 2>&1 | tail -10
fi

# Check and install symfony/polyfill-php73
if [ ! -d "vendor/symfony/polyfill-php73" ]; then
    echo "Installing symfony/polyfill-php73..."
    /opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer require symfony/polyfill-php73 --no-interaction 2>&1 | tail -10
fi

# Check and install symfony/polyfill-php81
if [ ! -d "vendor/symfony/polyfill-php81" ]; then
    echo "Installing symfony/polyfill-php81..."
    /opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer require symfony/polyfill-php81 --no-interaction 2>&1 | tail -10
fi

# Check and install ralouphie/getallheaders
if [ ! -d "vendor/ralouphie/getallheaders" ]; then
    echo "Installing ralouphie/getallheaders..."
    /opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer require ralouphie/getallheaders --no-interaction 2>&1 | tail -10
fi

# Check and install phpseclib/phpseclib
if [ ! -d "vendor/phpseclib/phpseclib" ]; then
    echo "Installing phpseclib/phpseclib..."
    /opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer require phpseclib/phpseclib --no-interaction 2>&1 | tail -10
fi

# Regenerate autoloader
echo ""
echo "Regenerating autoloader..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer dump-autoload --no-interaction 2>&1 | tail -10

# Uncomment the polyfill-php80 line if package is now installed
if [ -d "vendor/symfony/polyfill-php80" ]; then
    echo ""
    echo "Uncommenting polyfill-php80 in autoloader..."
    sed -i '' 's|// '\''a4a119a56e50fbb293281d9a48007e0e'\'' =>|'\''a4a119a56e50fbb293281d9a48007e0e'\'' =>|' vendor/composer/autoload_files.php
    echo "✓ Fixed"
fi

echo ""
echo "=========================================="
echo "Done! All missing packages should be installed."
echo "=========================================="



