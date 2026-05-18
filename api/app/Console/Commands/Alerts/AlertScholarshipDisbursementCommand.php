<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertScholarshipDisbursementShell
 *
 * Sends alerts for scholarship recipient payment structure estimates where
 * the estimated disbursement date is within X days (before or after today).
 *
 * Threshold JSON: {"value": N, "condition": 1|2}
 *   condition 1 = days BEFORE disbursement:
 *       DATEDIFF(estimated_disbursement_date, NOW()) BETWEEN 0 AND value
 *   condition 2 = days AFTER disbursement:
 *       DATEDIFF(NOW(), estimated_disbursement_date) BETWEEN 0 AND value
 *
 * Recipients are resolved by role only (no institution filter), matching
 * the CakePHP shell which called getEmailList() without an institution ID.
 *
 * Usage:
 *   php artisan alerts:scholarship-disbursement
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *
 * @package App\Console\Commands\Alerts
 */
class AlertScholarshipDisbursementCommand extends AlertCommandBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    //POCOR-9509: start - artisan command signature
    protected $signature = 'alerts:scholarship-disbursement
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}';
    //POCOR-9509: end

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for scholarship disbursement estimates (Laravel port)';

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

        return $this->runFeatureAlert('ScholarshipDisbursement');
    }

    /**
     * POCOR-9509: Override recipient resolution to use global roles (no institution filter)
     *
     * The CakePHP shell called getEmailList($rule['security_roles']) without an
     * institution_id, so scholarships are global — not scoped to any institution.
     *
     * @param array $item Pending item data (unused for recipient resolution here)
     * @return array Contact list ['email' => [...], 'phone' => [...]]
     */
    protected function resolveRecipients(array $item): array
    {
        //POCOR-9509: No institution_id — scholarship alerts are global
        return $this->recipientResolver->getRoleAssociatedContactList(
            $this->rule->security_roles
        );
    }

    /**
     * POCOR-9509: Query scholarship_recipient_payment_structure_estimates for records
     * whose estimated_disbursement_date falls within the threshold window.
     *
     * Join chain:
     *   scholarship_recipient_payment_structure_estimates (Estimates)
     *   LEFT JOIN scholarship_disbursement_categories (DisbursementCategories)
     *       ON DisbursementCategories.id = Estimates.scholarship_disbursement_category_id
     *   LEFT JOIN scholarship_recipient_payment_structures (RecipientPaymentStructures)
     *       ON RecipientPaymentStructures.id = Estimates.scholarship_recipient_payment_structure_id
     *   LEFT JOIN security_users (Recipients)
     *       ON Recipients.id = Estimates.recipient_id
     *   LEFT JOIN scholarships (Scholarships)
     *       ON Scholarships.id = Estimates.scholarship_id
     *
     * @param string $featureKey Feature identifier (unused but required by interface)
     * @return array List of matching estimate records as arrays
     */
    protected function getPendingItems(string $featureKey): array
    {
        //POCOR-9509: Parse threshold JSON to get condition and day window
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $conditionKey = (int) ($threshold['condition'] ?? 1);
        $thresholdDay = (int) ($threshold['value'] ?? 1);

        //POCOR-9509: Build SQL date condition based on condition key
        // condition 1 = days BEFORE disbursement (date is in the future, within window)
        // condition 2 = days AFTER disbursement (date is in the past, within window)
        $sqlConditions = [
            1 => DB::raw('DATEDIFF(Estimates.estimated_disbursement_date, NOW()) BETWEEN 0 AND ' . $thresholdDay),
            2 => DB::raw('DATEDIFF(NOW(), Estimates.estimated_disbursement_date) BETWEEN 0 AND ' . $thresholdDay),
        ];

        if (!array_key_exists($conditionKey, $sqlConditions)) {
            $this->warn("Unknown condition key [{$conditionKey}] in threshold, skipping.");
            return [];
        }

        //POCOR-9509: Build query with all necessary joins for placeholder data
        $query = DB::table('scholarship_recipient_payment_structure_estimates as Estimates')
            ->leftJoin(
                'scholarship_disbursement_categories as DisbursementCategories',
                'DisbursementCategories.id',
                '=',
                'Estimates.scholarship_disbursement_category_id'
            )
            ->leftJoin(
                'scholarship_recipient_payment_structures as RecipientPaymentStructures',
                'RecipientPaymentStructures.id',
                '=',
                'Estimates.scholarship_recipient_payment_structure_id'
            )
            ->leftJoin(
                'security_users as Recipients',
                'Recipients.id',
                '=',
                'Estimates.recipient_id'
            )
            ->leftJoin(
                'scholarships as Scholarships',
                'Scholarships.id',
                '=',
                'Estimates.scholarship_id'
            )
            ->where($sqlConditions[$conditionKey])
            ->select([
                // Estimates fields
                'Estimates.id',
                'Estimates.estimated_disbursement_date',
                'Estimates.estimated_amount',
                'Estimates.comments',
                // DisbursementCategories fields
                'DisbursementCategories.name as disbursement_category_name',
                // RecipientPaymentStructures fields
                'RecipientPaymentStructures.code as payment_structure_code',
                'RecipientPaymentStructures.name as payment_structure_name',
                // Recipients (security_users) fields
                'Recipients.first_name as recipient_first_name',
                'Recipients.middle_name as recipient_middle_name',
                'Recipients.third_name as recipient_third_name',
                'Recipients.last_name as recipient_last_name',
                'Recipients.preferred_name as recipient_preferred_name',
                'Recipients.email as recipient_email',
                'Recipients.address as recipient_address',
                'Recipients.postal_code as recipient_postal_code',
                'Recipients.date_of_birth as recipient_date_of_birth',
                // Scholarships fields
                'Scholarships.code as scholarship_code',
                'Scholarships.name as scholarship_name',
                'Scholarships.description as scholarship_description',
                'Scholarships.application_open_date as scholarship_application_open_date',
                'Scholarships.application_close_date as scholarship_application_close_date',
                'Scholarships.maximum_award_amount as scholarship_maximum_award_amount',
                'Scholarships.total_amount as scholarship_total_amount',
                'Scholarships.duration as scholarship_duration',
                'Scholarships.bond as scholarship_bond',
            ]);

        return $query->get()->map(function ($item) {
            return (array) $item;
        })->toArray();
    }

    /**
     * POCOR-9509: Map estimate item data to placeholder => value array
     *
     * Placeholders match those defined in AlertRuleScholarshipDisbursementBehavior.php:
     *   ${threshold.value}, ${day_difference}, ${estimated_disbursement_date},
     *   ${estimated_amount}, ${comments},
     *   ${disbursement_category.name},
     *   ${payment_structure.code}, ${payment_structure.name},
     *   ${recipient.first_name}, ${recipient.middle_name}, ${recipient.third_name},
     *   ${recipient.last_name}, ${recipient.name}, ${recipient.preferred_name},
     *   ${recipient.email}, ${recipient.address}, ${recipient.postal_code},
     *   ${recipient.date_of_birth},
     *   ${scholarship.code}, ${scholarship.name}, ${scholarship.description},
     *   ${scholarship.application_open_date}, ${scholarship.application_close_date},
     *   ${scholarship.maximum_award_amount}, ${scholarship.total_amount},
     *   ${scholarship.duration}, ${scholarship.bond}
     *
     * @param array $item Estimate data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        //POCOR-9509: Parse threshold for value
        $threshold = json_decode($this->rule->threshold ?? '{}', true);

        //POCOR-9509: Calculate absolute day difference between today and estimated_disbursement_date
        $today = Carbon::now()->startOfDay();
        $disbursementDate = $item['estimated_disbursement_date'] ?? null;
        $dayDiff = '';
        if ($disbursementDate) {
            $dayDiff = (string) abs($today->diffInDays(Carbon::parse($disbursementDate)->startOfDay(), false));
        }

        //POCOR-9509: Build full recipient name from name parts
        $nameParts = array_filter([
            $item['recipient_first_name'] ?? '',
            $item['recipient_middle_name'] ?? '',
            $item['recipient_third_name'] ?? '',
            $item['recipient_last_name'] ?? '',
        ]);
        $recipientFullName = implode(' ', $nameParts);

        return [
            //POCOR-9509: Threshold placeholder
            '${threshold.value}'                      => (string) ($threshold['value'] ?? ''),

            //POCOR-9509: Date/amount placeholders from estimate record
            '${day_difference}'                       => $dayDiff,
            '${estimated_disbursement_date}'          => (string) ($item['estimated_disbursement_date'] ?? ''),
            '${estimated_amount}'                     => (string) ($item['estimated_amount'] ?? ''),
            '${comments}'                             => (string) ($item['comments'] ?? ''),

            //POCOR-9509: Disbursement category placeholder
            '${disbursement_category.name}'           => (string) ($item['disbursement_category_name'] ?? ''),

            //POCOR-9509: Recipient payment structure placeholders
            '${payment_structure.code}'               => (string) ($item['payment_structure_code'] ?? ''),
            '${payment_structure.name}'               => (string) ($item['payment_structure_name'] ?? ''),

            //POCOR-9509: Recipient (security_users) placeholders
            '${recipient.first_name}'                 => (string) ($item['recipient_first_name'] ?? ''),
            '${recipient.middle_name}'                => (string) ($item['recipient_middle_name'] ?? ''),
            '${recipient.third_name}'                 => (string) ($item['recipient_third_name'] ?? ''),
            '${recipient.last_name}'                  => (string) ($item['recipient_last_name'] ?? ''),
            '${recipient.name}'                       => $recipientFullName,
            '${recipient.preferred_name}'             => (string) ($item['recipient_preferred_name'] ?? ''),
            '${recipient.email}'                      => (string) ($item['recipient_email'] ?? ''),
            '${recipient.address}'                    => (string) ($item['recipient_address'] ?? ''),
            '${recipient.postal_code}'                => (string) ($item['recipient_postal_code'] ?? ''),
            '${recipient.date_of_birth}'              => (string) ($item['recipient_date_of_birth'] ?? ''),

            //POCOR-9509: Scholarship placeholders
            '${scholarship.code}'                     => (string) ($item['scholarship_code'] ?? ''),
            '${scholarship.name}'                     => (string) ($item['scholarship_name'] ?? ''),
            '${scholarship.description}'              => (string) ($item['scholarship_description'] ?? ''),
            '${scholarship.application_open_date}'    => (string) ($item['scholarship_application_open_date'] ?? ''),
            '${scholarship.application_close_date}'   => (string) ($item['scholarship_application_close_date'] ?? ''),
            '${scholarship.maximum_award_amount}'     => (string) ($item['scholarship_maximum_award_amount'] ?? ''),
            '${scholarship.total_amount}'             => (string) ($item['scholarship_total_amount'] ?? ''),
            '${scholarship.duration}'                 => (string) ($item['scholarship_duration'] ?? ''),
            '${scholarship.bond}'                     => (string) ($item['scholarship_bond'] ?? ''),
        ];
    }
}
