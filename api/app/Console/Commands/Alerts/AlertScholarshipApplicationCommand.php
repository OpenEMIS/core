<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertScholarshipApplicationShell
 *
 * Sends alerts for scholarship applications whose scholarship's application_close_date
 * is within X days. The recipient is the application's assignee (security_user) directly —
 * NOT role-based.
 *
 * Threshold JSON format (stored in alert_rules.threshold):
 *   {"value": 7, "condition": 1, "category": "PENDING"}
 *   - value:     Days before application_close_date to fire the alert
 *   - condition: 1 = before close date
 *   - category:  Workflow step category to filter (e.g. "PENDING")
 *
 * Usage:
 *   php artisan alerts:scholarship-application
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *
 * @package App\Console\Commands\Alerts
 */
class AlertScholarshipApplicationCommand extends AlertCommandBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:scholarship-application
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for scholarship applications approaching close date (Laravel port)';

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

        return $this->runFeatureAlert('ScholarshipApplication');
    }

    /**
     * POCOR-9509: Override prepareContext to skip role requirement
     *
     * Scholarship application alerts are sent directly to the application's assignee,
     * not to role-based recipients. The parent check for non-empty security_roles is
     * therefore bypassed here, and roles are set to an empty array.
     *
     * @return bool True if context is valid
     */
    protected function prepareContext(): bool
    {
        //POCOR-9509: start - load rule without role validation (assignee-based, not role-based)
        $this->userId = (int) $this->option('user_id');
        $this->ruleId = (int) $this->option('rule_id');
        $this->processId = (int) $this->option('process_id');

        if (!$this->userId || !$this->ruleId) {
            $this->error("Missing required --user_id or --rule_id.");
            return false;
        }

        // Load alert rule
        $this->rule = DB::table('alert_rules')
            ->where('id', $this->ruleId)
            ->first();

        if (!$this->rule) {
            $this->error("Alert rule with ID {$this->ruleId} not found.");
            return false;
        }

        // POCOR-9509: Scholarship alerts send to the application assignee directly.
        // No security roles are needed; set to empty array so parent methods remain safe.
        $this->rule->security_roles = [];

        return true;
        //POCOR-9509: end
    }

    /**
     * POCOR-9509: Get pending scholarship application records to alert on
     *
     * Queries scholarship_applications joined to scholarships, applicants, assignees
     * and workflow_steps. Filters by:
     *   - scholarship.application_close_date = today + threshold.value days
     *   - workflow_steps.category = threshold.category
     *
     * @param string $featureKey Feature identifier
     * @return array List of scholarship application data items
     */
    protected function getPendingItems(string $featureKey): array
    {
        //POCOR-9509: start - parse threshold and calculate target close date
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $daysBefore = (int) ($threshold['value'] ?? 1);
        $category = $threshold['category'] ?? '';

        // Calculate the target application_close_date (today + daysBefore)
        $targetDate = Carbon::now()->addDays($daysBefore)->format('Y-m-d');
        //POCOR-9509: end

        //POCOR-9509: start - build query joining all required tables
        $query = DB::table('scholarship_applications as Applications')
            ->join('scholarships as Scholarships', 'Scholarships.id', '=', 'Applications.scholarship_id')
            ->join('security_users as Applicants', 'Applicants.id', '=', 'Applications.applicant_id')
            ->join('security_users as Assignees', 'Assignees.id', '=', 'Applications.assignee_id')
            ->join('workflow_steps as Statuses', 'Statuses.id', '=', 'Applications.status_id')
            ->where('Scholarships.application_close_date', $targetDate)
            ->select([
                'Applications.id',
                'Applications.applicant_id',
                'Applications.assignee_id',
                'Applications.scholarship_id',
                'Applications.status_id',
                // Scholarship fields
                'Scholarships.code as scholarship_code',
                'Scholarships.name as scholarship_name',
                'Scholarships.description as scholarship_description',
                'Scholarships.application_open_date as scholarship_application_open_date',
                'Scholarships.application_close_date as scholarship_application_close_date',
                'Scholarships.maximum_award_amount as scholarship_maximum_award_amount',
                'Scholarships.total_amount as scholarship_total_amount',
                'Scholarships.duration as scholarship_duration',
                'Scholarships.bond as scholarship_bond',
                // Applicant fields
                'Applicants.first_name as applicant_first_name',
                'Applicants.middle_name as applicant_middle_name',
                'Applicants.third_name as applicant_third_name',
                'Applicants.last_name as applicant_last_name',
                'Applicants.preferred_name as applicant_preferred_name',
                'Applicants.email as applicant_email',
                'Applicants.address as applicant_address',
                'Applicants.postal_code as applicant_postal_code',
                'Applicants.date_of_birth as applicant_date_of_birth',
                // Assignee fields (recipient)
                'Assignees.first_name as assignee_first_name',
                'Assignees.last_name as assignee_last_name',
                'Assignees.email as assignee_email',
                // Workflow step category
                'Statuses.category as status_category',
            ]);

        // POCOR-9509: Filter by workflow step category when specified in threshold
        if (!empty($category)) {
            $query->where('Statuses.category', $category);
        }
        //POCOR-9509: end

        return $query->get()->map(function ($item) {
            return (array) $item;
        })->toArray();
    }

    /**
     * POCOR-9509: Override resolveRecipients to send directly to the application's assignee
     *
     * The CakePHP shell resolved recipients as:
     *   $assigneeEntity = $this->Users->find()->where(['id' => $assigneeId])->first();
     *   $email = $assigneeEntity->email;
     *   $assigneeEmail = $name . ' <' . $email . '>';
     *
     * This override returns the assignee's email directly without role-based lookup.
     *
     * @param array $item Pending item data from getPendingItems()
     * @return array Contact list ['email' => [...], 'phone' => []]
     */
    protected function resolveRecipients(array $item): array
    {
        //POCOR-9509: start - build assignee email in "Name <email>" format
        $email = $item['assignee_email'] ?? '';
        $name = trim(($item['assignee_first_name'] ?? '') . ' ' . ($item['assignee_last_name'] ?? ''));

        if (empty($email)) {
            return ['email' => [], 'phone' => []];
        }

        // Format as "Full Name <email@example.com>" matching CakePHP shell behaviour
        $formatted = !empty($name) ? $name . ' <' . $email . '>' : $email;

        return ['email' => [$formatted], 'phone' => []];
        //POCOR-9509: end
    }

    /**
     * POCOR-9509: Fill placeholders for scholarship application alert
     *
     * @param array $item Scholarship application data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        //POCOR-9509: start - calculate day_difference between application_close_date and today
        $today = Carbon::now()->startOfDay();
        $closeDate = $item['scholarship_application_close_date'] ?? null;
        $dayDiff = '';
        if ($closeDate) {
            $dayDiff = (string) abs($today->diffInDays(Carbon::parse($closeDate)->startOfDay(), false));
        }

        $threshold = json_decode($this->rule->threshold ?? '{}', true);

        return [
            // Scholarship fields
            '${scholarship.code}'                  => $item['scholarship_code'] ?? '',
            '${scholarship.name}'                  => $item['scholarship_name'] ?? '',
            '${scholarship.description}'           => $item['scholarship_description'] ?? '',
            '${scholarship.application_close_date}' => $item['scholarship_application_close_date'] ?? '',
            '${scholarship.application_open_date}' => $item['scholarship_application_open_date'] ?? '',
            '${scholarship.maximum_award_amount}'  => (string) ($item['scholarship_maximum_award_amount'] ?? ''),
            '${scholarship.total_amount}'          => (string) ($item['scholarship_total_amount'] ?? ''),
            '${scholarship.duration}'              => (string) ($item['scholarship_duration'] ?? ''),
            '${scholarship.bond}'                  => (string) ($item['scholarship_bond'] ?? ''),
            // Applicant fields
            '${applicant.first_name}'              => $item['applicant_first_name'] ?? '',
            '${applicant.middle_name}'             => $item['applicant_middle_name'] ?? '',
            '${applicant.third_name}'              => $item['applicant_third_name'] ?? '',
            '${applicant.last_name}'               => $item['applicant_last_name'] ?? '',
            '${applicant.preferred_name}'          => $item['applicant_preferred_name'] ?? '',
            '${applicant.email}'                   => $item['applicant_email'] ?? '',
            '${applicant.address}'                 => $item['applicant_address'] ?? '',
            '${applicant.postal_code}'             => $item['applicant_postal_code'] ?? '',
            '${applicant.date_of_birth}'           => $item['applicant_date_of_birth'] ?? '',
            // Assignee fields
            '${assignee.name}'                     => trim(($item['assignee_first_name'] ?? '') . ' ' . ($item['assignee_last_name'] ?? '')),
            '${assignee.email}'                    => $item['assignee_email'] ?? '',
            // Computed fields
            '${day_difference}'                    => $dayDiff,
            '${threshold.value}'                   => (string) ($threshold['value'] ?? ''),
        ];
        //POCOR-9509: end
    }
}
