#!/bin/bash
# Script to reinstall CakePHP 5.2.0

cd "$(dirname "$0")"

echo "Removing old CakePHP installation..."
rm -rf vendor/cakephp/cakephp

echo "Resetting uncommitted changes in vendor packages..."
if [ -d "vendor/korditpteltd/iksso-cakephp-sso" ]; then
    cd vendor/korditpteltd/iksso-cakephp-sso && git reset --hard HEAD && cd ../../..
fi

if [ -d "vendor/korditpteltd/ikrst-cakephp-restful" ]; then
    cd vendor/korditpteltd/ikrst-cakephp-restful && git reset --hard HEAD && cd ../../..
fi

echo "Reinstalling dependencies with PHP 8.1..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer install --no-interaction --prefer-dist

echo ""
echo "Verifying CakePHP version..."
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer show cakephp/cakephp | grep -E "^name|^versions|^version"

echo ""
echo "Done!"



