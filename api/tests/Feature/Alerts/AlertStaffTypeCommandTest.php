<?php

namespace Tests\Feature\Alerts;

/**
 * POCOR-9509: Smoke test for alerts:staff-type
 *
 * Verifies the command is registered, prepareContext() succeeds with a seeded
 * rule, and the command exits 0 gracefully when institution_staff has no
 * records matching the staff type threshold window.
 */
class AlertStaffTypeCommandTest extends ScheduledAlertCommandTestCase
{
    protected function ruleId(): int        { return 9004; }
    protected function feature(): string    { return 'StaffType'; }
    protected function artisanCommand(): string { return 'alerts:staff-type'; }
    protected function thresholdJson(): string  { return '{"value":7,"staff_type":1,"condition":1}'; }

    /** @test */
    public function command_runs_cleanly_with_no_matching_staff_type_data(): void
    {
        $this->runAlertCommand()->assertExitCode(0);
        $this->assertNoQueueRowsForFeature();
    }
}
