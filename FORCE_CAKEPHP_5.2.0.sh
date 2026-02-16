#!/bin/bash
set -e

cd "$(dirname "$0")"

echo "=========================================="
echo "FORCING CAKEPHP 5.2.0 INSTALLATION"
echo "=========================================="
echo ""

# Step 1: Remove old CakePHP
echo "Step 1: Removing vendor/cakephp/cakephp..."
rm -rf vendor/cakephp/cakephp
echo "✓ Removed"

# Step 2: Clear composer cache
echo ""
echo "Step 2: Clearing Composer cache..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer clear-cache 2>&1 | grep -v "Warning" || true
echo "✓ Cache cleared"

# Step 3: Remove from installed.json
echo ""
echo "Step 3: Removing CakePHP from installed.json..."
if [ -f "vendor/composer/installed.json" ]; then
    # Backup
    cp vendor/composer/installed.json vendor/composer/installed.json.backup
    
    # Remove cakephp/cakephp entry using PHP
    /opt/homebrew/Cellar/php@8.1/*/bin/php -r "
        \$data = json_decode(file_get_contents('vendor/composer/installed.json'), true);
        if (isset(\$data['packages'])) {
            \$data['packages'] = array_filter(\$data['packages'], function(\$pkg) {
                return \$pkg['name'] !== 'cakephp/cakephp';
            });
            \$data['packages'] = array_values(\$data['packages']);
        }
        file_put_contents('vendor/composer/installed.json', json_encode(\$data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    "
    echo "✓ Removed from installed.json"
fi

# Step 4: Force install from lock file
echo ""
echo "Step 4: Installing CakePHP 5.2.0 from composer.lock..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer install --no-interaction --prefer-dist 2>&1 | grep -E "(Installing|Updating|cakephp)" | tail -20

# Step 5: Verify
echo ""
echo "=========================================="
echo "VERIFICATION:"
echo "=========================================="
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer show cakephp/cakephp 2>&1 | grep -E "^name|^versions|^version" | head -3

# Check vendor directory
echo ""
if [ -f "vendor/cakephp/cakephp/composer.json" ]; then
    echo "PHP requirement in vendor/cakephp/cakephp/composer.json:"
    grep '"php":' vendor/cakephp/cakephp/composer.json
    if grep -q '"php": ">=8.1"' vendor/cakephp/cakephp/composer.json; then
        echo ""
        echo "✓✓✓ SUCCESS: CakePHP 5.2.0 is installed! ✓✓✓"
    else
        echo ""
        echo "✗ ERROR: Still showing CakePHP 4.x (PHP >=7.4 requirement found)"
    fi
else
    echo "✗ ERROR: vendor/cakephp/cakephp/composer.json not found"
fi

echo ""
echo "=========================================="



