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
        
        $date =  '"'.$year.'-'.$month.'%"';
        $dateSecond =  '"'.$yearSecond.'-'.$month.'%"';  //POCOR-6854
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
            ->andWhere([$absentDays->aliasField('start_date LIKE '.$date)])
            ->orWhere([$absentDays->aliasField('start_date LIKE '.$dateSecond)])  //POCOR-6854
            ->Where($where)
            ->group([$this->aliasField('student_id'),
                $absentDays->aliasField('student_id')]);
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) 
                use($date,$dateSecond)
            {
                return $results->map(function ($row) use($date,$dateSecond)
                { 
                    $row['referrer_full_name'] = $row['first_name'] .' '.$row['middle_name'].' '.$row['third_name'].' '. $row['last_name'];
                    $row['Absent_Date'] = $date;
                    $row['Absent_Date_Second'] = $dateSecond;  //POCOR-6854
                    $alldate = $row['absent_start'];
                    $split = explode(',', $alldate);
                    $i_max = 31;
                    for( $i=1; $i<=$i_max; $i++ )
                        { 
                            
                            $row['Day'.$i] = '';
                            
                        }
                    $index = 0;
                    foreach($split as $key=>$comma)
                    {
                        $splits = explode('/', $comma);
                        $startDate =  $splits[0];
                        $endDate =  $splits[1];
                        $datearray = $this->getBetweenDates($startDate, $endDate);
                        foreach($datearray as $key=>$val) 
                        {
                            $daytrim = date('d', strtotime($val));
                            $day  = ltrim($daytrim, '0');
                            $i_max=31;
                            for( $i=1; $i<=$i_max; $i++ )
                                { 
                                    if ($i == $day)
                                    {
                                        $row['Day'.$i] = 1;
                                    }
                                }
                        }
                        $index++;
                    }       
                    return $row;
                });
            });
            

    }

    function getBetweenDates($startDate, $endDate)
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
    }

    /**
     * Generate the all Header for sheet
     */
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $i_max = 31;
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
        for( $i=1; $i<=$i_max; $i++ )
        { 
            $newFields[]=[
            'key'   => '',
            'field' => 'Day'.$i,
            'type'  => 'integer',
            'label' => __('Day'.$i),
            ];
        }
        

        $fields->exchangeArray($newFields);
    }

    /**
    * Get staff absences days
    */
    public function onExcelGetTotalAbsenceDay(Event $event, Entity $entity)
    {
        $userid =  $entity->student_id;
        $institutionId =  $entity->institution_id;
        $Absent_Date =  $entity->Absent_Date;
        $Absent_Date_Second =  $entity->Absent_Date_Second; //POCOR-6854
        $Institutionstudent = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $studentleave = TableRegistry::get('Institution.InstitutionStudentAbsenceDays');
        $absenceDays = $studentleave->find()
            ->select([
                'days' => "SUM(".$studentleave->aliasField('absent_days').")"
            ])
            ->andWhere([$studentleave->aliasField('start_date LIKE '.$Absent_Date)])
            ->orWhere([$studentleave->aliasField('start_date LIKE '.$Absent_Date_Second)])  //POCOR-6854
            ->where([$studentleave->aliasField('student_id') => $userid]);
            if($absenceDays!=null){
                $data = $absenceDays->toArray();
                $entity->total_absence_days = '';
                foreach($data as $key=>$val){
                    $entity->total_absence_days = $val['days'];
                }
                 return $entity->total_absence_days;
            }
            return '';
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
