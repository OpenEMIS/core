# Fix CakePHP Version to 5.2.0

The vendor directory still has CakePHP 4.4.16 installed. Follow these steps to fix it:

## Step 1: Remove the old CakePHP installation
```bash
cd /Users/ehteramahmad/Desktop/openemis_version_upgrade_new_bkp_instituion_working_copy/pocor-openemis-core
rm -rf vendor/cakephp/cakephp
```

## Step 2: Reset uncommitted changes in vendor packages (if blocking)
```bash
cd vendor/korditpteltd/iksso-cakephp-sso && git reset --hard HEAD && cd ../../..
cd vendor/korditpteltd/ikrst-cakephp-restful && git reset --hard HEAD && cd ../../..
```

## Step 3: Clear Composer cache (optional but recommended)
```bash
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer clear-cache
```

## Step 4: Reinstall from composer.lock
```bash
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer install --no-interaction --prefer-dist
```

## Step 5: Verify the version
```bash
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer show cakephp/cakephp | grep -E "^name|^versions|^version"
```

You should see `versions : * 5.2.0` instead of `4.4.16`.

## Alternative: Force update (if above doesn't work)
```bash
/opt/homebrew/Cellar/php@8.1/*/bin/php /opt/homebrew/bin/composer update cakephp/cakephp:5.2.0 --with-dependencies --no-interaction --prefer-dist
```



