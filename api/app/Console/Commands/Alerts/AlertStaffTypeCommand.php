<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertStaffTypeShell
 *
 * Sends alerts for staff reaching end_date thresholds for specific staff types.
 * This is a SCHEDULED alert command - runs via CheckAndQueueAlerts worker, NOT event-based.
 *
 * Query Pattern (matches CakePHP StaffTable::getModelAlertData):
 * - Filters by staff_type_id from threshold
 * - Requires end_date IS NOT NULL
 * - Applies DATEDIFF condition:
 *   - condition=1 (DAYS_BEFORE): end_date is 0 to value days in FUTURE
 *   - condition=2 (DAYS_AFTER): end_date is 0 to value days in PAST
 * - Joins to security_users, institutions, staff_types for placeholder data
 *
 * Usage (via CheckAndQueueAlerts worker):
 *   php artisan alerts:staff-type --user_id=1 --rule_id=9 --process_id=123
 *
 * Usage (manual test):
 *   php artisan alerts:staff-type --user_id=1 --rule_id=9
 *   php artisan alerts:staff-type --user_id=1 --rule_id=9 --entity_id=100
 *
 * @package App\Console\Commands\Alerts
 */
class AlertStaffTypeCommand extends AlertCommandBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:staff-type
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}
                            {--entity_id= : Optional institution_staff ID to filter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for staff reaching end_date thresholds (scheduled)';

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

        // POCOR-9509: Optional entity_id validation for filtering
        $entityId = $this->option('entity_id');
        if ($entityId !== null) {
            $entityId = (int) $entityId;
            $exists = DB::table('institution_staff')
                ->where('id', $entityId)
                ->exists();

            if (!$exists) {
                $this->error("Institution staff record with ID {$entityId} not found.");
                //POCOR-9509: mark process failed so system_processes never hangs at status=1
                $this->markProcessFailed("Institution staff record with ID {$entityId} not found");
                return self::FAILURE;
            }
        }

        return $this->runFeatureAlert('StaffType');
    }

    /**
     * POCOR-9509: Get pending staff type records to alert on
     *
     * Matches CakePHP StaffTable::getModelAlertData() logic:
     * - Filters by staff_type_id from threshold
     * - Requires end_date IS NOT NULL
     * - Applies DATEDIFF condition:
     *   - condition=1 (DAYS_BEFORE): DATEDIFF(end_date, NOW()) BETWEEN 0 AND value
     *   - condition=2 (DAYS_AFTER): DATEDIFF(NOW(), end_date) BETWEEN 0 AND value
     *
     * @param string $featureKey Feature identifier (should be 'StaffType')
     * @return array List of staff data items matching threshold
     */
    protected function getPendingItems(string $featureKey): array
    {
        // POCOR-9509: Parse threshold (staff_type, condition, value)
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $staffTypeId = (int) ($threshold['staff_type'] ?? 0);
        $condition = (int) ($threshold['condition'] ?? 1);
        $value = (int) ($threshold['value'] ?? 1);

        if (!$staffTypeId) {
            // $this->info("No staff_type configured in threshold"); //POCOR-9509: commented out per CLAUDE.md            return [];
        }

        // POCOR-9509: Build DATEDIFF condition (matches CakePHP logic)
        $conditions = [
            1 => DB::raw("DATEDIFF(InstitutionStaff.end_date, NOW()) BETWEEN 0 AND {$value}"), // DAYS_BEFORE
            2 => DB::raw("DATEDIFF(NOW(), InstitutionStaff.end_date) BETWEEN 0 AND {$value}"), // DAYS_AFTER
        ];

        if (!isset($conditions[$condition])) {
            $this->error("Invalid condition value: {$condition}");
            return [];
        }

        // POCOR-9509: Get optional entity_id filter
        $entityId = $this->option('entity_id');
        if ($entityId !== null) {
            $entityId = (int) $entityId;
        }

        // POCOR-9509: Query institution_staff matching threshold
        $query = DB::table('institution_staff as InstitutionStaff')
            ->join('security_users as Users', 'Users.id', '=', 'InstitutionStaff.staff_id')
            ->join('institutions as Institutions', 'Institutions.id', '=', 'InstitutionStaff.institution_id')
            ->join('staff_types as StaffTypes', 'StaffTypes.id', '=', 'InstitutionStaff.staff_type_id')
            ->where('InstitutionStaff.staff_type_id', $staffTypeId)
            ->whereNotNull('InstitutionStaff.end_date')
            ->whereRaw($conditions[$condition])
            ->select([
                'InstitutionStaff.id',
                'InstitutionStaff.staff_id',
                'InstitutionStaff.institution_id',
                'InstitutionStaff.start_date',
                'InstitutionStaff.end_date',
                'StaffTypes.name as staff_type_name',
                'Users.id as user_id',
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
                'Institutions.id as institution_id_full',
                'Institutions.name as institution_name',
                'Institutions.code as institution_code',
                'Institutions.address as institution_address',
                'Institutions.postal_code as institution_postal_code',
                'Institutions.contact_person as institution_contact_person',
                'Institutions.telephone as institution_telephone',
                'Institutions.email as institution_email',
                'Institutions.website as institution_website',
            ]);

        // POCOR-9509: Optional entity_id filter
        if ($entityId !== null) {
            $query->where('InstitutionStaff.id', $entityId);
        }

        $results = $query->get()->map(function ($item) {
            return (array) $item;
        })->toArray();

        if (empty($results)) {
            // $this->info("No staff records found matching threshold (staff_type={$staffTypeId}, condition={$condition}, value={$value})");
            return [];
        }

        // $this->info("Found " . count($results) . " staff record(s) matching threshold");

        return $results;
    }


    /**
     * POCOR-9509: Fill placeholders for staff type alert
     *
     * Matches CakePHP AlertStaffTypeShell logic.
     * Calculates day_difference from end_date similar to AlertStaffTypeShell line 45-49.
     *
     * @param array $item Staff data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        // POCOR-9509: Calculate day difference (matches CakePHP logic)
        $today = Carbon::now()->startOfDay();
        $endDate = $item['end_date'] ?? null;
        $dayDiff = '';
        if ($endDate) {
            $dayDiff = abs($today->diffInDays(Carbon::parse($endDate)->startOfDay(), false));
        }

        $threshold = json_decode($this->rule->threshold ?? '{}', true);

        return [
            '${threshold.value}' => (string) ($threshold['value'] ?? ''),
            '${staff_type.name}' => $item['staff_type_name'] ?? '',
            '${start_date}' => $item['start_date'] ?? '',
            '${end_date}' => $item['end_date'] ?? '',
            '${day_difference}' => (string) $dayDiff,
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
