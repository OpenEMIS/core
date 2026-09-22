<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

// POCOR-9813: Student Attendance Report - lists students recorded as PRESENT (or absent, with
// the absence type shown) for each marked class/period/subject slot within the selected date
// range, one row per student per month per period/subject - mirrors the existing Staff
// Attendance report's day_1..day_31 layout, but derives presence from
// student_attendance_marked_records (which slots were actually marked) left-joined against
// institution_student_absence_details (which students were recorded absent for that slot),
// since - unlike staff - there is no direct per-student "present" record: a student is
// PRESENT for any marked slot unless an absence record exists for them.
class StudentAttendancesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Institution.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false,
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->getAlias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape',
        ];
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);

        $academicPeriodId = (int) $requestData->academic_period_id;
        $areaId = $requestData->area_education_id ?? '';
        $institutionId = $requestData->institution_id ?? null;
        $educationGradeId = $requestData->education_grade_id ?? -1;
        $genderId = $requestData->gender_id ?? -1;
        $startDate = date('Y-m-d', strtotime($requestData->report_start_date));
        $endDate = date('Y-m-d', strtotime($requestData->report_end_date));

        $filterInstitutionIds = $this->parseInstitutionIds($institutionId);

        $conditions = [];
        if (!empty($filterInstitutionIds)) {
            $conditions[$this->aliasField('institution_id') . ' IN'] = $filterInstitutionIds;
        }
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($educationGradeId) && $educationGradeId != -1) {
            $conditions[$this->aliasField('education_grade_id')] = $educationGradeId;
        }
        if (!empty($genderId) && $genderId != -1) {
            $conditions['Users.gender_id'] = $genderId;
        }
        if ($areaId !== '' && $areaId != -1) {
            $areaIds = $this->getChildren($areaId, []);
            $areaIds[] = $areaId;
            $conditions['Institutions.area_id IN'] = $areaIds;
        }

        $this->applyJoins($query, $academicPeriodId, $startDate, $endDate, $filterInstitutionIds);
        $this->applySelectFields($query);

        $query
            ->where($conditions)
            ->group([
                $this->aliasField('student_id'),
                $this->aliasField('institution_class_id'),
                'period_subject_generator.period',
                'period_subject_generator.subject_id',
                'month_generator.year_name',
                'month_generator.month_id',
            ])
            ->order([
                'Institutions.code',
                'Users.first_name',
                'Users.last_name',
                'month_generator.year_name',
                'month_generator.month_id',
            ]);

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $row['student_name'] = trim("{$row['first_name']} {$row['middle_name']} {$row['third_name']} {$row['last_name']}");
                return $row;
            });
        });
    }

    private function parseInstitutionIds($institutionId)
    {
        $ids = [];
        if (is_object($institutionId) && isset($institutionId->_ids)) {
            $ids = (array) $institutionId->_ids;
        } elseif (is_array($institutionId) && isset($institutionId['_ids'])) {
            $ids = (array) $institutionId['_ids'];
        } elseif (!empty($institutionId)) {
            $ids = [$institutionId];
        }

        return array_values(array_filter($ids, function ($id) {
            return $id !== '' && $id !== null && (int) $id > 0;
        }));
    }

    private function applySelectFields(Query $query)
    {
        $query->select([
            'academic_period_name' => 'AcademicPeriods.name',
            'institution_code' => 'Institutions.code',
            'institution_name' => 'Institutions.name',
            'openemis_no' => 'Users.openemis_no',
            'first_name' => 'Users.first_name',
            'middle_name' => 'Users.middle_name',
            'third_name' => 'Users.third_name',
            'last_name' => 'Users.last_name',
            'gender_name' => 'Genders.name',
            'education_grade_name' => 'EducationGrades.name',
            'class_name' => 'InstitutionClasses.name',
            'year_name' => 'month_generator.year_name',
            'month_name' => 'month_generator.month_name',
            'attendance_name' => 'period_subject_generator.attendance_name',
            'default_identity_type' => "(SELECT IFNULL(student_identities.identity_type, ''))",
            'identity_number' => "(SELECT IFNULL(student_identities.identity_number, ''))",
        ]);

        for ($i = 1; $i <= 31; $i++) {
            $query->select(["day_{$i}" => "(SELECT IFNULL(student_attendance_info.day_{$i}, ''))"]);
        }
    }

    private function applyJoins(Query $query, $academicPeriodId, $startDate, $endDate, array $filterInstitutionIds = [])
    {
        $studentIdField = $this->aliasField('student_id');
        $institutionClassIdField = $this->aliasField('institution_class_id');

        // Scope the two heaviest subqueries down to the selected institution(s) up front - like
        // StaffAttendancesTable's own $conditionSQL does for its equivalent subquery - instead of
        // computing them system-wide and relying on the outer WHERE to narrow the result. Without
        // this, selecting one institution (the common case) still made these subqueries scan every
        // institution's marked/absence records for the date range.
        $institutionConditionSQL = '';
        if (!empty($filterInstitutionIds)) {
            $idsList = implode(',', array_map('intval', $filterInstitutionIds));
            $institutionConditionSQL = "AND mr.institution_id IN ({$idsList})";
        }

        $query
            ->innerJoin(['Users' => 'security_users'], ["Users.id = {$studentIdField}"])
            ->innerJoin(['Institutions' => 'institutions'], ['Institutions.id = ' . $this->aliasField('institution_id')])
            ->innerJoin(['InstitutionClasses' => 'institution_classes'], ["InstitutionClasses.id = {$institutionClassIdField}"])
            ->innerJoin(['EducationGrades' => 'education_grades'], ['EducationGrades.id = ' . $this->aliasField('education_grade_id')])
            ->innerJoin(['AcademicPeriods' => 'academic_periods'], ['AcademicPeriods.id = ' . $this->aliasField('academic_period_id')])
            ->leftJoin(['Genders' => 'genders'], ['Genders.id = Users.gender_id']);

        $query->join([
            'student_identities' => [
                'type' => 'LEFT',
                'table' => "(SELECT user_identities.security_user_id
                            ,GROUP_CONCAT(identity_types.name) identity_type
                            ,GROUP_CONCAT(user_identities.number) identity_number
                            FROM user_identities
                            INNER JOIN identity_types ON identity_types.id = user_identities.identity_type_id
                            WHERE identity_types.default = 1
                            GROUP BY user_identities.security_user_id)",
                'conditions' => ['student_identities.security_user_id = Users.id'],
            ],
        ]);

        $monthSql = $this->buildMonthGeneratorSQL($academicPeriodId, $startDate, $endDate);
        $query->join([
            'month_generator' => [
                'type' => 'INNER',
                'table' => "({$monthSql})",
                'conditions' => ['month_generator.academic_period_id = ' . $this->aliasField('academic_period_id')],
            ],
        ]);

        $periodSubjectSql = $this->buildPeriodSubjectGeneratorSQL($academicPeriodId, $startDate, $endDate, $institutionConditionSQL);
        $query->join([
            'period_subject_generator' => [
                'type' => 'INNER',
                'table' => "({$periodSubjectSql})",
                'conditions' => ["period_subject_generator.institution_class_id = {$institutionClassIdField}"],
            ],
        ]);

        $attendanceSql = $this->buildAttendanceInfoSQL($academicPeriodId, $startDate, $endDate, $institutionConditionSQL);
        $query->join([
            'student_attendance_info' => [
                'type' => 'LEFT',
                'table' => "({$attendanceSql})",
                'conditions' => [
                    "student_attendance_info.student_id = {$studentIdField}",
                    "student_attendance_info.institution_class_id = {$institutionClassIdField}",
                    'student_attendance_info.period = period_subject_generator.period',
                    'student_attendance_info.subject_id <=> period_subject_generator.subject_id',
                    'student_attendance_info.year_name = month_generator.year_name',
                    'student_attendance_info.month_id = month_generator.month_id',
                ],
            ],
        ]);
    }

    private function buildMonthGeneratorSQL($academicPeriodId, $startDate, $endDate)
    {
        $academicPeriodId = (int) $academicPeriodId;

        return <<<SQL
        SELECT
            academic_period_id,
            YEAR(m1)  AS year_name,
            MONTH(m1) AS month_id,
            MONTHNAME(m1) AS month_name,
            DATE_FORMAT(m1, '%Y-%m-01') AS month_start,
            LAST_DAY(m1)                AS month_end
        FROM (
            SELECT (ap.start_date - INTERVAL DAYOFMONTH(ap.start_date)-1 DAY) + INTERVAL m MONTH AS m1,
                   ap.end_date,
                   ap.id academic_period_id
            FROM academic_periods ap
            CROSS JOIN (
                SELECT @rownum := @rownum + 1 AS m
                FROM (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) t1,
                     (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) t2,
                     (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) t3,
                     (SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4) t4,
                     (SELECT @rownum := -1) t0
            ) d1
            WHERE ap.id = {$academicPeriodId}
        ) d2
        WHERE m1 <= d2.end_date
          AND m1 BETWEEN '{$startDate}' AND '{$endDate}'
        ORDER BY m1
        SQL;
    }

    // One row per distinct (institution_class_id, period, subject_id) actually marked within the
    // selected date range - this is the row-grain driver for the "Attendance Name" column (the
    // ticket's Period/Subject column): each class/period/subject combination gets its own row
    // per student per month, matching the reference report layout.
    private function buildPeriodSubjectGeneratorSQL($academicPeriodId, $startDate, $endDate, $institutionConditionSQL = '')
    {
        $academicPeriodId = (int) $academicPeriodId;

        return <<<SQL
        SELECT
            mr.institution_class_id,
            mr.period,
            mr.subject_id,
            MAX(IF(subjects.name IS NOT NULL, subjects.name, CONCAT('Period ', mr.period))) AS attendance_name
        FROM student_attendance_marked_records mr
        LEFT JOIN institution_subjects subjects ON subjects.id = mr.subject_id
        WHERE mr.academic_period_id = {$academicPeriodId}
          AND mr.date BETWEEN '{$startDate}' AND '{$endDate}'
          AND mr.no_scheduled_class = 0
          {$institutionConditionSQL}
        GROUP BY mr.institution_class_id, mr.period, mr.subject_id
        SQL;
    }

    // Pivots each student's per-day status (day_1..day_31) for a given class + period/subject
    // slot + month. A student is PRESENT for any marked slot unless a matching row exists in
    // institution_student_absence_details for that same student/date/period/subject, in which
    // case the absence type's name is shown instead.
    private function buildAttendanceInfoSQL($academicPeriodId, $startDate, $endDate, $institutionConditionSQL = '')
    {
        $academicPeriodId = (int) $academicPeriodId;

        $dayCases = [];
        for ($i = 1; $i <= 31; $i++) {
            $dayCases[] = "CASE WHEN DAY(mr.date) = {$i} THEN IF(ab.student_id IS NOT NULL, IFNULL(ab_types.name, 'Absent'), 'Present') ELSE '' END AS day_{$i}";
        }
        $dayCasesSql = implode(",\n                   ", $dayCases);

        return <<<SQL
        SELECT academic_period_id, student_id, institution_class_id, period, subject_id, year_name, month_id,
               MAX(day_1) day_1, MAX(day_2) day_2, MAX(day_3) day_3, MAX(day_4) day_4,
               MAX(day_5) day_5, MAX(day_6) day_6, MAX(day_7) day_7, MAX(day_8) day_8,
               MAX(day_9) day_9, MAX(day_10) day_10, MAX(day_11) day_11, MAX(day_12) day_12,
               MAX(day_13) day_13, MAX(day_14) day_14, MAX(day_15) day_15, MAX(day_16) day_16,
               MAX(day_17) day_17, MAX(day_18) day_18, MAX(day_19) day_19, MAX(day_20) day_20,
               MAX(day_21) day_21, MAX(day_22) day_22, MAX(day_23) day_23, MAX(day_24) day_24,
               MAX(day_25) day_25, MAX(day_26) day_26, MAX(day_27) day_27, MAX(day_28) day_28,
               MAX(day_29) day_29, MAX(day_30) day_30, MAX(day_31) day_31
        FROM (
            SELECT mr.academic_period_id, ics.student_id, mr.institution_class_id, mr.period, mr.subject_id,
                   YEAR(mr.date) year_name, MONTH(mr.date) month_id,
                   {$dayCasesSql}
            FROM student_attendance_marked_records mr
            INNER JOIN institution_class_students ics
                ON ics.institution_class_id = mr.institution_class_id
               AND ics.academic_period_id = mr.academic_period_id
            LEFT JOIN institution_student_absence_details ab
                ON ab.student_id = ics.student_id
               AND ab.institution_id = mr.institution_id
               AND ab.academic_period_id = mr.academic_period_id
               AND ab.date = mr.date
               AND ab.period = mr.period
               AND (ab.subject_id <=> mr.subject_id)
            LEFT JOIN absence_types ab_types ON ab_types.id = ab.absence_type_id
            WHERE mr.academic_period_id = {$academicPeriodId}
              AND mr.date BETWEEN '{$startDate}' AND '{$endDate}'
              AND mr.no_scheduled_class = 0
              {$institutionConditionSQL}
            GROUP BY ics.student_id, mr.institution_class_id, mr.period, mr.subject_id, mr.date
        ) subq
        GROUP BY academic_period_id, student_id, institution_class_id, period, subject_id, year_name, month_id
        SQL;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = ['key' => 'academic_period_name', 'field' => 'academic_period_name', 'type' => 'string', 'label' => __('Academic Period')];
        $newFields[] = ['key' => 'institution_code', 'field' => 'institution_code', 'type' => 'string', 'label' => __('Institution Code')];
        $newFields[] = ['key' => 'institution_name', 'field' => 'institution_name', 'type' => 'string', 'label' => __('Institution Name')];
        $newFields[] = ['key' => 'openemis_no', 'field' => 'openemis_no', 'type' => 'string', 'label' => __('OpenEMIS ID')];
        $newFields[] = ['key' => 'default_identity_type', 'field' => 'default_identity_type', 'type' => 'string', 'label' => __('Default Identity Type')];
        $newFields[] = ['key' => 'identity_number', 'field' => 'identity_number', 'type' => 'string', 'label' => __('Identity Number')];
        $newFields[] = ['key' => 'student_name', 'field' => 'student_name', 'type' => 'string', 'label' => __('Student Name')];
        $newFields[] = ['key' => 'gender_name', 'field' => 'gender_name', 'type' => 'string', 'label' => __('Gender')];
        $newFields[] = ['key' => 'education_grade_name', 'field' => 'education_grade_name', 'type' => 'string', 'label' => __('Education Grade')];
        $newFields[] = ['key' => 'class_name', 'field' => 'class_name', 'type' => 'string', 'label' => __('Class')];
        $newFields[] = ['key' => 'month_name', 'field' => 'month_name', 'type' => 'string', 'label' => __('Month')];
        $newFields[] = ['key' => 'attendance_name', 'field' => 'attendance_name', 'type' => 'string', 'label' => __('Attendance Name')];

        for ($i = 1; $i <= 31; $i++) {
            $newFields[] = ['key' => 'day_' . $i, 'field' => 'day_' . $i, 'type' => 'string', 'label' => __('Day ' . $i)];
        }

        $fields->exchangeArray($newFields);
    }

    public function getChildren($id, $idArray)
    {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
            ->where([$Areas->aliasField('parent_id') => $id])
            ->toArray();

        foreach ($result as $value) {
            $idArray[] = $value['id'];
            $idArray = $this->getChildren($value['id'], $idArray);
        }

        return $idArray;
    }
}
