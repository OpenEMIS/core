<?php

namespace Tests\Feature\Alerts;

/**
 * POCOR-9509: Smoke test for alerts:scholarship-application
 *
 * Verifies the command is registered, prepareContext() succeeds with a seeded
 * rule, and the command exits 0 gracefully when no scholarship applications
 * are approaching their close date within the threshold window.
 */
class AlertScholarshipApplicationCommandTest extends ScheduledAlertCommandTestCase
{
    protected function ruleId(): int        { return 9009; }
    protected function feature(): string    { return 'ScholarshipApplication'; }
    protected function artisanCommand(): string { return 'alerts:scholarship-application'; }
    protected function thresholdJson(): string  { return '{"value":7,"condition":1,"category":"PENDING"}'; }

    /** @test */
    public function command_runs_cleanly_with_no_applications_approaching_close_date(): void
    {
        $this->runAlertCommand()->assertExitCode(0);
        $this->assertNoQueueRowsForFeature();
    }
}
