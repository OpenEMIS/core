<?php
//POCOR-9611: Students Weekly Attendance — InstitutionStandards feature template
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use App\Model\Table\AppTable;
use Cake\Datasource\ConnectionManager;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use DateTime;

/**
 * Students Weekly Attendance report.
 *
 * Registered as a feature under Institution > Statistics > Standard.
 * Generates one Excel row per student showing PRESENT/LATE/EXCUSED/UNEXCUSED/NOTMARKED/NO CLASS
 * status for each school day × attendance slot (period or subject) within the selected week.
 *
 * Supports attendance modes:
 *   DAY (type 1)            — period slots, subject_id=0
 *   SUBJECT (type 2)        — subject slots, period=0 in absence_details / period=1 in mark_records
 *   DAY_AND_SUBJECT (type 3)— both sets of columns
 *
 * Input params (stored as JSON in report_progress.params by ReportListBehavior):
 *   institution_id, academic_period_id, education_grade_id, institution_class_id, week_start_day (Y-m-d)
 *
 * @ticket POCOR-9611
 */
class InstitutionStudentWeeklyAttendanceTable extends AppTable
{
    public function initialize(array $config): void
    {
        //POCOR-9611: institution_class_students is the root data table for this report
        $this->setTable('institution_class_students');
        parent::initialize($config);

        $this->addBehavior('Excel', [
            'excludes'   => [],
            'pages'      => false,
            'autoFields' => false,
        ]);
        $this->addBehavior('Report.ReportList');
    }

    //POCOR-9611: No entity validation needed — data goes to report_progress, not this table
    public function validationDefault(Validator $validator): Validator
    {
        return $validator;
    }

