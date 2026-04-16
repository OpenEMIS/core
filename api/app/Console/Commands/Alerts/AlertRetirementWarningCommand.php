<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertRetirementWarningCommand
 *
 * Sends alerts for staff approaching retirement age.
 *
 * Usage:
 *   php artisan alerts:retirement-warning
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *
 * @package App\Console\Commands\Alerts
 */
class AlertRetirementWarningCommand extends AlertCommandBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected  $signature = 'alerts:retirement-warning
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected  $description = 'POCOR-9509: Send alerts for staff approaching retirement (Laravel port)';

    /**
     * POCOR-9509: Execute the console command
     *
     * @return int
     */
    public function handle(): int
    {
        if (!$this->prepareContext()) { //POCOR-9509: self::FAILURE — Command class not imported here
            return self::FAILURE;
        }

        return $this->runFeatureAlert('RetirementWarning');
    }

    /**
     * POCOR-9509: Get pending retirement warnings
     *
     * Queries staff with date_of_birth indicating retirement age.
     *
     * @param string $featureKey Feature identifier
     * @return array List of staff approaching retirement
     */
    protected function getPendingItems(string $featureKey): array
    {
        // Parse threshold (default 60 years before retirement)
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $yearBefore = (int) ($threshold['value'] ?? 60);

        // Calculate target birthdate
        $targetDate = Carbon::now()->subYears($yearBefore)->format('Y-m-d');

        // Check if user is super admin
        $user = DB::table('security_users')->where('id', $this->userId)->first();
        $isSuperAdmin = $user->super_admin ?? false;

        // Build query conditions
        $query = DB::table('institution_staff as Staff')
            ->join('security_users as Users', 'Users.id', '=', 'Staff.staff_id')
            ->join('institutions as Institutions', 'Institutions.id', '=', 'Staff.institution_id')
            ->join('staff_statuses as StaffStatuses', 'StaffStatuses.id', '=', 'Staff.staff_status_id')
            ->where('Users.date_of_birth', '<=', $targetDate)
            ->where('StaffStatuses.code', 'ASSIGNED')
            ->select([
                'Staff.id',
                'Staff.staff_id as user_id',
                'Staff.institution_id',
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

                $query->whereIn('Staff.institution_id', $institutionIds);
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
     * POCOR-9509: Fill placeholders for retirement warning alert
     *
     * @param array $item Staff data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        // Calculate age
        $birthDate = $item['date_of_birth'] ?? null;
        $age = '';
        if ($birthDate) {
            $age = Carbon::parse($birthDate)->age;
        }

        // Get threshold value
        $threshold = json_decode($this->rule->threshold ?? '{}', true);

        //POCOR-9509: use user. prefix for consistency with StaffEmployment/StaffLeave/StaffType/License commands
        return [
            '${threshold.value}' => (string) ($threshold['value'] ?? ''),
            '${age}' => (string) $age,
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
