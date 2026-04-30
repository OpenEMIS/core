<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertStudentAdmissionCommand
 *
 * Sends alerts for student admission status changes.
 *
 * Usage:
 *   php artisan alerts:student-admission
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *       --status_id=81
 *       --admission_id=456
 *
 * @package App\Console\Commands\Alerts
 */
class AlertStudentAdmissionCommand extends AlertCommandBase
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
    protected $signature = 'alerts:student-admission
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}
                            {--entity_id= : Entity ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for student admission status changes (Laravel port)';

    /**
     * POCOR-9509: Execute the console command
     *
     * @return int
     */
    public function handle(): int
    {
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::handle() ENTRY options: ' . json_encode($this->options())); //[TEMP-LOG]
        if (!$this->prepareContext()) {
            // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::handle() EXIT EARLY - prepareContext failed'); //[TEMP-LOG]
            return self::FAILURE;
        }
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::handle() calling runFeatureAlert(StudentAdmission)'); //[TEMP-LOG]
        $result = $this->runFeatureAlert('StudentAdmission');
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::handle() EXIT result=' . $result); //[TEMP-LOG]
        return $result;
    }

    /**
     * POCOR-9509: Override prepareContext to validate admission_id
     *
     * @return bool
     */
    protected function prepareContext(): bool
    {
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::prepareContext() ENTRY'); //[TEMP-LOG]
        // Call parent prepareContext first
        if (!parent::prepareContext()) {
            // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::prepareContext() EXIT FALSE - parent failed'); //[TEMP-LOG]
            return false;
        }

        // Get and validate admission_id
        $this->entityId = (int) $this->option('entity_id');
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::prepareContext() entityId=' . $this->entityId); //[TEMP-LOG]

        if (!$this->entityId) {
            $this->error("Missing required --entity_id option");
            // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::prepareContext() EXIT FALSE - no entity_id'); //[TEMP-LOG]
            return false;
        }

        // Load admission record
        $admission = DB::table('institution_student_admission')
            ->where('id', $this->entityId)
            ->first();
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::prepareContext() admission row: ' . json_encode($admission)); //[TEMP-LOG]

        if (!$admission) {
            $this->error("Admission with ID {$this->entityId} not found");
            // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::prepareContext() EXIT FALSE - admission not found'); //[TEMP-LOG]
            return false;
        }

        $this->studentId = $admission->student_id;
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::prepareContext() EXIT TRUE studentId=' . $this->studentId); //[TEMP-LOG]
        return true;
    }

    /**
     * POCOR-9509: Get pending student admission records to alert on
     *
     * Queries student_admission for the specific admission with workflow status.
     *
     * @param string $featureKey Feature identifier
     * @return array List of student admission data items
     */
    protected function getPendingItems(string $featureKey): array
    {
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::getPendingItems() ENTRY featureKey=' . $featureKey); //[TEMP-LOG]
        // Parse threshold (workflow step IDs)
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $workflowStepIds = $threshold['workflow_steps'] ?? [];
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::getPendingItems() threshold raw: ' . $this->rule->threshold . ' => workflowStepIds: ' . json_encode($workflowStepIds)); //[TEMP-LOG]

        if (empty($workflowStepIds)) {
            // $this->info("No workflow steps configured in threshold"); //POCOR-9509: commented out per CLAUDE.md
            // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::getPendingItems() EXIT [] - no workflow steps configured'); //[TEMP-LOG]
            return []; //POCOR-9509: no workflow steps = nothing to alert on
        }

        // Query admission with all related data including guardians
        $query = DB::table('institution_student_admission as StudentAdmission')
            ->join('security_users as Users', 'Users.id', '=', 'StudentAdmission.student_id')
            ->join('institutions as Institutions', 'Institutions.id', '=', 'StudentAdmission.institution_id')
            ->join('academic_periods as AcademicPeriods', 'AcademicPeriods.id', '=', 'StudentAdmission.academic_period_id')
            ->join('education_grades as EducationGrades', 'EducationGrades.id', '=', 'StudentAdmission.education_grade_id')
            ->join('workflow_steps as Statuses', 'Statuses.id', '=', 'StudentAdmission.status_id')
            ->leftJoin('student_guardians', 'student_guardians.student_id', '=', 'StudentAdmission.student_id')
            ->leftJoin('security_users as guardians', 'guardians.id', '=', 'student_guardians.guardian_id')
            ->leftJoin('guardian_relations', 'guardian_relations.id', '=', 'student_guardians.guardian_relation_id')
            ->leftJoin('user_contacts as guardian_contacts', 'guardian_contacts.security_user_id', '=', 'guardians.id')
            ->where('StudentAdmission.id', $this->entityId)
            ->whereIn('Statuses.id', $workflowStepIds)
            ->orderBy('guardian_relations.order', 'asc')
            ->orderBy('guardian_contacts.preferred', 'desc')
            ->select([
                'StudentAdmission.id',
                'StudentAdmission.student_id',
                'StudentAdmission.institution_id',
                'StudentAdmission.start_date',
                'StudentAdmission.end_date',
                'AcademicPeriods.name as academic_period_name',
                'Statuses.name as admission_status',
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
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::getPendingItems() query returned ' . count($results) . ' rows, entityId=' . $this->entityId . ', workflowStepIds=' . json_encode($workflowStepIds)); //[TEMP-LOG]
        if (!empty($results)) {
            // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::getPendingItems() first row: ' . json_encode($results[0])); //[TEMP-LOG]
        }

        // Take first record (deduplicate multiple guardian rows)
        $ret = !empty($results) ? [$results[0]] : [];
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::getPendingItems() EXIT returning ' . count($ret) . ' item(s)'); //[TEMP-LOG]
        return $ret;
    }

    /**
     * POCOR-9509: Resolve recipients for student admission alert
     *
     * Overrides parent to use student-associated contacts (guardians, student)
     *
     * @param array $item Pending item data
     * @return array Contact list
     */
    protected function resolveRecipients(array $item): array
    {
        $studentId = $item['student_id'] ?? null;
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::resolveRecipients() ENTRY studentId=' . $studentId . ' admission_id=' . ($item['id'] ?? 'N/A')); //[TEMP-LOG]

        if (!$studentId) {
            Log::error('[POCOR-9509] student_id not found in item id=' . ($item['id'] ?? '?')); //POCOR-9509
            return ['email' => [], 'phone' => []];
        }

        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::resolveRecipients() roles: ' . json_encode($this->rule->security_roles)); //[TEMP-LOG]
        $contacts = $this->recipientResolver->getStudentAssociatedContactList(
            $this->rule->security_roles,
            $studentId
        );
        // //Log::debug('[TEMP-LOG] @AlertStudentAdmissionCommand::resolveRecipients() EXIT email_count=' . count($contacts['email'] ?? []) . ' phone_count=' . count($contacts['phone'] ?? []) . ' emails=' . json_encode($contacts['email'] ?? [])); //[TEMP-LOG]

        return $contacts;
    }

    /**
     * POCOR-9509: Fill placeholders for student admission alert
     *
     * @param array $item Student admission data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        return [
            '${academic_period.name}' => $item['academic_period_name'] ?? '',
            '${start_date}' => $item['start_date'] ?? '',
            '${end_date}' => $item['end_date'] ?? '',
            '${admission_status}' => $item['admission_status'] ?? '',
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
