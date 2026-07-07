<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertStudentEnrolmentCommand
 *
 * Sends alerts for student enrolment status changes.
 *
 * Usage:
 *   php artisan alerts:student-enrolment
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *       --status_id=123
 *       --enrolment_id=456
 *
 * @package App\Console\Commands\Alerts
 */
class AlertStudentEnrolmentCommand extends AlertCommandBase
{
    /** @var int */
    protected $entityId = 0;

    /** @var int */
    protected $studentId = 0;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:student-enrolment
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}
                            {--entity_id= : Entity ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for student enrolment status changes (Laravel port)';

    /**
     * POCOR-9509: Execute the console command
     *
     * @return int
     */
    public function handle(): int
    {
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::handle() ENTRY options: ' . json_encode($this->options())); //[TEMP-LOG]
        if (!$this->prepareContext()) {
            // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::handle() EXIT EARLY - prepareContext failed'); //[TEMP-LOG]
            return self::FAILURE;
        }
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::handle() calling runFeatureAlert(StudentEnrolment)'); //[TEMP-LOG]
        $result = $this->runFeatureAlert('StudentEnrolment');
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::handle() EXIT result=' . $result); //[TEMP-LOG]
        return $result;
    }

    /**
     * POCOR-9509: Override prepareContext to validate enrolment_id
     *
     * @return bool
     */
    protected function prepareContext(): bool
    {
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::prepareContext() ENTRY'); //[TEMP-LOG]
        // Call parent prepareContext first
        if (!parent::prepareContext()) {
            // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::prepareContext() EXIT FALSE - parent failed'); //[TEMP-LOG]
            return false;
        }

        // Get and validate enrolment_id
        $this->entityId = (int) $this->option('entity_id');
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::prepareContext() entityId=' . $this->entityId); //[TEMP-LOG]

        if (!$this->entityId) {
            $this->error("Missing required --entity_id option");
            // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::prepareContext() EXIT FALSE - no entity_id'); //[TEMP-LOG]
            return false;
        }

        // Load enrolment record
        $enrolment = DB::table('institution_student_enrolment')
            ->where('id', $this->entityId)
            ->first();
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::prepareContext() enrolment row: ' . json_encode($enrolment)); //[TEMP-LOG]

        if (!$enrolment) {
            $this->error("Enrolment with ID {$this->entityId} not found");
            // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::prepareContext() EXIT FALSE - enrolment not found'); //[TEMP-LOG]
            return false;
        }

        $this->studentId = $enrolment->student_id;
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::prepareContext() EXIT TRUE studentId=' . $this->studentId); //[TEMP-LOG]
        return true;
    }

    /**
     * POCOR-9509: Get pending student enrolment records to alert on
     *
     * Queries student_enrolment for the specific enrolment with workflow status.
     *
     * @param string $featureKey Feature identifier
     * @return array List of student enrolment data items
     */
    protected function getPendingItems(string $featureKey): array
    {
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::getPendingItems() ENTRY featureKey=' . $featureKey); //[TEMP-LOG]
        // Parse threshold (workflow step IDs)
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $workflowStepIds = $threshold['workflow_steps'] ?? [];
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::getPendingItems() threshold raw: ' . $this->rule->threshold . ' => workflowStepIds: ' . json_encode($workflowStepIds)); //[TEMP-LOG]

        if (empty($workflowStepIds)) {
            // $this->info("No workflow steps configured in threshold"); //POCOR-9509: commented out per CLAUDE.md
            // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::getPendingItems() EXIT [] - no workflow steps configured'); //[TEMP-LOG]
            return []; //POCOR-9509: no workflow steps = nothing to alert on
        }

        // Query enrolment with all related data including guardians
        $query = DB::table('institution_student_enrolment as StudentEnrolment')
            ->join('security_users as Users', 'Users.id', '=', 'StudentEnrolment.student_id')
            ->join('institutions as Institutions', 'Institutions.id', '=', 'StudentEnrolment.institution_id')
            ->join('academic_periods as AcademicPeriods', 'AcademicPeriods.id', '=', 'StudentEnrolment.academic_period_id')
            ->join('education_grades as EducationGrades', 'EducationGrades.id', '=', 'StudentEnrolment.education_grade_id')
            ->join('workflow_steps as Statuses', 'Statuses.id', '=', 'StudentEnrolment.status_id')
            ->leftJoin('student_guardians', 'student_guardians.student_id', '=', 'StudentEnrolment.student_id')
            ->leftJoin('security_users as guardians', 'guardians.id', '=', 'student_guardians.guardian_id')
            ->leftJoin('guardian_relations', 'guardian_relations.id', '=', 'student_guardians.guardian_relation_id')
            ->leftJoin('user_contacts as guardian_contacts', 'guardian_contacts.security_user_id', '=', 'guardians.id')
            ->where('StudentEnrolment.id', $this->entityId)
            ->whereIn('Statuses.id', $workflowStepIds)
            ->orderBy('guardian_relations.order', 'asc')
            ->orderBy('guardian_contacts.preferred', 'desc')
            ->select([
                'StudentEnrolment.id',
                'StudentEnrolment.student_id',
                'StudentEnrolment.institution_id',
                'StudentEnrolment.start_date',
                'StudentEnrolment.end_date',
                'AcademicPeriods.name as academic_period_name',
                'Statuses.name as enrolment_status',
                DB::raw("CONCAT(Users.first_name, ' ', Users.last_name) as student_name"),
                'Users.openemis_no as student_openemis_no',
                'Users.first_name as student_first_name',
                'Users.middle_name as student_middle_name',
                'Users.third_name as student_third_name',
                'Users.last_name as student_last_name',
                'Users.preferred_name as student_preferred_name',
                'Users.email as student_email',
                'Users.address as student_address',
                'Users.postal_code as student_postal_code',
                'Users.date_of_birth as student_date_of_birth',
                'Institutions.name as institution_name',
                'Institutions.code as institution_code',
                'Institutions.address as institution_address',
                'Institutions.postal_code as institution_postal_code',
                'Institutions.contact_person as institution_contact_person',
                'Institutions.telephone as institution_telephone',
                'Institutions.email as institution_email',
                'Institutions.website as institution_website',
                'EducationGrades.name as grade_name',
                DB::raw("CONCAT(guardians.first_name, ' ', guardians.last_name) as guardian_name"),
                'guardian_relations.name as guardian_relation',
                'guardian_contacts.value as guardian_contact',
            ]);

        $results = $query->get()->map(function ($item) {
            return (array) $item;
        })->toArray();
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::getPendingItems() query returned ' . count($results) . ' rows, entityId=' . $this->entityId . ', workflowStepIds=' . json_encode($workflowStepIds)); //[TEMP-LOG]
        if (!empty($results)) {
            // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::getPendingItems() first row: ' . json_encode($results[0])); //[TEMP-LOG]
        }

        // Take first record (deduplicate multiple guardian rows)
        $ret = !empty($results) ? [$results[0]] : [];
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::getPendingItems() EXIT returning ' . count($ret) . ' item(s)'); //[TEMP-LOG]
        return $ret;
    }

    /**
     * POCOR-9509: Resolve recipients for student enrolment alert
     *
     * Overrides parent to use student-associated contacts (guardians, student)
     *
     * @param array $item Pending item data
     * @return array Contact list
     */
    protected function resolveRecipients(array $item): array
    {
        $studentId = $item['student_id'] ?? null;
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::resolveRecipients() ENTRY studentId=' . $studentId . ' enrolment_id=' . ($item['id'] ?? 'N/A')); //[TEMP-LOG]

        if (!$studentId) {
            Log::error('[POCOR-9509] student_id not found in item id=' . ($item['id'] ?? '?')); //POCOR-9509
            return ['email' => [], 'phone' => []];
        }

        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::resolveRecipients() roles: ' . json_encode($this->rule->security_roles)); //[TEMP-LOG]
        $contacts = $this->recipientResolver->getStudentAssociatedContactList(
            $this->rule->security_roles,
            $studentId
        );
        // //Log::debug('[TEMP-LOG] @AlertStudentEnrolmentCommand::resolveRecipients() EXIT email_count=' . count($contacts['email'] ?? []) . ' phone_count=' . count($contacts['phone'] ?? []) . ' emails=' . json_encode($contacts['email'] ?? [])); //[TEMP-LOG]

        return $contacts;
    }

    /**
     * POCOR-9509: Fill placeholders for student enrolment alert
     *
     * @param array $item Student enrolment data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        return [
            '${academic_period.name}' => $item['academic_period_name'] ?? '',
            '${start_date}' => $item['start_date'] ?? '',
            '${end_date}' => $item['end_date'] ?? '',
            '${enrolment_status}' => $item['enrolment_status'] ?? '',
            '${student.name}' => $item['student_name'] ?? '',
            '${student.openemis_no}' => $item['student_openemis_no'] ?? '',
            '${student.first_name}' => $item['student_first_name'] ?? '',
            '${student.middle_name}' => $item['student_middle_name'] ?? '',
            '${student.third_name}' => $item['student_third_name'] ?? '',
            '${student.last_name}' => $item['student_last_name'] ?? '',
            '${student.preferred_name}' => $item['student_preferred_name'] ?? '',
            '${student.email}' => $item['student_email'] ?? '',
            '${student.address}' => $item['student_address'] ?? '',
            '${student.postal_code}' => $item['student_postal_code'] ?? '',
            '${student.date_of_birth}' => $item['student_date_of_birth'] ?? '',
            '${institution.name}' => $item['institution_name'] ?? '',
            '${institution.code}' => $item['institution_code'] ?? '',
            '${institution.address}' => $item['institution_address'] ?? '',
            '${institution.postal_code}' => $item['institution_postal_code'] ?? '',
            '${institution.contact_person}' => $item['institution_contact_person'] ?? '',
            '${institution.telephone}' => $item['institution_telephone'] ?? '',
            '${institution.email}' => $item['institution_email'] ?? '',
            '${institution.website}' => $item['institution_website'] ?? '',
            '${grade.name}' => $item['grade_name'] ?? '',
            '${guardian.name}' => $item['guardian_name'] ?? '',
            '${guardian.relation}' => $item['guardian_relation'] ?? '',
            '${guardian.contact}' => $item['guardian_contact'] ?? '',
        ];
    }
}
