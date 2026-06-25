<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertLicenseRenewalShell
 *
 * Sends alerts for staff who have not completed enough training hours
 * within the validity period of a license expiring within X days.
 *
 * Usage:
 *   php artisan alerts:license-renewal
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *
 * @package App\Console\Commands\Alerts
 */
class AlertLicenseRenewalCommand extends AlertCommandBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:license-renewal
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for staff license renewal — insufficient training hours (Laravel port)';

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

        return $this->runFeatureAlert('LicenseRenewal');
    }

    /**
     * POCOR-9509: Get pending license renewal records to alert on
     *
     * Queries staff_licenses for licenses expiring within threshold.value days
     * (condition 1 = before expiry: DATEDIFF(expiry_date, NOW()) BETWEEN 0 AND value).
     * For each license, sums credit_hours from staff_trainings within the license
     * validity period and in the configured training categories.
     * Only licenses where total credit hours < threshold.hour are returned.
     *
     * @param string $featureKey Feature identifier
     * @return array List of license data items ready for alerting
     */
    protected function getPendingItems(string $featureKey): array
    {
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() ENTRY - featureKey=' . $featureKey); //[TEMP-LOG]

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() ENTRY - featureKey=' . $featureKey); //[TEMP-LOG]
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $daysBefore       = (int) ($threshold['value']               ?? 0);
        $licenseTypeId    = (int) ($threshold['license_type']        ?? 0);
        $minHour          = (int) ($threshold['hour']                ?? 0);
        $trainingCatIds   = (array) ($threshold['training_categories'] ?? []);

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Parsed threshold: daysBefore=' . $daysBefore . ', licenseTypeId=' . $licenseTypeId . ', minHour=' . $minHour . ', trainingCatIds=' . json_encode($trainingCatIds)); //[TEMP-LOG]
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Raw rule threshold: ' . ($this->rule->threshold ?? 'null')); //[TEMP-LOG]

        //POCOR-9509: Validate required threshold values
        if (!$daysBefore || !$licenseTypeId) {
            // $this->info("Invalid threshold configuration for LicenseRenewal"); //POCOR-9509: commented out per CLAUDE.md            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() EXIT [] - Invalid threshold (daysBefore=' . $daysBefore . ', licenseTypeId=' . $licenseTypeId . ')'); //[TEMP-LOG]
            return [];
        }

        //POCOR-9509: Condition 1 only — licenses expiring between today and X days from now
        $dateCondition = "DATEDIFF(staff_licenses.expiry_date, NOW()) BETWEEN 0 AND {$daysBefore}";
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() SQL date condition: ' . $dateCondition); //[TEMP-LOG]

        //POCOR-9509: Fetch all matching licenses with user details
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Executing license query...'); //[TEMP-LOG]
        $licenses = DB::table('staff_licenses')
            ->join('license_types as LicenseTypes', 'LicenseTypes.id', '=', 'staff_licenses.license_type_id')
            ->join('security_users as Users', 'Users.id', '=', 'staff_licenses.security_user_id')
            ->where('staff_licenses.license_type_id', $licenseTypeId)
            ->whereNotNull('staff_licenses.expiry_date')
            ->whereRaw($dateCondition)
            ->select([
                'staff_licenses.id',
                'staff_licenses.license_number',
                'staff_licenses.issue_date',
                'staff_licenses.expiry_date',
                'staff_licenses.issuer',
                'staff_licenses.security_user_id as staff_id',
                'LicenseTypes.name as license_type_name',
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
            ])
            ->get();

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() License query returned ' . $licenses->count() . ' rows'); //[TEMP-LOG]

        if ($licenses->isEmpty()) {
            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() EXIT [] - No licenses found matching criteria'); //[TEMP-LOG]
            return [];
        }

        $items = [];
        $loopCount = 0;
        $skippedThreshold = 0;
        $skippedInstitution = 0;

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Starting license loop over ' . $licenses->count() . ' licenses'); //[TEMP-LOG]

        foreach ($licenses as $license) {
            $loopCount++;
            $license = (array) $license;
            $staffId      = (int) $license['staff_id'];
            $issueDate    = $license['issue_date'];
            $expiryDate   = $license['expiry_date'];

            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Loop ' . $loopCount . ': staff_id=' . $staffId . ', license_number=' . ($license['license_number'] ?? 'N/A') . ', expiry=' . $expiryDate); //[TEMP-LOG]

            //POCOR-9509: Sum credit_hours from staff_trainings within license validity period
            //            filtered by the configured training categories
            $totalCreditHours = 0;

            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Querying staff_trainings for staff_id=' . $staffId . ' between ' . $issueDate . ' and ' . $expiryDate . ' with categories=' . json_encode($trainingCatIds)); //[TEMP-LOG]

            $trainingQuery = DB::table('staff_trainings')
                ->where('staff_trainings.staff_id', $staffId)
                ->whereBetween('staff_trainings.completed_date', [$issueDate, $expiryDate]);

            if (!empty($trainingCatIds)) {
                $trainingQuery->whereIn('staff_trainings.staff_training_category_id', $trainingCatIds);
            }

            $trainingSum = $trainingQuery->sum('staff_trainings.credit_hours');
            $totalCreditHours = (int) ($trainingSum ?? 0);

            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() staff_id=' . $staffId . ' total_credit_hours=' . $totalCreditHours . ' (threshold=' . $minHour . ')'); //[TEMP-LOG]

            //POCOR-9509: Only alert if staff has fewer hours than the threshold
            if ($totalCreditHours >= $minHour) {
                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() SKIPPING staff_id=' . $staffId . ' - meets threshold (total=' . $totalCreditHours . ' >= ' . $minHour . ')'); //[TEMP-LOG]
                $skippedThreshold++;
                continue;
            }

            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Staff meets alert criteria (below threshold), looking up institutions...'); //[TEMP-LOG]

            //POCOR-9509: Licenses don't carry institution_id — look up assigned institutions
            //            via institution_staff (same logic as CakePHP shell)
            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Querying institution_staff for staff_id=' . $staffId); //[TEMP-LOG]
            $institutionStaffRecords = DB::table('institution_staff as InstitutionStaff')
                ->join('institutions as Institutions', 'Institutions.id', '=', 'InstitutionStaff.institution_id')
                ->join('staff_statuses as StaffStatuses', 'StaffStatuses.id', '=', 'InstitutionStaff.staff_status_id')
                ->where('InstitutionStaff.staff_id', $staffId)
                ->where('StaffStatuses.code', 'ASSIGNED')
                ->select([
                    'InstitutionStaff.institution_id',
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

            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Institution query returned ' . $institutionStaffRecords->count() . ' records'); //[TEMP-LOG]

            if ($institutionStaffRecords->isEmpty()) {
                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() SKIPPING staff_id=' . $staffId . ' - no assigned institutions found'); //[TEMP-LOG]
                $skippedInstitution++;
                continue;
            }

            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Found ' . $institutionStaffRecords->count() . ' institution(s) for staff_id=' . $staffId . ' - generating alert items'); //[TEMP-LOG]

            //POCOR-9509: Emit one alert item per assigned institution (mirrors CakePHP foreach)
            foreach ($institutionStaffRecords as $institutionRow) {
                $institutionRow = (array) $institutionRow;

                $item = array_merge($license, $institutionRow, [
                    'total_credit_hours' => $totalCreditHours,
                ]);

                // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Created alert item: institution_id=' . $item['institution_id'] . ', institution_name=' . ($item['institution_name'] ?? 'N/A')); //[TEMP-LOG]

                $items[] = $item;
            }
        }

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() Loop complete: processed=' . $loopCount . ', skipped_threshold=' . $skippedThreshold . ', skipped_institution=' . $skippedInstitution . ', total_items=' . count($items)); //[TEMP-LOG]

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::getPendingItems() EXIT - returning ' . count($items) . ' items'); //[TEMP-LOG]
        return $items;
    }

    /**
     * POCOR-9509: Resolve recipients using the institution_id embedded in each item.
     *
     * Licenses do not carry an institution_id directly; getPendingItems() resolves
     * institution membership and stores it as institution_id on each emitted item.
     * This override passes that value to the base-class recipient resolver.
     *
     * @param array $item Pending item data (includes institution_id)
     * @return array Contact list [email => [...], phone => [...]]
     */
    protected function resolveRecipients(array $item): array
    {
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::resolveRecipients() ENTRY'); //[TEMP-LOG]

        //POCOR-9509: institution_id is set by getPendingItems() from institution_staff join
        $institutionId = $item['institution_id'] ?? null;

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::resolveRecipients() institution_id=' . ($institutionId ?? 'null') . ', staff_id=' . ($item['staff_id'] ?? 'N/A')); //[TEMP-LOG]
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::resolveRecipients() Resolving contacts for security_roles (count=' . count($this->rule->security_roles ?? []) . ')'); //[TEMP-LOG]

        $contacts = $this->recipientResolver->getRoleAssociatedContactList(
            $this->rule->security_roles,
            $institutionId
        );

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::resolveRecipients() Resolved contacts: email_count=' . count($contacts['email'] ?? []) . ', phone_count=' . count($contacts['phone'] ?? [])); //[TEMP-LOG]
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::resolveRecipients() EXIT'); //[TEMP-LOG]

        return $contacts;
    }

    /**
     * POCOR-9509: Fill placeholders for license renewal alert
     *
     * @param array $item License data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::fillPlaceholders() ENTRY'); //[TEMP-LOG]

        //POCOR-9509: Calculate absolute days between today and expiry date
        $today      = Carbon::now()->startOfDay();
        $expiryDate = $item['expiry_date'] ?? null;
        $dayDiff    = '';
        if ($expiryDate) {
            $dayDiff = (string) abs($today->diffInDays(Carbon::parse($expiryDate)->startOfDay(), false));
            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::fillPlaceholders() Day difference calculated: ' . $dayDiff . ' (expiry=' . $expiryDate . ')'); //[TEMP-LOG]
        } else {
            // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::fillPlaceholders() No expiry date, dayDiff empty'); //[TEMP-LOG]
        }

        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::fillPlaceholders() Threshold: value=' . ($threshold['value'] ?? 'null') . ', hour=' . ($threshold['hour'] ?? 'null')); //[TEMP-LOG]

        $placeholders = [
            //POCOR-9509: License fields
            '${license_type.name}'     => $item['license_type_name']          ?? '',
            '${license_number}'        => $item['license_number']              ?? '',
            '${issue_date}'            => $item['issue_date']                  ?? '',
            '${expiry_date}'           => $item['expiry_date']                 ?? '',
            '${issuer}'                => $item['issuer']                      ?? '',
            //POCOR-9509: Training / threshold fields
            '${total_credit_hours}'    => (string) ($item['total_credit_hours'] ?? 0),
            '${threshold.value}'       => (string) ($threshold['value']         ?? ''),
            '${threshold.hour}'        => (string) ($threshold['hour']          ?? ''),
            //POCOR-9509: Date difference
            '${day_difference}'        => $dayDiff,
            //POCOR-9509: User fields
            '${user.openemis_no}'      => $item['openemis_no']                 ?? '',
            '${user.first_name}'       => $item['first_name']                  ?? '',
            '${user.middle_name}'      => $item['middle_name']                 ?? '',
            '${user.third_name}'       => $item['third_name']                  ?? '',
            '${user.last_name}'        => $item['last_name']                   ?? '',
            '${user.preferred_name}'   => $item['preferred_name']              ?? '',
            '${user.email}'            => $item['email']                       ?? '',
            '${user.address}'          => $item['address']                     ?? '',
            '${user.postal_code}'      => $item['postal_code']                 ?? '',
            '${user.date_of_birth}'    => $item['date_of_birth']               ?? '',
            //POCOR-9509: Institution fields
            '${institution.name}'            => $item['institution_name']            ?? '',
            '${institution.code}'            => $item['institution_code']            ?? '',
            '${institution.address}'         => $item['institution_address']         ?? '',
            '${institution.postal_code}'     => $item['institution_postal_code']     ?? '',
            '${institution.contact_person}'  => $item['institution_contact_person']  ?? '',
            '${institution.telephone}'       => $item['institution_telephone']       ?? '',
            '${institution.email}'           => $item['institution_email']           ?? '',
            '${institution.website}'         => $item['institution_website']         ?? '',
        ];

        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::fillPlaceholders() Placeholders filled: ' . json_encode($placeholders)); //[TEMP-LOG]
        // // Log::debug('[TEMP-LOG] @' . class_basename($this) . '::fillPlaceholders() EXIT'); //[TEMP-LOG]

        return $placeholders;
    }
}
