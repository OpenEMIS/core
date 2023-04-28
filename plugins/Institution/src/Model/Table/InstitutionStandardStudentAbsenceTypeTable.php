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
 * @ticket POCOR-6632
 * type array
 */
class InstitutionStandardStudentAbsenceTypeTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absence_details');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' => 'absence_type_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'foreignKey' => 'education_grade_id']);
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
        $institutionId = $requestData->institution_id;
        $gradeId = $requestData->education_grade_id;
        $classId = $requestData->institution_class_id;
        $where = [];
        if ($gradeId != -1) {
               $where[$this->aliasField('education_grade_id')] = $gradeId;
        }
        if ($classId != 0) {
               $where[$this->aliasField('institution_class_id')] = $classId;
        }
        $where[$this->aliasField('academic_period_id')] = $academicPeriodId;
        $where[$this->aliasField('institution_id')] = $institutionId;
        $query
            ->select([
                'student_id' => $this->aliasField('student_id'),
                'institution_id' => $this->aliasField('institution_id'),
                'education_grade_id' => $this->aliasField('education_grade_id'),
                'institution_class_id' => $this->aliasField('institution_class_id'),
                'absence_type_id' => $this->aliasField('absence_type_id'),
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'student_absence_reason_id' => $this->aliasField('student_absence_reason_id'),
                'get_date'=>$this->aliasField('date'),
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
            ->Where($where)
            ->group([$this->aliasField('student_id'),'Institutions.id','EducationGrades.id','InstitutionClasses.id',
                'AcademicPeriods.id'
            ]);
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
            {
                return $results->map(function ($row)
                {
                    $studentAbsenceReasonData = TableRegistry::get('Institution.StudentAbsenceReasons');
                    $absence = TableRegistry::get('Institution.InstitutionStudentAbsenceDetails');
                    $row['referrer_full_name'] = $row['first_name'].' '.$row['middle_name'].' '.$row['third_name'].' '.$row['last_name'];
                    //change POCOR-7374 for excused count
                     $checkstudent = $this->find()->select(['absence_type_id'])->where([$this->aliasField('student_id') => $row['student_id'],$this->aliasField('absence_type_id') => 1])->first();
                     if(!empty($checkstudent)){
                        //POCOR-6754
                        if($checkstudent == 1)
                        {
                            $where[$this->aliasField('student_id')] = $row['student_id'];
                            $where[$this->aliasField('absence_type_id')] = 1;
                            $where[$this->aliasField('education_grade_id')] = $row['education_grade_id'];
                            $where[$this->aliasField('institution_class_id')] = $row['institution_class_id'];
                            $where[$this->aliasField('academic_period_id')] = $row['academic_period_id'];
                            $studentAbsenceReason = TableRegistry::get('student_absence_reasons');        
                            $customFieldData = $studentAbsenceReason->find()
                                ->select([
                                    'reason_id' => 'student_absence_reasons.id',
                                    'custom_field' => 'student_absence_reasons.name',
                                ])
                                ->toArray();
                                $val->reason_id = '';
                                $val->reason = '';
                            foreach($customFieldData as $val) 
                            {
                                $custom_field_id = $val->reason_id;
                                $custom_field = $val->custom_field;
                                $where[$this->aliasField('student_absence_reason_id')] = $custom_field_id;
                                $absenceType = $this->find()
                                ->select([
                                    'reason' => "COUNT(".$this->aliasField('student_absence_reason_id').")",
                                    'reason_id' => $this->aliasField('student_absence_reason_id'),
                                    'absence_type' => "COUNT(".$this->aliasField('absence_type_id').")",
                                ])
                                ->contain([
                                 'AcademicPeriods' => [
                                        'fields' => ['AcademicPeriods.id']
                                    ],
                                    'Institutions' => [
                                        'fields' => ['Institutions.id']
                                    ],
                                    'InstitutionClasses' => [
                                        'fields' => ['InstitutionClasses.id']
                                    ],
                                    'EducationGrades' => [
                                        'fields' => ['EducationGrades.id']
                                    ],
                                ])
                                ->leftJoin([$studentAbsenceReasonData->alias() => $studentAbsenceReasonData->table()],
                                        [$studentAbsenceReasonData->aliasField('id = ') . $this->aliasField('student_absence_reason_id')])
                                ->Where($where)
                                ->group([$this->aliasField('student_id'),'Institutions.id','EducationGrades.id','InstitutionClasses.id','AcademicPeriods.id'])
                                ->toArray();
                                if(!empty($absenceType)){
                                    foreach($absenceType as $val) {
                                        if($val->reason_id!=null){
                                            $row[$val->reason_id] = $val->reason;  
                                        }
                                        
                                    }
                                }
                            }
                                
                        } 
                    }  
                        
                    return $row;
                });
            });

    }
    

    public function onExcelGetAbsenceTypeLate(Event $event, Entity $entity)
    {
        $type =$entity->absence_type_id;
        $absencetype = TableRegistry::get('Institution.AbsenceTypes');
        $absence = TableRegistry::get('Institution.InstitutionStudentAbsenceDetails');
        $findabsent = $absence->find()
                      ->leftJoin(['AbsenceTypes' => 'absence_types'], 
                        ['AbsenceTypes.id = '. $absence->aliasField('absence_type_id')])
            ->select([
                'late_count' => "COUNT(".$absence->aliasField('absence_type_id').")"

                ])
            ->where([$absencetype->aliasField('code')=>'LATE',
                    $absence->aliasField('student_id')=>$entity->student_id,
                    $absence->aliasField('academic_period_id')=>$entity->academic_period_id]);
        $get_absent_type_data = $findabsent->toArray();
        $entity->get_absent_late  ='';
        foreach($get_absent_type_data as $val){
            $entity->get_absent_late = $val['late_count'];
        }
        return $entity->get_absent_late;

    }

    public function onExcelGetAbsenceTypeUnexcused(Event $event, Entity $entity)
    { 
        $type = $entity->absence_type_id;
        $absencetype = TableRegistry::get('Institution.AbsenceTypes');
        $absence = TableRegistry::get('Institution.InstitutionStudentAbsenceDetails');
        $findabsent = $absence->find()
                      ->leftJoin(['AbsenceTypes' => 'absence_types'], 
                        ['AbsenceTypes.id = '. $absence->aliasField('absence_type_id')])
                ->select([
                    'unexcused_count' => "COUNT(".$absence->aliasField('absence_type_id').")"

                    ])
            ->where([$absencetype->aliasField('code')=>'UNEXCUSED', 
                $absence->aliasField('student_id')=>$entity->student_id,
                $absence->aliasField('academic_period_id')=>$entity->academic_period_id]);
        $get_absent_type_data = $findabsent->toArray();
        $entity->get_absent_unexcused = '';
        foreach($get_absent_type_data as $val){
            $entity->get_absent_unexcused = $val['unexcused_count'];
        }
        return $entity->get_absent_unexcused;
    }

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
            'label' => __('Institution Code'),
        ];
        $newFields[] = [
            'key'   => 'institution_name',
            'field' => 'institution_name',
            'type'  => 'string',
            'label' => __('Institution Name'),
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
            'key' => 'Users.identity_number',
            'field' => 'user_identities_default',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $newFields[] = [
            'key'   => 'referrer_full_name',
            'field' => 'referrer_full_name',
            'type'  => 'string',
            'label' => __('Student Full Name'),
        ];
        
        $newFields[] = [
            'key'   => 'absence_type_late',
            'field' => 'absence_type_late',
            'type'  => 'string',
            'label' => __('Late'),
        ];
        $newFields[] = [
            'key'   => 'absence_type_unexcused',
            'field' => 'absence_type_unexcused',
            'type'  => 'string',
            'label' => __('Unexcused'),
        ];
        $studentAbsenceReason = TableRegistry::get('student_absence_reasons');        
        $customFieldData = $studentAbsenceReason->find()
            ->select([
                'reason_id' => 'student_absence_reasons.id',
                'custom_field' => 'student_absence_reasons.name',
            ])
            ->toArray();
        
        foreach($customFieldData as $val) {
            $custom_field_id = $val->reason_id;
            $custom_field = $val->custom_field;
            $newFields[] = [
                'key' => '',
                'field' => $custom_field_id,
                'type' => 'string',
                'label' => __($custom_field)
            ];
        }

        $fields->exchangeArray($newFields);
    } 

}
