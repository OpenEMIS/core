<?php

namespace Tests\Feature\Alerts;

/**
 * POCOR-9509: Smoke test for alerts:license-validity
 *
 * Verifies the command is registered, prepareContext() succeeds with a seeded
 * rule, and the command exits 0 gracefully when staff_licenses has no records
 * expiring within the threshold window.
 */
class AlertLicenseValidityCommandTest extends ScheduledAlertCommandTestCase
{
    protected function ruleId(): int        { return 9007; }
    protected function feature(): string    { return 'LicenseValidity'; }
    protected function artisanCommand(): string { return 'alerts:license-validity'; }
    protected function thresholdJson(): string  { return '{"value":30,"license_type":1,"condition":1}'; }

    /** @test */
    public function command_runs_cleanly_with_no_expiring_licenses(): void
    {
        $this->runAlertCommand()->assertExitCode(0);
        $this->assertNoQueueRowsForFeature();
    }
}
