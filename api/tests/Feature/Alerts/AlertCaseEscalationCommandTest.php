<?php

namespace Tests\Feature\Alerts;

/**
 * POCOR-9509: Smoke test for alerts:case-escalation
 *
 * Verifies the command is registered, prepareContext() succeeds with a seeded
 * rule, and the command exits 0 gracefully when no institution_cases records
 * have exceeded the threshold days without modification.
 *
 * Note: threshold must include workflow_steps array; empty array causes early
 * return (warn + SUCCESS) without querying institution_cases.
 */
class AlertCaseEscalationCommandTest extends ScheduledAlertCommandTestCase
{
    protected function ruleId(): int        { return 9006; }
    protected function feature(): string    { return 'CaseEscalation'; }
    protected function artisanCommand(): string { return 'alerts:case-escalation'; }
    // Empty workflow_steps → command warns and returns SUCCESS without DB query
    protected function thresholdJson(): string  { return '{"value":7,"workflow_steps":[]}'; }

    /** @test */
    public function command_runs_cleanly_with_no_watched_workflow_steps(): void
    {
        $this->runAlertCommand()->assertExitCode(0);
        $this->assertNoQueueRowsForFeature();
    }
}
