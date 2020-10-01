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
            if($entity->bmi <= 18.5){
                $bodyMassIndex = "Underweight";
            }elseif($entity->bmi > 18.5 && $entity->bmi <= 24.9){
                $bodyMassIndex = "Normal";
            }elseif($entity->bmi > 25 && $entity->bmi <= 29.9){
                $bodyMassIndex = "Overweight";
            }elseif($entity->bmi > 29.9){
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

        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($institutionId)) {
            $conditions['Institutions.id'] = $institutionId;
        }

        $institutions = TableRegistry::get('Institution.Institutions');
        $institutionIds = $institutions->find('list', [
                                                    'keyField' => 'id',
                                                    'valueField' => 'id'
                                                ])
                        ->where(['institution_type_id' => $institutionTypeId])
                        ->toArray();

        if (!empty($institutionTypeId)) {
            $conditions['BodyMasses.institution_id IN'] = $institutionIds;
        }
        

        
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;
        
        $Class = TableRegistry::get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $areas = TableRegistry::get('Area.Areas');
        $institutionsTable = TableRegistry::get('institutions');
        //echo '<pre>'; print_r($area); die;
        $query
            ->select([
                $this->aliasField('student_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id'),
                'institution_code' => 'Institutions.code',
                'bm_date' => 'UserBodyMasses.date',
                'bm_height' => 'UserBodyMasses.height',
                'bm_weight' => 'UserBodyMasses.weight',
                'bm_body_mass_index' => 'UserBodyMasses.body_mass_index',
                'bmi' => 'UserBodyMasses.body_mass_index',
                'bm_comment' => 'UserBodyMasses.comment',
                'class_name' => 'InstitutionClasses.name',
                'area_code' => 'Areas.code'
            ])
            ->contain([
                'Users' => [
                    'fields' => [
                        'openemis_no' => 'Users.openemis_no',
                        'Users.first_name',
                        'Users.middle_name',
                        'Users.third_name',
                        'Users.last_name',
                        'date_of_birth' => 'Users.date_of_birth',
                        'identity_number' => 'Users.identity_number',
                        'identity_type' => 'Users.identity_type_id'
                    ]
                ],
                'EducationGrades' => [
                    'fields' => [
                        'name'
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
                ]
            ])
             ->innerJoin(
                ['UserBodyMasses' => 'user_body_masses'],
                [
                    'UserBodyMasses.security_user_id = ' . $this->aliasField('student_id'),
                    'UserBodyMasses.academic_period_id = ' . $this->aliasField('academic_period_id')
                ]
            )
            ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
            ])
            ->leftJoin([$Class->alias() => $Class->table()], [
                $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
            ])
            ->leftJoin([$areas->alias() => $areas->table()], [
                 $areas->aliasField('id')=>$areas->aliasField('area_id')
            ])

             ->where($conditions);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
      //  echo '<pre>'; print_r($fields); 
        $cloneFields = $fields->getArrayCopy();
      //  echo '<pre>'; print_r($cloneFields); die;
        $extraFields = [];

        $extraFieldsFirst[] = [
            'key' => 'area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];


        $extraFieldsFirst[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
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
            'key' => 'Users.gender',
            'field' => 'gender',
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

        $newFields = array_merge($extraFieldsFirst, $cloneFields, $extraFields);
        $fields->exchangeArray($newFields);
    }
}
