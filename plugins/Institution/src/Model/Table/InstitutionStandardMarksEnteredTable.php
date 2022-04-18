<?php

namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Session;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Datasource\ConnectionManager;

/**
 * Marks Entered by Staff
 * POCOR-6630
 */
class InstitutionStandardMarksEnteredTable extends AppTable
{

    public function initialize(array $config)
    {
         $this->table('assessment_item_results');
        parent::initialize($config);

        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AssessmentGradingOptions', ['className' => 'Assessment.AssessmentGradingOptions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses','foreignKey' => 'institution_classes_id']);
        $this->addBehavior('Report.ReportList');
        // Behaviours
        $this->addBehavior('Excel', [
            'excludes' => [],
            'pages' => false,
            'autoFields' => false
        ]);
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

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
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

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature                = $this->request->data[$this->alias()]['feature'];
            $AcademicPeriodTable    = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodOptions  = $AcademicPeriodTable->getYearList();
            $currentPeriod          = $AcademicPeriodTable->getCurrent();
            $attr['options']        = $academicPeriodOptions;
            $attr['type']           = 'select';
            $attr['select']         = false;
            $attr['onChangeReload'] = true;
            if (empty($request->data[$this->alias()]['academic_period_id'])) {
                $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
            }
            return $attr;
        }
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
        $requestData           = json_decode($settings['process']['params']);
        $academicPeriodId      = $requestData->academic_period_id;
        $institutionId         = $requestData->institution_id;
        $assessmentId         = $requestData->assessment_id;
        $assessmentPeriodId   = $requestData->assessment_period_id;
        $where = [];
        if ($assessmentId != 0) {
               $where[$this->aliasField('assessment_id')] = $assessmentId;
        }
        $where[$this->aliasField('assessment_period_id')] = $assessmentPeriodId;
        $where[$this->aliasField('academic_period_id')] = $academicPeriodId;
        $where[$this->aliasField('institution_id')] = $institutionId;
            $query
            ->select([
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('institution_classes_id'),
                $this->aliasField('assessment_id'),
                $this->aliasField('assessment_period_id'),
                $this->aliasField('academic_period_id'),
                'marks_entered'=> $this->aliasField('marks'),
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
                        'number' => 'Users.identity_number',
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
                        'academic_period_name'=>'AcademicPeriods.name'
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
                'Assessments' => [
                    'fields' => [
                       'assessments_name'=> 'Assessments.name',
                    ]
                ],
                'AssessmentPeriods' => [
                    'fields' => [
                       'assessment_periods_name'=> 'AssessmentPeriods.name',
                    ]
                ],
            ])
            
            /*->leftJoin(
                [$institution->alias() => $institution->table()],
                [$institution->aliasField('id = ') . 'InstitutionStaff.institution_id']
            )*/
            
