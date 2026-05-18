<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertStaffEmploymentCommand
 *
 * Sends alerts for staff employment status milestones (X days before/after status date).
 *
 * Usage:
 *   php artisan alerts:staff-employment
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *
 * @package App\Console\Commands\Alerts
 */
class AlertStaffEmploymentCommand extends AlertCommandBase
{
    const CONDITION_DAYS_BEFORE = 1;
    const CONDITION_DAYS_AFTER = 2;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:staff-employment
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for staff employment status milestones (Laravel port)';

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

        return $this->runFeatureAlert('StaffEmployment');
    }

    /**
     * POCOR-9509: Get pending employment status records to alert on
     *
     * Queries staff_employment_statuses for records X days before/after status_date.
     *
     * @param string $featureKey Feature identifier
     * @return array List of employment status data items
     */
    protected function getPendingItems(string $featureKey): array
    {
        // Parse threshold
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $value = (int) ($threshold['value'] ?? 0);
        $statusTypeId = (int) ($threshold['employment_type'] ?? 0);
        $condition = (int) ($threshold['condition'] ?? 0);

        // Validate threshold
        if (!$statusTypeId || !$value || !in_array($condition, [self::CONDITION_DAYS_BEFORE, self::CONDITION_DAYS_AFTER], true)) {
            // $this->info("Invalid threshold configuration"); //POCOR-9509: commented out per CLAUDE.md
            return [];
        }

        // Build date condition
        $dateCondition = null;
        if ($condition === self::CONDITION_DAYS_BEFORE) {
            $dateCondition = "DATEDIFF(staff_employment_statuses.status_date, NOW()) BETWEEN 0 AND $value";
        } elseif ($condition === self::CONDITION_DAYS_AFTER) {
            $dateCondition = "DATEDIFF(NOW(), staff_employment_statuses.status_date) BETWEEN 0 AND $value";
        }

        if (!$dateCondition) {
            return [];
        }

        // Check if user is super admin
        $user = DB::table('security_users')->where('id', $this->userId)->first();
        $isSuperAdmin = $user->super_admin ?? false;

        // Build query
        $query = DB::table('staff_employment_statuses')
            ->join('security_users as Users', 'Users.id', '=', 'staff_employment_statuses.staff_id')
            ->join('employment_status_types as EmploymentStatusTypes', 'EmploymentStatusTypes.id', '=', 'staff_employment_statuses.status_type_id')
            ->join('institution_staff as InstitutionStaff', 'InstitutionStaff.staff_id', '=', 'staff_employment_statuses.staff_id')
            ->join('institutions as Institutions', 'Institutions.id', '=', 'InstitutionStaff.institution_id')
            ->join('staff_statuses as StaffStatuses', 'StaffStatuses.id', '=', 'InstitutionStaff.staff_status_id')
            ->where('staff_employment_statuses.status_type_id', $statusTypeId)
            ->whereNotNull('staff_employment_statuses.status_date')
            ->whereRaw($dateCondition)
            ->where('StaffStatuses.code', 'ASSIGNED')
            ->distinct()
            ->select([
                'staff_employment_statuses.id',
                'staff_employment_statuses.staff_id',
                'staff_employment_statuses.status_date',
                'InstitutionStaff.institution_id',
                'EmploymentStatusTypes.name as employment_type_name',
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

        // Filter by areas if not super admin
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

                $query->whereIn('InstitutionStaff.institution_id', $institutionIds);
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
     * POCOR-9509: Fill placeholders for staff employment alert
     *
     * @param array $item Employment status data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        $threshold = json_decode($this->rule->threshold ?? '{}', true);

        return [
            '${threshold.value}' => (string) ($threshold['value'] ?? ''),
            '${employment_type.name}' => $item['employment_type_name'] ?? '',
            '${employment_date}' => $item['status_date'] ?? '',
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
