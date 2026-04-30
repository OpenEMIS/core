<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Command to send alerts for student status changes
 *
 * Sends alerts for student status changes.
 *
 * Usage:
 *   php artisan alerts:student-status-change
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *       --entity_id=550e8400-e29b-41d4-a716-446655440000
 *
 * @package App\Console\Commands\Alerts
 */
class AlertStudentStatusChangeCommand extends AlertCommandBase
{
    /** @var string */
    protected $entityId = '';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:student-status-change
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}
                            {--entity_id= : Entity ID (institution_students.id UUID)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for student status changes';

    /**
     * POCOR-9509: Execute the console command
     *
     * @return int
     */
    public function handle(): int
    {
        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::handle() ENTRY options: ' . json_encode($this->options())); //[TEMP-LOG]
        if (!$this->prepareContext()) {
            // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::handle() EXIT EARLY - prepareContext failed'); //[TEMP-LOG]
            return self::FAILURE;
        }
        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::handle() calling runFeatureAlert(StudentStatus)'); //[TEMP-LOG]
        $result = $this->runFeatureAlert('StudentStatus'); //POCOR-9509: match behavior feature key (was 'StudentStatusChange')
        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::handle() EXIT result=' . $result); //[TEMP-LOG]
        return $result;
    }

    /**
     * POCOR-9509: Override prepareContext to validate entity_id (institution_students.id)
     *
     * @return bool
     */
    protected function prepareContext(): bool
    {
        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::prepareContext() ENTRY'); //[TEMP-LOG]
        // Call parent prepareContext first
        if (!parent::prepareContext()) {
            // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::prepareContext() EXIT FALSE - parent failed'); //[TEMP-LOG]
            return false;
        }

        // Get and validate entity_id
        $this->entityId = (string) $this->option('entity_id');
        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::prepareContext() entityId=' . $this->entityId); //[TEMP-LOG]

        if ($this->entityId === '') {
            $this->error("Missing required --entity_id option (institution_students.id)");
            // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::prepareContext() EXIT FALSE - no entity_id'); //[TEMP-LOG]
            return false;
        }

        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::prepareContext() EXIT TRUE'); //[TEMP-LOG]
        return true;
    }

    /**
     * POCOR-9509: Get pending student records to alert on for status changes
     *
     * Queries institution_students for the specific student status record.
     *
     * @param string $featureKey Feature identifier
     * @return array List of student data items
     */
    protected function getPendingItems(string $featureKey): array
    {
        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::getPendingItems() ENTRY featureKey=' . $featureKey . ' entityId=' . $this->entityId); //[TEMP-LOG]
        // Parse threshold (workflow step IDs)
        $threshold = json_decode($this->rule->threshold ?? '{}', true);
        $studentStatusIds = $threshold['statuses'] ?? [];
        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::getPendingItems() threshold raw: ' . $this->rule->threshold . ' => studentStatusIds: ' . json_encode($studentStatusIds)); //[TEMP-LOG]

        if (empty($studentStatusIds)) {
            // $this->info("No statuses configured in threshold"); //POCOR-9509: commented out per CLAUDE.md
            // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::getPendingItems() EXIT [] - no statuses configured'); //[TEMP-LOG]
            return []; //POCOR-9509: no status IDs = nothing to alert on
        }

        // Query student with all related data including guardians
        // Join institution_students on Users.id, not Users.student_status_id
        $data = DB::table('institution_students as InstitutionStudents')
            ->leftJoin('security_users as Users', 'Users.id', '=', 'InstitutionStudents.student_id')
            ->leftJoin('institutions as Institutions', 'Institutions.id', '=', 'InstitutionStudents.institution_id')
            ->leftJoin('academic_periods as AcademicPeriods', 'AcademicPeriods.id', '=', 'InstitutionStudents.academic_period_id')
            ->leftJoin('education_grades as EducationGrades', 'EducationGrades.id', '=', 'InstitutionStudents.education_grade_id')
            ->leftJoin('student_statuses as StudentStatuses', 'StudentStatuses.id', '=', 'InstitutionStudents.student_status_id')
            ->where('InstitutionStudents.id', $this->entityId)
            ->whereIn('StudentStatuses.id', $studentStatusIds)
            ->select([
                'InstitutionStudents.id',
                'InstitutionStudents.student_id as student_id',
                'InstitutionStudents.institution_id as institution_id', //POCOR-9509: needed for staff-role recipient resolution
                'InstitutionStudents.start_date',
                'InstitutionStudents.end_date',
                'AcademicPeriods.name as academic_period_name',
                'StudentStatuses.name as student_status',
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
            ])
            ->first(); // We will get the student details once

        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::getPendingItems() query result: ' . json_encode($data)); //[TEMP-LOG]
        if (!$data) {
            $this->error('[POCOR-9509] No relevant student status data found for placeholders institution_student_id=' . $this->entityId . ' studentStatusIds=' . json_encode($studentStatusIds));
            // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::getPendingItems() EXIT [] - no data found for entityId=' . $this->entityId . ' statusIds=' . json_encode($studentStatusIds)); //[TEMP-LOG]
            return [];
        }

        // Get all guardians for this student
        $guardians = DB::table('student_guardians')
            ->join('security_users as guardians', 'guardians.id', '=', 'student_guardians.guardian_id')
            ->leftJoin('guardian_relations', 'guardian_relations.id', '=', 'student_guardians.guardian_relation_id')
            ->where('student_guardians.student_id', $data->student_id)
            ->select([
                DB::raw("CONCAT(guardians.first_name, ' ', guardians.last_name) as guardian_name"),
                'guardian_relations.name as guardian_relation',
                'guardians.email as guardian_email',
                'guardians.mobile_number as guardian_mobile_number',
            ])
            ->get();

        $guardianNames = [];
        $guardianRelations = [];
        $guardianContacts = [];

        foreach ($guardians as $guardian) {
            if ($guardian->guardian_name) {
                $guardianNames[] = $guardian->guardian_name;
            }
            if ($guardian->guardian_relation) {
                $guardianRelations[] = $guardian->guardian_relation;
            }
            if ($guardian->guardian_email) {
                $guardianContacts[] = $guardian->guardian_email . ' (email)';
            }
            if ($guardian->guardian_mobile_number) {
                $guardianContacts[] = $guardian->guardian_mobile_number . ' (mobile)';
            }
        }

        $student = [
            'student_id' => $data->student_id,
            'institution_id' => $data->institution_id, //POCOR-9509: needed for staff-role recipient resolution
            '${academic_period.name}' => $data->academic_period_name ?? '',
            '${start_date}' => $data->start_date ?? '',
            '${end_date}' => $data->end_date ?? '',
            '${student_status}' => $data->student_status ?? '',
            '${student.name}' => $data->student_name ?? '',
            '${student.openemis_no}' => $data->student_openemis_no ?? '',
            '${student.first_name}' => $data->student_first_name ?? '',
            '${student.middle_name}' => $data->student_middle_name ?? '',
            '${student.third_name}' => $data->student_third_name ?? '',
            '${student.last_name}' => $data->student_last_name ?? '',
            '${student.preferred_name}' => $data->student_preferred_name ?? '',
            '${student.email}' => $data->student_email ?? '',
            '${student.address}' => $data->student_address ?? '',
            '${student.postal_code}' => $data->student_postal_code ?? '',
            '${student.date_of_birth}' => $data->student_date_of_birth ?? '',
            '${institution.name}' => $data->institution_name ?? '',
            '${institution.code}' => $data->institution_code ?? '',
            '${institution.address}' => $data->institution_address ?? '',
            '${institution.postal_code}' => $data->institution_postal_code ?? '',
            '${institution.contact_person}' => $data->institution_contact_person ?? '',
            '${institution.telephone}' => $data->institution_telephone ?? '',
            '${institution.email}' => $data->institution_email ?? '',
            '${institution.website}' => $data->institution_website ?? '',
            '${grade.name}' => $data->grade_name ?? '',
            '${guardian.name}' => implode(', ', $guardianNames),
            '${guardian.relation}' => implode(', ', $guardianRelations),
            '${guardian.contact}' => implode(', ', $guardianContacts),
        ];

        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::getPendingItems() EXIT returning 1 item student_id=' . ($student['student_id'] ?? 'null')); //[TEMP-LOG]
        return [$student]; // Return as indexed array
    }

    /**
     * POCOR-9509: Resolve recipients for student status change alert
     *
     * Overrides parent to use student-associated contacts (guardians, student)
     *
     * @param array $item Pending item data
     * @return array Contact list
     */
    protected function resolveRecipients(array $item): array
    {
        $studentId    = (int) ($item['student_id'] ?? 0);
        $institutionId = (int) ($item['institution_id'] ?? 0);
        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::resolveRecipients() ENTRY studentId=' . $studentId . ' institutionId=' . $institutionId); //[TEMP-LOG]
        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::resolveRecipients() roles: ' . json_encode($this->rule->security_roles)); //[TEMP-LOG]

        //POCOR-9509: split roles into student-associated (Guardian=9, Student=8) vs institution-staff (everything else)
        $studentRoleIds = [self::ROLE_STUDENT, self::ROLE_GUARDIAN]; // 8, 9
        $studentRoles = array_values(array_filter(
            $this->rule->security_roles,
            fn($r) => in_array((int)(is_array($r) ? $r['id'] : $r->id), $studentRoleIds, true)
        ));
        $staffRoles = array_values(array_filter(
            $this->rule->security_roles,
            fn($r) => !in_array((int)(is_array($r) ? $r['id'] : $r->id), $studentRoleIds, true)
        ));

        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::resolveRecipients() studentRoles=' . count($studentRoles) . ' staffRoles=' . count($staffRoles)); //[TEMP-LOG]

        $studentContacts = ['email' => [], 'phone' => []];
        if (!empty($studentRoles) && $studentId) {
            $studentContacts = $this->recipientResolver->getStudentAssociatedContactList($studentRoles, $studentId);
        }

        $staffContacts = ['email' => [], 'phone' => []];
        if (!empty($staffRoles) && $institutionId) {
            $staffContacts = $this->recipientResolver->getRoleAssociatedContactList($staffRoles, $institutionId);
        }

        $contacts = [
            'email' => array_values(array_unique(array_merge($studentContacts['email'], $staffContacts['email']))),
            'phone' => array_values(array_unique(array_merge($studentContacts['phone'], $staffContacts['phone']))),
        ];

        // //Log::debug('[TEMP-LOG] @AlertStudentStatusChangeCommand::resolveRecipients() EXIT email_count=' . count($contacts['email']) . ' phone_count=' . count($contacts['phone']) . ' emails=' . json_encode($contacts['email'])); //[TEMP-LOG]
        return $contacts;
    }

    /**
     * POCOR-9509: Fill placeholders for student status change alert
     *
     * @param array $item Student data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        unset($item['student_id']);     // Remove non-placeholder metadata keys
        unset($item['institution_id']); //POCOR-9509: remove before returning as placeholder map
        return $item; // The rest of the keys are already in ${...} format for placeholders
    }
}
