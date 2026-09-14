<?php
//POCOR-9611: Students Monthly Attendance — parallel to InstitutionStudentWeeklyAttendance
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
 * Students Monthly Attendance report.
 *
 * Registered as a feature under Institution > Statistics > Standard.
 * Generates one Excel row per student showing PRESENT/LATE/EXCUSED/UNEXCUSED/NOTMARKED/NO CLASS
 * status for each school day (Mon-Fri) × attendance slot within the selected month.
 *
 * Subjects are sourced from the timetable (institution_schedule_timetables chain) when one
 * exists for the class; falls back to institution_subjects when no timetable is configured.
 *
 * Input params (stored as JSON in report_progress.params by ReportListBehavior):
 *   institution_id, academic_period_id, education_grade_id, institution_class_id, month (2-digit '01'-'12')
 *
 * @ticket POCOR-9611
 */
class InstitutionStudentMonthlyAttendanceTable extends AppTable
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
        //POCOR-9611: Pre-compute slots and month dates; store in $settings for the other events
        $requestData      = json_decode($settings['process']['params']);
        $academicPeriodId = (int)$requestData->academic_period_id;
        $institutionId    = (int)$requestData->institution_id;
        $monthNum         = $requestData->month ?? '';
        $gradeId = isset($requestData->education_grade_id) ? (int)$requestData->education_grade_id : null;
        $classId = isset($requestData->institution_class_id) ? (int)$requestData->institution_class_id : null;

        //POCOR-9611: Compute dates FIRST so slot detection is scoped to the actual report month
        $month        = $this->_getMonthDays($monthNum, $academicPeriodId);
        $reportDates  = array_values($month['days'] ?? []);
        $slots        = $this->_getAttendanceSlots($academicPeriodId, $institutionId, $classId, $gradeId, $reportDates);
        $markTypeName = $this->_getMarkTypeName($academicPeriodId, $institutionId, $classId, $gradeId, $reportDates);
        $colDefs      = $this->_buildColDefs($slots, $month);

        $settings['_slots']          = $slots;
        $settings['_month']          = $month;
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
            ['key' => 'class_name',    'field' => 'class_name',    'type' => 'string', 'label' => __('Class')],
            ['key' => 'attendance_by', 'field' => 'attendance_by', 'type' => 'string', 'label' => __('Attendance By')],
            ['key' => 'month_label',   'field' => 'month_label',   'type' => 'string', 'label' => __('Month')],
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
        //POCOR-9611: Build single-month query and wrap as CakePHP ORM subquery
        $requestData      = json_decode($settings['process']['params']);
        $institutionId    = (int)$requestData->institution_id;
        $academicPeriodId = (int)$requestData->academic_period_id;
        $gradeId = isset($requestData->education_grade_id) ? (int)$requestData->education_grade_id : null;
        $classId = isset($requestData->institution_class_id) ? (int)$requestData->institution_class_id : null;

        $slots        = $settings['_slots'];
        $month        = $settings['_month'];
        $markTypeName = $settings['_mark_type_name'];

        if (empty($month) || empty($month['days']) || empty($slots)) {
            $query->where(['1 = 0']); //POCOR-9611: nothing to show
            return;
        }

        $monthSql = $this->_buildMonthSQL(
            $month,
            $slots,
            $institutionId,
            $academicPeriodId,
            $classId,
            $gradeId,
            $markTypeName
        );

        //POCOR-9611: Build SELECT list matching all aliases produced by _buildMonthSQL
        $selectList = ['openemis_no', 'student_name', 'class_name', 'attendance_by', 'month_label'];
        foreach ($month['days'] as $dayKey => $_) {
            foreach ($slots as $slot) {
                $selectList[] = "col_{$dayKey}_{$slot['key']}";
            }
        }
        $selectList[] = 'total_present';
        $selectList[] = 'total_late';
        $selectList[] = 'total_absent';

        $selectExpr = [];
        foreach ($selectList as $col) {
            $selectExpr[$col] = "md.{$col}";
        }

        $query
            ->select($selectExpr)
            ->from(['md' => "({$monthSql})"])
            ->order(['md.student_name']);
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
            'PRESENT'   => '#92D050',
            'LATE'      => '#FFC000',
            'EXCUSED'   => '#FFFF00',
            'UNEXCUSED' => '#FF0000',
            'NOTMARKED' => '#D3D3D3',
            'NO CLASS'  => '#808080',
        ];

        //POCOR-9611: XLSXWriter uses 'fill' (hex string) — not 'fill_color'/'fill_pattern_type'
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
     * For period slots: same logic as weekly — detect from mark records, fallback to mark type statuses.
     * For subject slots: prefer timetable subjects (institution_schedule_timetables chain) when a
     *   timetable exists for the class; falls back to institution_subjects otherwise.
     *
     * @return array[]
     */
    private function _getAttendanceSlots(int $academicPeriodId, int $institutionId, ?int $classId, ?int $gradeId, array $reportDates = []): array
    {
        $conn        = ConnectionManager::get('default');
        $classFilter = ($classId !== null && $classId >= 1) ? "AND institution_class_id = {$classId}" : '';
        $gradeFilter = ($gradeId !== null && $gradeId >= 1) ? "AND education_grade_id = {$gradeId}"  : '';
        $hasPeriods  = false;
        $hasSubjects = false;

        //POCOR-9611: Step 1 — detect from mark records scoped to report dates + class
        if (!empty($reportDates)) {
            $dateList = "'" . implode("','", $reportDates) . "'";
            $stmt = $conn->execute(
                "SELECT MAX(CASE WHEN subject_id = 0 THEN 1 ELSE 0 END) AS has_periods,
                        MAX(CASE WHEN subject_id > 0 THEN 1 ELSE 0 END) AS has_subjects
                 FROM student_attendance_marked_records
                 WHERE institution_id      = {$institutionId}
                   AND academic_period_id = {$academicPeriodId}
                   {$classFilter} {$gradeFilter}
                   AND date IN ({$dateList})"
            );
            $modes       = $stmt->fetch('assoc');
            $hasPeriods  = $modes ? (bool)$modes['has_periods']  : false;
            $hasSubjects = $modes ? (bool)$modes['has_subjects'] : false;
        }

        //POCOR-9611: Step 2 — widen to full period for same class
        if (!$hasPeriods && !$hasSubjects) {
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
        }

        //POCOR-9611: Step 3 — last resort: mark type statuses (institution-wide, date-range aware)
        if (!$hasPeriods && !$hasSubjects) {
            $dateRangeWhere = '';
            if (!empty($reportDates)) {
                $rangeStart     = min($reportDates);
                $rangeEnd       = max($reportDates);
                $dateRangeWhere = "AND sms.date_enabled <= '{$rangeEnd}' AND (sms.date_disabled IS NULL OR sms.date_disabled >= '{$rangeStart}')";
            } else {
                $dateRangeWhere = "AND (sms.date_disabled IS NULL OR sms.date_disabled >= CURDATE())";
            }
            $stmt = $conn->execute(
                "SELECT DISTINCT mt.student_attendance_type_id
                 FROM student_mark_type_statuses sms
                 JOIN student_attendance_mark_types mt ON mt.id = sms.student_attendance_mark_type_id
                 WHERE sms.academic_period_id = {$academicPeriodId}
                   {$dateRangeWhere}"
            );
            foreach ($stmt->fetchAll('assoc') as $r) {
                $tid = (int)$r['student_attendance_type_id'];
                if ($tid === 1 || $tid === 3) { $hasPeriods  = true; }
                if ($tid === 2 || $tid === 3) { $hasSubjects = true; }
            }
        }

        $slots = [];

        //POCOR-9611: Period slots — look up the mark type active during the report dates
        if ($hasPeriods) {
            $periodDateWhere = '';
            if (!empty($reportDates)) {
                $rangeStart      = min($reportDates);
                $rangeEnd        = max($reportDates);
                $periodDateWhere = "AND sms.date_enabled <= '{$rangeEnd}' AND (sms.date_disabled IS NULL OR sms.date_disabled >= '{$rangeStart}')";
            }
            $stmt = $conn->execute(
                "SELECT sms.student_attendance_mark_type_id
                 FROM student_mark_type_statuses sms
                 JOIN student_attendance_mark_types mt ON mt.id = sms.student_attendance_mark_type_id
                 WHERE sms.academic_period_id = {$academicPeriodId}
                   AND mt.student_attendance_type_id IN (1, 3)
                   {$periodDateWhere}
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

        //POCOR-9611: Subject slots — prefer timetable subjects when a timetable exists for this class
        if ($hasSubjects) {
            $timetableSubjects = [];

            if ($classId !== null && $classId >= 1) {
                //POCOR-9611: Timetable chain: timetables → lesson_details → curriculum_lessons → institution_subjects
                $stmt = $conn->execute(
                    "SELECT DISTINCT isub.id, isub.name
                     FROM institution_schedule_timetables ist
                     JOIN institution_schedule_lesson_details isld
                       ON isld.institution_schedule_timetable_id = ist.id
                     JOIN institution_schedule_curriculum_lessons iscl
                       ON iscl.institution_schedule_lesson_detail_id = isld.id
                     JOIN institution_subjects isub
                       ON isub.id = iscl.institution_subject_id
                     WHERE ist.institution_id    = {$institutionId}
                       AND ist.academic_period_id = {$academicPeriodId}
                       AND ist.institution_class_id = {$classId}
                     ORDER BY isub.name"
                );
                $timetableSubjects = $stmt->fetchAll('assoc');
            }

            if (!empty($timetableSubjects)) {
                //POCOR-9611: Timetable found — restrict columns to scheduled subjects only
                foreach ($timetableSubjects as $r) {
                    $sid     = (int)$r['id'];
                    $slots[] = [
                        'key'            => "s{$sid}",
                        'label'          => $r['name'],
                        'mr_period'      => 1,
                        'mr_subject_id'  => $sid,
                        'abd_period'     => 0,
                        'abd_subject_id' => $sid,
                    ];
                }
            } else {
                //POCOR-9611: No timetable — fall back to all institution_subjects for the scope
                $subjectWhere = "isub.institution_id = {$institutionId}"
                    . " AND isub.academic_period_id = {$academicPeriodId}";
                if ($gradeId !== null && $gradeId >= 1) {
                    $subjectWhere .= " AND isub.education_grade_id = {$gradeId}";
                }

                if ($classId !== null && $classId >= 1) {
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
        }

        //POCOR-9611: Fallback
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
     */
    private function _getMarkTypeName(int $academicPeriodId, int $institutionId, ?int $classId, ?int $gradeId, array $reportDates = []): string
    {
        $conn        = ConnectionManager::get('default');
        $classFilter = ($classId !== null && $classId >= 1) ? "AND institution_class_id = {$classId}" : '';
        $gradeFilter = ($gradeId !== null && $gradeId >= 1) ? "AND education_grade_id = {$gradeId}"  : '';
        $hasPeriods  = false;
        $hasSubjects = false;

        //POCOR-9611: Step 1 — detect from mark records scoped to the report dates + class
        if (!empty($reportDates)) {
            $dateList = "'" . implode("','", $reportDates) . "'";
            $stmt = $conn->execute(
                "SELECT MAX(CASE WHEN subject_id = 0 THEN 1 ELSE 0 END) AS has_periods,
                        MAX(CASE WHEN subject_id > 0 THEN 1 ELSE 0 END) AS has_subjects
                 FROM student_attendance_marked_records
                 WHERE institution_id      = {$institutionId}
                   AND academic_period_id = {$academicPeriodId}
                   {$classFilter} {$gradeFilter}
                   AND date IN ({$dateList})"
            );
            $modes       = $stmt->fetch('assoc');
            $hasPeriods  = $modes ? (bool)$modes['has_periods']  : false;
            $hasSubjects = $modes ? (bool)$modes['has_subjects'] : false;
        }

        //POCOR-9611: Step 2 — widen to full period for same class
        if (!$hasPeriods && !$hasSubjects) {
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
        }

        if ($hasPeriods && $hasSubjects) { $typeIds = '(3)'; }
        elseif ($hasSubjects)            { $typeIds = '(2, 3)'; }
        else                             { $typeIds = '(1, 3)'; }

        //POCOR-9611: Look up name of mark type active during the report dates
        $dateRangeWhere = '';
        if (!empty($reportDates)) {
            $rangeStart     = min($reportDates);
            $rangeEnd       = max($reportDates);
            $dateRangeWhere = "AND sms.date_enabled <= '{$rangeEnd}' AND (sms.date_disabled IS NULL OR sms.date_disabled >= '{$rangeStart}')";
        }

        $stmt = $conn->execute(
            "SELECT mt.name
             FROM student_mark_type_statuses sms
             JOIN student_attendance_mark_types mt ON mt.id = sms.student_attendance_mark_type_id
             WHERE sms.academic_period_id = {$academicPeriodId}
               AND mt.student_attendance_type_id IN {$typeIds}
               {$dateRangeWhere}
             ORDER BY sms.date_enabled DESC
             LIMIT 1"
        );
        $row = $stmt->fetch('assoc');
        return $row ? $row['name'] : 'By Period';
    }

    /**
     * Return all Mon-Fri dates within the selected month that fall inside the academic period.
     *
     * @param string $monthNum  Two-digit month ('01'–'12')
     * @param int    $academicPeriodId
     * @return array ['label' => 'April 2026', 'days' => ['d20260401' => '2026-04-01', ...]]
     */
    private function _getMonthDays(string $monthNum, int $academicPeriodId): array
    {
        if (empty($monthNum)) {
            return [];
        }

        $conn = ConnectionManager::get('default');
        $stmt = $conn->execute(
            'SELECT start_date, end_date FROM academic_periods WHERE id = ? LIMIT 1',
            [$academicPeriodId]
        );
        $period = $stmt->fetch('assoc');
        if (!$period) {
            return [];
        }

        $periodStart    = new DateTime($period['start_date']);
        $periodEnd      = new DateTime($period['end_date']);
        $startYear      = (int)$periodStart->format('Y');
        $endYear        = (int)$periodEnd->format('Y');
        $monthInt       = (int)$monthNum;
        $startMonthInt  = (int)$periodStart->format('m');

        //POCOR-9611: Determine year for this month — use start year if the month falls in the first half of the period
        $year = ($monthInt >= $startMonthInt) ? $startYear : $endYear;

        $firstOfMonth = new DateTime(sprintf('%04d-%02d-01', $year, $monthInt));
        $lastOfMonth  = new DateTime($firstOfMonth->format('Y-m-t'));

        //POCOR-9611: Clamp to academic period boundaries
        $rangeStart = ($firstOfMonth < $periodStart) ? clone $periodStart : clone $firstOfMonth;
        $rangeEnd   = ($lastOfMonth  > $periodEnd)   ? clone $periodEnd   : clone $lastOfMonth;

        //POCOR-9611: Resolve school working days from config (first_day_of_week + days_per_week)
        $cfgStmt = $conn->execute(
            "SELECT code, value FROM config_items WHERE code IN ('first_day_of_week', 'days_per_week')"
        );
        $cfgMap = array_column($cfgStmt->fetchAll('assoc'), 'value', 'code');

        $firstDayOfWeek = (isset($cfgMap['first_day_of_week']) && $cfgMap['first_day_of_week'] !== '')
            ? (int)$cfgMap['first_day_of_week'] : 1; // default: Monday
        $daysPerWeek = (isset($cfgMap['days_per_week']) && $cfgMap['days_per_week'] !== '')
            ? (int)$cfgMap['days_per_week'] : 5;     // default: 5 days

        // Config 0=Sun → ISO 7; 1=Mon → ISO 1; ...; 6=Sat → ISO 6
        $firstDayIso = $firstDayOfWeek === 0 ? 7 : $firstDayOfWeek;

        // Build ISO-weekday hash-set for school days (e.g. Mon-Fri = {1,2,3,4,5})
        $schoolDaySet = [];
        for ($i = 0; $i < $daysPerWeek; $i++) {
            $isoDay = (($firstDayIso - 1 + $i) % 7) + 1;
            $schoolDaySet[$isoDay] = true;
        }

        $label = $firstOfMonth->format('F Y'); // "April 2026"
        $days  = [];
        $cur   = clone $rangeStart;

        while ($cur <= $rangeEnd) {
            $dow = (int)$cur->format('N'); // 1=Mon … 7=Sun
            if (isset($schoolDaySet[$dow])) {
                $key        = 'd' . $cur->format('Ymd');
                $days[$key] = $cur->format('Y-m-d');
            }
            $cur->modify('+1 day');
        }

        //POCOR-9611: Guarantee chronological order — dYYYYMMDD keys sort lexicographically = by date
        ksort($days);

        return ['label' => $label, 'days' => $days];
    }

    /**
     * Build column definitions for each day × slot combination.
     * Label: "Mon (01.04.2026) - Morning Session" or "Mon (01.04.2026) - Mathematics".
     *
     * @return array [['key'=>'col_d20260401_p1', 'label'=>'...'], ...]
     */
    private function _buildColDefs(array $slots, array $month = []): array
    {
        $days    = $month['days'] ?? [];
        $colDefs = [];

        foreach ($days as $dayKey => $dateStr) {
            $d          = new DateTime($dateStr);
            $dayName    = $d->format('D'); // "Mon", "Tue", ...
            $dateSuffix = ' (' . $d->format('d.m.Y') . ')';

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
     * Build the full-month result SQL using three targeted queries + PHP assembly.
     *
     * Same strategy as _buildWeekSQL in InstitutionStudentWeeklyAttendanceTable:
     *   Query 1 — all students for the scope
     *   Query 2 — all mark records for the month
     *   Query 3 — all absence details for the month
     *   PHP     — assemble each cell from indexed lookup maps
     *
     * Returns a UNION ALL SELECT … literal SQL suitable for use as a subquery.
     *
     * @return string
     */
    private function _buildMonthSQL(
        array $month,
        array $slots,
        int $institutionId,
        int $academicPeriodId,
        ?int $classId,
        ?int $gradeId,
        string $markTypeName
    ): string {
        $conn          = ConnectionManager::get('default');
        $monthLabel    = addslashes($month['label']);
        $markTypeSafe  = addslashes($markTypeName);
        $days          = $month['days'];

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

        if (empty($students)) {
            return $this->_buildEmptySQL($month, $slots);
        }

        // ---------------------------------------------------------------
        // Query 2: mark records for the entire month
        // ---------------------------------------------------------------
        $markRows = $conn->execute(
            "SELECT date, period, subject_id, no_scheduled_class, institution_class_id, education_grade_id
             FROM student_attendance_marked_records
             WHERE institution_id      = {$institutionId}
               AND academic_period_id = {$academicPeriodId}
               {$whereClass} {$whereGrade}
               AND date IN ({$dateList})"
        )->fetchAll('assoc');

        $mrIndex = [];
        foreach ($markRows as $mr) {
            $mrIndex["{$mr['date']}|{$mr['period']}|{$mr['subject_id']}"] = $mr;
        }

        // ---------------------------------------------------------------
        // Query 3: absence details for the entire month
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
        // PHP assembly
        // ---------------------------------------------------------------
        $absenceTypeMap = [1 => 'EXCUSED', 2 => 'UNEXCUSED', 3 => 'LATE'];
        $unionParts     = [];
        $firstRow       = true;

        foreach ($students as $student) {
            $studentId = $student['student_id'];
            $displayNo = addslashes($student['openemis_no']);
            $fullName  = addslashes($student['openemis_no'] . ' - ' . $student['full_name']);
            $className = addslashes($student['class_name']);

            if ($firstRow) {
                $cols = [
                    "'{$displayNo}'    AS openemis_no",
                    "'{$fullName}'     AS student_name",
                    "'{$className}'    AS class_name",
                    "'{$markTypeSafe}' AS attendance_by",
                    "'{$monthLabel}'   AS month_label",
                ];
            } else {
                $cols = ["'{$displayNo}'", "'{$fullName}'", "'{$className}'", "'{$markTypeSafe}'", "'{$monthLabel}'"];
            }

            $totalPresent = 0;
            $totalLate    = 0;
            $totalAbsent  = 0;

            foreach ($days as $dayKey => $date) {
                $firstSlot      = true;
                $dayFirstStatus = 'NOTMARKED';

                foreach ($slots as $slot) {
                    $mrKey  = "{$date}|{$slot['mr_period']}|{$slot['mr_subject_id']}";
                    $abKey  = "{$studentId}|{$date}|{$slot['abd_period']}|{$slot['abd_subject_id']}";
                    $mr     = $mrIndex[$mrKey] ?? null;
                    $ab     = $abIndex[$abKey] ?? null;

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

                //POCOR-9611: Totals based on first slot of each day; LATE counts as present too
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

            $cols[] = $firstRow ? "{$totalPresent} AS total_present" : (string)$totalPresent;
            $cols[] = $firstRow ? "{$totalLate}    AS total_late"    : (string)$totalLate;
            $cols[] = $firstRow ? "{$totalAbsent}  AS total_absent"  : (string)$totalAbsent;

            $unionParts[] = 'SELECT ' . implode(', ', $cols);
            $firstRow = false;
        }

        return implode("\nUNION ALL\n", $unionParts);
    }

    /**
     * Returns an empty-result SQL with the correct column aliases when there are no students.
     */
    private function _buildEmptySQL(array $month, array $slots): string
    {
        //POCOR-9611: NULL literals with correct aliases; WHERE 1=0 ensures zero rows
        $cols = ['NULL AS openemis_no', 'NULL AS student_name', 'NULL AS class_name',
                 'NULL AS attendance_by', 'NULL AS month_label'];
        foreach ($month['days'] ?? [] as $dayKey => $_) {
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
