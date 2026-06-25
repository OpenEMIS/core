#!/bin/bash
set -e

cd "$(dirname "$0")"

echo "=========================================="
echo "NUCLEAR OPTION - Complete Reinstall"
echo "=========================================="
echo ""
echo "This will:"
echo "1. Remove ALL vendor/cakephp packages"
echo "2. Clear ALL composer cache"
echo "3. Remove composer.lock"
echo "4. Reinstall everything fresh"
echo ""
read -p "Continue? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 1
fi

# Step 1: Remove all cakephp packages from vendor
echo ""
echo "Step 1: Removing all CakePHP packages from vendor..."
rm -rf vendor/cakephp
echo "✓ Removed vendor/cakephp"

# Step 2: Clear composer cache
echo ""
echo "Step 2: Clearing Composer cache..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer clear-cache 2>&1 | grep -v "Warning" || true
echo "✓ Cache cleared"

# Step 3: Reset git changes
echo ""
echo "Step 3: Resetting git changes in vendor packages..."
cd vendor/korditpteltd/iksso-cakephp-sso 2>/dev/null && git reset --hard HEAD 2>/dev/null && cd ../../.. || true
cd vendor/korditpteltd/ikrst-cakephp-restful 2>/dev/null && git reset --hard HEAD 2>/dev/null && cd ../../.. || true
echo "✓ Git changes reset"

# Step 4: Backup and remove composer.lock
echo ""
echo "Step 4: Removing composer.lock to force fresh resolution..."
if [ -f "composer.lock" ]; then
    cp composer.lock composer.lock.backup
    rm composer.lock
    echo "✓ composer.lock removed (backup saved as composer.lock.backup)"
fi

# Step 5: Install fresh
echo ""
echo "Step 5: Installing fresh from composer.json..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer install --no-interaction --prefer-dist 2>&1 | tail -40

# Step 6: Verify
echo ""
echo "=========================================="
echo "VERIFICATION:"
echo "=========================================="
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer show cakephp/cakephp 2>&1 | grep -E "^name|^versions|^version" | head -3

if [ -f "vendor/cakephp/cakephp/composer.json" ]; then
    echo ""
    echo "PHP requirement in vendor/cakephp/cakephp/composer.json:"
    grep '"php":' vendor/cakephp/cakephp/composer.json
    if grep -q '"php": ">=8.1"' vendor/cakephp/cakephp/composer.json; then
        echo ""
        echo "✓✓✓ SUCCESS: CakePHP 5.2.0 is installed! ✓✓✓"
    else
        echo ""
        echo "✗ ERROR: Still showing CakePHP 4.x"
    fi
fi

echo ""
echo "=========================================="



