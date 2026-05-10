<?php
declare(strict_types=1);

namespace App\Utility;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * ApplicationTimezone
 *
 * Loads the organization "display" timezone from config (cached) while PHP default remains UTC.
 * Database values should be stored in UTC; use TimezoneHelper or utcToDisplay() for output formatting.
 *
 * @author Ehteram Ahmad <ehteram.ahmad@dataforall.org>
 */
class ApplicationTimezone
{
    public const CACHE_KEY = 'display_timezone_identifier';

    public const CACHE_CONFIG = 'app_config';

    /**
     * Read Time Zone from config_items (via cache) and expose as Configure::read('App.displayTimezone').
     * Does not call date_default_timezone_set() — PHP remains on UTC from bootstrap.
     */
    public static function registerDisplayTimezone(): void
    {
        $cached = Cache::read(self::CACHE_KEY, self::CACHE_CONFIG);
        if ($cached !== false && is_string($cached) && $cached !== '') {
            $tz = self::normalizeTimezoneIdentifier($cached);
            Configure::write('App.displayTimezone', $tz);
            return;
        }

        try {
            $ConfigItemTable = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
            $row = $ConfigItemTable
                ->find()
                ->select(['value'])
                ->where([$ConfigItemTable->aliasField('name') => 'Time Zone'])
                ->first();

            $raw = $row && $row->value !== null && $row->value !== ''
                ? (string)$row->value
                : (string)(Configure::read('App.displayTimezoneFallback') ?? 'UTC');
            $tz = self::normalizeTimezoneIdentifier($raw);
            Cache::write(self::CACHE_KEY, $tz, self::CACHE_CONFIG);
            Configure::write('App.displayTimezone', $tz);
        } catch (\Throwable $e) {
            $fallback = self::normalizeTimezoneIdentifier(
                (string)(Configure::read('App.displayTimezoneFallback') ?? 'UTC')
            );
            Configure::write('App.displayTimezone', $fallback);
        }
    }

    public static function clearDisplayTimezoneCache(): void
    {
        Cache::delete(self::CACHE_KEY, self::CACHE_CONFIG);
        Configure::delete('App.displayTimezone');
    }

    /**
     * Return configured display timezone (loads cache/db on first call).
     */
    public static function getDisplayTimezone(): string
    {
        $configured = Configure::read('App.displayTimezone');
        if (is_string($configured) && $configured !== '') {
            return self::normalizeTimezoneIdentifier($configured);
        }

        self::registerDisplayTimezone();
        $configured = Configure::read('App.displayTimezone');
        if (is_string($configured) && $configured !== '') {
            return self::normalizeTimezoneIdentifier($configured);
        }

        return self::normalizeTimezoneIdentifier(
            (string)(Configure::read('App.displayTimezoneFallback') ?? 'UTC')
        );
    }

    public static function normalizeTimezoneIdentifier(string $identifier): string
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return 'UTC';
        }
        if (in_array($identifier, timezone_identifiers_list(), true)) {
            return $identifier;
        }

        return 'UTC';
    }
}
