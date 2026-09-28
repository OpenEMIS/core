<?php
//POCOR-9831: Student Attendance Weekly Report - moved from Institutions > Statistics > Standard (POCOR-9611)
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\ConnectionManager;
use App\Model\Table\AppTable;

/**
 * Student Attendance Weekly Report (Reports > Institutions).
 *
 * One Excel row per student x attendance slot (period or subject) of the student's class,
 * one column per working day between Start Date and End Date, plus per-row totals.
 * Each class/grade is resolved to its own attendance mode, so a report spanning several
 * classes (Class = All) or grades (Grade = All) still lists the right periods/subjects
 * for every student - e.g. both Morning and Afternoon rows for a two-period class.
 *
 * Input params (report_progress.params): academic_period_id, area_education_id,
 * institution_id (_ids or 0 = all), education_grade_id (-1 = all),
 * institution_class_id (0 = all), report_start_date, report_end_date.
 *
 * @ticket POCOR-9831
 */
class StudentAttendanceWeeklyReportTable extends AppTable
{
    //POCOR-9831: status => cell fill colour
    const STATUS_COLOURS = [
        'PRESENT'   => '#92D050',
        'LATE'      => '#FFC000',
        'EXCUSED'   => '#FFFF00',
        'UNEXCUSED' => '#FF0000',
        'NOTMARKED' => '#D3D3D3',
        'NO CLASS'  => '#808080',
    ];

    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);

        $this->addBehavior('Excel', [
            'excludes'   => [],
            'pages'      => false,
            'autoFields' => false,
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets): void
    {
        $requestData = json_decode($settings['process']['params']);

        $dates = $this->getReportDates($requestData->report_start_date ?? '', $requestData->report_end_date ?? '');
        $settings['_dates'] = $dates;
        $settings['_slots'] = $this->buildSlots($requestData, $dates);

        $sheets[] = [
            'name'        => __('Student Attendance'),
            'table'       => $this,
            'query'       => $this->find(),
            'orientation' => 'landscape',
        ];
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields): void
    {
        $newFields = [
            ['key' => 'institution_code', 'field' => 'institution_code', 'type' => 'string', 'label' => __('Institution Code')],
            ['key' => 'institution_name', 'field' => 'institution_name', 'type' => 'string', 'label' => __('Institution Name')],
            ['key' => 'education_grade',  'field' => 'education_grade',  'type' => 'string', 'label' => __('Education Grade')],
            ['key' => 'class_name',       'field' => 'class_name',       'type' => 'string', 'label' => __('Class')],
            ['key' => 'openemis_no',      'field' => 'openemis_no',      'type' => 'string', 'label' => __('OpenEMIS ID')],
            ['key' => 'student_name',     'field' => 'student_name',     'type' => 'string', 'label' => __('Name')],
            ['key' => 'attendance_by',    'field' => 'attendance_by',    'type' => 'string', 'label' => __('Attendance By')],
            ['key' => 'slot_label',       'field' => 'slot_label',       'type' => 'string', 'label' => __('Period') . ' / ' . __('Subject')],
        ];

        //POCOR-9831: one column per working day in the selected date range
        foreach ($settings['_dates'] ?? [] as $key => $date) {
            $newFields[] = [
                'key'   => $key,
                'field' => $key,
                'type'  => 'attendance_status',
                'label' => __($date->format('l')) . ' ' . $date->format('d/m/Y'),
            ];
        }

        $newFields[] = ['key' => 'total_present', 'field' => 'total_present', 'type' => 'integer', 'label' => __('Total present')];
        $newFields[] = ['key' => 'total_late',    'field' => 'total_late',    'type' => 'integer', 'label' => __('Total late')];
        $newFields[] = ['key' => 'total_absent',  'field' => 'total_absent',  'type' => 'integer', 'label' => __('Total absent')];

        $fields->exchangeArray($newFields);
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query): void
    {
        $requestData = json_decode($settings['process']['params']);
        $dates = $settings['_dates'] ?? [];
        $slots = $settings['_slots'] ?? [];

        if (empty($dates) || empty($slots)) {
            $query->where(['1 = 0']); //POCOR-9831: nothing to report
            return;
        }

        //POCOR-9831: derived table of (class, grade, slot index) - one row per slot of each class/grade
        $slotRows = [];
        foreach ($slots as $pairKey => $pairSlots) {
            [$classId, $gradeId] = array_map('intval', explode('|', $pairKey));
            foreach (array_keys($pairSlots) as $index) {
                $slotRows[] = empty($slotRows)
                    ? "SELECT {$classId} AS institution_class_id, {$gradeId} AS education_grade_id, {$index} AS slot_index"
                    : "SELECT {$classId}, {$gradeId}, {$index}";
            }
        }
        $slotSql = implode(' UNION ALL ', $slotRows);

        $alias = $this->getAlias();
        $query
            ->select([
                'student_id'           => $this->aliasField('student_id'),
                'institution_class_id' => $this->aliasField('institution_class_id'),
                'education_grade_id'   => $this->aliasField('education_grade_id'),
                'slot_index'           => 'Slots.slot_index',
                'institution_code'     => 'Institutions.code',
                'institution_name'     => 'Institutions.name',
                'education_grade'      => 'EducationGrades.name',
                'class_name'           => 'InstitutionClasses.name',
                'openemis_no'          => 'Users.openemis_no',
                'first_name'           => 'Users.first_name',
                'middle_name'          => 'Users.middle_name',
                'third_name'           => 'Users.third_name',
                'last_name'            => 'Users.last_name',
            ])
            ->innerJoin(['Slots' => "({$slotSql})"], [
                'Slots.institution_class_id = ' . $this->aliasField('institution_class_id'),
                'Slots.education_grade_id = ' . $this->aliasField('education_grade_id'),
            ])
            ->innerJoin(['Users' => 'security_users'], ['Users.id = ' . $this->aliasField('student_id')])
            ->innerJoin(['Institutions' => 'institutions'], ['Institutions.id = ' . $this->aliasField('institution_id')])
            ->innerJoin(['InstitutionClasses' => 'institution_classes'], ['InstitutionClasses.id = ' . $this->aliasField('institution_class_id')])
            ->innerJoin(['EducationGrades' => 'education_grades'], ['EducationGrades.id = ' . $this->aliasField('education_grade_id')])
            ->where($this->getScopeConditions($requestData, $alias))
            ->order([
                'Institutions.code'       => 'ASC',
                'EducationGrades.name'    => 'ASC',
                'InstitutionClasses.name' => 'ASC',
                'Users.first_name'        => 'ASC',
                'Users.last_name'         => 'ASC',
                $this->aliasField('student_id') => 'ASC',
                'Slots.slot_index'        => 'ASC',
            ]);

        if (empty($requestData->super_admin)) {
            //POCOR-9831: non super admins only see institutions they have access to
            $query->find('byAccess', [
                'user_id' => $requestData->user_id,
                'institution_field_alias' => $this->aliasField('institution_id'),
            ]);
        }

        $academicPeriodId = (int)$requestData->academic_period_id;
        $query->formatResults(function (CollectionInterface $results) use ($dates, $slots, $academicPeriodId) {
            return $this->assembleRows($results->toList(), $dates, $slots, $academicPeriodId);
        });
    }

    /**
     * Cell renderer for attendance_status columns: value + background colour per status.
     */
    public function onExcelRenderAttendanceStatus(EventInterface $event, Entity $entity, array $attr): array
    {
        $value = $entity->has($attr['field']) ? $entity->{$attr['field']} : '';
        $style = isset(self::STATUS_COLOURS[$value])
            ? ['fill' => self::STATUS_COLOURS[$value], 'halign' => 'center']
            : ['halign' => 'center'];

        return ['value' => __($value), 'style' => $style];
    }

    /**
     * Working days (per first_day_of_week / days_per_week config) between the two dates,
     * keyed by the Excel column key.
     *
     * @return DateTime[] ['d_20260901' => DateTime, ...]
     */
    private function getReportDates(string $startDate, string $endDate): array
    {
        if ($startDate === '' || $endDate === '') {
            return [];
        }

        $workingDays = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods')->getWorkingDaysOfWeek();
        $current = $this->parseDate($startDate);
        $end = $this->parseDate($endDate);
        if ($current === null || $end === null) {
            return [];
        }

        $dates = [];
        while ($current <= $end) {
            if (in_array($current->format('l'), $workingDays, true)) {
                $dates['d_' . $current->format('Ymd')] = clone $current;
            }
            $current->modify('+1 day');
        }
        return $dates;
    }

    /**
     * Parse a report date posted by the datepicker (system date format, without ordinals),
     * falling back to PHP's native parsing for Y-m-d / d-m-Y values.
     */
    private function parseDate(string $value): ?DateTime
    {
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $systemDateFormat = $ConfigItems->value('date_format') ?: 'd-m-Y';
        $editableDateFormat = preg_replace('/\s+/', ' ', trim(str_replace('S', '', $systemDateFormat))) ?: 'd-m-Y';

        $date = DateTime::createFromFormat('!' . $editableDateFormat, $value);
        if ($date !== false) {
            return $date;
        }
        try {
            return (new DateTime($value))->setTime(0, 0);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * WHERE conditions shared by the main query and slot resolution.
     */
    private function getScopeConditions($requestData, string $alias): array
    {
        $conditions = [
            $alias . '.academic_period_id' => (int)$requestData->academic_period_id,
        ];

        $institutionIds = $this->getInstitutionIds($requestData);
        if (!empty($institutionIds)) {
            $conditions[$alias . '.institution_id IN'] = $institutionIds;
        } else {
            //POCOR-9831: "All Institutions" is limited to the selected area (and its sub-areas)
            $areaId = (int)($requestData->area_education_id ?? -1);
            if ($areaId > 0) {
                $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                $area = $Areas->get($areaId);
                $institutionQuery = TableRegistry::getTableLocator()->get('Institution.Institutions')->find()
                    ->select(['Institutions.id'])
                    ->innerJoin(['AreaScope' => 'areas'], ['AreaScope.id = Institutions.area_id'])
                    ->where(['AreaScope.lft >=' => $area->lft, 'AreaScope.rght <=' => $area->rght]);
                $conditions[$alias . '.institution_id IN'] = $institutionQuery;
            }
        }

        $gradeId = (int)($requestData->education_grade_id ?? -1);
        if ($gradeId > 0) {
            $conditions[$alias . '.education_grade_id'] = $gradeId;
        }
        $classId = (int)($requestData->institution_class_id ?? 0);
        if ($classId > 0) {
            $conditions[$alias . '.institution_class_id'] = $classId;
        }
        return $conditions;
    }

    private function getInstitutionIds($requestData): array
    {
        $institutionId = $requestData->institution_id ?? null;
        if (is_object($institutionId) && isset($institutionId->_ids)) {
            $ids = (array)$institutionId->_ids;
        } elseif (is_array($institutionId) && isset($institutionId['_ids'])) {
            $ids = (array)$institutionId['_ids'];
        } else {
            $ids = [$institutionId];
        }
        return array_values(array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        }));
    }

    /**
     * Resolve the attendance slots of every class/grade in scope.
     *
     * The mode per grade follows the same chain as the Institution > Attendance > Students
     * screen (student_mark_type_status_grades -> student_mark_type_statuses ->
     * student_attendance_mark_types -> student_attendance_types): SUBJECT gives one slot
     * per subject of the class, anything else gives one slot per configured period.
     *
     * @return array ['classId|gradeId' => [index => slot], ...]
     */
    private function buildSlots($requestData, array $dates): array
    {
        if (empty($dates)) {
            return [];
        }
        $conn = ConnectionManager::get('default');
        $academicPeriodId = (int)$requestData->academic_period_id;
        $startDate = reset($dates)->format('Y-m-d');
        $endDate = end($dates)->format('Y-m-d');

        //POCOR-9831: class/grade pairs that actually have students in scope
        $pairs = $this->find()
            ->select([
                'institution_class_id' => $this->aliasField('institution_class_id'),
                'education_grade_id'   => $this->aliasField('education_grade_id'),
            ])
            ->where($this->getScopeConditions($requestData, $this->getAlias()))
            ->distinct([$this->aliasField('institution_class_id'), $this->aliasField('education_grade_id')])
            ->disableHydration()
            ->toArray();
        if (empty($pairs)) {
            return [];
        }
        $gradeIds = array_values(array_unique(array_map('intval', array_column($pairs, 'education_grade_id'))));

        //POCOR-9831: active mark type per grade for the report range - latest enabled wins
        $markTypeRows = $conn->execute(
            'SELECT smtsg.education_grade_id, samt.id AS mark_type_id, sat.code AS type_code
             FROM student_mark_type_status_grades smtsg
             JOIN student_mark_type_statuses smts   ON smts.id = smtsg.student_mark_type_status_id
             JOIN student_attendance_mark_types samt ON samt.id = smts.student_attendance_mark_type_id
             JOIN student_attendance_types sat       ON sat.id = samt.student_attendance_type_id
             WHERE smts.academic_period_id = ?
               AND smts.date_enabled <= ?
               AND smts.date_disabled >= ?
               AND smtsg.education_grade_id IN (' . implode(',', $gradeIds) . ')
             ORDER BY smts.date_enabled DESC, samt.id DESC',
            [$academicPeriodId, $endDate, $startDate]
        )->fetchAll('assoc');
        $gradeMarkType = [];
        foreach ($markTypeRows as $row) {
            $gradeMarkType[(int)$row['education_grade_id']] ??= $row;
        }

        //POCOR-9831: periods of every period-mode mark type
        $periodsByMarkType = [];
        $periodMarkTypeIds = [];
        foreach ($gradeMarkType as $row) {
            if ($row['type_code'] !== 'SUBJECT') {
                $periodMarkTypeIds[] = (int)$row['mark_type_id'];
            }
        }
        if (!empty($periodMarkTypeIds)) {
            $periodRows = $conn->execute(
                'SELECT student_attendance_mark_type_id, `period`, name
                 FROM student_attendance_per_day_periods
                 WHERE student_attendance_mark_type_id IN (' . implode(',', array_unique($periodMarkTypeIds)) . ')
                 ORDER BY `period`'
            )->fetchAll('assoc');
            foreach ($periodRows as $row) {
                $p = (int)$row['period'];
                $periodsByMarkType[(int)$row['student_attendance_mark_type_id']][] = $this->makeSlot(__('Period'), $row['name'], $p, 0, $p, 0);
            }
        }

        //POCOR-9831: subjects of every subject-mode class/grade
        $subjectsByPair = [];
        $subjectClassIds = [];
        foreach ($pairs as $pair) {
            $markType = $gradeMarkType[(int)$pair['education_grade_id']] ?? null;
            if ($markType !== null && $markType['type_code'] === 'SUBJECT') {
                $subjectClassIds[] = (int)$pair['institution_class_id'];
            }
        }
        if (!empty($subjectClassIds)) {
            $subjectRows = $conn->execute(
                'SELECT ics.institution_class_id, isub.education_grade_id, isub.id, isub.name
                 FROM institution_class_subjects ics
                 JOIN institution_subjects isub ON isub.id = ics.institution_subject_id
                 WHERE ics.institution_class_id IN (' . implode(',', array_unique($subjectClassIds)) . ')
                   AND isub.academic_period_id = ?
                 ORDER BY isub.name',
                [$academicPeriodId]
            )->fetchAll('assoc');
            foreach ($subjectRows as $row) {
                $sid = (int)$row['id'];
                //POCOR-9611: subject mode stores period=1 in marked records, period=0 in absence details
                $subjectsByPair["{$row['institution_class_id']}|{$row['education_grade_id']}"][$sid] = $this->makeSlot(__('Subject'), $row['name'], 1, $sid, 0, $sid);
            }
        }

        $slots = [];
        foreach ($pairs as $pair) {
            $pairKey = "{$pair['institution_class_id']}|{$pair['education_grade_id']}";
            $markType = $gradeMarkType[(int)$pair['education_grade_id']] ?? null;

            if ($markType !== null && $markType['type_code'] === 'SUBJECT') {
                $pairSlots = array_values($subjectsByPair[$pairKey] ?? [])
                    ?: [$this->makeSlot(__('Subject'), __('Subject'), 1, 0, 0, 0)];
            } else {
                $pairSlots = ($markType !== null ? ($periodsByMarkType[(int)$markType['mark_type_id']] ?? []) : [])
                    ?: [$this->makeSlot(__('Period'), __('Period') . ' 1', 1, 0, 1, 0)];
            }
            $slots[$pairKey] = $pairSlots;
        }
        return $slots;
    }

    private function makeSlot(string $attendanceBy, string $label, int $mrPeriod, int $mrSubjectId, int $abdPeriod, int $abdSubjectId): array
    {
        return [
            'attendance_by'  => $attendanceBy,
            'label'          => $label,
            'mr_period'      => $mrPeriod,
            'mr_subject_id'  => $mrSubjectId,
            'abd_period'     => $abdPeriod,
            'abd_subject_id' => $abdSubjectId,
        ];
    }

    /**
     * Fill the day columns and totals of each student x slot row from bulk-fetched
     * marked records and absence details (two queries for the whole result set).
     */
    private function assembleRows(array $rows, array $dates, array $slots, int $academicPeriodId): array
    {
        if (empty($rows)) {
            return $rows;
        }
        $conn = ConnectionManager::get('default');
        $startDate = reset($dates)->format('Y-m-d');
        $endDate = end($dates)->format('Y-m-d');
        $classIds = implode(',', array_unique(array_map(function ($row) {
            return (int)$row->institution_class_id;
        }, $rows)));

        $markIndex = [];
        $markRows = $conn->execute(
            "SELECT institution_class_id, education_grade_id, date, period, subject_id, no_scheduled_class
             FROM student_attendance_marked_records
             WHERE academic_period_id = ? AND institution_class_id IN ({$classIds})
               AND date BETWEEN ? AND ?",
            [$academicPeriodId, $startDate, $endDate]
        )->fetchAll('assoc');
        foreach ($markRows as $mr) {
            $markIndex["{$mr['institution_class_id']}|{$mr['education_grade_id']}|{$mr['date']}|{$mr['period']}|{$mr['subject_id']}"] = (int)$mr['no_scheduled_class'];
        }

        $absenceIndex = [];
        $absenceRows = $conn->execute(
            "SELECT student_id, institution_class_id, date, period, subject_id, absence_type_id
             FROM institution_student_absence_details
             WHERE academic_period_id = ? AND institution_class_id IN ({$classIds})
               AND date BETWEEN ? AND ?",
            [$academicPeriodId, $startDate, $endDate]
        )->fetchAll('assoc');
        foreach ($absenceRows as $ab) {
            $absenceIndex["{$ab['student_id']}|{$ab['institution_class_id']}|{$ab['date']}|{$ab['period']}|{$ab['subject_id']}"] = (int)$ab['absence_type_id'];
        }

        $absenceCodes = TableRegistry::getTableLocator()->get('Institution.AbsenceTypes')->getCodeList();

        foreach ($rows as $row) {
            $classId = (int)$row->institution_class_id;
            $gradeId = (int)$row->education_grade_id;
            $slot = $slots["{$classId}|{$gradeId}"][(int)$row->slot_index];

            $row->student_name = trim(implode(' ', array_filter([$row->first_name, $row->middle_name, $row->third_name, $row->last_name])));
            $row->attendance_by = $slot['attendance_by'];
            $row->slot_label = $slot['label'];

            $totals = ['present' => 0, 'late' => 0, 'absent' => 0];
            foreach ($dates as $key => $date) {
                $day = $date->format('Y-m-d');
                $markKey = "{$classId}|{$gradeId}|{$day}|{$slot['mr_period']}|{$slot['mr_subject_id']}";
                $absenceKey = "{$row->student_id}|{$classId}|{$day}|{$slot['abd_period']}|{$slot['abd_subject_id']}";

                if (!isset($markIndex[$markKey])) {
                    $status = 'NOTMARKED';
                } elseif ($markIndex[$markKey] === 1) {
                    $status = 'NO CLASS';
                } elseif (isset($absenceIndex[$absenceKey])) {
                    $status = $absenceCodes[$absenceIndex[$absenceKey]] ?? 'PRESENT';
                } else {
                    $status = 'PRESENT';
                }
                $row->{$key} = $status;

                //POCOR-9611: LATE counts as present and late; NO CLASS / NOTMARKED count nowhere
                if ($status === 'PRESENT' || $status === 'LATE') {
                    $totals['present']++;
                }
                if ($status === 'LATE') {
                    $totals['late']++;
                }
                if ($status === 'EXCUSED' || $status === 'UNEXCUSED') {
                    $totals['absent']++;
                }
            }
            $row->total_present = $totals['present'];
            $row->total_late = $totals['late'];
            $row->total_absent = $totals['absent'];
        }
        return $rows;
    }
}
