<?php
namespace Report\Model\Table;

use ArrayObject;
use DateInterval;
use DatePeriod;
use Cake\Event\Event;
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

    public function initialize(array $config)
    {
        $this->table('institution_staff');
        $config['Modified'] = false;
        $config['Created'] = false;
        parent::initialize($config);

        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses', ['className' => 'Staff.StaffStatuses']);
        $this->belongsTo('InstitutionPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'staff_id']);
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {   
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];

    }

    /**
     *  POCOR-5181
     * staff attendance sheet formate change in POCOR-5181
    **/  

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);        
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $start_report_Date = $requestData->report_start_date;
        $end_report_Date = $requestData->report_end_date;
        $startDates = date("Y-m-d", strtotime($start_report_Date));
        $endDates = date("Y-m-d", strtotime($end_report_Date));
        $conditions = [];
        $join = [];
        $where = [];
        $StaffAttendances = TableRegistry::get('Institution.InstitutionStaffAttendances');
        $securityUsers = TableRegistry::get('security_users');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $getyear = $AcademicPeriods->find('all')
                   ->select(['name'=>$AcademicPeriods->aliasField('name')])
                   ->where(['id'=>$academicPeriodId])
                   ->limit(1);
        foreach($getyear->toArray() as $val) {
            $year  = $val['name'];
        }
        if (!empty($institutionId) && $institutionId != 0) {
            $conditions[$this->aliasField('institution_id')]=$institutionId;
        }

        /*if ($areaId != -1 && !empty($areaId)) {
            $conditions[$this->aliasField('Institutions.area_id')]=$areaId;
        }*/

        if(!empty($academicPeriodId)){
           $where['institution_staff_attendances.academic_period_id'] = $academicPeriodId;
        }
        if (!empty($institutionId) && $institutionId != 0) {
            $where['institution_staff_attendances.institution_id'] = $institutionId;
        }
        
        $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'institution_id' => 'Institutions.id',
                'position_title' =>  $query->func()->concat([
                    'InstitutionPositions.position_no' => 'literal',
                    " - ",
                    'StaffPositionTitles.name' => 'literal'
                ]),
                'identity_type' => 'IdentityTypes.name',
                'identity_number' => 'UserIdentity.number',
                'openemis_no' => 'security_users.openemis_no',
                'first_name' => 'security_users.first_name',
                'middle_name' => 'security_users.middle_name',
                'third_name' => 'security_users.third_name',
                'last_name' => 'security_users.last_name',
                'year_name' => 'month_generator.year_name',
                'month_name' => 'month_generator.month_name',
               'day_1'=> "(SELECT IFNULL(staff_attendance_info.day_1, ''))",                                         
               'day_2'=> "(SELECT IFNULL(staff_attendance_info.day_2, ''))",                                         
               'day_3'=> "(SELECT IFNULL(staff_attendance_info.day_3, ''))",                                          
               'day_4'=> "(SELECT IFNULL(staff_attendance_info.day_4, ''))",                                         
               'day_5'=> "(SELECT IFNULL(staff_attendance_info.day_5, ''))",                                          
               'day_6'=> "(SELECT IFNULL(staff_attendance_info.day_6, ''))",                                         
               'day_7'=> "(SELECT IFNULL(staff_attendance_info.day_7, ''))",                                          
               'day_8'=> "(SELECT IFNULL(staff_attendance_info.day_8, ''))",                                          
               'day_9'=> "(SELECT IFNULL(staff_attendance_info.day_9, ''))",                                         
               'day_10'=> "(SELECT IFNULL(staff_attendance_info.day_10, ''))",                                 
               'day_11'=> "(SELECT IFNULL(staff_attendance_info.day_11, ''))",                                        
               'day_12'=> "(SELECT IFNULL(staff_attendance_info.day_12, ''))",                                        
               'day_13'=> "(SELECT IFNULL(staff_attendance_info.day_13, ''))",                                        
               'day_14'=> "(SELECT IFNULL(staff_attendance_info.day_14, ''))",                                        
               'day_15'=> "(SELECT IFNULL(staff_attendance_info.day_15, ''))",                                        
               'day_16'=> "(SELECT IFNULL(staff_attendance_info.day_16, ''))",                                        
               'day_17'=> "(SELECT IFNULL(staff_attendance_info.day_17, ''))",                                        
               'day_18'=> "(SELECT IFNULL(staff_attendance_info.day_18, ''))",                                        
               'day_19'=> "(SELECT IFNULL(staff_attendance_info.day_19, ''))",                                        
               'day_20'=> "(SELECT IFNULL(staff_attendance_info.day_20, ''))",                                        
               'day_21'=> "(SELECT IFNULL(staff_attendance_info.day_21, ''))",                                        
               'day_22'=> "(SELECT IFNULL(staff_attendance_info.day_22, ''))",
               'day_23'=> "(SELECT IFNULL(staff_attendance_info.day_23, ''))",  
                'day_24'=> "(SELECT IFNULL(staff_attendance_info.day_24, ''))",  
                'day_25'=> "(SELECT IFNULL(staff_attendance_info.day_25, ''))",  
                'day_26'=> "(SELECT IFNULL(staff_attendance_info.day_26, ''))",  
                'day_27'=> "(SELECT IFNULL(staff_attendance_info.day_27, ''))",  
                'day_28'=> "(SELECT IFNULL(staff_attendance_info.day_28, ''))",  
                'day_29'=> "(SELECT IFNULL(staff_attendance_info.day_29, ''))",  
                'day_30'=> "(SELECT IFNULL(staff_attendance_info.day_30, ''))",  
                'day_31'=> "(SELECT IFNULL(staff_attendance_info.day_31, ''))",  
               ])
            ->innerJoin([$securityUsers->alias() => $securityUsers->table()],[
                   $this->aliasField('staff_id = ') . $securityUsers->aliasField('id'),
                ])
            ->innerJoin(['Institutions' => 'institutions'], [
                'Institutions.id = ' . $this->aliasfield('institution_id'),
            ])
            ->innerJoin(['InstitutionPositions' => 'institution_positions'], [
                'InstitutionPositions.id = ' . $this->aliasfield('institution_position_id'),
            ])
            ->innerJoin(['StaffPositionTitles' => 'staff_position_titles'], [
                'StaffPositionTitles.id = InstitutionPositions.staff_position_title_id',
            ])
            ->leftJoin(['UserNationalities' => 'user_nationalities'], [
                'UserNationalities.security_user_id = ' . $this->aliasfield('staff_id'),
            ])
            ->leftJoin(['Nationalities' => 'nationalities'], [
               'Nationalities.id = UserNationalities.nationality_id',
               'AND' => [
                    'Nationalities.default = 1',
                ]
            ])
            ->leftJoin(['IdentityTypes' => 'identity_types'], [
                'IdentityTypes.id = Nationalities.identity_type_id',
            ])
            ->leftJoin(['UserIdentity' => 'user_identities'], [
                'UserIdentity.security_user_id = ' . $this->aliasfield('staff_id'),
            ]);

            $join['academic_periods'] = [
                'type' => 'inner',
                'table' => 'academic_periods',
                'conditions' => [
                    'OR' => [
                        [
                            $this->aliasField('end_date') . ' IS NOT NULL',
                            $this->aliasField('start_date') . ' <=' => $startDate,
                            $this->aliasField('end_date') . ' >=' => $startDate,
                        ],
                        [
                            $this->aliasField('end_date') . ' IS NOT NULL',
                            $this->aliasField('start_date') . ' <=' => $endDate,
                            $this->aliasField('end_date') . ' >=' => $endDate,
                        ],
                        [
                            $this->aliasField('end_date') . ' IS NOT NULL',
                            $this->aliasField('start_date') . ' >=' => $endDate,
                            $this->aliasField('end_date') . ' <=' => $endDate,
                        ],
                        [
                            $this->aliasField('end_date') . ' IS NULL',
                            $this->aliasField('start_date') . ' <=' => $endDate,
                        ]
                    ],
                    
                ],      
            ];

            $join['month_generator'] = [
                'type' => 'inner',
                'table' => "(SELECT academic_period_id
        ,YEAR(m1) year_name
        ,MONTH(m1) month_id
        ,MONTHNAME(m1) month_name
    FROM
    (
        SELECT  (academic_periods.start_date - INTERVAL DAYOFMONTH(academic_periods.start_date)-1 DAY) +INTERVAL m MONTH AS m1
            ,academic_periods.end_date
            ,academic_periods.id academic_period_id
        FROM academic_periods
        CROSS JOIN
        (
            SELECT  @rownum:= @rownum+1 AS m
            FROM
            (
                SELECT  1
                UNION
                SELECT  2
                UNION
                SELECT  3
                UNION
                SELECT  4
            ) t1, (
            SELECT  1
            UNION
            SELECT  2
            UNION
            SELECT  3
            UNION
            SELECT  4) t2
                ,(
            SELECT  1
            UNION
            SELECT  2
            UNION
            SELECT  3
            UNION
            SELECT  4) t3
                ,(
            SELECT  1
            UNION
            SELECT  2
            UNION
            SELECT  3
            UNION
            SELECT  4) t4,(SELECT  @rownum:= -1) t0
        ) d1
        WHERE academic_periods.id = $academicPeriodId
    ) d2
    WHERE m1 <= d2.end_date
    ORDER BY m1
 )",
 'conditions' => ['month_generator.academic_period_id = academic_periods.id'],
];
 $join[' '] = [
                'type' => 'left',
                'table' => "(SELECT subq.academic_period_id
        ,subq.staff_id
        ,YEAR(subq.date) year_name
        ,MONTH(subq.date) month_id
        ,MAX(subq.day_1) day_1
        ,MAX(subq.day_2) day_2
        ,MAX(subq.day_3) day_3
        ,MAX(subq.day_4) day_4
        ,MAX(subq.day_5) day_5
        ,MAX(subq.day_6) day_6
        ,MAX(subq.day_7) day_7
        ,MAX(subq.day_8) day_8
        ,MAX(subq.day_9) day_9
        ,MAX(subq.day_10) day_10
        ,MAX(subq.day_11) day_11
        ,MAX(subq.day_12) day_12
        ,MAX(subq.day_13) day_13
        ,MAX(subq.day_14) day_14
        ,MAX(subq.day_15) day_15
        ,MAX(subq.day_16) day_16
        ,MAX(subq.day_17) day_17
        ,MAX(subq.day_18) day_18
        ,MAX(subq.day_19) day_19
        ,MAX(subq.day_20) day_20
        ,MAX(subq.day_21) day_21
        ,MAX(subq.day_22) day_22
        ,MAX(subq.day_23) day_23
        ,MAX(subq.day_24) day_24
        ,MAX(subq.day_25) day_25
        ,MAX(subq.day_26) day_26
        ,MAX(subq.day_27) day_27
        ,MAX(subq.day_28) day_28
        ,MAX(subq.day_29) day_29
        ,MAX(subq.day_30) day_30
        ,MAX(subq.day_31) day_31
    FROM 
    (
        (SELECT institution_staff_attendances.academic_period_id
            ,institution_staff_attendances.staff_id
            ,institution_staff_attendances.date
            ,CASE WHEN DAY(institution_staff_attendances.date) = 1 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_1
            ,CASE WHEN DAY(institution_staff_attendances.date) = 2 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_2
            ,CASE WHEN DAY(institution_staff_attendances.date) = 3 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_3
            ,CASE WHEN DAY(institution_staff_attendances.date) = 4 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_4
            ,CASE WHEN DAY(institution_staff_attendances.date) = 5 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_5
            ,CASE WHEN DAY(institution_staff_attendances.date) = 6 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_6
            ,CASE WHEN DAY(institution_staff_attendances.date) = 7 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_7
            ,CASE WHEN DAY(institution_staff_attendances.date) = 8 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_8
            ,CASE WHEN DAY(institution_staff_attendances.date) = 9 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_9
            ,CASE WHEN DAY(institution_staff_attendances.date) = 10 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_10
            ,CASE WHEN DAY(institution_staff_attendances.date) = 11 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_11
            ,CASE WHEN DAY(institution_staff_attendances.date) = 12 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_12
            ,CASE WHEN DAY(institution_staff_attendances.date) = 13 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_13
            ,CASE WHEN DAY(institution_staff_attendances.date) = 14 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_14
            ,CASE WHEN DAY(institution_staff_attendances.date) = 15 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_15
            ,CASE WHEN DAY(institution_staff_attendances.date) = 16 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_16
            ,CASE WHEN DAY(institution_staff_attendances.date) = 17 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_17
            ,CASE WHEN DAY(institution_staff_attendances.date) = 18 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_18
            ,CASE WHEN DAY(institution_staff_attendances.date) = 19 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_19
            ,CASE WHEN DAY(institution_staff_attendances.date) = 20 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_20
            ,CASE WHEN DAY(institution_staff_attendances.date) = 21 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_21
            ,CASE WHEN DAY(institution_staff_attendances.date) = 22 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_22
            ,CASE WHEN DAY(institution_staff_attendances.date) = 23 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_23
            ,CASE WHEN DAY(institution_staff_attendances.date) = 24 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_24
            ,CASE WHEN DAY(institution_staff_attendances.date) = 25 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_25
            ,CASE WHEN DAY(institution_staff_attendances.date) = 26 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_26
            ,CASE WHEN DAY(institution_staff_attendances.date) = 27 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_27
            ,CASE WHEN DAY(institution_staff_attendances.date) = 28 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_28
            ,CASE WHEN DAY(institution_staff_attendances.date) = 29 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_29
            ,CASE WHEN DAY(institution_staff_attendances.date) = 30 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_30

            ,CASE WHEN DAY(institution_staff_attendances.date) = 31 THEN IF(institution_staff_attendances.time_in IS NULL, '', CONCAT(institution_staff_attendances.time_in, IF(institution_staff_attendances.time_out IS NULL, '', CONCAT('-', institution_staff_attendances.time_out)))) ELSE '' END day_31
        FROM institution_staff_attendances
        WHERE institution_staff_attendances.academic_period_id = 31
        AND institution_staff_attendances.institution_id = 6
        GROUP BY institution_staff_attendances.staff_id
            ,institution_staff_attendances.date
    )) subq 
    GROUP BY subq.academic_period_id
        ,subq.staff_id
        ,YEAR(subq.date)
        ,MONTH(subq.date)
 ) staff_attendance_info",
    'conditions' => [
        'staff_attendance_info.academic_period_id = month_generator.academic_period_id',
        'staff_attendance_info.staff_id = security_users.id',
        'staff_attendance_info.year_name = month_generator.year_name',
        'staff_attendance_info.month_id  = month_generator.month_id'
    ],
    ];
             
    $query->where($conditions)->group(['security_users.id','month_generator.year_name','month_generator.month_id'])
    ->order(['institutions.code','security_users.openemis_no','month_generator.year_name','month_generator.month_id']);
    $query->join($join);
    $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row)  {
                    $row['referrer_full_name'] = $row['first_name'] .' '.$row['middle_name'].' '.$row['third_name'].' '. $row['last_name'];
                    return $row;

                });
            });

    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
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
            'key' => '',
            'field' => 'position_title',
            'type' => 'string',
            'label' => __('Position Title')
        ];
        
        $newArray[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newArray[] = [
            'key' => 'referrer_full_name',
            'field' => 'referrer_full_name',
            'type' => 'string',
            'label' => __('Staff Name')
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

        for( $i=1; $i<=$i_max; $i++ ) //POCOR-5181 
        { 
            $newArray[]=[
            'key'   => 'day_'.$i,
            'field' => 'day_'.$i,
            'type'  => 'string',
            'label' => __('day_'.$i),
            ];
        }
        
        $fields->exchangeArray($newArray);
    }

    /*public function onExcelRenderAttendance(Event $event, Entity $entity, array $attr)
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
        $StaffLeave = TableRegistry::get('Institution.StaffLeave');
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
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
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
        $StaffAttendances = TableRegistry::get('Institution.InstitutionStaffAttendances');
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
