<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;
use Cake\ORM\Entity;
use DateTime;
use Cake\Datasource\ConnectionManager;
/**
 * Get the Student Absences details in excel file 
 * @ticket POCOR-6631
 */
class InstitutionStandardStudentAbsencesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absences');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'institution_class_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' =>'education_grade_id']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('InstitutionStudentAbsenceDays', ['className' => 'Institution.InstitutionStudentAbsenceDays', 'foreignKey' =>'institution_student_absence_day_id']);

        // Behaviours
        $this->addBehavior('Excel', [
            'excludes' => [],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);

        $controllerName = $this->controller->name;
        $institutions_crumb = __('Institutions');
        $parent_crumb       = __('Statistics');
        $reportName         = __('Standard');
        
        //# START: Crumb
        $this->Navigation->removeCrumb($this->getHeader($this->alias));
        $this->Navigation->addCrumb($institutions_crumb . ' ' . $parent_crumb);
        //# END: Crumb
        $this->controller->set('contentHeader', __($institutions_crumb) . ' ' . $parent_crumb . ' - ' . $reportName);
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request)
    {
        $session = $this->request->session();
        $institution_id = $session->read('Institution.Institutions.id');
        $request->data[$this->alias()]['current_institution_id'] = $institution_id;
        $request->data[$this->alias()]['institution_id'] = $institution_id;
        if ($action == 'add') {
            $attr['value'] = 'xlsx';
            $attr['attr']['value'] = 'Excel';
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $options = $options = $this->controller->getInstitutionStatisticStandardReportFeature();
        $attr['options'] = $options;
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->data[$this->alias()]['feature']))) {
            $option = $attr['options'];
            reset($option);
            $this->request->data[$this->alias()]['feature'] = key($option);
        }
        return $attr;
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $academic_period = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $getyear = $academic_period->find('all')
                   ->select(['end_year','name'=>$academic_period->aliasField('start_year')]) //POCOR-6854
                   ->where(['id'=>$academicPeriodId])
                   ->limit(1);
        foreach($getyear->toArray() as $val) {
            $year  = $val['name'];
            $yearSecond  = $val['end_year']; //POCOR-6854
        }
        $institutionId = $requestData->institution_id;
        $gradeId = $requestData->education_grade_id;
        $classId = $requestData->institution_class_id;
        $month = $requestData->month;
        $absentDays = TableRegistry::get('Institution. InstitutionStudentAbsenceDays');
        $where = [];
        if ($gradeId != -1) {
            $where[$this->aliasField('education_grade_id')] = $gradeId;
        }
        if ($classId != 0) {
            $where[$this->aliasField('institution_class_id')] = $classId;
        }
        $where[$this->aliasField('academic_period_id')] = $academicPeriodId;
        $where[$this->aliasField('institution_id')] = $institutionId;
        /*$where['MONTH(InstitutionStudentAbsenceDays.start_date)<'] = 06;
        $where['YEAR(InstitutionStudentAbsenceDays.start_date)<'] = 2022;
        $where['MONTH(InstitutionStudentAbsenceDays.end_date)>'] = 06;
        $where['YEAR(InstitutionStudentAbsenceDays.end_date)'] = 2022;*/
        
        $date =  '"'.$year.'-'.$month.'%"';
        $datelike =  '"'.$year.'-'.$month.'"';
        $dateSecond =  '"'.$yearSecond.'-'.$month.'%"';  //POCOR-6854
        $yearSecond =  $yearSecond;  //POCOR-6854
        $query
            ->select([
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                'education_grades'=>$this->aliasField('education_grade_id'),
                'institution_class'=>$this->aliasField('institution_class_id'),
                'absent_start'=> "(GROUP_CONCAT(DISTINCT".$absentDays->aliasField('start_date').",' / ',".$absentDays->aliasField('end_date')." SEPARATOR ', '))",
                'openemis_no' => 'Users.openemis_no',
                'first_name' => 'Users.first_name',
                'middle_name' => 'Users.middle_name',
                'third_name' => 'Users.third_name',
                'last_name' => 'Users.last_name',
                'academic_period' => 'AcademicPeriods.name',
                ])
            ->contain([
                'Users' => [
                   'fields' => [
                    'Users.id',
                      'openemis_no' => 'Users.openemis_no',
                        'first_name' => 'Users.first_name',
                        'middle_name' => 'Users.middle_name',
                        'third_name' => 'Users.third_name',
                        'last_name' => 'Users.last_name',
                   ]
             ],
             'Users.Identities.IdentityTypes' => [
                    'fields' => [
                        'Identities.number',
                        'IdentityTypes.name',
                        'IdentityTypes.default'
                    ]
                ],
             'AcademicPeriods' => [
                    'fields' => [
                        'academic_period_id'=>'AcademicPeriods.id',
                        'academic_period'=>'AcademicPeriods.name'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                       'institution_name'=> 'Institutions.name',
                        'institution_code'=>'Institutions.code'
                    ]
                ],
                'InstitutionClasses' => [
                    'fields' => [
                       'institution_Class_name'=> 'InstitutionClasses.name'
                    ]
                ],
                'EducationGrades' => [
                    'fields' => [
                       'education_grade_name'=> 'EducationGrades.name',
                    ]
                ],
            ])
            ->InnerJoin([$absentDays->alias() => $absentDays->table()],
                [$absentDays->aliasField('student_id = ') . $this->aliasField('student_id')]
            )

            /*->andWhere([$absentDays->aliasField('start_date LIKE '.$date)])
            ->orWhere([$absentDays->aliasField('start_date LIKE '.$dateSecond)]) */ //POCOR-6854
            ->Where($where)->group([$this->aliasField('student_id'),
                $absentDays->aliasField('student_id')]);
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) 
                use($date,$dateSecond,$datelike,$month,$yearSecond)
            {
                return $results->map(function ($row) use($date,$dateSecond,$datelike,$month,$yearSecond)
                { 
                    $row['referrer_full_name'] = $row['first_name'] .' '.$row['middle_name'].' '.$row['third_name'].' '. $row['last_name'];
                    $row['Absent_Date'] = $date;
                    $row['absent_Date_like'] = $datelike;//POCOR-7334
                    $row['month'] = $month;//POCOR-7334
                    $row['Absent_Date_Second'] = $dateSecond;  //POCOR-6854
                    $alldate = $row['absent_start'];
                    $academicPeriodGet = $row['academic_period'];
                    $row['end_year'] = $yearSecond;
                    $split = explode(',', $alldate);
                    return $row;
                });
            });

    }

    /*function getBetweenDates($startDate, $endDate)
    {
        $rangArray = [];
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
             
        for ($currentDate = $startDate; $currentDate <= $endDate; 
                                        $currentDate += (86400)) {
                                                
            $date = date('Y-m-d', $currentDate);
            $rangArray[] = $date;
        }
        return $rangArray;
    }*/

    /**
     * Generate the all Header for sheet
     */
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key'   => 'academic_period',
            'field' => 'academic_period',
            'type'  => 'integer',
            'label' => __('Academic Period'),
        ];
        $newFields[] = [
            'key'   => 'institution_code',
            'field' => 'institution_code',
            'type'  => 'string',
            'label' => __('School Code'),
        ];
        $newFields[] = [
            'key'   => 'institution_name',
            'field' => 'institution_name',
            'type'  => 'string',
            'label' => __('School Name'),
        ];
        $newFields[] = [
            'key'   => 'education_grade_name',
            'field' => 'education_grade_name',
            'type'  => 'string',
            'label' => __('Grade'),
        ];
        $newFields[] = [
            'key'   => 'institution_Class_name',
            'field' => 'institution_Class_name',
            'type'  => 'string',
            'label' => __('Class'),
        ];
        $newFields[] = [
            'key'   => 'openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $newFields[] = [
            'key'   => 'referrer_full_name',
            'field' => 'referrer_full_name',
            'type'  => 'string',
            'label' => __('Student Full Name'),
        ];
        $newFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'user_identities_default',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $newFields[] = [
            'key'   => 'total_absence_day',
            'field' => 'total_absence_day',
            'type'  => 'integer',
            'label' => __('Total absences'),
        ];

        $newFields[] = [
            'key'   => 'day_one',
            'field' => 'day_one',
            'type'  => 'integer',
            'label' => __('Day 1'),
        ];
        $newFields[] = [
            'key'   => 'day_two',
            'field' => 'day_two',
            'type'  => 'integer',
            'label' => __('Day 2'),
        ];
        $newFields[] = [
            'key'   => 'day_three',
            'field' => 'day_three',
            'type'  => 'integer',
            'label' => __('Day 3'),
        ];
        $newFields[] = [
            'key'   => 'day_four',
            'field' => 'day_four',
            'type'  => 'integer',
            'label' => __('Day 4'),
        ];
        $newFields[] = [
            'key'   => 'day_five',
            'field' => 'day_five',
            'type'  => 'integer',
            'label' => __('Day 5'),
        ];
        $newFields[] = [
            'key'   => 'day_six',
            'field' => 'day_six',
            'type'  => 'integer',
            'label' => __('Day 6'),
        ];
        $newFields[] = [
            'key'   => 'day_seven',
            'field' => 'day_seven',
            'type'  => 'integer',
            'label' => __('Day 7'),
        ];
        $newFields[] = [
            'key'   => 'day_eight',
            'field' => 'day_eight',
            'type'  => 'integer',
            'label' => __('Day 8'),
        ];
        $newFields[] = [
            'key'   => 'day_nine',
            'field' => 'day_nine',
            'type'  => 'integer',
            'label' => __('Day 9'),
        ];
        $newFields[] = [
            'key'   => 'day_ten',
            'field' => 'day_ten',
            'type'  => 'integer',
            'label' => __('Day 10'),
        ];
        $newFields[] = [
            'key'   => 'dayeleven',
            'field' => 'dayeleven',
            'type'  => 'integer',
            'label' => __('Day 11'),
        ];
        $newFields[] = [
            'key'   => 'daytwelve',
            'field' => 'daytwelve',
            'type'  => 'integer',
            'label' => __('Day 12'),
        ];
        $newFields[] = [
            'key'   => 'daythirteen',
            'field' => 'daythirteen',
            'type'  => 'integer',
            'label' => __('Day 13'),
        ];
        $newFields[] = [
            'key'   => 'dayfourteen',
            'field' => 'dayfourteen',
            'type'  => 'integer',
            'label' => __('Day 14'),
        ];
        $newFields[] = [
            'key'   => 'dayfifteen',
            'field' => 'dayfifteen',
            'type'  => 'integer',
            'label' => __('Day 15'),
        ];
        $newFields[] = [
            'key'   => 'daysixteen',
            'field' => 'daysixteen',
            'type'  => 'integer',
            'label' => __('Day 16'),
        ];
        $newFields[] = [
            'key'   => 'dayseventeen',
            'field' => 'dayseventeen',
            'type'  => 'integer',
            'label' => __('Day 17'),
        ];
        $newFields[] = [
            'key'   => 'dayeighteen',
            'field' => 'dayeighteen',
            'type'  => 'integer',
            'label' => __('Day 18'),
        ];
        $newFields[] = [
            'key'   => 'daynineteen',
            'field' => 'daynineteen',
            'type'  => 'integer',
            'label' => __('Day 19'),
        ];
        $newFields[] = [
            'key'   => 'daytwenty',
            'field' => 'daytwenty',
            'type'  => 'integer',
            'label' => __('Day 20'),
        ];
        $newFields[] = [
            'key'   => 'daytwentyone',
            'field' => 'daytwentyone',
            'type'  => 'integer',
            'label' => __('Day 21'),
        ];
        $newFields[] = [
            'key'   => 'daytwentytwo',
            'field' => 'daytwentytwo',
            'type'  => 'integer',
            'label' => __('Day 22'),
        ];
        $newFields[] = [
            'key'   => 'daytwentythree',
            'field' => 'daytwentythree',
            'type'  => 'integer',
            'label' => __('Day 23'),
        ];
        $newFields[] = [
            'key'   => 'daytwentyfour',
            'field' => 'daytwentyfour',
            'type'  => 'integer',
            'label' => __('Day 24'),
        ];
        $newFields[] = [
            'key'   => 'daytwentyfive',
            'field' => 'daytwentyfive',
            'type'  => 'integer',
            'label' => __('Day 25'),
        ];
        $newFields[] = [
            'key'   => 'daytwentysix',
            'field' => 'daytwentysix',
            'type'  => 'integer',
            'label' => __('Day 26'),
        ];
        $newFields[] = [
            'key'   => 'daytwentyseven',
            'field' => 'daytwentyseven',
            'type'  => 'integer',
            'label' => __('Day 27'),
        ];
        $newFields[] = [
            'key'   => 'daytwentyeight',
            'field' => 'daytwentyeight',
            'type'  => 'integer',
            'label' => __('Day 28'),
        ];
        $newFields[] = [
            'key'   => 'daytwentynine',
            'field' => 'daytwentynine',
            'type'  => 'integer',
            'label' => __('Day 29'),
        ];
        $newFields[] = [
            'key'   => 'daythirty',
            'field' => 'daythirty',
            'type'  => 'integer',
            'label' => __('Day 30'),
        ];
        $newFields[] = [
            'key'   => 'daythirtyone',
            'field' => 'daythirtyone',
            'type'  => 'integer',
            'label' => __('Day 31'),
        ];
        
        

        $fields->exchangeArray($newFields);
    }

    /**
    * Get staff absences days
    * whole logic change in POCOR-7334 
    */
    public function onExcelGetTotalAbsenceDay(Event $event, Entity $entity)
    {
        $userid =  $entity->student_id;
        $institutionId =  $entity->institution_id;
        $Absent_Date =  $entity->Absent_Date;
        $Absent_Date_Second =  $entity->Absent_Date_Second; //POCOR-6854
        $requestDate = $entity->absent_Date_like; //POCOR-7334
        $requestDateGet = str_replace('"', '', $requestDate);
        $requestmonth = $entity->month; 
        $academic_period =  $entity->academic_period; 
        $yearSecond =  $entity->end_year; 
       // print_r($currentYear);die;
        $connection = ConnectionManager::get('default');
        $entity->total_absence_days = '';
        $statement = $connection->prepare("SELECT subq.institution_id
                        ,subq.student_id
                        ,SUM(subq.number_of_days_based_on_selected_month) number_of_days_based_on_selected_month
                        ,MAX(subq.day_1) as day_1
                        ,MAX(subq.day_2) as day_2
                        ,MAX(subq.day_3) as day_3
                        ,MAX(subq.day_4) as day_4
                        ,MAX(subq.day_5) as day_5
                        ,MAX(subq.day_6) as day_6
                        ,MAX(subq.day_7) as day_7
                        ,MAX(subq.day_8) as day_8
                        ,MAX(subq.day_9) as day_9
                        ,MAX(subq.day_10) as day_10
                        ,MAX(subq.day_11) as day_11
                        ,MAX(subq.day_12) as day_12
                        ,MAX(subq.day_13) as day_13
                        ,MAX(subq.day_14) as day_14
                        ,MAX(subq.day_15) as day_15
                        ,MAX(subq.day_16) as day_16
                        ,MAX(subq.day_17) as day_17
                        ,MAX(subq.day_18) as day_18
                        ,MAX(subq.day_19) as day_19
                        ,MAX(subq.day_20) as day_20
                        ,MAX(subq.day_21) as day_21
                        ,MAX(subq.day_22) as day_22
                        ,MAX(subq.day_23) as day_23
                        ,MAX(subq.day_24) as day_24
                        ,MAX(subq.day_25) as day_25
                        ,MAX(subq.day_26) as day_26
                        ,MAX(subq.day_27) as day_27
                        ,MAX(subq.day_28) as day_28
                        ,MAX(subq.day_29) as day_29
                        ,MAX(subq.day_30) as day_30
                        ,MAX(subq.day_31) as day_31
                    FROM 
                    (
                        SELECT institution_student_absence_days.institution_id
                          ,institution_student_absence_days.student_id
                          ,institution_student_absence_days.start_date
                          ,institution_student_absence_days.end_date
                          ,institution_student_absence_days.absent_days existing_number_of_days
                          ,DATEDIFF(institution_student_absence_days.end_date, institution_student_absence_days.start_date) + 1 AS new_number_of_days
                          ,LEAST(LAST_DAY(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01')), institution_student_absence_days.end_date) - GREATEST(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01'), institution_student_absence_days.start_date) + 1 AS number_of_days_based_on_selected_month
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_1
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-02') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_2
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-03') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_3
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-04') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_4
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-05') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_5
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-06') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_6
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-07') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_7
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-08') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_8
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-09') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_9
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-10') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_10
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-11') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_11
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-12') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_12
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-13') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_13
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-14') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_14
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-15') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_15
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-16') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_16
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-17') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_17
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-18') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_18
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-19') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_19
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-20') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_20
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-21') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_21
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-22') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_22
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-23') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_23
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-24') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_24
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-25') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_25
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-26') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_26
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-27') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_27
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-28') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_28
                          ,IF(DAY(LAST_DAY(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01'))) >= 29 AND CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-29') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_29
                          ,IF(DAY(LAST_DAY(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01'))) >= 30 AND CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-30') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_30
                          ,IF(DAY(LAST_DAY(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01'))) = 31 AND CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-31') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_31
                        FROM institution_student_absence_days, (SELECT @month_id := $requestmonth as month_id, @year_id := $yearSecond AS year_id) AS variables
                        WHERE institution_student_absence_days.student_id = $userid
                        AND institution_student_absence_days.institution_id = $institutionId
                        AND MONTH(institution_student_absence_days.start_date) <= $requestmonth
                        AND YEAR(institution_student_absence_days.start_date) <= $yearSecond
                        AND MONTH(institution_student_absence_days.end_date) >= $requestmonth
                        AND YEAR(institution_student_absence_days.end_date) >= $yearSecond
                    ) subq
                    GROUP BY subq.student_id");
         $statement->execute();
       // print_r($gg);
       $list =  $statement->fetchAll(\PDO::FETCH_ASSOC);
        $days = [];
        if(!empty($list)){
            foreach($list as $val){
                $total_absence_days = $val['number_of_days_based_on_selected_month'];
                $daysOne = $val['day_1'];
                $daysTwo = $val['day_2'];
                $daysThree = $val['day_3'];
                $daysFour = $val['day_4'];
                $daysFive = $val['day_5'];
                $daysSix = $val['day_6'];
                $daysSeven = $val['day_7'];
                $daysEight = $val['day_8'];
                $daysNine = $val['day_9'];
                $daysten = $val['day_10'];
                $dayseleven = $val['day_11'];
                $daysTewele = $val['day_12'];
                $daysThirteen = $val['day_13'];
                $daysFourteen = $val['day_14'];
                $daysFifiteen = $val['day_15'];
                $daysSixteen = $val['day_16'];
                $daysSevenTeen = $val['day_17'];
                $daysEighteen = $val['day_18'];
                $daysNineteen = $val['day_19'];
                $daysTewenty = $val['day_20'];
                $daysTewentyOne = $val['day_21'];
                $daysTewentytwo = $val['day_22'];
                $daysTewentythree = $val['day_23'];
                $daysTewentyFour = $val['day_24'];
                $daysTewentyFive = $val['day_25'];
                $daysTewentySix = $val['day_26'];
                $daysTewentySeven = $val['day_27'];
                $daysTewentyeight = $val['day_28'];
                $daysTewentynine= $val['day_29'];
                $daysThirty = $val['day_30'];
                $daysThirtyOne = $val['day_31'];
                $entity->total_absence_days = $total_absence_days;
                $entity->daysOne = $daysOne;
                $entity->daysTwo = $daysTwo;
                $entity->daysThree = $daysThree;
                $entity->daysFour = $daysFour;
                $entity->daysFive = $daysFive;
                $entity->daysSix = $daysSix;
                $entity->daysSeven = $daysSeven;
                $entity->daysEight = $daysEight;
                $entity->daysNine = $daysNine;
                $entity->daysTen = $daysten;
                $entity->dayseleven = $dayseleven;
                $entity->daysTewele = $daysTewele;
                $entity->daysThirteen = $daysThirteen;
                $entity->daysFourteen = $daysFourteen;
                $entity->daysFifiteen = $daysFifiteen;
                $entity->daysSixteen = $daysSixteen;
                $entity->daysSevenTeen = $daysSevenTeen;
                $entity->daysEighteen = $daysEighteen;
                $entity->daysNineteen = $daysNineteen;
                $entity->daysTewenty = $daysTewenty;
                $entity->daysTewentyOne = $daysTewentyOne;
                $entity->daysTewentytwo = $daysTewentytwo;
                $entity->daysTewentythree = $daysTewentythree;
                $entity->daysTewentyFour = $daysTewentyFour;
                $entity->daysTewentyFive = $daysTewentyFive;
                $entity->daysTewentySix = $daysTewentySix;
                $entity->daysTewentySeven = $daysTewentySeven;
                $entity->daysTewentyeight = $daysTewentyeight;
                $entity->daysTewentynine = $daysTewentynine;
                $entity->daysThirty = $daysThirty;
                $entity->daysThirtyOne = $daysThirtyOne;
            }
    }
       return $entity->total_absence_days;
        
        
    }

    public function onExcelGetDayOne(Event $event, Entity $entity)
    {
        return $entity->daysOne ;
    }
     public function onExcelGetDayTwo(Event $event, Entity $entity)
    {
        return $entity->daysTwo ;
    }
     public function onExcelGetDayThree(Event $event, Entity $entity)
    {
        return $entity->daysThree ;
    }
     public function onExcelGetDayFour(Event $event, Entity $entity)
    {
        return $entity->daysFour ;
    }
     public function onExcelGetDayFive(Event $event, Entity $entity)
    {
        return $entity->daysFive ;
    }
     public function onExcelGetDaySix(Event $event, Entity $entity)
    {
        return $entity->daysSix ;
    }
     public function onExcelGetDaySeven(Event $event, Entity $entity)
    {
        return $entity->daysSeven ;
    }
     public function onExcelGetDayEight(Event $event, Entity $entity)
    {
        return $entity->daysEight ;
    }
     public function onExcelGetDayNine(Event $event, Entity $entity)
    {
        return $entity->daysNine ;
    }
     public function onExcelGetDayTen(Event $event, Entity $entity)
    {
        return $entity->daysten ;
    }
    public function onExcelGetDayeleven(Event $event, Entity $entity)
    {
        return $entity->dayseleven ;
    }
    public function onExcelGetDaytwelve(Event $event, Entity $entity)
    {
        return $entity->daysTewele ;
    }
     public function onExcelGetDaythirteen(Event $event, Entity $entity)
    {
        return $entity->daysThirteen ;
    }
     public function onExcelGetDayfourteen(Event $event, Entity $entity)
    {
        return $entity->daysFourteen ;
    }
     public function onExcelGetDayfifteen(Event $event, Entity $entity)
    {
        return $entity->daysFifiteen ;
    }
     public function onExcelGetDaysixteen(Event $event, Entity $entity)
    {
        return $entity->daysSixteen ;
    }
     public function onExcelGetDayseventeen(Event $event, Entity $entity)
    {
        return $entity->daysSevenTeen ;
    }
     public function onExcelGetDayeighteen(Event $event, Entity $entity)
    {
        return $entity->daysEighteen ;
    }
     public function onExcelGetDaynineTeen(Event $event, Entity $entity)
    {
        return $entity->daysNineteen ;
    }
     public function onExcelGetDaytwenty(Event $event, Entity $entity)
    {
        return $entity->daysTewenty ;
    }
     public function onExcelGetDaytwentyone(Event $event, Entity $entity)
    {
        return $entity->daysTewentyOne ;
    }
     public function onExcelGetDaytwentytwo(Event $event, Entity $entity)
    {
        return $entity->daysTewentytwo ;
    }
     public function onExcelGetDaytwentythree(Event $event, Entity $entity)
    {
        return $entity->daysTewentythree ;
    }
     public function onExcelGetDaytwentyfour(Event $event, Entity $entity)
    {
        return $entity->daysTewentyFour ;
    }
     public function onExcelGetDaytwentyfive(Event $event, Entity $entity)
    {
        return $entity->daysTewentyFive ;
    }
     public function onExcelGetDaytwentysix(Event $event, Entity $entity)
    {
        return $entity->daysTewentySix ;
    }
     public function onExcelGetDaytwentyseven(Event $event, Entity $entity)
    {
        return $entity->daysTewentySeven ;
    }
     public function onExcelGetDaytwentyeight(Event $event, Entity $entity)
    {
        return $entity->daysTewentyeight ;
    }
     public function onExcelGetDaytwentynine(Event $event, Entity $entity)
    {
        return $entity->daysTewentynine ;
    }
     public function onExcelGetDaythirty(Event $event, Entity $entity)
    {
        return $entity->daysThirty ;
    } 
    public function onExcelGetDaythirtyone(Event $event, Entity $entity)
    {
        return $entity->daysThirtyOne ;
    }


    /**
    * on excel get identity type 
    */ 
    public function onExcelGetUserIdentitiesDefault(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('user')) {
            if ($entity->user->has('identities')) {
                if (!empty($entity->user->identities)) {
                    $identities = $entity->user->identities;
                    foreach ($identities as $key => $value) {
                        if ($value->identity_type->default == 1) {
                            $return[] = $value->number;
                        }
                    }
                }
            }
        }
        return implode(', ', array_values($return));
    }

   
}
