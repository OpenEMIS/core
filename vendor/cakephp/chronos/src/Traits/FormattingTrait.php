<?php
/**
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @copyright     Copyright (c) Brian Nesbitt <brian@nesbot.com>
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Chronos\Traits;

use Cake\Chronos\ChronosInterface;
use DateTime;

/**
 * Provides string formatting methods for datetime instances.
 *
 * Expects implementing classes to define static::$toStringFormat
 */
trait FormattingTrait
{
    /**
     * Reset the format used to the default when type juggling a ChronosInterface instance to a string
     *
     * @return void
     */
    public static function resetToStringFormat()
    {
        static::setToStringFormat(ChronosInterface::DEFAULT_TO_STRING_FORMAT);
    }

    /**
     * Set the default format used when type juggling a ChronosInterface instance to a string
     *
     * @param string $format The format to use in future __toString() calls.
     * @return void
     */
    public static function setToStringFormat($format)
    {
        static::$toStringFormat = $format;
    }

    /**
     * Format the instance as a string using the set format
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format(static::$toStringFormat);
    }

    /**
     * Format the instance as date
     *
     * @return string
     */
    public function toDateString()
    {
        return $this->format('Y-m-d');
    }

    /**
     * Format the instance as a readable date
     *
     * @return string
     */
    public function toFormattedDateString()
    {
        return $this->format('M j, Y');
    }

    /**
     * Format the instance as time
     *
     * @return string
     */
    public function toTimeString()
    {
        return $this->format('H:i:s');
    }

    /**
     * Format the instance as date and time
     *
     * @return string
     */
    public function toDateTimeString()
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * Format the instance with day, date and time
     *
     * @return string
     */
    public function toDayDateTimeString()
    {
        return $this->format('D, M j, Y g:i A');
    }

    /**
     * Format the instance as ATOM
     *
     * @return string
     */
    public function toAtomString()
    {
        return $this->format(DateTime::ATOM);
    }

    /**
     * Format the instance as COOKIE
     *
     * @return string
     */
    public function toCookieString()
    {
        return $this->format(DateTime::COOKIE);
    }

    /**
     * Format the instance as ISO8601
     *
     * @return string
     */
    public function toIso8601String()
    {
        return $this->format(DateTime::ATOM);
    }

    /**
     * Format the instance as RFC822
     *
     * @return string
     */
    public function toRfc822String()
    {
        return $this->format(DateTime::RFC822);
    }

    /**
     * Format the instance as RFC850
     *
     * @return string
     */
    public function toRfc850String()
    {
        return $this->format(DateTime::RFC850);
    }

    /**
     * Format the instance as RFC1036
     *
     * @return string
     */
    public function toRfc1036String()
    {
        return $this->format(DateTime::RFC1036);
    }

    /**
     * Format the instance as RFC1123
     *
     * @return string
     */
    public function toRfc1123String()
    {
        return $this->format(DateTime::RFC1123);
    }

    /**
     * Format the instance as RFC2822
     *
     * @return string
     */
    public function toRfc2822String()
    {
        return $this->format(DateTime::RFC2822);
    }

    /**
     * Format the instance as RFC3339
     *
     * @return string
     */
    public function toRfc3339String()
    {
        return $this->format(DateTime::RFC3339);
    }

    /**
     * Format the instance as RSS
     *
     * @return string
     */
    public function toRssString()
    {
        return $this->format(DateTime::RSS);
    }

    /**
     * Format the instance as W3C
     *
     * @return string
     */
    public function toW3cString()
    {
        return $this->format(DateTime::W3C);
    }

    /**
     * Returns a UNIX timestamp.
     *
     * @return string UNIX timestamp
     */
    public function toUnixString()
    {
        return $this->format('U');
    }

    /**
     * Returns the quarter
     *
     * @param bool $range Range.
     * @return int|array 1, 2, 3, or 4 quarter of year or array if $range true
     */
    public function toQuarter($range = false)
    {
        $quarter = ceil($this->format('m') / 3);
        if ($range === false) {
            return $quarter;
        }

        $year = $this->format('Y');
        switch ($quarter) {
            case 1:
                return [$year . '-01-01', $year . '-03-31'];
            case 2:
                return [$year . '-04-01', $year . '-06-30'];
            case 3:
                return [$year . '-07-01', $year . '-09-30'];
            case 4:
                return [$year . '-10-01', $year . '-12-31'];
        }
    }

    /**
     * @return int
     */
    public function toWeek()
    {
        return (int)$this->format('W');
    }
}
