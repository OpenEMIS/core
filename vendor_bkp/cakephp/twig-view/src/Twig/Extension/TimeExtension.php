<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * Copyright (c) 2014 Cees-Jan Kiewiet
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\TwigView\Twig\Extension;

use Cake\Chronos\Chronos;
use Cake\Chronos\ChronosDate;
use Cake\I18n\DateTime;
use DateTimeZone;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class TimeExtension.
 */
class TimeExtension extends AbstractExtension
{
    private ?CoreExtension $coreExt;

    /**
     * Get declared filters.
     *
     * @return array<\Twig\TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('date', [$this, 'formatDate']),
        ];
    }

    /**
     * Get declared functions.
     *
     * @return array<\Twig\TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('date', function ($time = null, $timezone = null) {
                return new DateTime($time, $timezone);
            }),
            new TwigFunction('time', function ($time = null, $timezone = null) {
                return new DateTime($time, $timezone);
            }),
            new TwigFunction('timezones', 'Cake\I18n\DateTime::listTimezones'),
        ];
    }

    /**
     * Format a date/datetime value
     *
     * Includes shims for \Chronos\ChronosDate as Twig doesn't.
     *
     * @param mixed $date The date to format.
     * @param ?string $format The format to use, null to use the default.
     * @param \DateTimeZone|string|false|null $timezone The target timezone, null to use system.
     */
    public function formatDate(
        mixed $date,
        ?string $format = null,
        DateTimeZone|string|false|null $timezone = null
    ): string {
        if (!isset($this->coreExt)) {
            $this->coreExt = new CoreExtension();
        }
        if ($date instanceof ChronosDate) {
            $date = $date->toDateString();
        }
        if ($date instanceof Chronos) {
            $date = $date->toIso8601String();
        }

        return $this->coreExt->formatDate($date, $format, $timezone);
    }
}
