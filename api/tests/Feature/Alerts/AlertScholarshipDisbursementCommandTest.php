<?php

namespace Tests\Feature\Alerts;

/**
 * POCOR-9509: Smoke test for alerts:scholarship-disbursement
 *
 * Verifies the command is registered, prepareContext() succeeds with a seeded
 * rule, and the command exits 0 gracefully when no scholarship disbursements
 * are due within the threshold window.
 */
class AlertScholarshipDisbursementCommandTest extends ScheduledAlertCommandTestCase
{
    protected function ruleId(): int        { return 9010; }
    protected function feature(): string    { return 'ScholarshipDisbursement'; }
    protected function artisanCommand(): string { return 'alerts:scholarship-disbursement'; }
    protected function thresholdJson(): string  { return '{"value":7,"condition":1}'; }

    /** @test */
    public function command_runs_cleanly_with_no_disbursements_due(): void
    {
        $this->runAlertCommand()->assertExitCode(0);
        $this->assertNoQueueRowsForFeature();
    }
}
