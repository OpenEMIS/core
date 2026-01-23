#!/bin/bash
# This script removes the old CakePHP 4.4.16 directory

cd "$(dirname "$0")"

echo "Removing vendor/cakephp/cakephp directory..."
rm -rf vendor/cakephp/cakephp

if [ ! -d "vendor/cakephp/cakephp" ]; then
    echo "SUCCESS: Directory removed!"
    echo ""
    echo "Now run:"
    echo "/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer install --no-interaction --prefer-dist"
else
    echo "ERROR: Directory still exists"
fi



