<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertCaseEscalationShell
 *
 * Sends alerts for institution cases that have been open (unmodified) longer than
 * the configured threshold (in days) and are still in a watched workflow step.
 *
 * Threshold JSON format (stored in alert_rules.threshold):
 *   {"value": 7, "workflow_steps": [1, 2, 3]}
 *   - value:          Number of days since creation before escalation fires
 *   - workflow_steps: Array of workflow_steps.id values to monitor (e.g. "Open")
 *
 * Usage:
 *   php artisan alerts:case-escalation
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *
 * @package App\Console\Commands\Alerts
 */
class AlertCaseEscalationCommand extends AlertCommandBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:case-escalation
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for institution cases that have escalated past their threshold (Laravel port)';

    /**
     * POCOR-9509: Execute the console command
     *
     * @return int
     */
    public function handle(): int
    {
        if (!$this->prepareContext()) {
            return self::FAILURE;
        }

        return $this->runFeatureAlert('CaseEscalation');
    }

    /**
     * POCOR-9509: Get escalated institution cases to alert on
     *
     * Mirrors CakePHP InstitutionCasesTable::getModelAlertData():
     * - Cases older than threshold.value days (by created date)
     * - Still in one of the watched workflow steps (threshold.workflow_steps)
     * - Never modified (modified IS NULL AND modified_user_id IS NULL)
     *
     * @param string $featureKey Feature identifier
     * @return array List of case data items
     */
    protected function getPendingItems(string $featureKey): array
    {
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $daysBefore = (int) ($threshold['value'] ?? 1);
        $watchedStepIds = $threshold['workflow_steps'] ?? [];

        if (empty($watchedStepIds)) {
            $this->warn("No workflow_steps defined in threshold for rule ID {$this->ruleId}. Skipping.");
            return [];
        }

        // POCOR-9509: Find cases older than X days, in a watched step, never touched
        $cases = DB::table('institution_cases as Cases')
            ->join('workflow_steps as Statuses', 'Statuses.id', '=', 'Cases.status_id')
            ->join('institutions as Institutions', 'Institutions.id', '=', 'Cases.institution_id')
            ->join('security_users as Assignees', 'Assignees.id', '=', 'Cases.assignee_id')
            ->join('case_types as CaseTypes', 'CaseTypes.id', '=', 'Cases.case_type_id')
            ->join('case_priorities as CasePriorities', 'CasePriorities.id', '=', 'Cases.case_priority_id')
            ->whereIn('Cases.status_id', $watchedStepIds)
            ->whereNull('Cases.modified')
            ->whereNull('Cases.modified_user_id')
            ->whereRaw("DATEDIFF(NOW(), Cases.created) > ?", [$daysBefore])
            ->select([
                'Cases.id',
                'Cases.case_number',
                'Cases.title',
                'Cases.description',
                'Cases.created',
                'Cases.institution_id',
                'Cases.assignee_id',
                'Statuses.name as status_name',
                'CaseTypes.name as case_type_name',
                'CasePriorities.name as case_priority_name',
                'Assignees.first_name as assignee_first_name',
                'Assignees.middle_name as assignee_middle_name',
                'Assignees.last_name as assignee_last_name',
                'Assignees.email as assignee_email',
                'Assignees.openemis_no as assignee_openemis_no',
                'Institutions.name as institution_name',
                'Institutions.code as institution_code',
                'Institutions.address as institution_address',
                'Institutions.postal_code as institution_postal_code',
                'Institutions.contact_person as institution_contact_person',
                'Institutions.telephone as institution_telephone',
                'Institutions.email as institution_email',
                'Institutions.website as institution_website',
            ])
            ->get();

        if ($cases->isEmpty()) {
            return [];
        }

        return $cases->map(function ($case) use ($daysBefore) {
            $item = (array) $case;
            $item['days_open'] = (int) Carbon::parse($case->created)->diffInDays(Carbon::now());
            $item['threshold_days'] = $daysBefore;
            return $item;
        })->toArray();
    }

    /**
     * POCOR-9509: Fill placeholders for case escalation alert
     *
     * Maps case data to ${placeholder} => value array.
     *
     * @param array $item Case data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        // Log::debug("Placeholders sent at " . json_encode($item)); //POCOR-9509: commented out — full item dump not needed in production
        return [
            // Case fields
            '${case.case_number}'      => $item['case_number'] ?? '',
            '${case.title}'            => $item['title'] ?? '',
            '${case.description}'      => $item['description'] ?? '',
            '${case.created}'          => $item['created'] ?? '',
            '${case.status}'           => $item['status_name'] ?? '',
            '${case.type}'             => $item['case_type_name'] ?? '',
            '${case.priority}'         => $item['case_priority_name'] ?? '',
            '${days_open}'             => (string) ($item['days_open'] ?? 0),
            '${threshold.value}'       => (string) ($threshold['value'] ?? ''),
            // Assignee fields
            '${assignee.openemis_no}'  => $item['assignee_openemis_no'] ?? '',
            '${assignee.first_name}'   => $item['assignee_first_name'] ?? '',
            '${assignee.middle_name}'  => $item['assignee_middle_name'] ?? '',
            '${assignee.last_name}'    => $item['assignee_last_name'] ?? '',
            '${assignee.name}'         => trim(($item['assignee_first_name'] ?? '') . ' ' . ($item['assignee_last_name'] ?? '')),
            '${assignee.email}'        => $item['assignee_email'] ?? '',
            // Institution fields
            '${institution.name}'      => $item['institution_name'] ?? '',
            '${institution.code}'      => $item['institution_code'] ?? '',
            '${institution.address}'   => $item['institution_address'] ?? '',
            '${institution.postal_code}' => $item['institution_postal_code'] ?? '',
            '${institution.contact_person}' => $item['institution_contact_person'] ?? '',
            '${institution.telephone}' => $item['institution_telephone'] ?? '',
            '${institution.email}'     => $item['institution_email'] ?? '',
            '${institution.website}'   => $item['institution_website'] ?? '',
        ];
    }
}
