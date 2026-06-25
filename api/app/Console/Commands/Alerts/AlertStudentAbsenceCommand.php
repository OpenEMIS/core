<?php
declare(strict_types=1);

namespace App\Console\Commands\Alerts;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9509: Laravel port of CakePHP's AlertStudentAbsenceCommand
 *
 * Sends alerts for students with excessive absences.
 *
 * Usage:
 *   php artisan alerts:student-absence
 *       --user_id=1
 *       --rule_id=5
 *       --process_id=123
 *       --student_id=456
 *       --institution_id=789
 *       --academic_period_id=10
 *       --institution_class_id=20
 *       --period=1
 *       --subject_id=30
 *
 * @package App\Console\Commands\Alerts
 */
class AlertStudentAbsenceCommand extends AlertCommandBase
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:student-absence
                            {--user_id= : User ID triggering the alert}
                            {--rule_id= : Alert rule ID}
                            {--process_id= : System process ID}
                            {--student_id= : Student security_user_id}
                            {--institution_id= : Institution ID}
                            {--academic_period_id= : Academic period ID}
                            {--institution_class_id= : Institution class ID}
                            {--period= : Period ID}
                            {--subject_id= : Subject ID}
                            {--date= : Optional date filter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'POCOR-9509: Send alerts for student absences (Laravel port)';

    /**
     * POCOR-9509: Execute the console command
     *
     * @return int
     */
    public function handle(): int
    {
        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::handle() ENTRY'); //[TEMP-LOG]
        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::handle() options: ' . json_encode($this->options())); //[TEMP-LOG]

        if (!$this->prepareContext()) {
            // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::handle() EXIT EARLY - prepareContext() failed'); //[TEMP-LOG]
            return self::FAILURE;
        }

        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::handle() prepareContext() succeeded'); //[TEMP-LOG]

        // Validate student-specific parameters
        $studentId = (int) $this->option('student_id');
        $academicPeriodId = (int) $this->option('academic_period_id');

        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::handle() studentId=' . $studentId . ', academicPeriodId=' . $academicPeriodId); //[TEMP-LOG]

        if (!$studentId || !$academicPeriodId) {
            $this->error("Missing required options: student_id, academic_period_id");
            //POCOR-9509: mark process failed so system_processes never hangs at status=1
            $this->markProcessFailed('Missing required options: student_id, academic_period_id');
            return self::FAILURE;
        }

        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::handle() About to call runFeatureAlert()'); //[TEMP-LOG]
        $result = $this->runFeatureAlert('StudentAttendance');
        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::handle() EXIT - result=' . $result); //[TEMP-LOG]
        return $result;
    }

    /**
     * POCOR-9509: Get pending absence records to alert on
     *
     * Queries institution_student_absence_details for absences that exceed the threshold.
     *
     * @param string $featureKey Feature identifier
     * @return array List of absence data items
     */
    protected function getPendingItems(string $featureKey): array
    {
        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::getPendingItems() ENTRY - featureKey=' . $featureKey); //[TEMP-LOG]

        $studentId = (int) $this->option('student_id');
        $academicPeriodId = (int) $this->option('academic_period_id');
        $threshold = (int) ($this->rule->threshold ?? 1);

        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::getPendingItems() studentId=' . $studentId . ', academicPeriodId=' . $academicPeriodId . ', threshold=' . $threshold); //[TEMP-LOG]

        // Query absences for this student
        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::getPendingItems() Executing DB query...'); //[TEMP-LOG]
        $absences = DB::table('institution_student_absence_details as absences')
            ->join('security_users as users', 'users.id', '=', 'absences.student_id')
            ->join('institutions', 'institutions.id', '=', 'absences.institution_id')
            ->leftJoin('genders', 'genders.id', '=', 'users.gender_id')
            ->leftJoin('nationalities as main_nat', 'main_nat.id', '=', 'users.nationality_id')
            ->leftJoin('identity_types as main_id_type', 'main_id_type.id', '=', 'users.identity_type_id')
            ->where('absences.student_id', $studentId)
            ->where('absences.academic_period_id', $academicPeriodId)
            ->whereIn('absences.absence_type_id', [1, 2]) // Absent types
            ->orderBy('absences.date', 'ASC')
            ->select([
                'absences.date',
                'absences.student_id',
                'absences.institution_id',
                'absences.institution_class_id',
                'users.openemis_no as student_openemis_no',
                'users.first_name as student_first_name',
                'users.middle_name as student_middle_name',
                'users.third_name as student_third_name',
                'users.last_name as student_last_name',
                'users.preferred_name as student_preferred_name',
                'users.email as student_email',
                'users.address as student_address',
                'users.postal_code as student_postal_code',
                'users.date_of_birth as student_date_of_birth',
                'users.identity_number as student_identity_number',
                'institutions.name as institution_name',
                'institutions.code as institution_code',
                'institutions.address as institution_address',
                'institutions.postal_code as institution_postal_code',
                'institutions.contact_person as institution_contact_person',
                'institutions.telephone as institution_telephone',
                'institutions.email as institution_email',
                'institutions.website as institution_website',
                'genders.name as gender_name',
                'main_nat.name as nationality_name',
                'main_id_type.name as identity_type_name',
            ])
            ->get()
            ->toArray();

        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::getPendingItems() Query returned ' . count($absences) . ' rows'); //[TEMP-LOG]

        if (empty($absences)) {
            // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::getPendingItems() EXIT - No absences found'); //[TEMP-LOG]
            return [];
        }

        // Log first few absence records for debugging
        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::getPendingItems() Sample data (first 3): ' . json_encode(array_slice($absences, 0, 3))); //[TEMP-LOG]

        //POCOR-9509: start - Honor system config `calculate_daily_attendance` (config_items.code).
        //  value=1 → "Mark absent if one or more records absent": ANY absent record on a date
        //           counts the date as a fully-absent day (default current behaviour).
        //  value=2 → "Mark present if one or more records present": a date is a fully-absent
        //           day only when EVERY EXPECTED period for that class+date has an absent
        //           row. If marking is incomplete (P1 saved, P2 not yet opened) the date is
        //           NOT counted — incomplete data must not produce a false-positive alert.
        //
        //Resolution: per (class, date), look up `student_attendance_mark_types.attendance_per_day`
        //via institution_class_grades → student_mark_type_status_grades → student_mark_type_statuses
        //(same chain AttendanceRepository::getAttendancePerDayOptionsByClass uses). For
        //SUBJECT-only mode (attendance_type.code = 'SUBJECT', attendance_per_day=0) there is no
        //expected count — fall back to comparing absent_count vs marked-so-far records (handles
        //"5 of 20 subjects taught today" sparse scheduling on a best-effort basis pending
        //design clarification on subject-mode semantics).
        //
        //IMPORTANT: This only adjusts `total_days`. `total_times` (count of absence rows) is
        //unchanged — the message text reads "X times absent, Y days absent" and both stay
        //accurate.
        $attendanceRule = (int) (DB::table('config_items')
            ->where('code', 'calculate_daily_attendance')
            ->value('value') ?? 1);

        // Count unique absence dates (rule 1 default — every distinct date counts)
        $uniqueDates = [];
        $absentCountByDate = [];
        $classIdByDate = [];
        foreach ($absences as $absence) {
            if (!empty($absence->date)) {
                $dateKey = (string) $absence->date;
                $uniqueDates[$dateKey] = true;
                $absentCountByDate[$dateKey] = ($absentCountByDate[$dateKey] ?? 0) + 1;
                $classIdByDate[$dateKey] = (int) $absence->institution_class_id;
            }
        }

        if ($attendanceRule === 2 && !empty($uniqueDates)) {
            $droppedDates = [];
            foreach ($uniqueDates as $date => $_) {
                $classId = $classIdByDate[$date];
                // Resolve mark-type for THIS class on THIS date.
                $markTypeRow = DB::table('student_attendance_mark_types as mt')
                    ->leftJoin('student_attendance_types as t', 't.id', '=', 'mt.student_attendance_type_id')
                    ->leftJoin('student_mark_type_statuses as s', 's.student_attendance_mark_type_id', '=', 'mt.id')
                    ->leftJoin('student_mark_type_status_grades as sg', 'sg.student_mark_type_status_id', '=', 's.id')
                    ->join('institution_class_grades as cg', 'cg.education_grade_id', '=', 'sg.education_grade_id')
                    ->where('cg.institution_class_id', $classId)
                    ->where('s.academic_period_id', $academicPeriodId)
                    ->where('s.date_enabled', '<=', $date)
                    ->where('s.date_disabled', '>=', $date)
                    ->select('mt.attendance_per_day', 't.code as type_code')
                    ->first();

                $typeCode      = $markTypeRow->type_code ?? '';
                $expectedPerDay = (int) ($markTypeRow->attendance_per_day ?? 0);

                if ($typeCode !== 'SUBJECT' && $expectedPerDay > 0) {
                    // DAY or DAY_AND_SUBJECT: incomplete marking ⇒ don't count.
                    if ($absentCountByDate[$date] < $expectedPerDay) {
                        unset($uniqueDates[$date]);
                        $droppedDates[] = $date . '(absent=' . $absentCountByDate[$date] . '/expected=' . $expectedPerDay . ')';
                    }
                } else {
                    // SUBJECT-only: no expected count — fall back to marked-records comparison.
                    $markedCount = (int) DB::table('student_attendance_marked_records')
                        ->where('institution_class_id', $classId)
                        ->where('date', $date)
                        ->where('no_scheduled_class', 0)
                        ->count();
                    if ($markedCount > 0 && $absentCountByDate[$date] < $markedCount) {
                        unset($uniqueDates[$date]);
                        $droppedDates[] = $date . '(SUBJECT,absent=' . $absentCountByDate[$date] . '/marked=' . $markedCount . ')';
                    }
                }
            }
            if (!empty($droppedDates)) {
                // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::getPendingItems() Rule=2 dropped ' . count($droppedDates) . ' incomplete/partial dates: ' . implode(',', $droppedDates)); //[TEMP-LOG]
            }
        }
        //POCOR-9509: end

        $totalDays = count($uniqueDates);

        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::getPendingItems() Unique dates count: ' . $totalDays . ', Total records: ' . count($absences)); //[TEMP-LOG]

        // Check against threshold
        if ($totalDays < $threshold) {
            // $this->info("Student has {$totalDays} absence days, below threshold of {$threshold}"); //POCOR-9509: commented out per CLAUDE.md
            // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::getPendingItems() EXIT - Below threshold'); //[TEMP-LOG]
            return [];
        }

        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::getPendingItems() Threshold met, building result'); //[TEMP-LOG]

        // Build result from first absence record (all have same student/institution data)
        $first = $absences[0];

        $result = [[
            'student_id' => $first->student_id,
            'institution_id' => $first->institution_id,
            'institution_class_id' => $first->institution_class_id,
            'student_name' => trim($first->student_first_name . ' ' . $first->student_last_name),
            'student_openemis_no' => $first->student_openemis_no,
            'student_first_name' => $first->student_first_name,
            'student_middle_name' => $first->student_middle_name,
            'student_third_name' => $first->student_third_name,
            'student_last_name' => $first->student_last_name,
            'student_preferred_name' => $first->student_preferred_name,
            'student_email' => $first->student_email,
            'student_address' => $first->student_address,
            'student_postal_code' => $first->student_postal_code,
            'student_date_of_birth' => $first->student_date_of_birth,
            'student_identity_number' => $first->student_identity_number,
            'student_gender' => $first->gender_name ?? '',
            'student_nationality' => $first->nationality_name ?? '',
            'student_identity_type' => $first->identity_type_name ?? '',
            'institution_name' => $first->institution_name,
            'institution_code' => $first->institution_code,
            'institution_address' => $first->institution_address,
            'institution_postal_code' => $first->institution_postal_code,
            'institution_contact_person' => $first->institution_contact_person,
            'institution_telephone' => $first->institution_telephone,
            'institution_email' => $first->institution_email,
            'institution_website' => $first->institution_website,
            'total_days' => $totalDays,
            'total_times' => count($absences),
        ]];

        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::getPendingItems() EXIT - returning 1 pending item with total_days=' . $totalDays); //[TEMP-LOG]
        return $result;
    }

    /**
     * POCOR-9509: Resolve recipients for student absence alert
     *
     * Overrides parent to use student-associated contacts (guardians, student)
     *
     * @param array $item Pending item data
     * @return array Contact list
     */
    protected function resolveRecipients(array $item): array
    {
        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::resolveRecipients() ENTRY'); //[TEMP-LOG]
        // // Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::resolveRecipients() institution_id=' . ($item['institution_id'] ?? 'null') . ', institution_class_id=' . ($item['institution_class_id'] ?? 'null')); //[TEMP-LOG]

        $institutionId    = (int) ($item['institution_id'] ?? 0);
        $institutionClassId = (int) ($item['institution_class_id'] ?? 0);

        // POCOR-9509: StudentAttendance recipients = this class's staff only + Principal/Deputy Principal at this institution only
        // Step 1: class teachers (primary + secondary) — only if Teacher(6) or Homeroom Teacher(5) is in alerts_roles
        $classStaffRoleIds = [5, 6]; // Homeroom Teacher, Teacher
        $hasClassStaffRole = !empty(array_filter(
            $this->rule->security_roles,
            fn($r) => in_array((int)(is_array($r) ? $r['id'] : $r->id), $classStaffRoleIds, true)
        ));
        $classContacts = ['email' => [], 'phone' => []];
        if ($institutionClassId && $hasClassStaffRole) {
            $classContacts = $this->recipientResolver->getClassTeacherContactList($institutionClassId);
        }

        // Step 2: Principal (4) + Deputy Principal (11) at this institution — only if present in alerts_roles
        $managementRoleIds = [4, 11]; // Principal, Deputy Principal
        $managementRoles = array_values(array_filter(
            $this->rule->security_roles,
            fn($r) => in_array((int)(is_array($r) ? $r['id'] : $r->id), $managementRoleIds, true)
        ));
        $principalContacts = ['email' => [], 'phone' => []];
        if ($institutionId && !empty($managementRoles)) {
            $principalContacts = $this->recipientResolver->getRoleAssociatedContactList(
                $managementRoles,
                $institutionId
            );
        }

        $contacts = [
            'email' => array_values(array_unique(array_merge($classContacts['email'], $principalContacts['email']))),
            'phone' => array_values(array_unique(array_merge($classContacts['phone'], $principalContacts['phone']))),
        ];

        // //Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::resolveRecipients() TOTAL: email=' . count($contacts['email']) . ', phone=' . count($contacts['phone'])); //[TEMP-LOG]
        // //Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::resolveRecipients() EXIT'); //[TEMP-LOG]
        return $contacts;
    }

    /**
     * POCOR-9509: Fill placeholders for student absence alert
     *
     * Maps absence data to ${placeholder} => value array.
     * Placeholders match CakePHP's AlertStudentAbsenceCommand.
     *
     * @param array $item Absence data from getPendingItems()
     * @return array Placeholder => value mapping
     */
    protected function fillPlaceholders(array $item): array
    {
        $threshold = (int) ($this->rule->threshold ?? 1);

        // //Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::fillPlaceholders() ENTRY'); //[TEMP-LOG]
        // //Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::fillPlaceholders() threshold=' . $threshold . ', item: ' . json_encode($item)); //[TEMP-LOG]

        $placeholders = [
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
            '${student.gender}' => $item['student_gender'] ?? '',
            '${student.identity_number}' => $item['student_identity_number'] ?? '',
            '${student.main_nationality}' => $item['student_nationality'] ?? '',
            '${student.identity_type}' => $item['student_identity_type'] ?? '',
            '${institution.name}' => $item['institution_name'] ?? '',
            '${institution.code}' => $item['institution_code'] ?? '',
            '${institution.address}' => $item['institution_address'] ?? '',
            '${institution.postal_code}' => $item['institution_postal_code'] ?? '',
            '${institution.contact_person}' => $item['institution_contact_person'] ?? '',
            '${institution.telephone}' => $item['institution_telephone'] ?? '',
            '${institution.email}' => $item['institution_email'] ?? '',
            '${institution.website}' => $item['institution_website'] ?? '',
            '${total_days}' => (string) ($item['total_days'] ?? 0),
            '${total_times}' => (string) ($item['total_times'] ?? 0),
            '${threshold}' => (string) $threshold,
        ];

        // //Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::fillPlaceholders() Generated placeholders: ' . json_encode($placeholders)); //[TEMP-LOG]
        // //Log::debug('[TEMP-LOG] @AlertStudentAbsenceCommand::fillPlaceholders() EXIT'); //[TEMP-LOG]
        return $placeholders;
    }
}
