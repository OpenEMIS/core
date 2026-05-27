<?php
namespace Institution\Model\Traits;

use Cake\I18n\FrozenTime;
use Cake\ORM\ResultSet;

/**
 * StaleProfileBannerTrait
 *
 * Reusable stale-profile alert banner for all four profile table types
 * (Institution, Staff, Student, Class). POCOR-9593.
 *
 * Usage:
 *   use StaleProfileBannerTrait;
 *
 * Then in indexAfterAction (after POCOR-9598 window check):
 *   $this->showStaleProfileBanner($data, $templateEntity, $completedOnField, $statusField);
 */
trait StaleProfileBannerTrait
{
    /**
     * Returns true if today falls within the template's generation window.
     * A template with no dates set is always considered open.
     *
     * @param mixed $template  ORM entity with generate_start_date / generate_end_date
     * @return bool
     */
    private function isGenerationWindowOpen($template): bool //POCOR-9593
    {
        $startDate = !empty($template->generate_start_date) ? $template->generate_start_date->format('Y-m-d') : null;
        $endDate   = !empty($template->generate_end_date)   ? $template->generate_end_date->format('Y-m-d')   : null;

        if (is_null($startDate) && is_null($endDate)) {
            return true; // no restriction — always open
        }
        if (is_null($startDate) || is_null($endDate)) {
            return false; // only one date set — treat as closed
        }
        $today = FrozenTime::now()->format('Y-m-d');
        return $today >= $startDate && $today <= $endDate;
    }

    /**
     * Scans $data for rows that are Generated/Published and ≥ 30 days old.
     * Returns ['maxDays' => int, 'staleCount' => int].
     *
     * @param ResultSet $data
     * @param string    $completedOnField  Entity property name for completed_on (e.g. 'report_card_completed_on')
     * @param string    $statusField       Entity property name for status     (e.g. 'report_card_status')
     * @return array{maxDays: int, staleCount: int}
     */
    private function getStaleProfileStats(ResultSet $data, string $completedOnField, string $statusField): array //POCOR-9593
    {
        $maxDays    = 0;
        $staleCount = 0;
        $now        = FrozenTime::now();

        foreach ($data as $row) {
            if (!$row->has($completedOnField) || empty($row->$completedOnField)) {
                continue;
            }
            $status = $row->has($statusField) ? $row->$statusField : self::NEW_REPORT;
            if (!in_array($status, [self::GENERATED, self::PUBLISHED])) {
                continue;
            }
            $completed = new FrozenTime($row->$completedOnField);
            $days      = $now->greaterThan($completed) ? (int) $now->diffInDays($completed) : 0;

            if ($days >= 30) {
                $staleCount++;
            }
            if ($days > $maxDays) {
                $maxDays = $days;
            }
        }

        return ['maxDays' => $maxDays, 'staleCount' => $staleCount];
    }

    /**
     * Shows the appropriate stale-profile alert banner.
     * Must be called AFTER any POCOR-9598 "not enabled" alert so it takes precedence.
     *
     * Banner logic:
     *  - Window open  + stale rows ≥ 30d → regenerate prompt (singular or plural)
     *  - Window closed + stale rows ≥ 30d → combined not-enabled + age note (singular or plural)
     *  - Window closed + no stale rows    → plain "not enabled" (InstitutionsProfileTable only;
     *                                        Student/Staff/Classes already handle this via POCOR-9598)
     *  - Window open  + no stale rows     → no banner
     *
     * @param ResultSet $data
     * @param mixed     $templateEntity   ORM entity for the selected profile template
     * @param string    $completedOnField Entity property name for completed_on
     * @param string    $statusField      Entity property name for status
     * @param bool      $showNotEnabledFallback  Set true for tables that have no separate POCOR-9598 alert (InstitutionsProfileTable)
     */
    private function showStaleProfileBanner(ResultSet $data, $templateEntity, string $completedOnField, string $statusField, bool $showNotEnabledFallback = false): void //POCOR-9593
    {
        if (empty($templateEntity)) {
            return;
        }

        $windowOpen = $this->isGenerationWindowOpen($templateEntity);
        $stats      = $this->getStaleProfileStats($data, $completedOnField, $statusField);
        $maxDays    = $stats['maxDays'];
        $staleCount = $stats['staleCount'];
        if ($maxDays >= 30 && $windowOpen) {
            $message = ($staleCount === 1)
                ? sprintf(__('This report was generated %d days ago. To ensure this report reflects the most recent data updates, please regenerate the report before viewing or downloading.'), $maxDays)
                : sprintf(__('There are reports generated up to %d days ago. To ensure these reports reflect the most recent data updates, please regenerate the reports before viewing or downloading.'), $maxDays);
            $this->Alert->warning($message, ['type' => 'string', 'reset' => true]);

        } elseif ($maxDays >= 30 && !$windowOpen) {
            $message = ($staleCount === 1)
                ? sprintf(__('This profile template generation is not enabled. Consult with system administrator to check the dates. Note: this report is %d days old and may not reflect the most recent data.'), $maxDays)
                : sprintf(__('This profile template generation is not enabled. Consult with system administrator to check the dates. Note: some reports are up to %d days old and may not reflect the most recent data.'), $maxDays);
            $this->Alert->warning($message, ['type' => 'string', 'reset' => true]);

        } elseif ($showNotEnabledFallback && !$windowOpen) {
            $this->Alert->warning(
                __('This profile template generation is not enabled. Consult with system administrator to check the dates.'),
                ['type' => 'string', 'reset' => true]
            );
        }
    }
}
