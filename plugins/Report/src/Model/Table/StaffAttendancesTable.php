<?php

namespace Report\Model\Table;

use ArrayObject;
use DateInterval;
use DatePeriod;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\I18n\Time;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;

class StaffAttendancesTable extends ControllerActionTable
{
    private $_leaveData = [];
    private $_attendanceData = [];

    public function initialize(array $config): void
    {
        $this->setTable('institution_staff');
        $config['Modified'] = false;
        $config['Created'] = false;
        parent::initialize($config);

        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);
        $this->belongsTo('InstitutionPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('AcademicPeriod.Period');
        $this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);
        $this->addBehavior('Excel', [
            'excludes' => [
                'start_date',
                'end_date',
                'start_year',
                'end_year',
                'FTE',
                'staff_type_id',
                'staff_status_id',
                'institution_id',
                'institution_position_id',
                'security_group_user_id'
            ]
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->getAlias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    /**
     *  POCOR-9003 refactured
     **/

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);

        // Extract and prepare parameters
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $startDate = date("Y-m-d", strtotime($requestData->report_start_date));
        $endDate = date("Y-m-d", strtotime($requestData->report_end_date));
        $startMonth = date('m', strtotime($startDate));
        $endMonth = date('m', strtotime($endDate));
        $conditions = [];
        if (!empty($institutionId) && $institutionId != 0) {
            $conditions[] = ['Institutions.id = ' . $institutionId];
            $conditionSQL = "AND institution_staff_attendances.institution_id = {$institutionId}";
        } else {
            $conditionSQL = '';
        }

        if (!empty($academicPeriodId)) {
            $conditions[] = ['academic_periods.id = ' . $academicPeriodId];
        }

        // Apply joins
        $this->applyJoins($query, $academicPeriodId, $startMonth, $endMonth, $startDate, $endDate, $conditionSQL);

        // Apply select columns
        $this->applySelectFields($query);

        // Apply where, group and order
        $query
            ->where($conditions)
            ->group(['security_users.id', 'month_generator.year_name', 'month_generator.month_id'])
            ->order(['Institutions.code', 'security_users.openemis_no', 'month_generator.year_name', 'month_generator.month_id']);

        // Format the final output
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $row['referrer_full_name'] = trim("{$row['first_name']} {$row['middle_name']} {$row['third_name']} {$row['last_name']}");
                return $row;
            });
        });
        //        Log::debug($query->sql());
    }

    private function applySelectFields(Query $query)
    {
        $query->select([
            'institution_code' => 'Institutions.code',
            'institution_name' => 'Institutions.name',
            'institution_id' => 'Institutions.id',
            'position_title' =>
            'CONCAT(`InstitutionPositions`.`position_no`, " - ", `StaffPositionTitles`.`name`)',
            'identity_type' => 'IdentityTypes.name',
            'identity_number' => 'UserIdentity.number',
            'openemis_no' => 'security_users.openemis_no',
            'first_name' => 'security_users.first_name',
            'middle_name' => 'security_users.middle_name',
            'third_name' => 'security_users.third_name',
            'last_name' => 'security_users.last_name',
            'year_name' => 'month_generator.year_name',
            'month_name' => 'month_generator.month_name',
        ]);

        // Add dynamic day fields
        for ($i = 1; $i <= 31; $i++) {
            $query->select(["day_{$i}" => "(SELECT IFNULL(staff_attendance_info.day_{$i}, ''))"]);
        }
        $attendedDayExpressions = [];
        for ($i = 1; $i <= 31; $i++) {
            $attendedDayExpressions[] = "CASE WHEN staff_attendance_info.day_{$i} != '' THEN 1 ELSE 0 END";
        }

        $attendedDaysExpr = implode(' + ', $attendedDayExpressions);
        $query->select(['attended_days' => "($attendedDaysExpr)"]);
    }

    private function applyJoins(Query $query, $academicPeriodId, $startMonth, $endMonth, $startDate, $endDate, $conditionSQL)
    {
        $institution_staff = $this->getAlias();
        $query
            ->innerJoin(['security_users' => 'security_users'], [
                $this->aliasField('staff_id') . ' = security_users.id'
            ])
            ->innerJoin(['Institutions' => 'institutions'], [
                'Institutions.id = ' . $this->aliasField('institution_id'),
            ])
            ->innerJoin(['InstitutionPositions' => 'institution_positions'], [
                'InstitutionPositions.id = ' . $this->aliasField('institution_position_id'),
            ])
            ->innerJoin(['StaffPositionTitles' => 'staff_position_titles'], [
                'StaffPositionTitles.id = InstitutionPositions.staff_position_title_id',
            ])
            ->leftJoin(['UserNationalities' => 'user_nationalities'], [
                'UserNationalities.security_user_id = ' . $this->aliasField('staff_id'),
            ])
            ->leftJoin(['Nationalities' => 'nationalities'], [
                'Nationalities.id = UserNationalities.nationality_id',
                'AND' => ['Nationalities.default = 1']
            ])
            ->leftJoin(['IdentityTypes' => 'identity_types'], [
                'IdentityTypes.id = Nationalities.identity_type_id',
            ])
            ->leftJoin(['UserIdentity' => 'user_identities'], [
                'UserIdentity.security_user_id = ' . $this->aliasField('staff_id'),
            ])->join([
                'academic_periods' => [
                    'type' => 'INNER',
                    'table' => 'academic_periods',
                    'conditions' => [
                        "(
                {$institution_staff}.end_date IS NOT NULL
                AND {$institution_staff}.start_date <= academic_periods.end_date
                AND {$institution_staff}.end_date >= academic_periods.start_date
            )
            OR (
                {$institution_staff}.end_date IS NULL
                AND {$institution_staff}.start_date <= academic_periods.end_date
            )"
                    ]
                ]
            ]);;

        // Month generator join (creates one row per month)
        $monthSql = $this->buildMonthGeneratorSQL($academicPeriodId, $startDate, $endDate);
        $institution_staff = $this->getAlias();  //POCOR-9311

        $query->join([
            'month_generator' => [
                'type' => 'INNER',
                'table' => "({$monthSql})",
                'conditions' => [ //POCOR-9311
                    'month_generator.academic_period_id = academic_periods.id',
                    "(
                        {$institution_staff}.start_date <= month_generator.month_end
                        AND (
                                {$institution_staff}.end_date IS NULL
                            OR {$institution_staff}.end_date >= month_generator.month_start
                        )
                        )"
                ]
            ]
        ]);

        // Staff attendance info (pivoted days)
        $attendanceSql = $this->buildAttendanceInfoSQL($academicPeriodId, $startDate, $endDate, $conditionSQL);
        $query->join([
            'staff_attendance_info' => [
                'type' => 'LEFT',
                'table' => "({$attendanceSql})",
                'conditions' => [
                    'staff_attendance_info.academic_period_id = month_generator.academic_period_id',
                    'staff_attendance_info.staff_id = security_users.id',
                    'staff_attendance_info.year_name = month_generator.year_name',
                    'staff_attendance_info.month_id = month_generator.month_id'
                ]
            ]
        ]);
    }

    private function buildMonthGeneratorSQL($academicPeriodId, $startDate, $endDate)
    { //POCOR-9311 start
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
        //POCOR-9311 end
    }


    private function buildAttendanceInfoSQL($academicPeriodId, $startDate, $endDate, $conditionSQL)
    {
        $dayCases = [];
        for ($i = 1; $i <= 31; $i++) {
            $dayCases[] = "CASE WHEN DAY(date) = {$i} THEN IF(time_in IS NULL, '', CONCAT(time_in, IF(time_out IS NULL, '', CONCAT('-', time_out)))) ELSE '' END AS day_{$i}";
        }
        $dayCasesSql = implode(",\n", $dayCases);

        return <<<SQL
        SELECT academic_period_id, staff_id, YEAR(date) year_name, MONTH(date) month_id,
               MAX(day_1) day_1, MAX(day_2) day_2, MAX(day_3) day_3, MAX(day_4) day_4,
               MAX(day_5) day_5, MAX(day_6) day_6, MAX(day_7) day_7, MAX(day_8) day_8,
               MAX(day_9) day_9, MAX(day_10) day_10, MAX(day_11) day_11, MAX(day_12) day_12,
               MAX(day_13) day_13, MAX(day_14) day_14, MAX(day_15) day_15, MAX(day_16) day_16,
               MAX(day_17) day_17, MAX(day_18) day_18, MAX(day_19) day_19, MAX(day_20) day_20,
               MAX(day_21) day_21, MAX(day_22) day_22, MAX(day_23) day_23, MAX(day_24) day_24,
               MAX(day_25) day_25, MAX(day_26) day_26, MAX(day_27) day_27, MAX(day_28) day_28,
               MAX(day_29) day_29, MAX(day_30) day_30, MAX(day_31) day_31
        FROM (
            SELECT academic_period_id, staff_id, date,
                   {$dayCasesSql}
            FROM institution_staff_attendances
            WHERE academic_period_id = {$academicPeriodId}
              AND date BETWEEN '{$startDate}' AND '{$endDate}'
              {$conditionSQL}
            GROUP BY staff_id, date
        ) subq
        GROUP BY academic_period_id, staff_id, year_name, month_id
    SQL;
    }
    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
    {
        $newArray = [];
        $i_max = 31; //POCOR-5181
        $newArray[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        $newArray[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $newArray[] = [
            'key' => '',
            'field' => 'position_title',
            'type' => 'string',
            'label' => __('Position Title')
        ];

        $newArray[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $newArray[] = [
            'key' => '',
            'field' => 'attended_days',
            'type' => 'integer',
            'label' => __('Attended Days')
        ];

        $newArray[] = [
            'key' => 'referrer_full_name',
            'field' => 'referrer_full_name',
            'type' => 'string',
            'label' => __('Staff Name')
        ];
        $newArray[] = [
            'key' => '',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Default Identity Type')
        ];
        $newArray[] = [
            'key' => '',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $newArray[] = [
            'key' => 'year_name',
            'field' => 'year_name',
            'type' => 'integer',
            'label' => __('Year')
        ];

        $newArray[] = [
            'key' => 'month_name',
            'field' => 'month_name',
            'type' => 'integer',
            'label' => __('Month')
        ];

        for ($i = 1; $i <= $i_max; $i++) //POCOR-5181
        {
            $newArray[] = [
                'key'   => 'day_' . $i,
                'field' => 'day_' . $i,
                'type'  => 'string',
                'label' => __('day_' . $i),
            ];
        }

        $fields->exchangeArray($newArray);
    }

    /*public function onExcelRenderAttendance(EventInterface $event, Entity $entity, array $attr)
    {
        // get the data from the temporary variable
        $leaveData = $this->_leaveData;
        $attendanceData = $this->_attendanceData;

        if (isset($leaveData[$entity->staff_id][$attr['date']])) {
            $leaveObj = $leaveData[$entity->staff_id][$attr['date']]['leave'];
            if ($leaveObj->full_day) {
                return sprintf('%s %s %s', __('Absent'), __('Full'), __('Day'));
            } else {
                // maybe can remove this part. Here is just getting the time in and time out if detected that a staff is on half day leave
                if (isset($attendanceData[$entity->staff_id][$attr['date']])) {
                    $attendanceObj = $attendanceData[$entity->staff_id][$attr['date']]['attendance'];
                    $timeIn = $attendanceObj->time_in ? $attendanceObj->time_in->format('H:i:s') : '';
                    $timeOut = $attendanceObj->time_out ? ' - '.$attendanceObj->time_out->format('H:i:s') : '';
                    return sprintf('%s %s %s', __('Absent'), __('Half'), __('Day')).
                    "\r\n". $timeIn . $timeOut;
                }
                return sprintf('%s %s %s', __('Absent'), __('Half'), __('Day'));
            }
        }

        if (isset($attendanceData[$entity->staff_id][$attr['date']])) {
            $attendanceObj = $attendanceData[$entity->staff_id][$attr['date']]['attendance'];
            $timeIn = $attendanceObj->time_in ? $attendanceObj->time_in->format('H:i:s') : '';
            $timeOut = $attendanceObj->time_out ? ' - '.$attendanceObj->time_out->format('H:i:s') : '';
            return $timeIn. $timeOut;
        }

        return 'Attendance Not Marked';
    }

    public function getLeaveData($monthStartDay, $monthEndDay, $institutionId)
    {
        // getting data for staff leave
        $StaffLeave = TableRegistry::getTableLocator()->get('Institution.StaffLeave');
        $where = [
            'OR' => [
                [
                    $StaffLeave->aliasField("date_to <= '") . $monthEndDay. "'",
                    $StaffLeave->aliasField("date_from >= '") . $monthStartDay. "'"
                ],
                [
                    $StaffLeave->aliasField("date_to <= '") . $monthEndDay. "'",
                    $StaffLeave->aliasField("date_to >= '") . $monthStartDay. "'"
                ],
                [
                    $StaffLeave->aliasField("date_from <= '") . $monthEndDay. "'",
                    $StaffLeave->aliasField("date_from >= '") . $monthStartDay. "'"
                ],
                [
                    $StaffLeave->aliasField("date_from <= '") . $monthStartDay. "'",
                    $StaffLeave->aliasField("date_to >= '") . $monthEndDay. "'"
                ]
            ],
            $StaffLeave->aliasField('institution_id') => $institutionId
        ];

        $StaffLeaveArr = $StaffLeave
            ->find()
            ->where($where)
            ->toArray();

        // reformating staff leave array
        $leaveByStaffIdRecords = [];
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $workingDaysOfWeek = $AcademicPeriods->getWorkingDaysOfWeek();
        foreach ($StaffLeaveArr as $key => $value) {
            $staffId = $value->staff_id;
            $startDate = date_create($value->date_from);
            $endDate = date_create($value->date_to);
            $endDate = $endDate->modify('+1 day');
            $interval = new DateInterval('P1D');
            $datePeriod = new DatePeriod($startDate, $interval, $endDate);
            foreach ($datePeriod as $key => $date) {
                $dayText = $date->format('l');
                $dateStr = $date->format('Y-m-d');
                // to ensure that the date is within the start and end time of the excel sheet and that the date is working day of the week
                if (in_array($dayText, $workingDaysOfWeek) && $monthStartDay <= $dateStr && $monthEndDay >= $dateStr) {
                    $leaveByStaffIdRecords[$staffId][$dateStr]['leave'] = $value;
                }
            }
        }
        return $leaveByStaffIdRecords;
    }


    public function getAttendanceData($monthStartDay, $monthEndDay, $institutionId)
    {
        // getting data for staff attendance
        $StaffAttendances = TableRegistry::getTableLocator()->get('Institution.InstitutionStaffAttendances');
        $StaffAttendancesArr = $StaffAttendances
             ->find()
            ->where([
                $StaffAttendances->aliasField('institution_id') => $institutionId,
                $StaffAttendances->aliasField('date').' >= ' => $monthStartDay,
                $StaffAttendances->aliasField('date').' <= ' => $monthEndDay,
            ])
            ->toArray();

        // reformating staff attendance array
        $attendanceByStaffIdRecords = [];
        foreach ($StaffAttendancesArr as $key => $value) {
            $dateStr = $value->date->format('Y-m-d');
            $staffId = $value->staff_id;
            $attendanceByStaffIdRecords[$staffId][$dateStr]['attendance'] = $value;
        }
        return $attendanceByStaffIdRecords;
    }*/
}