            ->group([$this->aliasField('student_id')])
        ->Where($where);
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
            {
                return $results->map(function ($row)
                {
                    $row['referrer_full_name'] = $row['first_name'] .' '.$row['middle_name'].' '.$row['third_name'].' '. $row['last_name'];
                    return $row;
                });
            });
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key'   => 'academic_period_name',
            'field' => 'academic_period_name',
            'type'  => 'string',
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
            'type'  => 'integer',
            'label' => __('OpenEMIS ID'),
        ];
        $newFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'user_identities_default',
            'type' => 'string',
            'label' => __($identity->name)
        ];
        $newFields[] = [
            'key'   => 'referrer_full_name',
            'field' => 'referrer_full_name',
            'type'  => 'string',
            'label' => __('Student Full Name'),
        ];
        
        $newFields[] = [
            'key'   => 'assessment_periods_name',
            'field' => 'assessment_periods_name',
            'type'  => 'integer',
            'label' => __('Assessment Period'),
        ];
        $newFields[] = [
            'key'   => 'assessment_name',
            'field' => 'assessment_name',
            'type'  => 'string',
            'label' => __('Assessment Term'),
        ];
        
        $newFields[] = [
            'key'   => 'marks_entered',
            'field' => 'marks_entered',
            'type'  => 'integer',
            'label' => __('The total number of marks entered'),
        ];
        $newFields[] = [
            'key'   => 'entry_percentage',
            'field' => 'entry_percentage',
            'type'  => 'string',
            'label' => __('School marks entry percentage'),
        ];
        $newFields[] = [
            'key'   => 'marks_not_entered',
            'field' => 'marks_not_entered',
            'type'  => 'integer',
            'label' => __('The total number of marks not entered'),
        ];

        $fields->exchangeArray($newFields);
    }

    /**
     * Get student identity type
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

    /**
     * get total marks entered
    */
    public function onExcelGetEntryPercentage(Event $event, Entity $entity)
    {
        $assessmentType = TableRegistry::get('Assessment.AssessmentItemResults');
        $total = $assessmentType->find()
                        ->select([
                            'marks' => "SUM(".$assessmentType->aliasField('marks').")",
                            'total_students' => "COUNT(".$assessmentType->aliasField('student_id').")"
                        ])
                        ->where([$assessmentType->aliasField('assessment_id')=>$entity->assessment_id,
                                $assessmentType->aliasField('academic_period_id')=>$entity->academic_period_id,
                            $assessmentType->aliasField('institution_id')=>$entity->institution_id,
                            $assessmentType->aliasField('assessment_period_id')=>$entity->assessment_period_id
                        ])
                        ->group([$assessmentType->aliasField('student_id')]);
        $entity->marks_entered_per = '';
        $marks = 0;
        $student = 0;
        if(!empty($total)){
            $totalMarks = $total->toArray();
            foreach($totalMarks as $value){
                $marks = $value['marks'];
                $student = $value['total_students'];
                return $entity->marks_entered_per = ($student/$marks) * 100;
                
            }
        }
        
    }

    /**
    *  total marks not entered
    */
    public function onExcelGetMarksNotEntered(Event $event, Entity $entity)
    {
        $assessmentType = TableRegistry::get('Assessment.AssessmentItemResults');
        $total = $assessmentType->find()
                ->select([
                    'subject' => $assessmentType->aliasField('education_subject_id'),
                   // 'total_students' => "SUM(".$assessmentType->aliasField('student_id').")"
                ])
                ->where([$assessmentType->aliasField('assessment_id')=>$entity->assessment_id,
                        $assessmentType->aliasField('academic_period_id')=>$entity->academic_period_id,
                    $assessmentType->aliasField('institution_id')=>$entity->institution_id,
                    $assessmentType->aliasField('assessment_period_id')=>$entity->assessment_period_id
                ])
                ->group([$assessmentType->aliasField('education_subject_id')]);
        $entity->marks_not_entered ='';
        if(!empty($total)){
            $totalMarks = $total->toArray();
            
            foreach($totalMarks as $value){
                $total_student_subject =0;
                $marks = 0;
                $subject = $value['subject'];
                $TotalStudents = $value['total_students'];
                $student = $assessmentType->find()
                        ->select([
                            'total_student_per_subject' => "COUNT(".$assessmentType->aliasField('student_id').")",
                            'marks' => "SUM(".$assessmentType->aliasField('marks').")"
                            ])
                        ->where([$assessmentType->aliasField('education_subject_id')=>$subject,
                        $assessmentType->aliasField('assessment_id')=>$entity->assessment_id,
                        $assessmentType->aliasField('academic_period_id')=>$entity->academic_period_id,
                        $assessmentType->aliasField('institution_id')=>$entity->institution_id,
                        $assessmentType->aliasField('assessment_period_id')=>$entity->assessment_period_id])
                        ->group([$assessmentType->aliasField('education_subject_id')]);
                if(!empty($student)){
                    $studentData = $student->toArray();
                    foreach($studentData as $value){
                        $total_student_subject = $value['total_student_per_subject'];
                        $marks = $value['marks'];
                    }
                }
              $entity->marks_not_entered = $marks - $total_student_subject;
               return $entity->marks_not_entered; 
            }
            
        }
    }
    
}
