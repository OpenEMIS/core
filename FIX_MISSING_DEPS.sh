#!/bin/bash
set -e

cd "$(dirname "$0")"

echo "=========================================="
echo "Fixing Missing Dependencies"
echo "=========================================="
echo ""

# Step 1: Install all dependencies from composer.lock
echo "Step 1: Installing all dependencies from composer.lock..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer install --no-interaction --prefer-dist 2>&1 | tail -50

# Step 2: Verify CakePHP version
echo ""
echo "=========================================="
echo "Verification:"
echo "=========================================="
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer show cakephp/cakephp 2>&1 | grep -E "^name|^versions|^version" | head -3

# Step 3: Check if symfony/polyfill-php80 exists
echo ""
if [ -d "vendor/symfony/polyfill-php80" ]; then
    echo "✓ symfony/polyfill-php80 is installed"
else
    echo "✗ symfony/polyfill-php80 is missing - installing..."
    /opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer require symfony/polyfill-php80 --no-interaction
fi

echo ""
echo "=========================================="
echo "Done!"
echo "=========================================="



