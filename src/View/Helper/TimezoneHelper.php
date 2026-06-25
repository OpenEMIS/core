<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\View\Helper;
use DateTimeInterface;
use DateTimeZone;

/**
 * TimezoneHelper
 *
 * Helper to format UTC datetime values into the configured display timezone.
 *
 * @author Ehteram Ahmad <ehteram.ahmad@dataforall.org>
 */
class TimezoneHelper extends Helper
{
    /**
     * @param \DateTimeInterface|string|int|null $value Value stored as UTC (or parseable as such).
     * @param string|int|array|null $format Intl format for i18nFormat(); default short date + time.
     */
    public function format(\DateTimeInterface|string|int|null $value, string|int|array|null $format = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $displayTz = Configure::read('App.displayTimezone') ?? 'UTC';
        $target = new DateTimeZone($displayTz);

        $t = $this->toUtcFrozenTime($value);
        if ($t === null) {
            return '';
        }

        $out = $t->setTimezone($target);
        if ($format !== null) {
            return (string)$out->i18nFormat($format);
        }

        return (string)$out->i18nFormat();
    }

    /**
     * Alias: convert a UTC instant to FrozenTime in the display timezone.
     */
    public function utcToDisplay(\DateTimeInterface|string|int|null $value): ?FrozenTime
    {
        if ($value === null || $value === '') {
            return null;
        }

        $displayTz = Configure::read('App.displayTimezone') ?? 'UTC';
        $target = new DateTimeZone($displayTz);
        $t = $this->toUtcFrozenTime($value);
        if ($t === null) {
            return null;
        }

        return $t->setTimezone($target);
    }

    /**
     * Normalize input to a FrozenTime in UTC (storage convention).
     */
    private function toUtcFrozenTime(\DateTimeInterface|string|int $value): ?FrozenTime
    {
        $utc = new DateTimeZone('UTC');
        try {
            if ($value instanceof DateTimeInterface) {
                return FrozenTime::createFromTimestamp($value->getTimestamp(), $utc);
            }
            if (is_int($value)) {
                return FrozenTime::createFromTimestamp($value, $utc);
            }

            return FrozenTime::parse((string)$value, $utc);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
