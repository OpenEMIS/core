<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

use App\Model\Table\AppTable;

class BodyMassesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);

        // Associations
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);

        // Behaviors
        $this->addBehavior('Excel', [
            'excludes' => [
                'student_status_id', 'academic_period_id', 'start_date', 'start_year', 'end_date', 'end_year', 'previous_institution_student_id'
            ],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(Event $event) 
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) 
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onExcelGetAge(Event $event, Entity $entity)
    {
        // Calculate the age
        $age = '';
        if (!empty($entity->academic_period->start_year) && !empty($entity->date_of_birth)) {
            $startYear = $entity->academic_period->start_year;
            $dob = $entity->date_of_birth->format('Y');
            $age = $startYear - $dob;
        }

        return $age;
    }
    
    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        $identityTypeName = '';
        if (!empty($entity->identity_type)) {
            $identityType = TableRegistry::get('FieldOption.IdentityTypes')->find()->where(['id'=>$entity->identity_type])->first();
            $identityTypeName = $identityType->name;
        }
        return $identityTypeName;
    }

    public function onExcelGetBmi(Event $event, Entity $entity)
    {
        
        $bodyMassIndex = '';
        
        if (!empty($entity->bmi) ) {
            if($entity->bmi <= 18.59){
                $bodyMassIndex = "Underweight";
            }elseif($entity->bmi > 18.59 && $entity->bmi <= 24.99){
                $bodyMassIndex = "Normal";
            }elseif($entity->bmi == 25.00){ //POCOR-6918
                $bodyMassIndex = "Normal";
            }elseif($entity->bmi > 25.00 && $entity->bmi <= 29.99){
                $bodyMassIndex = "Overweight";
            }elseif($entity->bmi > 29.99){
                $bodyMassIndex = "Obesity";
            }            
        }

        return $bodyMassIndex;
    } 
        
    public function onExcelGetGender(Event $event, Entity $entity)
    {
        $gender = '';
        if (!empty($entity->user->gender->name) ) {
            $gender = $entity->user->gender->name;
        }

        return $gender;
    }    

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $institutionTypeId = $requestData->institution_type_id;
        $areaId = $requestData->area_education_id;
        $selectedArea = $requestData->area_education_id;

        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if ($institutionId != 0) {
            $conditions['Institutions.id'] = $institutionId;
        }
        if ($areaId != -1 && $areaId != '') {
            //POCOR-6944 starts
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }//POCOR-6944 code ends
                $conditions['Institutions.area_id IN'] = $allselectedAreas;
        }
        
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;  
        $Class = TableRegistry::get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $areas = TableRegistry::get('Area.Areas');
        $institutionsTable = TableRegistry::get('institutions');
        
        $query
            ->select([
                $this->aliasField('student_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id'),
                'education_grade' => 'EducationGrades.name',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'bm_date' => 'UserBodyMasses.date',
                'bm_height' => 'UserBodyMasses.height',
                'bm_weight' => 'UserBodyMasses.weight',
                'bm_body_mass_index' => 'UserBodyMasses.body_mass_index',
                'bmi' => 'UserBodyMasses.body_mass_index',
                'bm_comment' => 'UserBodyMasses.comment',
                'class_name' => 'InstitutionClasses.name',
                'area_id' => 'Areas.id',
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name'
            ])
            ->contain([
                'Users' => [
                    'fields' => [
                        'openemis_no' => 'Users.openemis_no',
                        'student_first_name' => 'Users.first_name',
                        'student_middle_name' => 'Users.middle_name',
                        'student_third_name' => 'Users.third_name',
                        'student_last_name' => 'Users.last_name',
                        'student_preferred_name' => 'Users.preferred_name',
                        'date_of_birth' => 'Users.date_of_birth',
                        'identity_number' => 'Users.identity_number',
                        'identity_type' => 'Users.identity_type_id',
                        'gender_name' => 'Genders.name'
                    ]
                ],
                'EducationGrades' => [
                    'fields' => [
                            'education_grade' => 'EducationGrades.name'
                    ]
                ],
                'Users.Genders' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'name','code','area_id'
                    ]
                ],
                'AcademicPeriods' => [
                    'fields' => [
                        'name',
                        'start_year'
                    ]
                ],
                'Institutions.Areas'
            ])
             ->leftJoin(
                ['UserBodyMasses' => 'user_body_masses'],
                [
                    'UserBodyMasses.security_user_id = ' . $this->aliasField('student_id'),
                    'UserBodyMasses.academic_period_id = ' . $this->aliasField('academic_period_id')
                ]
            )
             
            ->innerJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
            ])
            ->leftJoin([$Class->alias() => $Class->table()], [
                $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
            ])

            ->where($conditions);
            //POCOR-6719 Starts
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use($type) {
            return $results->map(function ($row) use($type) {
                $areas1 = TableRegistry::get('areas');
                $areasData = $areas1
                            ->find()
                            ->where([$areas1->alias('code')=>$row->area_code])
                            ->first();
                $row['region_code'] = '';            
                $row['region_name'] = '';
                if(!empty($areasData)){
                    $areas = TableRegistry::get('areas');
                    $areaLevels = TableRegistry::get('area_levels');
                    $institutions = TableRegistry::get('institutions');
                    $val = $areas
                                ->find()
                                ->select([
                                    $areas1->aliasField('code'),
                                    $areas1->aliasField('name'),
                                    ])
                                ->leftJoin(
                                    [$areaLevels->alias() => $areaLevels->table()],
                                    [
                                        $areas->aliasField('area_level_id  = ') . $areaLevels->aliasField('id')
                                    ]
                                )
                                ->leftJoin(
                                    [$institutions->alias() => $institutions->table()],
                                    [
                                        $areas->aliasField('id  = ') . $institutions->aliasField('area_id')
                                    ]
                                )    
                                ->where([
                                    $areaLevels->aliasField('level !=') => 1,
                                    $areas->aliasField('id') => $areasData->parent_id
                                ])->first();
                    
                    if (!empty($val->name) && !empty($val->code)) {
                        $row['region_code'] = $val->code;
                        $row['region_name'] = $val->name;
                    }
                } 
                return $row;
            });
        });//POCOR-6719 Ends
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();
        $extraFields = [];

        $extraFieldsFirst[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ]; 

        $extraFieldsFirst[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];  

        //POCOR-6650 Starts
        $AreaLevelTbl = TableRegistry::get('area_levels');
        $AreaLevelArr = $AreaLevelTbl->find()->select(['id','name'])->order(['id'=>'DESC'])->limit(2)->hydrate(false)->toArray();
        //POCOR-6719 Starts
        $extraFieldsFirst[] = [
            'key' => '',
            'field' => 'region_name',
            'type' => 'string',
            'label' => __($AreaLevelArr[1]['name'])
        ];//POCOR-6719 Ends

        $extraFieldsFirst[] = [
            'key' => 'area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __($AreaLevelArr[0]['name'])
        ];//POCOR-6650 Ends
        
        $extraFieldsFirst[] = [
            'key' => 'area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Education Code')
        ];

        $extraFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraFields[] = [
            'key' => 'Users.student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student')
        ];

        $extraFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $extraFields[] = [
            'key' => 'Users.identity_type_id',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];  
        
        $extraFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $extraFields[] = [
            'key' => 'Users.age',
            'field' => 'age',
            'type' => 'string',
            'label' => __('Age')
        ];
        
        $extraFields[] = [
                'key' => 'InstitutionClasses.name',
                'field' => 'class_name',
                'type' => 'string',
                'label' => ''
            ];

        $extraFields[] = [
            'key' => 'Genders.gender',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => __('Gender')
        ];

        $extraFields[] = [
            'key' => 'date',
            'field' => 'bm_date',
            'type' => 'string',
            'label' => __('Date')
        ];

        $extraFields[] = [
            'key' => 'height',
            'field' => 'bm_height',
            'type' => 'string',
            'label' => __('Height')
        ];

        $extraFields[] = [
            'key' => 'weight',
            'field' => 'bm_weight',
            'type' => 'string',
            'label' => __('Weight')
        ];

        $extraFields[] = [
            'key' => 'body_mass_ready',
            'field' => 'bm_body_mass_index',
            'type' => 'string',
            'label' => __('Body Mass Index')
        ];
        
        $extraFields[] = [
            'key' => 'body_mass_index',
            'field' => 'bmi',
            'type' => 'string',
            'label' => __('BMI Category')
        ];

        $extraFields[] = [
            'key' => 'Comment',
            'field' => 'bm_comment',
            'type' => 'string',
            'label' => __('Comment')
        ];            

        $newFields = array_merge($extraFieldsFirst, $extraFields);
        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStudentName(Event $event, Entity $entity)
    {
        //cant use $this->Users->get() since it will load big data and cause memory allocation problem
        $studentName = [];
        ($entity->student_first_name) ? $studentName[] = $entity->student_first_name : '';
        ($entity->student_middle_name) ? $studentName[] = $entity->student_middle_name : '';
        ($entity->student_third_name) ? $studentName[] = $entity->student_third_name : '';
        ($entity->student_last_name) ? $studentName[] = $entity->student_last_name : '';

        return implode(' ', $studentName);
    }

    //POCOR-6944
    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ]) 
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }
}
