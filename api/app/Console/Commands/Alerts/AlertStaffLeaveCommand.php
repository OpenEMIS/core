<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertStaffLeaveCommand
 *
 * Sends alerts for staff leave reminders X days before leave end date.
 *
 * Usage:
 *   php artisan alerts:staff-leave
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *
 * @package App\Console\Commands\Alerts
 */
class AlertStaffLeaveCommand extends AlertCommandBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:staff-leave
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for staff leave reminders (Laravel port)';

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

        return $this->runFeatureAlert('StaffLeave');
    }

    /**
     * POCOR-9509: Get pending staff leave records to alert on
     *
     * Queries staff_leave for approved leaves ending X days from now.
     *
     * @param string $featureKey Feature identifier
     * @return array List of staff leave data items
     */
    protected function getPendingItems(string $featureKey): array
    {
        // Parse threshold (days before leave end)
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $daysBefore = (int) ($threshold['value'] ?? 1);
        $staffLeaveTypeId = (int) ($threshold['staff_leave_type'] ?? 0);

        // Calculate target date
        $targetDate = Carbon::now()->addDays($daysBefore)->format('Y-m-d');

        // Get approved workflow step IDs
        $approvedStepIds = $this->getApprovedStepIds();

        if (empty($approvedStepIds)) {
            // $this->info("No approved workflow steps found"); //POCOR-9509: commented out per CLAUDE.md
            return [];
        }

        // Check if user is super admin
        $user = DB::table('security_users')->where('id', $this->userId)->first();
        $isSuperAdmin = $user->super_admin ?? false;

        // Build query
        $query = DB::table('institution_staff_leave as StaffLeave')
            ->join('security_users as Users', 'Users.id', '=', 'StaffLeave.staff_id')
            ->join('institutions as Institutions', 'Institutions.id', '=', 'StaffLeave.institution_id')
            ->join('workflow_steps as Statuses', 'Statuses.id', '=', 'StaffLeave.status_id')
            ->join('staff_leave_types as StaffLeaveTypes', 'StaffLeaveTypes.id', '=', 'StaffLeave.staff_leave_type_id')
            ->whereIn('StaffLeave.status_id', $approvedStepIds)
            ->where('StaffLeave.date_to', $targetDate)
            ->where('StaffLeave.staff_leave_type_id', $staffLeaveTypeId)
            ->select([
                'StaffLeave.id',
                'StaffLeave.staff_id',
                'StaffLeave.institution_id',
                'StaffLeave.date_from',
                'StaffLeave.date_to',
                'StaffLeaveTypes.name as staff_leave_type_name',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name',
                'Users.email',
                'Users.address',
                'Users.postal_code',
                'Users.date_of_birth',
                'Institutions.name as institution_name',
                'Institutions.code as institution_code',
                'Institutions.address as institution_address',
                'Institutions.postal_code as institution_postal_code',
                'Institutions.contact_person as institution_contact_person',
                'Institutions.telephone as institution_telephone',
                'Institutions.email as institution_email',
                'Institutions.website as institution_website',
            ]);

        // Filter by institutions if not super admin
        if (!$isSuperAdmin) {
            $securityGroupIds = DB::table('security_group_users')
                ->where('security_user_id', $this->userId)
                ->pluck('security_group_id')
                ->toArray();

            if (!empty($securityGroupIds)) {
                $institutionIds = DB::table('security_group_institutions')
                    ->whereIn('security_group_id', $securityGroupIds)
                    ->pluck('institution_id')
                    ->toArray();

                $query->whereIn('StaffLeave.institution_id', $institutionIds);
            } else {
                // No institutions accessible
                return [];
            }
        }

        return $query->get()->map(function ($item) {
            return (array) $item;
        })->toArray();
    }

    /**
     * POCOR-9509: Get approved workflow step IDs for staff leave
     *
     * @return array List of workflow step IDs with "Approved" name
     */
    protected function getApprovedStepIds(): array
    {
        return DB::table('workflow_models')
            ->join('workflows', 'workflows.workflow_model_id', '=', 'workflow_models.id')
            ->join('workflow_steps', 'workflow_steps.workflow_id', '=', 'workflows.id')
            ->where('workflow_models.model', 'Institution.StaffLeave')
            ->where('workflow_steps.name', 'Approved')
            ->distinct()
            ->pluck('workflow_steps.id')
            ->toArray();
    }

    /**
     * POCOR-9509: Fill placeholders for staff leave alert
     *
     * @param array $item Staff leave data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        // POCOR-9509: Calculate day difference (normalized to start of day)
        $today = Carbon::now()->startOfDay();
        $leaveEndDate = $item['date_to'] ?? null;
        $dayDiff = '';
        if ($leaveEndDate) {
            $dayDiff = abs($today->diffInDays(Carbon::parse($leaveEndDate)->startOfDay(), false));
        }

        $threshold = json_decode($this->rule->threshold ?? '{}', true);

        return [
            '${threshold.value}' => (string) ($threshold['value'] ?? ''),
            '${staff_leave_type.name}' => $item['staff_leave_type_name'] ?? '',
            '${date_from}' => $item['date_from'] ?? '',
            '${date_to}' => $item['date_to'] ?? '',
            '${day_difference}' => (string) $dayDiff,
            '${employment_period}' => (string) $dayDiff,
            '${user.openemis_no}' => $item['openemis_no'] ?? '',
            '${user.first_name}' => $item['first_name'] ?? '',
            '${user.middle_name}' => $item['middle_name'] ?? '',
            '${user.third_name}' => $item['third_name'] ?? '',
            '${user.last_name}' => $item['last_name'] ?? '',
            '${user.preferred_name}' => $item['preferred_name'] ?? '',
            '${user.email}' => $item['email'] ?? '',
            '${user.address}' => $item['address'] ?? '',
            '${user.postal_code}' => $item['postal_code'] ?? '',
            '${user.date_of_birth}' => $item['date_of_birth'] ?? '',
            '${institution.name}' => $item['institution_name'] ?? '',
            '${institution.code}' => $item['institution_code'] ?? '',
            '${institution.address}' => $item['institution_address'] ?? '',
            '${institution.postal_code}' => $item['institution_postal_code'] ?? '',
            '${institution.contact_person}' => $item['institution_contact_person'] ?? '',
            '${institution.telephone}' => $item['institution_telephone'] ?? '',
            '${institution.email}' => $item['institution_email'] ?? '',
            '${institution.website}' => $item['institution_website'] ?? '',
        ];
    }
}
