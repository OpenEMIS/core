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
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
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
                   ->select(['name'=>$academic_period->aliasField('start_year')])
                   ->where(['id'=>$academicPeriodId])
                   ->limit(1);
        foreach($getyear->toArray() as $val) {
            $year  = $val['name'];
        }
        $institutionId = $requestData->institution_id;
        $gradeId = $requestData->education_grade_id;
        $classId = $requestData->institution_class_id;
        $month = $requestData->month;
        $absentDays = TableRegistry::get('Institution. InstitutionStudentAbsenceDays');
        if ($gradeId != -1) {
            $query->where([
                $this->aliasField('education_grade_id') => $gradeId
            ]);
        }
        $date =  '"'.$year.'-'.$month.'%"';
        $query
            ->select([
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('institution_class_id'),
                $this->aliasField('date'),
                'openemis_no' => 'Users.openemis_no',
                'first_name' => 'Users.first_name',
                'middle_name' => 'Users.middle_name',
                'third_name' => 'Users.third_name',
                'last_name' => 'Users.last_name',
                'identity_number' => 'Users.identity_number',
                ])
            ->contain([
                'Users' => [
                   'fields' => [
                      'openemis_no' => 'Users.openemis_no',
                        'first_name' => 'Users.first_name',
                        'middle_name' => 'Users.middle_name',
                        'third_name' => 'Users.third_name',
                        'last_name' => 'Users.last_name',
                        'identity_number' => 'Users.identity_number',
                   ]
             ],
            ])
            ->leftJoin([$absentDays->alias() => $absentDays->table()],
                [$absentDays->aliasField('id = ') . $this->aliasField('institution_student_absence_day_id')]
            )
            
            //->andWhere([$this->aliasField('date LIKE ".$date."')]);
           ->where([$this->aliasField('date') . ' LIKE ' =>  ".$date."])
           ->andWhere([$this->aliasField('academic_period_id') => $academicPeriodId,
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('institution_class_id') => $classId,
                    $this->aliasField('education_grade_id') => $gradeId,
                ]);
            //print_r($query->Sql());die;
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
            {
            return $results->map(function ($row)
            {
                    $row['referrer_full_name'] = $row['first_name'] .' '. $row['last_name'];
                    return $row;
            });
        });

    }

    /**
     * Generate the all Header for sheet
     */
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
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
            'label' => __('Student full name'),
        ];
        $newFields[] = [
            'key'   => 'identity_number',
            'field' => 'identity_number',
            'type'  => 'string',
            'label' => __('identity number'),
        ];
        /*$newFields[] = [
            'key'   => 'referrer_full_name',
            'field' => 'referrer_full_name',
            'type'  => 'string',
            'label' => __('Day'),
        ];*/
        $newFields[] = [
            'key'   => 'total_absence_day',
            'field' => 'total_absence_day',
            'type'  => 'integer',
            'label' => __('Total absences'),
        ];

        $fields->exchangeArray($newFields);
    }

    /**
    * Get staff absences days
    */
    public function onExcelGetTotalAbsenceDay(Event $event, Entity $entity)
    {
        $userid =  $entity->student_id;
        $institutionId =  $entity->institution_id;
        $Institutionstudent = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $studentleave = TableRegistry::get('Institution.InstitutionStudentAbsenceDays');
        $absenceDays = $Institutionstudent->find()
            ->leftJoin(['InstitutionStudentAbsenceDays' => 'institution_student_absence_days'], ['InstitutionStudentAbsences.student_id = '. $studentleave->aliasField('student_id')])
            ->select([
                'days' => "SUM(".$studentleave->aliasField('absent_days').")"

            ])
           // ->group(['InstitutionStaffLeave.staff_id'])
            ->where([$studentleave->aliasField('student_id') => $userid,
                    $studentleave->aliasField('institution_id') => $institutionId
                ]);
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
    * Get staff absences days
    */
    public function onExcelGetDays(Event $event, Entity $entity)
    {
        $userid =  $entity->student_id;
        $Institutionstudent = TableRegistry::get('Institution.InstitutionStudentAbsences');
        $studentleave = TableRegistry::get('Institution.InstitutionStudentAbsenceDays');
        $absenceDays = $Institutionstudent->find()
            ->leftJoin(['InstitutionStudentAbsenceDays' => 'institution_student_absence_days'], ['InstitutionStudentAbsences.student_id = '. $studentleave->aliasField('student_id')])
            ->select([
                'days' => "SUM(".$studentleave->aliasField('absent_days').")"

            ])
           // ->group(['InstitutionStaffLeave.staff_id'])
            ->where([$studentleave->aliasField('student_id') => $userid,
                    $studentleave->aliasField('institution_id') => $institutionId
                ]);
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
    public function onExcelGetInstitutionName(Event $event, Entity $entity)
    {

        return $entity->institution_id;
    }
    public function onExcelGetInstitutionCode(Event $event, Entity $entity)
    {

        return $entity->institution_id;
    }
    public function onExcelGetEducationGrade(Event $event, Entity $entity)
    {

        return $entity->education_grade_id;
    }
    public function onExcelGetInstitutionClass(Event $event, Entity $entity)
    {

        return $entity->institution_class_id;
    }
   
}
