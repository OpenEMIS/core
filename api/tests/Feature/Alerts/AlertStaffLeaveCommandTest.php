<?php

namespace Tests\Feature\Alerts;

/**
 * POCOR-9509: Smoke test for alerts:staff-leave
 *
 * Verifies the command is registered, prepareContext() succeeds with a seeded
 * rule, and the command exits 0 gracefully when institution_staff_leave has no
 * records ending on the target date.
 */
class AlertStaffLeaveCommandTest extends ScheduledAlertCommandTestCase
{
    protected function ruleId(): int        { return 9003; }
    protected function feature(): string    { return 'StaffLeave'; }
    protected function artisanCommand(): string { return 'alerts:staff-leave'; }
    protected function thresholdJson(): string  { return '{"value":3,"staff_leave_type":1}'; }

    /** @test */
    public function command_runs_cleanly_with_no_matching_staff_leave_data(): void
    {
        $this->runAlertCommand()->assertExitCode(0);
        $this->assertNoQueueRowsForFeature();
    }
}
