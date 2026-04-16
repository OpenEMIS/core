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

        //POCOR-9611: Resolve attendance slots (periods and/or subjects) for this institution+period
        $slots        = $this->_getAttendanceSlots($academicPeriodId, $institutionId, $classId, $gradeId);
        $markTypeName = $this->_getMarkTypeName($academicPeriodId, $institutionId, $classId, $gradeId);

        //POCOR-9611: Compute Mon-Fri dates from week_start_day
        $week    = $this->_getWeekDays($weekStartDay);
        $colDefs = $this->_buildColDefs($slots, $week);

        $settings['_slots']          = $slots;
        $settings['_week']           = $week;
        $settings['_col_defs']       = $colDefs;
        $settings['_mark_type_name'] = $markTypeName;

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
            ['key' => 'openemis_no',   'field' => 'openemis_no',   'type' => 'string', 'label' => __('OpenEMIS ID')],
            ['key' => 'student_name',  'field' => 'student_name',  'type' => 'string', 'label' => __('Name')],
            ['key' => 'class_name',    'field' => 'class_name',    'type' => 'string', 'label' => __('Class')], //POCOR-9611: class not required, always show
            ['key' => 'attendance_by', 'field' => 'attendance_by', 'type' => 'string', 'label' => __('Attendance By')],
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

        $slots        = $settings['_slots'];
        $week         = $settings['_week'];
        $markTypeName = $settings['_mark_type_name'];

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
            $markTypeName
        );

        //POCOR-9611: Build SELECT list matching all aliases produced by _buildWeekSQL
        $selectList = ['openemis_no', 'student_name', 'class_name', 'attendance_by', 'week_label'];
        foreach ($week['days'] as $dayKey => $_) {
            foreach ($slots as $slot) {
                $selectList[] = "col_{$dayKey}_{$slot['key']}";
            }
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
     * Resolve attendance slots for this report context.
     *
     * Each slot represents one column dimension (a period or a subject):
     *   key           — unique suffix used in SQL alias, e.g. "p1" or "s4984"
     *   label         — column header label
     *   mr_period     — period value in student_attendance_marked_records
     *   mr_subject_id — subject_id  in student_attendance_marked_records
     *   abd_period    — period value in institution_student_absence_details
     *   abd_subject_id— subject_id  in institution_student_absence_details
     *
     * Detection strategy (most reliable — works per-class):
     *   1. Inspect actual student_attendance_marked_records for the given scope.
     *      subject_id = 0  → period-based records exist.
     *      subject_id > 0  → subject-based records exist.
     *   2. Fallback when nothing is marked yet: check all currently active
     *      student_mark_type_statuses rows (not just the most recently enabled one,
     *      because multiple types can be active simultaneously for different classes).
     *
     * @return array[]
     */
    private function _getAttendanceSlots(int $academicPeriodId, int $institutionId, ?int $classId, ?int $gradeId): array
    {
        $conn = ConnectionManager::get('default');

        //POCOR-9611: Step 1 — detect from actual mark records (class-scoped when possible)
        $classFilter = ($classId !== null && $classId >= 1) ? "AND institution_class_id = {$classId}" : '';
        $gradeFilter = ($gradeId !== null && $gradeId >= 1) ? "AND education_grade_id = {$gradeId}"  : '';

        $stmt = $conn->execute(
            "SELECT
               MAX(CASE WHEN subject_id = 0 THEN 1 ELSE 0 END) AS has_periods,
               MAX(CASE WHEN subject_id > 0 THEN 1 ELSE 0 END) AS has_subjects
             FROM student_attendance_marked_records
             WHERE institution_id  = {$institutionId}
               AND academic_period_id = {$academicPeriodId}
               {$classFilter} {$gradeFilter}"
        );
        $modes       = $stmt->fetch('assoc');
        $hasPeriods  = $modes ? (bool)$modes['has_periods']  : false;
        $hasSubjects = $modes ? (bool)$modes['has_subjects'] : false;

        //POCOR-9611: Step 2 — fallback when attendance not yet marked for this scope
        if (!$hasPeriods && !$hasSubjects) {
            //POCOR-9611: Collect ALL currently active mark type statuses, not just ORDER BY date_enabled DESC LIMIT 1,
            //            because DAY and SUBJECT types can be active simultaneously for different classes
            $stmt = $conn->execute(
                "SELECT DISTINCT mt.student_attendance_type_id
                 FROM student_mark_type_statuses sms
                 JOIN student_attendance_mark_types mt ON mt.id = sms.student_attendance_mark_type_id
                 WHERE sms.academic_period_id = {$academicPeriodId}
                   AND (sms.date_disabled IS NULL OR sms.date_disabled >= CURDATE())"
            );
            foreach ($stmt->fetchAll('assoc') as $r) {
                $tid = (int)$r['student_attendance_type_id'];
                if ($tid === 1 || $tid === 3) { $hasPeriods  = true; }
                if ($tid === 2 || $tid === 3) { $hasSubjects = true; }
            }
        }

        $slots = [];

        //POCOR-9611: Period slots — present when DAY (1) or DAY_AND_SUBJECT (3) records exist
        if ($hasPeriods) {
            $stmt = $conn->execute(
                "SELECT sms.student_attendance_mark_type_id
                 FROM student_mark_type_statuses sms
                 JOIN student_attendance_mark_types mt ON mt.id = sms.student_attendance_mark_type_id
                 WHERE sms.academic_period_id = {$academicPeriodId}
                   AND mt.student_attendance_type_id IN (1, 3)
                 ORDER BY sms.date_enabled DESC
                 LIMIT 1"
            );
            $row        = $stmt->fetch('assoc');
            $markTypeId = $row ? (int)$row['student_attendance_mark_type_id'] : 1;

            $stmt = $conn->execute(
                'SELECT `period`, name
                 FROM student_attendance_per_day_periods
                 WHERE student_attendance_mark_type_id = ?
                 ORDER BY `period`',
                [$markTypeId]
            );
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
        }

        //POCOR-9611: Subject slots — present when SUBJECT (2) or DAY_AND_SUBJECT (3) records exist
        if ($hasSubjects) {
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
        }

        //POCOR-9611: Fallback — should not happen in a properly configured system
        return $slots ?: [[
            'key'            => 'p1',
            'label'          => 'Period 1',
            'mr_period'      => 1,
            'mr_subject_id'  => 0,
            'abd_period'     => 1,
            'abd_subject_id' => 0,
        ]];
    }

    /**
     * Return the mark type display name for the "Attendance By" column.
     *
     * Detects the actual mark type from mark records for the specific class/grade scope
     * (same strategy as _getAttendanceSlots) so the label matches the real attendance mode.
     * Falls back to the most recently enabled type when no records exist yet.
     */
    private function _getMarkTypeName(int $academicPeriodId, int $institutionId, ?int $classId, ?int $gradeId): string
    {
        $conn = ConnectionManager::get('default');

        //POCOR-9611: Detect period/subject mode from actual mark records for this class scope
        $classFilter = ($classId !== null && $classId >= 1) ? "AND institution_class_id = {$classId}" : '';
        $gradeFilter = ($gradeId !== null && $gradeId >= 1) ? "AND education_grade_id = {$gradeId}"  : '';

        $stmt = $conn->execute(
            "SELECT MAX(CASE WHEN subject_id = 0 THEN 1 ELSE 0 END) AS has_periods,
                    MAX(CASE WHEN subject_id > 0 THEN 1 ELSE 0 END) AS has_subjects
             FROM student_attendance_marked_records
             WHERE institution_id      = {$institutionId}
               AND academic_period_id = {$academicPeriodId}
               {$classFilter} {$gradeFilter}"
        );
        $modes       = $stmt->fetch('assoc');
        $hasPeriods  = $modes ? (bool)$modes['has_periods']  : false;
        $hasSubjects = $modes ? (bool)$modes['has_subjects'] : false;

        //POCOR-9611: Map detected mode to the attendance_type_id set to query
        //            type_id: 1=DAY, 2=SUBJECT, 3=DAY_AND_SUBJECT
        if ($hasPeriods && $hasSubjects) {
            $typeIds = '(3)';
        } elseif ($hasSubjects) {
            $typeIds = '(2, 3)';
        } else {
            $typeIds = '(1, 3)'; // default / fallback
        }

        $stmt = $conn->execute(
            "SELECT mt.name
             FROM student_mark_type_statuses sms
             JOIN student_attendance_mark_types mt ON mt.id = sms.student_attendance_mark_type_id
             WHERE sms.academic_period_id = {$academicPeriodId}
               AND mt.student_attendance_type_id IN {$typeIds}
             ORDER BY sms.date_enabled DESC
             LIMIT 1"
        );
        $row = $stmt->fetch('assoc');
        return $row ? $row['name'] : 'By Period';
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
     * Build column definitions for each day × slot combination.
     * Label: "Monday (13.04.2026) - Morning Session" or "Monday (13.04.2026) - Mathematics".
     *
     * @return array  [['key'=>'col_mon_p1', 'label'=>'...'], ...]
     */
    private function _buildColDefs(array $slots, array $week = []): array
    {
        //POCOR-9611: One column per day per slot (period or subject), date included in header
        $dayLabels = [
            'mon' => 'Monday',
            'tue' => 'Tuesday',
            'wed' => 'Wednesday',
            'thu' => 'Thursday',
            'fri' => 'Friday',
        ];
        $days    = $week['days'] ?? [];
        $colDefs = [];
        foreach ($dayLabels as $dayKey => $dayName) {
            $dateSuffix = '';
            if (!empty($days[$dayKey])) {
                $dateSuffix = ' (' . (new DateTime($days[$dayKey]))->format('d.m.Y') . ')';
            }
            foreach ($slots as $slot) {
                $colDefs[] = [
                    'key'   => "col_{$dayKey}_{$slot['key']}",
                    'label' => "{$dayName}{$dateSuffix} - {$slot['label']}",
                ];
            }
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
        string $markTypeName
    ): string {
        $conn         = ConnectionManager::get('default');
        $weekLabel    = addslashes($week['label']);
        $markTypeSafe = addslashes($markTypeName);
        $days         = $week['days'];

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
                    TRIM(CONCAT_WS(' ', su.first_name, su.middle_name, su.third_name, su.last_name)) AS full_name,
                    ic.name AS class_name
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

        $unionParts  = [];
        $firstRow    = true;

        foreach ($students as $student) {
            $studentId = $student['student_id'];
            $displayNo = addslashes($student['openemis_no']);
            $fullName  = addslashes($student['openemis_no'] . ' - ' . $student['full_name']);
            $className = addslashes($student['class_name']);

            //POCOR-9611: Aliases only on the first SELECT; MySQL inherits them for UNION ALL members
            if ($firstRow) {
                $cols = [
                    "'{$displayNo}'    AS openemis_no",
                    "'{$fullName}'     AS student_name",
                    "'{$className}'    AS class_name",
                    "'{$markTypeSafe}' AS attendance_by",
                    "'{$weekLabel}'    AS week_label",
                ];
            } else {
                $cols = ["'{$displayNo}'", "'{$fullName}'", "'{$className}'", "'{$markTypeSafe}'", "'{$weekLabel}'"];
            }

            $totalPresent = 0;
            $totalLate    = 0;
            $totalAbsent  = 0;

            foreach ($days as $dayKey => $date) {
                $firstSlot     = true;
                $dayFirstStatus = 'NOTMARKED'; //POCOR-9611: default when nothing marked

                foreach ($slots as $slot) {
                    $mrKey  = "{$date}|{$slot['mr_period']}|{$slot['mr_subject_id']}";
                    $abKey  = "{$studentId}|{$date}|{$slot['abd_period']}|{$slot['abd_subject_id']}";
                    $mr     = $mrIndex[$mrKey]  ?? null;
                    $ab     = $abIndex[$abKey]  ?? null;

                    if ($mr === null) {
                        $status = 'NOTMARKED';
                    } elseif ((int)$mr['no_scheduled_class'] === 1) {
                        $status = 'NO CLASS';
                    } elseif ($ab !== null) {
                        $status = $absenceTypeMap[(int)$ab['absence_type_id']] ?? 'PRESENT';
                    } else {
                        $status = 'PRESENT';
                    }

                    $colAlias = "col_{$dayKey}_{$slot['key']}";
                    $cols[]   = $firstRow ? "'{$status}' AS {$colAlias}" : "'{$status}'";

                    if ($firstSlot) {
                        $dayFirstStatus = $status;
                        $firstSlot      = false;
                    }
                }

                //POCOR-9611: Totals use first slot of each day as the day indicator.
                //            LATE counts as present (user requirement) + also increments total_late.
                //            NO CLASS and NOTMARKED days are excluded from all totals.
                if (in_array($dayFirstStatus, ['PRESENT', 'LATE'], true)) {
                    $totalPresent++;
                }
                if ($dayFirstStatus === 'LATE') {
                    $totalLate++;
                }
                if (in_array($dayFirstStatus, ['EXCUSED', 'UNEXCUSED'], true)) {
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
     * Returns an empty-result SQL with the correct column aliases when there are no students.
     * The outer wrapper selects from this subquery, so the aliases must exist even for zero rows.
     */
    private function _buildEmptySQL(array $week, array $slots): string
    {
        //POCOR-9611: NULL literals give the correct alias set; WHERE 1=0 ensures zero rows
        $cols = ['NULL AS openemis_no', 'NULL AS student_name', 'NULL AS class_name',
                 'NULL AS attendance_by', 'NULL AS week_label'];
        foreach ($week['days'] as $dayKey => $_) {
            foreach ($slots as $slot) {
                $cols[] = "NULL AS col_{$dayKey}_{$slot['key']}";
            }
        }
        $cols[] = '0 AS total_present';
        $cols[] = '0 AS total_late';
        $cols[] = '0 AS total_absent';
        return 'SELECT ' . implode(', ', $cols) . ' WHERE 1 = 0';
    }
}