    // -----------------------------------------------------------------------
    // Excel generation events (called by ContactExcelBehavior via ReportProgress)
    // -----------------------------------------------------------------------

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets): void
    {
        //POCOR-9611: Pre-compute slots and week dates; store in $settings for the other two events
        $requestData      = json_decode($settings['process']['params']);
        $academicPeriodId = (int)$requestData->academic_period_id;
        $institutionId    = (int)$requestData->institution_id;
        $weekStartDay     = $requestData->week_start_day ?? '';
        $gradeId = isset($requestData->education_grade_id) ? (int)$requestData->education_grade_id : null;
        $classId = isset($requestData->institution_class_id) ? (int)$requestData->institution_class_id : null;

        //POCOR-9611: Compute dates FIRST so slot detection is scoped to the actual report week
        $week        = $this->_getWeekDays($weekStartDay);
        $reportDates = array_values($week['days'] ?? []);
        $dayId       = $reportDates[0] ?? date('Y-m-d');

        //POCOR-9611: Resolve mode from the class's actual configuration (same lookup as the
        //            Institution > Attendance > Students screen), not from marked-record guessing.
        $resolved     = $this->_resolveAttendanceConfig($academicPeriodId, $classId, $gradeId, $dayId);
        $attendanceBy = $resolved['attendance_by'];
        $slots        = ($attendanceBy === 'subject')
            ? $this->_buildSubjectSlots($institutionId, $academicPeriodId, $classId, $gradeId)
            : $this->_buildPeriodSlots($resolved['mark_type_id']);
        $periodLabel  = implode(', ', array_values(array_unique(array_column($slots, 'label'))));
        $colDefs      = $this->_buildColDefs($slots, $week);

        $settings['_slots']         = $slots;
        $settings['_week']          = $week;
        $settings['_col_defs']      = $colDefs;
        $settings['_attendance_by'] = $attendanceBy;
        $settings['_period_label']  = $periodLabel;

        $sheets[] = [
            'name'        => $this->getAlias(),
            'table'       => $this,
            'query'       => $this->find(),
            'orientation' => 'landscape',
        ];
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields): void
    {
        //POCOR-9611: Fixed prefix columns + dynamic day×slot columns + fixed suffix totals
        $colDefs = $settings['_col_defs'] ?? [];

        $newFields = [
            ['key' => 'openemis_no',   'field' => 'openemis_no',   'type' => 'string', 'label' => __('Openemis ID')],
            ['key' => 'student_name',  'field' => 'student_name',  'type' => 'string', 'label' => __('Name')],
            ['key' => 'attendance_by', 'field' => 'attendance_by', 'type' => 'string', 'label' => __('Attendance By')],
            ['key' => 'period_label',  'field' => 'period_label',  'type' => 'string', 'label' => __('Period')],
            ['key' => 'week_label',    'field' => 'week_label',    'type' => 'string', 'label' => __('Current Week')],
        ];

        //POCOR-9611: attendance_status type triggers onExcelRenderAttendanceStatus for cell colouring
        foreach ($colDefs as $col) {
            $newFields[] = [
                'key'   => $col['key'],
                'field' => $col['key'],
                'type'  => 'attendance_status',
                'label' => $col['label'],
            ];
        }

        $newFields[] = ['key' => 'total_present', 'field' => 'total_present', 'type' => 'integer', 'label' => __('Total present')];
        $newFields[] = ['key' => 'total_late',    'field' => 'total_late',    'type' => 'integer', 'label' => __('Total late')];
        $newFields[] = ['key' => 'total_absent',  'field' => 'total_absent',  'type' => 'integer', 'label' => __('Total absent')];

        $fields->exchangeArray($newFields);
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query): void
    {
        //POCOR-9611: Build single-week query and wrap as CakePHP ORM subquery
        $requestData      = json_decode($settings['process']['params']);
        $institutionId    = (int)$requestData->institution_id;
        $academicPeriodId = (int)$requestData->academic_period_id;
        //POCOR-9611: null or <1 means "All"
        $gradeId = isset($requestData->education_grade_id) ? (int)$requestData->education_grade_id : null;
        $classId = isset($requestData->institution_class_id) ? (int)$requestData->institution_class_id : null;

        $slots         = $settings['_slots'];
        $week          = $settings['_week'];
        $attendanceBy  = $settings['_attendance_by'];
        $periodLabel   = $settings['_period_label'];

        if (empty($week) || empty($slots)) {
            $query->where(['1 = 0']); //POCOR-9611: nothing to show
            return;
        }

        $weekSql = $this->_buildWeekSQL(
            $week,
            $slots,
            $institutionId,
            $academicPeriodId,
            $classId,
            $gradeId,
            $attendanceBy,
            $periodLabel
        );

        //POCOR-9611: Build SELECT list matching all aliases produced by _buildWeekSQL
        //            One column per day (collapsed across slots) — see _buildWeekSQL/_buildColDefs
        $selectList = ['openemis_no', 'student_name', 'attendance_by', 'period_label', 'week_label'];
        foreach ($week['days'] as $dayKey => $_) {
            $selectList[] = "col_{$dayKey}";
        }
        $selectList[] = 'total_present';
        $selectList[] = 'total_late';
        $selectList[] = 'total_absent';

        $selectExpr = [];
        foreach ($selectList as $col) {
            $selectExpr[$col] = "wd.{$col}";
        }

        $query
            ->select($selectExpr)
            ->from(['wd' => "({$weekSql})"])
            ->order(['wd.student_name']);
    }

    /**
     * Cell renderer for attendance_status columns.
     * Returns value + XLSXWriter fill style per status.
     */
    public function onExcelRenderAttendanceStatus(EventInterface $event, Entity $entity, array $attr): array
    {
        //POCOR-9611: Map each attendance status to a background colour
        $value = $entity->has($attr['field']) ? $entity->{$attr['field']} : '';

        $colors = [
            'PRESENT'   => '#92D050', // green
            'LATE'      => '#FFC000', // amber
            'EXCUSED'   => '#FFFF00', // yellow
            'UNEXCUSED' => '#FF0000', // red
            'NOTMARKED' => '#D3D3D3', // light gray
            'NO CLASS'  => '#808080', // dark gray
        ];

        //POCOR-9611: XLSXWriter uses 'fill' (hex string) — not 'fill_color'/'fill_pattern_type' (PhpSpreadsheet keys)
        $style = isset($colors[$value])
            ? ['fill' => $colors[$value], 'halign' => 'center']
            : ['halign' => 'center'];

        return ['value' => $value, 'style' => $style];
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Resolve the authoritative attendance mode ('period' or 'subject') for this class, plus
     * the specific student_attendance_mark_types.id in effect (for period mode).
     *
     * This mirrors the exact join chain used by Attendance.StudentAttendanceTypes::
     * findAttendanceTypeCode() — the lookup that drives the "Attendance By" selector on
     * Institution > Attendance > Students for this same class — instead of guessing the mode
     * from whatever rows happen to sit in student_attendance_marked_records. That guess-based
     * approach broke down for classes with stale/legacy marked records (e.g. test/import data
     * with a subject_id left over from an earlier configuration): a single old subject-tagged
     * row was enough to flip the whole report to "subject" mode even though the class's
     * *current* configuration is Period-based.
     *
     * institution_class_grades ties the class to its grade(s); student_mark_type_status_grades
     * ties that grade to whichever mark type status is active for the academic period + day.
     * Only an exact 'SUBJECT' type code counts as subject mode — DAY and DAY_AND_SUBJECT (and
     * "nothing actively configured") all resolve to 'period', matching the default the real
     * marking screen itself falls back to.
     *
     * @return array{attendance_by: string, mark_type_id: ?int}
     */
    private function _resolveAttendanceConfig(int $academicPeriodId, ?int $classId, ?int $gradeId, string $dayId): array
    {
        $classFilter = ($classId !== null && $classId >= 1) ? "AND icg.institution_class_id = {$classId}" : '';
        $gradeFilter = ($gradeId !== null && $gradeId >= 1) ? "AND icg.education_grade_id = {$gradeId}"   : '';

        if ($classFilter === '' && $gradeFilter === '') {
            //POCOR-9611: No class or grade to scope by — a report spanning many classes can't
            //            have one single mode; default to Period, same as the real screen does.
            return ['attendance_by' => 'period', 'mark_type_id' => null];
        }

        $conn = ConnectionManager::get('default');
        $stmt = $conn->execute(
            "SELECT samt.id AS mark_type_id, sat.code AS type_code
             FROM institution_class_grades icg
             JOIN student_mark_type_status_grades smtsg ON smtsg.education_grade_id = icg.education_grade_id
             JOIN student_mark_type_statuses smts        ON smts.id = smtsg.student_mark_type_status_id
             JOIN student_attendance_mark_types samt      ON samt.id = smts.student_attendance_mark_type_id
             JOIN student_attendance_types sat            ON sat.id = samt.student_attendance_type_id
             WHERE smts.academic_period_id = {$academicPeriodId}
               AND smts.date_enabled <= '{$dayId}'
               AND smts.date_disabled >= '{$dayId}'
               {$classFilter} {$gradeFilter}
             ORDER BY smts.date_enabled DESC, samt.id DESC
             LIMIT 1"
        );
        $row = $stmt->fetch('assoc');

        if (!$row) {
            //POCOR-9611: Nothing actively enabled for this class/grade/day — default to Period.
            return ['attendance_by' => 'period', 'mark_type_id' => null];
        }

        return [
            'attendance_by' => ($row['type_code'] === 'SUBJECT') ? 'subject' : 'period',
            'mark_type_id'  => (int)$row['mark_type_id'],
        ];
    }

    /**
     * Build one column slot per configured period for a Period-mode mark type.
     * Falls back to a single generic "Period 1" slot when no mark type was resolved, or the
     * mark type has no per-day-period rows saved yet.
     *
     * @return array[]
     */
    private function _buildPeriodSlots(?int $markTypeId): array
    {
        if ($markTypeId !== null) {
            $conn = ConnectionManager::get('default');
            $stmt = $conn->execute(
                'SELECT `period`, name
                 FROM student_attendance_per_day_periods
                 WHERE student_attendance_mark_type_id = ?
                 ORDER BY `period`',
                [$markTypeId]
            );
            $slots = [];
            foreach ($stmt->fetchAll('assoc') as $r) {
                $p       = (int)$r['period'];
                $slots[] = [
                    'key'            => "p{$p}",
                    'label'          => $r['name'],
                    'mr_period'      => $p,
                    'mr_subject_id'  => 0,
                    'abd_period'     => $p,
                    'abd_subject_id' => 0,
                ];
            }
            if (!empty($slots)) {
                return $slots;
            }
        }

        return [[
            'key'            => 'p1',
            'label'          => 'Period 1',
            'mr_period'      => 1,
            'mr_subject_id'  => 0,
            'abd_period'     => 1,
            'abd_subject_id' => 0,
        ]];
    }

    /**
     * Build one column slot per subject assigned to the class (or institution-wide when no
     * class is given), for a class _resolveAttendanceConfig() has confirmed is Subject-mode.
     *
     * @return array[]
     */
    private function _buildSubjectSlots(int $institutionId, int $academicPeriodId, ?int $classId, ?int $gradeId): array
    {
        $conn         = ConnectionManager::get('default');
        $subjectWhere = "isub.institution_id = {$institutionId}"
            . " AND isub.academic_period_id = {$academicPeriodId}";
        if ($gradeId !== null && $gradeId >= 1) {
            $subjectWhere .= " AND isub.education_grade_id = {$gradeId}";
        }

        if ($classId !== null && $classId >= 1) {
            //POCOR-9611: Narrow to subjects assigned to this specific class
            $stmt = $conn->execute(
                "SELECT DISTINCT isub.id, isub.name
                 FROM institution_subjects isub
                 JOIN institution_class_subjects ics2
                   ON ics2.institution_subject_id = isub.id
                  AND ics2.institution_class_id = {$classId}
                 WHERE {$subjectWhere}
                 ORDER BY isub.name"
            );
        } else {
            $stmt = $conn->execute(
                "SELECT DISTINCT isub.id, isub.name
                 FROM institution_subjects isub
                 WHERE {$subjectWhere}
                 ORDER BY isub.name"
            );
        }

        $slots = [];
        foreach ($stmt->fetchAll('assoc') as $r) {
            $sid     = (int)$r['id'];
            //POCOR-9611: mark_records: period=1, subject_id=sid
            //            absence_details: period=0, subject_id=sid
            $slots[] = [
                'key'            => "s{$sid}",
                'label'          => $r['name'],
                'mr_period'      => 1,
                'mr_subject_id'  => $sid,
                'abd_period'     => 0,
                'abd_subject_id' => $sid,
            ];
        }

        //POCOR-9611: Confirmed Subject-mode but no subjects assigned yet — one generic slot
        //            so the report still renders instead of erroring out.
        return $slots ?: [[
            'key'            => 's0',
            'label'          => 'Subject',
            'mr_period'      => 1,
            'mr_subject_id'  => 0,
            'abd_period'     => 0,
            'abd_subject_id' => 0,
        ]];
    }

    /**
     * Return the Mon-Fri date array for the week containing week_start_day.
     *
     * @return array  ['label' => '...', 'days' => ['mon'=>Y-m-d, ..., 'fri'=>Y-m-d]]
     */
    private function _getWeekDays(string $weekStartDay): array
    {
        //POCOR-9611: week_start_day IS the week's first day; shift to Monday for school-day columns
        if (empty($weekStartDay)) {
            return [];
        }

        $start = new DateTime($weekStartDay);
        $dow   = (int)$start->format('N');
        if ($dow > 1) {
            $start->modify('-' . ($dow - 1) . ' days');
        }

        $mon = clone $start;
        $tue = (clone $start)->modify('+1 day');
        $wed = (clone $start)->modify('+2 days');
        $thu = (clone $start)->modify('+3 days');
        $fri = (clone $start)->modify('+4 days');

        return [
            'label' => $mon->format('d/m/Y') . ' - ' . $fri->format('d/m/Y'),
            'days'  => [
                'mon' => $mon->format('Y-m-d'),
                'tue' => $tue->format('Y-m-d'),
                'wed' => $wed->format('Y-m-d'),
                'thu' => $thu->format('Y-m-d'),
                'fri' => $fri->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Build one column definition per day (Monday-Friday), collapsed across slots.
     *
     * Label: "Monday-Morning (Nursery)" when there is exactly one slot (the common case —
     * a single period, or a single subject); just "Monday" when multiple periods/subjects
     * are marked for the same day, since one column can't list them all — the distinct
     * slot names still appear in the separate "Period" column instead.
     *
     * @return array  [['key'=>'col_mon', 'label'=>'...'], ...]
     */
    private function _buildColDefs(array $slots, array $week = []): array
    {
        $dayLabels = [
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
        ];
        //POCOR-9611: Single slot → suffix every day header with its label, matching the required format
        $singleSlotLabel = (count($slots) === 1) ? $slots[0]['label'] : null;

        $colDefs = [];
        foreach ($dayLabels as $dayKey => $dayName) {
            $label = ($singleSlotLabel !== null) ? "{$dayName}-{$singleSlotLabel}" : $dayName;
            $colDefs[] = [
                'key'   => "col_{$dayKey}",
                'label' => $label,
            ];
        }
        return $colDefs;
    }

    /**
     * Build the single-week result SQL using three targeted queries + PHP assembly.
     *
     * Replaces the old per-slot LEFT JOIN approach which hit MySQL's 61-table hard limit
     * when a class has many subjects (e.g. 8 subjects × 5 days × 2 JOINs = 80 tables).
     *
     * Strategy:
     *   Query 1 — all students for the scope (institution_class_students + names + class)
     *   Query 2 — all mark records for the week (student_attendance_marked_records)
     *   Query 3 — all absence details for the week (institution_student_absence_details)
     *   PHP     — assemble each cell from indexed lookup maps; no per-cell DB round-trips
     *
     * Returns a UNION ALL SELECT … literal SQL suitable for use as a subquery.
     * Column aliases appear only in the first SELECT; MySQL inherits them for the rest.
     *
     * @return string SQL (UNION ALL of literal rows, or empty-result SQL when no students)
     */
    private function _buildWeekSQL(
        array $week,
        array $slots,
        int $institutionId,
        int $academicPeriodId,
        ?int $classId,
        ?int $gradeId,
        string $attendanceBy,
        string $periodLabel
    ): string {
        $conn             = ConnectionManager::get('default');
        $weekLabel        = addslashes($week['label']);
        $attendanceBySafe = addslashes($attendanceBy);
        $periodLabelSafe  = addslashes($periodLabel);
        $days             = $week['days'];

        //POCOR-9611: WHERE fragments reused across all three queries
        $whereClass = ($classId !== null && $classId >= 1) ? "AND institution_class_id = {$classId}" : '';
        $whereGrade = ($gradeId !== null && $gradeId >= 1) ? "AND education_grade_id = {$gradeId}"  : '';
        $dateList   = "'" . implode("','", array_values($days)) . "'";

        // ---------------------------------------------------------------
        // Query 1: students
        // ---------------------------------------------------------------
        $whereStudentClass = ($classId !== null && $classId >= 1) ? "AND ics.institution_class_id = {$classId}" : '';
        $whereStudentGrade = ($gradeId !== null && $gradeId >= 1) ? "AND ics.education_grade_id = {$gradeId}"   : '';

        $students = $conn->execute(
            "SELECT ics.student_id, ics.institution_class_id, ics.education_grade_id,
                    su.openemis_no,
                    TRIM(CONCAT_WS(' ', su.first_name, su.middle_name, su.third_name, su.last_name)) AS full_name
             FROM institution_class_students ics
             JOIN security_users su ON su.id = ics.student_id
             JOIN institution_classes ic ON ic.id = ics.institution_class_id
             WHERE ics.academic_period_id = {$academicPeriodId}
               AND ics.institution_id    = {$institutionId}
               {$whereStudentClass} {$whereStudentGrade}
             ORDER BY full_name"
        )->fetchAll('assoc');

        //POCOR-9611: No students → return empty-result SQL with correct column aliases
        if (empty($students)) {
            return $this->_buildEmptySQL($week, $slots);
        }

        // ---------------------------------------------------------------
        // Query 2: mark records for the entire week (indexed by date|period|subject_id)
        // ---------------------------------------------------------------
        $markRows = $conn->execute(
            "SELECT date, period, subject_id, no_scheduled_class, institution_class_id, education_grade_id
             FROM student_attendance_marked_records
             WHERE institution_id      = {$institutionId}
               AND academic_period_id = {$academicPeriodId}
               {$whereClass} {$whereGrade}
               AND date IN ({$dateList})"
        )->fetchAll('assoc');

        //POCOR-9611: Index by "date|period|subject_id" (class/grade identical within scope)
        $mrIndex = [];
        foreach ($markRows as $mr) {
            $mrIndex["{$mr['date']}|{$mr['period']}|{$mr['subject_id']}"] = $mr;
        }

        // ---------------------------------------------------------------
        // Query 3: absence details for the entire week (indexed by student_id|date|period|subject_id)
        // ---------------------------------------------------------------
        $absenceRows = $conn->execute(
            "SELECT student_id, date, period, subject_id, absence_type_id
             FROM institution_student_absence_details
             WHERE institution_id      = {$institutionId}
               AND academic_period_id = {$academicPeriodId}
               {$whereClass} {$whereGrade}
               AND date IN ({$dateList})"
        )->fetchAll('assoc');

        $abIndex = [];
        foreach ($absenceRows as $ab) {
            $abIndex["{$ab['student_id']}|{$ab['date']}|{$ab['period']}|{$ab['subject_id']}"] = $ab;
        }

        // ---------------------------------------------------------------
        // PHP assembly: build one SELECT literal row per student
        // ---------------------------------------------------------------

        //POCOR-9611: absence_type_id → status label
        $absenceTypeMap = [1 => 'EXCUSED', 2 => 'UNEXCUSED', 3 => 'LATE'];
        //POCOR-9611(fix): same calculate_daily_attendance read as InstitutionStudentAbsencesTable
        //            ::getDailyAttendanceConfig() - governs resolveDayStatus()'s present-vs-absence
        //            priority below (1=once: absence wins; 2=all periods: present wins).
        $dailyAttendanceMode = self::getDailyAttendanceMode();

        $unionParts  = [];
        $firstRow    = true;

        foreach ($students as $student) {
            $studentId = $student['student_id'];
            $displayNo = addslashes($student['openemis_no']);
            $fullName  = addslashes($student['openemis_no'] . ' - ' . $student['full_name']);

            //POCOR-9611: Aliases only on the first SELECT; MySQL inherits them for UNION ALL members
            if ($firstRow) {
                $cols = [
                    "'{$displayNo}'       AS openemis_no",
                    "'{$fullName}'        AS student_name",
                    "'{$attendanceBySafe}' AS attendance_by",
                    "'{$periodLabelSafe}' AS period_label",
                    "'{$weekLabel}'       AS week_label",
                ];
            } else {
                $cols = ["'{$displayNo}'", "'{$fullName}'", "'{$attendanceBySafe}'", "'{$periodLabelSafe}'", "'{$weekLabel}'"];
            }

            $totalPresent = 0;
            $totalLate    = 0;
            $totalAbsent  = 0;

            //POCOR-9611(fix): One column per day — collapsed across slots per the "Mark present
            //            if one or more records present" rule. See resolveDayStatus() below.
            foreach ($days as $dayKey => $date) {
                $status = self::resolveDayStatus($slots, $date, $studentId, $mrIndex, $abIndex, $absenceTypeMap, $dailyAttendanceMode);

                $colAlias = "col_{$dayKey}";
                $cols[]   = $firstRow ? "'{$status}' AS {$colAlias}" : "'{$status}'";

                //POCOR-9611: LATE counts as present (user requirement) + also increments total_late.
                //            NO CLASS and NOTMARKED days are excluded from all totals.
                if (in_array($status, ['PRESENT', 'LATE'], true)) {
                    $totalPresent++;
                }
                if ($status === 'LATE') {
                    $totalLate++;
                }
                if (in_array($status, ['EXCUSED', 'UNEXCUSED'], true)) {
                    $totalAbsent++;
                }
            }

            $totalPresentAlias = $firstRow ? "{$totalPresent} AS total_present" : (string)$totalPresent;
            $totalLateAlias    = $firstRow ? "{$totalLate}    AS total_late"    : (string)$totalLate;
            $totalAbsentAlias  = $firstRow ? "{$totalAbsent}  AS total_absent"  : (string)$totalAbsent;

            $cols[] = $totalPresentAlias;
            $cols[] = $totalLateAlias;
            $cols[] = $totalAbsentAlias;

            $unionParts[] = 'SELECT ' . implode(', ', $cols);
            $firstRow = false;
        }

        return implode("\nUNION ALL\n", $unionParts);
    }

    /**
     * POCOR-9611(fix): calculate_daily_attendance config (1=once, 2=all periods) - same read
     * pattern as InstitutionStudentAbsencesTable::getDailyAttendanceConfig() (and
     * StudentAttendanceSummaryTable, ReportCardsTable, StudentAbsencesPerDaysTable, which all
     * branch on this same global config item). Defaults to 2 ("all periods") when unset, matching
     * this table's original present-wins-only behaviour.
     */
    private static function getDailyAttendanceMode(): int
    {
        return (int)(TableRegistry::getTableLocator()->get('Configuration.ConfigItems')
            ->find()
            ->select(['value'])
            ->where(['code' => 'calculate_daily_attendance'])
            ->first()['value'] ?? 2);
    }

    /**
     * POCOR-9611(fix): Collapses one day's several period/subject slots into a single status.
     *
     * Priority depends on the institution-wide `calculate_daily_attendance` config item (see
     * InstitutionStudentAbsencesTable::getDailyAttendanceConfig() for the same read pattern,
     * reused by several other tables too - StudentAttendanceSummaryTable, ReportCardsTable,
     * StudentAbsencesPerDaysTable):
     *   - mode 2 ("all periods"): PRESENT/LATE wins - "mark present if one or more records
     *     present". This is the only priority the original fix here supported; it is only
     *     correct for this config value.
     *   - mode 1 ("once"): the opposite priority - any single recorded absence (EXCUSED/
     *     UNEXCUSED) on the day marks the whole day absent, even if another period that same
     *     day is present. Reviewer-flagged gap: with mode 1 configured, the mode-2-only logic
     *     silently returned PRESENT on days the institution's own rule says are ABSENT.
     *
     * Evaluated across ALL slots for the day (not just the first one) either way; only when
     * nothing on any slot is present/late/excused/unexcused (e.g. every slot is genuinely
     * NOTMARKED, or NO CLASS) does this fall back to the first slot's own status, to stay
     * deterministic.
     *
     * Pure/static and DB-free by design so it can be unit tested without a database connection -
     * callers pass in the already-fetched mark/absence indexes (and the resolved config value)
     * rather than this method querying them itself.
     *
     * @param array  $slots               Ordered list of ['mr_period', 'mr_subject_id', 'abd_period', 'abd_subject_id']
     * @param string $date                The day being resolved, in the same format used as the mark-index key
     * @param mixed  $studentId           Student id, used to key into $abIndex
     * @param array  $mrIndex             Marked-records index, keyed "date|period|subject_id"
     * @param array  $abIndex             Absence-details index, keyed "studentId|date|period|subject_id"
     * @param array  $absenceTypeMap      Map of absence_type_id => status label
     * @param int    $dailyAttendanceMode calculate_daily_attendance config value: 1=once (any
     *                                    absence wins), 2=all periods (any present wins) - default
     *                                    2 preserves this method's original (mode-2-only) behaviour
     * @return string One of PRESENT, LATE, EXCUSED, UNEXCUSED, NO CLASS, NOTMARKED
     */
    public static function resolveDayStatus(
        array $slots,
        string $date,
        $studentId,
        array $mrIndex,
        array $abIndex,
        array $absenceTypeMap,
        int $dailyAttendanceMode = 2
    ): string {
        $fallbackStatus = 'NOTMARKED';
        $bestPresentStatus = null;
        $bestAbsenceStatus = null;

        foreach ($slots as $slotIndex => $slot) {
            $mrKey = "{$date}|{$slot['mr_period']}|{$slot['mr_subject_id']}";
            $abKey = "{$studentId}|{$date}|{$slot['abd_period']}|{$slot['abd_subject_id']}";
            $mr    = $mrIndex[$mrKey] ?? null;
            $ab    = $abIndex[$abKey] ?? null;

            if ($mr === null) {
                $slotStatus = 'NOTMARKED';
            } elseif ((int)$mr['no_scheduled_class'] === 1) {
                $slotStatus = 'NO CLASS';
            } elseif ($ab !== null) {
                $slotStatus = $absenceTypeMap[(int)$ab['absence_type_id']] ?? 'PRESENT';
            } else {
                $slotStatus = 'PRESENT';
            }

            if ($slotIndex === 0) {
                $fallbackStatus = $slotStatus; // preserves prior behaviour when nothing is present/marked
            }
            if ($bestPresentStatus === null && in_array($slotStatus, ['PRESENT', 'LATE'], true)) {
                $bestPresentStatus = $slotStatus;
            }
            if ($bestAbsenceStatus === null && in_array($slotStatus, ['EXCUSED', 'UNEXCUSED'], true)) {
                $bestAbsenceStatus = $slotStatus; // a real recorded absence beats an unmarked slot
            }
            // Once both a present and an absent slot have been seen, neither priority mode can
            // learn anything new from further slots - the winner between them is already fixed.
            if ($bestPresentStatus !== null && $bestAbsenceStatus !== null) {
                break;
            }
        }

        if ($dailyAttendanceMode === 1) {
            // "once": any single recorded absence marks the whole day absent, even over a present period.
            return $bestAbsenceStatus ?? $bestPresentStatus ?? $fallbackStatus;
        }

        // "all periods" (default/mode 2): any present period marks the whole day present.
        return $bestPresentStatus ?? $bestAbsenceStatus ?? $fallbackStatus;
    }

    /**
     * Returns an empty-result SQL with the correct column aliases when there are no students.
     * The outer wrapper selects from this subquery, so the aliases must exist even for zero rows.
     */
    private function _buildEmptySQL(array $week, array $slots): string
    {
        //POCOR-9611: NULL literals give the correct alias set; WHERE 1=0 ensures zero rows
        $cols = ['NULL AS openemis_no', 'NULL AS student_name',
                 'NULL AS attendance_by', 'NULL AS period_label', 'NULL AS week_label'];
        foreach ($week['days'] as $dayKey => $_) {
            $cols[] = "NULL AS col_{$dayKey}";
        }
        $cols[] = '0 AS total_present';
        $cols[] = '0 AS total_late';
        $cols[] = '0 AS total_absent';
        return 'SELECT ' . implode(', ', $cols) . ' WHERE 1 = 0';
    }
}