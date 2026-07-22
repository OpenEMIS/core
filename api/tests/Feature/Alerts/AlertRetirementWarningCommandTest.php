<?php

namespace Tests\Feature\Alerts;

/**
 * POCOR-9509: Smoke test for alerts:retirement-warning
 *
 * Verifies the command is registered, prepareContext() succeeds with a seeded
 * rule, and the command exits 0 gracefully when institution_staff has no
 * matching retirement-age records.
 */
class AlertRetirementWarningCommandTest extends ScheduledAlertCommandTestCase
{
    protected function ruleId(): int        { return 9001; }
    protected function feature(): string    { return 'RetirementWarning'; }
    protected function artisanCommand(): string { return 'alerts:retirement-warning'; }
    protected function thresholdJson(): string  { return '{"value":60}'; }

    /** @test */
    public function command_runs_cleanly_with_no_matching_retirement_data(): void
    {
        $this->runAlertCommand()->assertExitCode(0);
        $this->assertNoQueueRowsForFeature();
    }
}
