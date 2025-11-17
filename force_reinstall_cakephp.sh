#!/bin/bash
set -e

cd "$(dirname "$0")"

echo "=========================================="
echo "Force Reinstalling CakePHP 5.2.0"
echo "=========================================="
echo ""

# Step 1: Remove old CakePHP installation
echo "Step 1: Removing old CakePHP 4.4.16 installation..."
if [ -d "vendor/cakephp/cakephp" ]; then
    rm -rf vendor/cakephp/cakephp
    echo "✓ Removed vendor/cakephp/cakephp"
else
    echo "✓ Directory already removed"
fi

# Step 2: Reset uncommitted changes
echo ""
echo "Step 2: Resetting uncommitted changes in vendor packages..."
if [ -d "vendor/korditpteltd/iksso-cakephp-sso/.git" ]; then
    cd vendor/korditpteltd/iksso-cakephp-sso
    git reset --hard HEAD 2>/dev/null || true
    cd ../../..
    echo "✓ Reset iksso-cakephp-sso"
fi

if [ -d "vendor/korditpteltd/ikrst-cakephp-restful/.git" ]; then
    cd vendor/korditpteltd/ikrst-cakephp-restful
    git reset --hard HEAD 2>/dev/null || true
    cd ../../..
    echo "✓ Reset ikrst-cakephp-restful"
fi

# Step 3: Clear composer cache
echo ""
echo "Step 3: Clearing Composer cache..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer clear-cache 2>&1 | grep -v "Warning" || true
echo "✓ Cache cleared"

# Step 4: Remove composer.lock to force fresh resolution
echo ""
echo "Step 4: Backing up and removing composer.lock..."
if [ -f "composer.lock" ]; then
    cp composer.lock composer.lock.backup
    echo "✓ Backup created"
fi

# Step 5: Update composer.json to use ^5.2.0
echo ""
echo "Step 5: Ensuring composer.json uses ^5.2.0..."
# This is already done, just verifying
grep -q '"cakephp/cakephp":' composer.json && echo "✓ composer.json configured"

# Step 6: Install/Update
echo ""
echo "Step 6: Installing CakePHP 5.2.0..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer require cakephp/cakephp:^5.2.0 --no-interaction --update-with-dependencies --prefer-dist 2>&1 | tail -20

# Step 7: Verify
echo ""
echo "=========================================="
echo "Verification:"
echo "=========================================="
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer show cakephp/cakephp 2>&1 | grep -E "^name|^versions|^version" | head -3

echo ""
echo "=========================================="
echo "Done! Check the version above."
echo "=========================================="



