<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertLicenseValidityShell
 *
 * Sends alerts for staff licenses expiring (or recently expired) within a
 * configurable threshold window.
 *
 * Because staff_licenses has no institution_id, each license record is expanded
 * to one alert item per ASSIGNED institution_staff row for that staff member.
 *
 * Threshold JSON format:
 *   {"value": N, "license_type": X, "condition": 1|2}
 *   condition 1 = days BEFORE expiry  (DATEDIFF(expiry_date, NOW()) BETWEEN 0 AND N)
 *   condition 2 = days AFTER  expiry  (DATEDIFF(NOW(), expiry_date) BETWEEN 0 AND N)
 *
 * Usage:
 *   php artisan alerts:license-validity
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *
 * @package App\Console\Commands\Alerts
 */
class AlertLicenseValidityCommand extends AlertCommandBase
{
    //POCOR-9509: start - condition constants matching CakePHP getModelAlertData
    const CONDITION_DAYS_BEFORE = 1;
    const CONDITION_DAYS_AFTER  = 2;
    //POCOR-9509: end

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:license-validity
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for staff licenses expiring (Laravel port)';

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

        return $this->runFeatureAlert('LicenseValidity');
    }

    /**
     * POCOR-9509: Get pending license records to alert on
     *
     * Queries staff_licenses filtered by license_type and expiry window from
     * the threshold config. Because licenses have no institution_id, each
     * matching license is expanded to one item per ASSIGNED institution_staff
     * row for that staff member (mirrors CakePHP AlertLicenseValidityShell).
     *
     * @param string $featureKey Feature identifier
     * @return array List of license+institution data items, one per assignment
     */
    protected function getPendingItems(string $featureKey): array
    {
        //POCOR-9509: start - parse threshold JSON
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $value         = (int) ($threshold['value']        ?? 0);
        $licenseTypeId = (int) ($threshold['license_type'] ?? 0);
        $condition     = (int) ($threshold['condition']    ?? 0);
        //POCOR-9509: end

        //POCOR-9509: validate required threshold fields
        if (!$value || !$licenseTypeId || !in_array($condition, [self::CONDITION_DAYS_BEFORE, self::CONDITION_DAYS_AFTER], true)) {
            // $this->info("Invalid threshold configuration for LicenseValidity"); //POCOR-9509: commented out per CLAUDE.md            return [];
        }

        //POCOR-9509: start - build DATEDIFF condition matching CakePHP getModelAlertData
        if ($condition === self::CONDITION_DAYS_BEFORE) {
            $dateCondition = "DATEDIFF(staff_licenses.expiry_date, NOW()) BETWEEN 0 AND $value";
        } else {
            $dateCondition = "DATEDIFF(NOW(), staff_licenses.expiry_date) BETWEEN 0 AND $value";
        }
        //POCOR-9509: end

        //POCOR-9509: fetch matching licenses (no institution join yet — licenses have no institution_id)
        $licenses = DB::table('staff_licenses')
            ->join('security_users as Users', 'Users.id', '=', 'staff_licenses.security_user_id')
            ->join('license_types as LicenseTypes', 'LicenseTypes.id', '=', 'staff_licenses.license_type_id')
            ->where('staff_licenses.license_type_id', $licenseTypeId)
            ->whereNotNull('staff_licenses.expiry_date')
            ->whereRaw($dateCondition)
            ->select([
                'staff_licenses.id',
                'staff_licenses.security_user_id as user_id',
                'staff_licenses.license_number',
                'staff_licenses.issue_date',
                'staff_licenses.expiry_date',
                'staff_licenses.issuer',
                'LicenseTypes.name as license_type_name',
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
            ])
            ->get();

        if ($licenses->isEmpty()) {
            return [];
        }

        //POCOR-9509: check if the triggering user is super admin (mirrors CakePHP pattern)
        $user = DB::table('security_users')->where('id', $this->userId)->first();
        $isSuperAdmin = (bool) ($user->super_admin ?? false);

        //POCOR-9509: start - optionally restrict accessible institution IDs for non-super-admins
        $accessibleInstitutionIds = null;
        if (!$isSuperAdmin) {
            $securityGroupIds = DB::table('security_group_users')
                ->where('security_user_id', $this->userId)
                ->pluck('security_group_id')
                ->toArray();

            if (empty($securityGroupIds)) {
                return [];
            }

            $accessibleInstitutionIds = DB::table('security_group_institutions')
                ->whereIn('security_group_id', $securityGroupIds)
                ->pluck('institution_id')
                ->toArray();

            if (empty($accessibleInstitutionIds)) {
                return [];
            }
        }
        //POCOR-9509: end

        //POCOR-9509: start - expand each license to one item per ASSIGNED institution
        // (mirrors CakePHP shell: "license don't have institution_id, check in institution staff")
        $items = [];

        foreach ($licenses as $license) {
            //POCOR-9509: look up all ASSIGNED institution_staff rows for this staff member
            $assignmentQuery = DB::table('institution_staff')
                ->join('staff_statuses', 'staff_statuses.id', '=', 'institution_staff.staff_status_id')
                ->join('institutions as Institutions', 'Institutions.id', '=', 'institution_staff.institution_id')
                ->where('institution_staff.staff_id', $license->user_id)
                ->where('staff_statuses.code', 'ASSIGNED')
                ->select([
                    'institution_staff.institution_id',
                    'Institutions.name as institution_name',
                    'Institutions.code as institution_code',
                    'Institutions.address as institution_address',
                    'Institutions.postal_code as institution_postal_code',
                    'Institutions.contact_person as institution_contact_person',
                    'Institutions.telephone as institution_telephone',
                    'Institutions.email as institution_email',
                    'Institutions.website as institution_website',
                ]);

            //POCOR-9509: restrict to accessible institutions for non-super-admin users
            if ($accessibleInstitutionIds !== null) {
                $assignmentQuery->whereIn('institution_staff.institution_id', $accessibleInstitutionIds);
            }

            $assignments = $assignmentQuery->get();

            if ($assignments->isEmpty()) {
                continue;
            }

            //POCOR-9509: start - calculate day_difference (absolute days between expiry_date and today)
            $today      = Carbon::now()->startOfDay();
            $expiryDate = $license->expiry_date ? Carbon::parse($license->expiry_date)->startOfDay() : null;
            $dayDiff    = $expiryDate ? (int) abs($today->diffInDays($expiryDate, false)) : 0;
            //POCOR-9509: end

            //POCOR-9509: emit one item per institution assignment
            foreach ($assignments as $assignment) {
                $items[] = [
                    //POCOR-9509: license fields
                    'id'                => $license->id,
                    'user_id'           => $license->user_id,
                    'license_number'    => $license->license_number,
                    'issue_date'        => $license->issue_date,
                    'expiry_date'       => $license->expiry_date,
                    'issuer'            => $license->issuer,
                    'license_type_name' => $license->license_type_name,
                    'day_difference'    => $dayDiff,
                    //POCOR-9509: user fields from security_users
                    'openemis_no'       => $license->openemis_no,
                    'first_name'        => $license->first_name,
                    'middle_name'       => $license->middle_name,
                    'third_name'        => $license->third_name,
                    'last_name'         => $license->last_name,
                    'preferred_name'    => $license->preferred_name,
                    'email'             => $license->email,
                    'address'           => $license->address,
                    'postal_code'       => $license->postal_code,
                    'date_of_birth'     => $license->date_of_birth,
                    //POCOR-9509: institution fields from assignment
                    'institution_id'               => $assignment->institution_id,
                    'institution_name'             => $assignment->institution_name,
                    'institution_code'             => $assignment->institution_code,
                    'institution_address'          => $assignment->institution_address,
                    'institution_postal_code'      => $assignment->institution_postal_code,
                    'institution_contact_person'   => $assignment->institution_contact_person,
                    'institution_telephone'        => $assignment->institution_telephone,
                    'institution_email'            => $assignment->institution_email,
                    'institution_website'          => $assignment->institution_website,
                ];
            }
        }
        //POCOR-9509: end

        return $items;
    }

    /**
     * POCOR-9509: Resolve recipients using institution_id from the expanded item
     *
     * Overrides base class to pass the institution_id that was set during the
     * per-assignment expansion in getPendingItems().
     *
     * @param array $item License+institution item from getPendingItems()
     * @return array Contact list [email => [...], phone => [...]]
     */
    protected function resolveRecipients(array $item): array
    {
        //POCOR-9509: institution_id is guaranteed to be set by getPendingItems() expansion
        return $this->recipientResolver->getRoleAssociatedContactList(
            $this->rule->security_roles,
            $item['institution_id']
        );
    }

    /**
     * POCOR-9509: Fill placeholders for license validity alert
     *
     * Maps license fields, user fields, institution fields, day_difference, and
     * the threshold value to ${placeholder} keys for subject/message replacement.
     *
     * @param array $item License+institution data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        //POCOR-9509: decode threshold once for the ${threshold.value} placeholder
        $threshold = json_decode($this->rule->threshold ?? '{}', true);

        return [
            //POCOR-9509: start - threshold and license-specific placeholders
            '${threshold.value}'      => (string) ($threshold['value'] ?? ''),
            '${license_type.name}'    => $item['license_type_name'] ?? '',
            '${license_number}'       => $item['license_number'] ?? '',
            '${issue_date}'           => $item['issue_date'] ?? '',
            '${expiry_date}'          => $item['expiry_date'] ?? '',
            '${issuer}'               => $item['issuer'] ?? '',
            '${day_difference}'       => (string) ($item['day_difference'] ?? ''),
            //POCOR-9509: end
            //POCOR-9509: start - user placeholders
            '${user.openemis_no}'     => $item['openemis_no'] ?? '',
            '${user.first_name}'      => $item['first_name'] ?? '',
            '${user.middle_name}'     => $item['middle_name'] ?? '',
            '${user.third_name}'      => $item['third_name'] ?? '',
            '${user.last_name}'       => $item['last_name'] ?? '',
            '${user.preferred_name}'  => $item['preferred_name'] ?? '',
            '${user.email}'           => $item['email'] ?? '',
            '${user.address}'         => $item['address'] ?? '',
            '${user.postal_code}'     => $item['postal_code'] ?? '',
            '${user.date_of_birth}'   => $item['date_of_birth'] ?? '',
            //POCOR-9509: end
            //POCOR-9509: start - institution placeholders
            '${institution.name}'           => $item['institution_name'] ?? '',
            '${institution.code}'           => $item['institution_code'] ?? '',
            '${institution.address}'        => $item['institution_address'] ?? '',
            '${institution.postal_code}'    => $item['institution_postal_code'] ?? '',
            '${institution.contact_person}' => $item['institution_contact_person'] ?? '',
            '${institution.telephone}'      => $item['institution_telephone'] ?? '',
            '${institution.email}'          => $item['institution_email'] ?? '',
            '${institution.website}'        => $item['institution_website'] ?? '',
            //POCOR-9509: end
        ];
    }
}
