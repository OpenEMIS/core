<?php

namespace Tests\Feature\Alerts;

/**
 * POCOR-9509: Smoke test for alerts:staff-employment
 *
 * Verifies the command is registered, prepareContext() succeeds with a seeded
 * rule, and the command exits 0 gracefully when institution_staff_employment_statuses
 * has no matching records within the threshold window.
 */
class AlertStaffEmploymentCommandTest extends ScheduledAlertCommandTestCase
{
    protected function ruleId(): int        { return 9002; }
    protected function feature(): string    { return 'StaffEmployment'; }
    protected function artisanCommand(): string { return 'alerts:staff-employment'; }
    protected function thresholdJson(): string  { return '{"value":7,"employment_type":1,"condition":1}'; }

    /** @test */
    public function command_runs_cleanly_with_no_matching_staff_employment_data(): void
    {
        $this->runAlertCommand()->assertExitCode(0);
        $this->assertNoQueueRowsForFeature();
    }
}
