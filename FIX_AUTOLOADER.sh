#!/bin/bash
set -e

cd "$(dirname "$0")"

echo "=========================================="
echo "Fixing Missing symfony/polyfill-php80"
echo "=========================================="
echo ""

# Step 1: Install the missing package
echo "Step 1: Installing symfony/polyfill-php80..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer require symfony/polyfill-php80 --no-interaction 2>&1 | tail -20

# Step 2: Regenerate autoloader
echo ""
echo "Step 2: Regenerating autoloader..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer dump-autoload --no-interaction 2>&1 | tail -10

# Step 3: Verify
echo ""
echo "=========================================="
echo "Verification:"
echo "=========================================="
if [ -d "vendor/symfony/polyfill-php80" ]; then
    echo "✓ symfony/polyfill-php80 is installed"
    ls -la vendor/symfony/polyfill-php80/bootstrap.php
else
    echo "✗ symfony/polyfill-php80 is still missing"
fi

echo ""
echo "=========================================="
echo "Done! Try accessing the application now."
echo "=========================================="



