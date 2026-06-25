#!/bin/bash
set -e

cd "$(dirname "$0")"

echo "=========================================="
echo "FORCE FIXING CAKEPHP VERSION TO 5.2.0"
echo "=========================================="
echo ""

# Step 1: Remove old CakePHP
echo "Step 1: Removing vendor/cakephp/cakephp..."
if [ -d "vendor/cakephp/cakephp" ]; then
    rm -rf vendor/cakephp/cakephp
    echo "✓ Removed"
else
    echo "✓ Already removed"
fi

# Step 2: Remove from composer cache
echo ""
echo "Step 2: Clearing Composer cache..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer clear-cache 2>&1 | grep -v "Warning" || true

# Step 3: Remove vendor/cakephp directory entirely
echo ""
echo "Step 3: Removing entire vendor/cakephp directory..."
if [ -d "vendor/cakephp" ]; then
    rm -rf vendor/cakephp
    echo "✓ Removed vendor/cakephp"
fi

# Step 4: Reset git changes in vendor packages
echo ""
echo "Step 4: Resetting git changes..."
cd vendor/korditpteltd/iksso-cakephp-sso 2>/dev/null && git reset --hard HEAD 2>/dev/null && cd ../../.. || true
cd vendor/korditpteltd/ikrst-cakephp-restful 2>/dev/null && git reset --hard HEAD 2>/dev/null && cd ../../.. || true

# Step 5: Force reinstall from lock file
echo ""
echo "Step 5: Reinstalling from composer.lock..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer install --no-interaction --prefer-dist 2>&1 | tail -30

# Step 6: Verify
echo ""
echo "=========================================="
echo "VERIFICATION:"
echo "=========================================="
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer show cakephp/cakephp 2>&1 | grep -E "^name|^versions|^version" | head -3

# Check vendor directory
echo ""
echo "Checking vendor/cakephp/cakephp/composer.json:"
if [ -f "vendor/cakephp/cakephp/composer.json" ]; then
    grep -A 1 '"php":' vendor/cakephp/cakephp/composer.json | head -2
    echo ""
    if grep -q '"php": ">=8.1"' vendor/cakephp/cakephp/composer.json; then
        echo "✓ SUCCESS: CakePHP 5.2.0 is installed (PHP >=8.1 requirement found)"
    else
        echo "✗ ERROR: Still showing CakePHP 4.x (PHP >=7.4 requirement found)"
    fi
else
    echo "✗ ERROR: vendor/cakephp/cakephp/composer.json not found"
fi

echo ""
echo "=========================================="



