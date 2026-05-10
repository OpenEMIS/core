<?php

namespace Tests\Feature\Alerts;

use Illuminate\Support\Facades\DB;

/**
 * POCOR-9509: Smoke test for alerts:system-updates
 *
 * Verifies the command is registered, prepareContext() succeeds with a seeded
 * rule, and the command exits 0 gracefully when version_api_domain is not
 * configured (no external HTTP call attempted).
 */
class AlertSystemUpdatesCommandTest extends ScheduledAlertCommandTestCase
{
    protected function ruleId(): int        { return 9005; }
    protected function feature(): string    { return 'SystemUpdates'; }
    protected function artisanCommand(): string { return 'alerts:system-updates'; }
    protected function thresholdJson(): string  { return '{}'; }

    /** @test */
    public function command_runs_cleanly_without_version_api_domain_configured(): void
    {
        $this->runAlertCommand()->assertExitCode(0);
        $this->assertNoQueueRowsForFeature();
    }
}
