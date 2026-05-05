<?php

namespace Tests\Feature\Alerts;

/**
 * POCOR-9509: Smoke test for alerts:license-renewal
 *
 * Verifies the command is registered, prepareContext() succeeds with a seeded
 * rule, and the command exits 0 gracefully when no staff licenses are due for
 * renewal within the threshold window.
 */
class AlertLicenseRenewalCommandTest extends ScheduledAlertCommandTestCase
{
    protected function ruleId(): int        { return 9008; }
    protected function feature(): string    { return 'LicenseRenewal'; }
    protected function artisanCommand(): string { return 'alerts:license-renewal'; }
    protected function thresholdJson(): string  { return '{"value":30,"license_type":1,"condition":1}'; }

    /** @test */
    public function command_runs_cleanly_with_no_licenses_due_for_renewal(): void
    {
        $this->runAlertCommand()->assertExitCode(0);
        $this->assertNoQueueRowsForFeature();
    }
}
