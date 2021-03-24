<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

use App\Model\Table\AppTable;

class StaffHealthReportsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_staff');
        parent::initialize($config);

        // Associations
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        //$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        //$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        //$this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);

        // Behaviors
        $this->addBehavior('Excel', [
            'excludes' => [
                'student_status_id','start_date', 'start_year', 'end_date', 'end_year'
            ],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('AcademicPeriod.Period');
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

    public function onExcelGetHealthInsurance(Event $event, Entity $entity)
    {
        $healthInsurance = ($entity->health_insurance == 1)?'Yes':'No';
        return $healthInsurance;
    }
    
    public function onExcelGetSevere(Event $event, Entity $entity)
    {
        $severe = ($entity->severe == 1)?'Yes':'No';
        return $severe;
    }
    
    public function onExcelGetCurrent(Event $event, Entity $entity)
    {
        $current = ($entity->current == 1)?'Yes':'No';
        return $current;
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
        $healthReportType = $requestData->health_report_type;
        
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;
        
        $Class = TableRegistry::get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');

        $conditions = [];
        if (!empty($academicPeriodId)) {
            //$conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        
        if (!empty($institutionId) && $institutionId =='-1') {            
            $conditions[$ClassStudents->aliasField('student_status_id != ')] = '1';
        }
        
        if (!empty($institutionId) && $institutionId !='-1') {
            $conditions['Institutions.id'] = $institutionId;
        }
        
        if($healthReportType == 'Overview'){
            $query
                ->select([
                    $this->aliasField('staff_id'),
                   // $this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                   // $this->aliasField('academic_period_id'),                
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
                    'student_name' => $query->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),
                    'first_name' =>'Users.first_name',
                    'middle_name' => 'Users.middle_name',
                    'last_name' => 'Users.last_name',
                    'blood_type' => 'UserHealths.blood_type',
                    'doctor_name' => 'UserHealths.doctor_name',
                    'doctor_contact' => 'UserHealths.doctor_contact',
                    'medical_facility' => 'UserHealths.medical_facility',
                    'health_insurance' => 'UserHealths.health_insurance'
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
                    // 'EducationGrades' => [
                    //     'fields' => [
                    //         'name'
                    //     ]
                    // ],
                    'Users.Genders' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'Institutions' => [
                        'fields' => [
                            'name',
                            'code'
                        ]
                    ],
                    // 'AcademicPeriods' => [
                    //     'fields' => [
                    //         'name',
                    //         'start_year'
                    //     ]
                    // ]
                ])
                 ->innerJoin(
                    ['UserHealths' => 'user_healths'],
                    [
                        'UserHealths.security_user_id = ' . $this->aliasField('staff_id')
                    ]
                )
                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('staff_id'),
                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,                  
                ])
                ->leftJoin([$Class->alias() => $Class->table()], [
                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                 ->where($conditions);
            
        }elseif($healthReportType == 'Allergies'){
            $query
                ->select([
                    $this->aliasField('staff_id'),
                    ///$this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                   // $this->aliasField('academic_period_id'),                
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
                    // 'student_name' => $query->func()->concat([
                    //     'Users.first_name' => 'literal',
                    //     " ",
                    //     'Users.last_name' => 'literal'
                    // ]),
                    'first_name' =>'Users.first_name',
                    'middle_name' => 'Users.middle_name',
                    'last_name' => 'Users.last_name',
                    'description' => 'UserHealthAllergies.description',
                    'severe' => 'UserHealthAllergies.severe',
                    'comment' => 'UserHealthAllergies.comment',
                    'health_allergy_type_id' => 'UserHealthAllergies.health_allergy_type_id',
                    'health_allergy_type_name' => 'HealthAllergyTypes.name'
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
                    // 'EducationGrades' => [
                    //     'fields' => [
                    //         'name'
                    //     ]
                    // ],
                    'Users.Genders' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'Institutions' => [
                        'fields' => [
                            'name',
                            'code'
                        ]
                    ],
                    // 'AcademicPeriods' => [
                    //     'fields' => [
                    //         'name',
                    //         'start_year'
                    //     ]
                    // ]
                ])
                ->innerJoin(
                    ['UserHealthAllergies' => 'user_health_allergies'],
                    [
                        'UserHealthAllergies.security_user_id = ' . $this->aliasField('staff_id')
                    ]
                )
                ->innerJoin(
                    ['HealthAllergyTypes' => 'health_allergy_types'],
                    [
                        'HealthAllergyTypes.id = UserHealthAllergies.health_allergy_type_id'
                    ]
                )
                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('staff_id'),
                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                ])
                ->leftJoin([$Class->alias() => $Class->table()], [
                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                 ->where($conditions);
        }elseif($healthReportType == 'Consultations'){
            $query
                ->select([
                    $this->aliasField('staff_id'),
                    //$this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                   // $this->aliasField('academic_period_id'),                
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
                    'student_name' => $query->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),
                    'first_name' =>'Users.first_name',
                    'middle_name' => 'Users.middle_name',
                    'last_name' => 'Users.last_name',
                    'health_consultation_date' => 'UserHealthConsultations.date',
                    'health_consultation_description' => 'UserHealthConsultations.description',
                    'health_consultation_treatment' => 'UserHealthConsultations.treatment',
                    'health_consultation_type_id' => 'UserHealthConsultations.health_consultation_type_id',
                    'health_consultation_type_name' => 'HealthConsultationTypes.name'
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
                    // 'EducationGrades' => [
                    //     'fields' => [
                    //         'name'
                    //     ]
                    // ],
                    'Users.Genders' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'Institutions' => [
                        'fields' => [
                            'name',
                            'code'
                        ]
                    ],
                    // 'AcademicPeriods' => [
                    //     'fields' => [
                    //         'name',
                    //         'start_year'
                    //     ]
                    // ]
                ])
                ->innerJoin(
                    ['UserHealthConsultations' => 'user_health_consultations'],
                    [
                        'UserHealthConsultations.security_user_id = ' . $this->aliasField('staff_id')
                    ]
                )
                ->innerJoin(
                    ['HealthConsultationTypes' => 'health_consultation_types'],
                    [
                        'HealthConsultationTypes.id = UserHealthConsultations.health_consultation_type_id'
                    ]
                )
                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('staff_id'),
                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                ])
                ->leftJoin([$Class->alias() => $Class->table()], [
                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                 ->where($conditions);
        }elseif($healthReportType == 'Families'){
            $query
                ->select([
                    $this->aliasField('staff_id'),
                    //$this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    //$this->aliasField('academic_period_id'),                
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
                    'student_name' => $query->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),
                    'first_name' =>'Users.first_name',
                    'middle_name' => 'Users.middle_name',
                    'last_name' => 'Users.last_name',
                    'current' => 'UserHealthFamilies.current',
                    'user_health_family_comment' => 'UserHealthFamilies.comment',
                    'user_health_family_relationship_name' => 'HealthRelationships.name',
                    'user_health_family_condition_name' => 'HealthConditions.name'
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
                    // 'EducationGrades' => [
                    //     'fields' => [
                    //         'name'
                    //     ]
                    // ],
                    'Users.Genders' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'Institutions' => [
                        'fields' => [
                            'name',
                            'code'
                        ]
                    ],
                    // 'AcademicPeriods' => [
                    //     'fields' => [
                    //         'name',
                    //         'start_year'
                    //     ]
                    // ]
                ])
                ->innerJoin(
                    ['UserHealthFamilies' => 'user_health_families'],
                    [
                        'UserHealthFamilies.security_user_id = ' . $this->aliasField('staff_id')
                    ]
                )
                ->innerJoin(
                    ['HealthRelationships' => 'health_relationships'],
                    [
                        'HealthRelationships.id = UserHealthFamilies.health_relationship_id'
                    ]
                )
                ->innerJoin(
                    ['HealthConditions' => 'health_conditions'],
                    [
                        'HealthConditions.id = UserHealthFamilies.health_condition_id'
                    ]
                )
                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('staff_id'),
                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                ])
                ->leftJoin([$Class->alias() => $Class->table()], [
                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                 ->where($conditions);
        }elseif($healthReportType == 'Histories'){
            $query
                ->select([
                    $this->aliasField('staff_id'),
                    //$this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    //$this->aliasField('academic_period_id'),                
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
                    'student_name' => $query->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),
                    'first_name' =>'Users.first_name',
                    'middle_name' => 'Users.middle_name',
                    'last_name' => 'Users.last_name',
                    'current' => 'UserHealthHistories.current',
                    'user_health_history_comment' => 'UserHealthHistories.comment',
                    'user_health_history_condition_name' => 'HealthConditions.name'
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
                    // 'EducationGrades' => [
                    //     'fields' => [
                    //         'name'
                    //     ]
                    // ],
                    'Users.Genders' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'Institutions' => [
                        'fields' => [
                            'name',
                            'code'
                        ]
                    ],
                    // 'AcademicPeriods' => [
                    //     'fields' => [
                    //         'name',
                    //         'start_year'
                    //     ]
                    // ]
                ])
                ->innerJoin(
                    ['UserHealthHistories' => 'user_health_histories'],
                    [
                        'UserHealthHistories.security_user_id = ' . $this->aliasField('staff_id')
                    ]
                )
                ->innerJoin(
                    ['HealthConditions' => 'health_conditions'],
                    [
                        'HealthConditions.id = UserHealthHistories.health_condition_id'
                    ]
                )
                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('staff_id'),
                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                ])
                ->leftJoin([$Class->alias() => $Class->table()], [
                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                 ->where($conditions);
        }elseif($healthReportType == 'Immunizations'){
            $query
                ->select([
                    $this->aliasField('staff_id'),
                   //$this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                   // $this->aliasField('academic_period_id'),                
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
                    'student_name' => $query->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),
                    'first_name' =>'Users.first_name',
                    'middle_name' => 'Users.middle_name',
                    'last_name' => 'Users.last_name',
                    'user_health_immunization_current' => 'UserHealthImmunizations.date',
                    'user_health_immunization_comment' => 'UserHealthImmunizations.comment',
                    'user_health_immunization_dosage' => 'UserHealthImmunizations.dosage',
                    'user_health_immunization_type_name' => 'HealthImmunizationTypes.name'
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
                    // 'EducationGrades' => [
                    //     'fields' => [
                    //         'name'
                    //     ]
                    // ],
                    'Users.Genders' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'Institutions' => [
                        'fields' => [
                            'name',
                            'code'
                        ]
                    ],
                    // 'AcademicPeriods' => [
                    //     'fields' => [
                    //         'name',
                    //         'start_year'
                    //     ]
                    // ]
                ])
                ->innerJoin(
                    ['UserHealthImmunizations' => 'user_health_immunizations'],
                    [
                        'UserHealthImmunizations.security_user_id = ' . $this->aliasField('staff_id')
                    ]
                )
                ->innerJoin(
                    ['HealthImmunizationTypes' => 'health_immunization_types'],
                    [
                        'HealthImmunizationTypes.id = UserHealthImmunizations.health_immunization_type_id'
                    ]
                )
                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('staff_id'),
                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                ])
                ->leftJoin([$Class->alias() => $Class->table()], [
                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                 ->where($conditions);
        }elseif($healthReportType == 'Medications'){
            $query
                ->select([
                    $this->aliasField('staff_id'),
                    //$this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    //$this->aliasField('academic_period_id'),                
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
                    'student_name' => $query->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),
                    'first_name' =>'Users.first_name',
                    'middle_name' => 'Users.middle_name',
                    'last_name' => 'Users.last_name',
                    'user_health_medication_name' => 'UserHealthMedications.name',
                    'user_health_medication_dosage' => 'UserHealthMedications.dosage',
                    'user_health_medication_start_date' => 'UserHealthMedications.start_date',
                    'user_health_medication_end_date' => 'UserHealthMedications.end_date'
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
                    // 'EducationGrades' => [
                    //     'fields' => [
                    //         'name'
                    //     ]
                    // ],
                    'Users.Genders' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'Institutions' => [
                        'fields' => [
                            'name',
                            'code'
                        ]
                    ],
                    // 'AcademicPeriods' => [
                    //     'fields' => [
                    //         'name',
                    //         'start_year'
                    //     ]
                    // ]
                ])
                ->innerJoin(
                    ['UserHealthMedications' => 'user_health_medications'],
                    [
                        'UserHealthMedications.security_user_id = ' . $this->aliasField('staff_id')
                    ]
                )
                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('staff_id'),
                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                ])
                ->leftJoin([$Class->alias() => $Class->table()], [
                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                 ->where($conditions);
        }elseif($healthReportType == 'Tests'){
            $query
                ->select([
                    $this->aliasField('staff_id'),
                    //$this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    //$this->aliasField('academic_period_id'),                
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
                    'student_name' => $query->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),
                    'first_name' =>'Users.first_name',
                    'middle_name' => 'Users.middle_name',
                    'last_name' => 'Users.last_name',
                    'user_health_test_date' => 'UserHealthTests.date',
                    'user_health_test_result' => 'UserHealthTests.result',
                    'user_health_test_comment' => 'UserHealthTests.comment',
                    'user_health_test_type_name' => 'HealthTestTypes.name'
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
                    // 'EducationGrades' => [
                    //     'fields' => [
                    //         'name'
                    //     ]
                    // ],
                    'Users.Genders' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'Institutions' => [
                        'fields' => [
                            'name',
                            'code'
                        ]
                    ],
                    // 'AcademicPeriods' => [
                    //     'fields' => [
                    //         'name',
                    //         'start_year'
                    //     ]
                    // ]
                ])
                ->innerJoin(
                    ['UserHealthTests' => 'user_health_tests'],
                    [
                        'UserHealthTests.security_user_id = ' . $this->aliasField('staff_id')
                    ]
                )
                ->innerJoin(
                    ['HealthTestTypes' => 'health_test_types'],
                    [
                        'HealthTestTypes.id = UserHealthTests.health_test_type_id'
                    ]
                )
                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('staff_id'),
                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                ])
                ->leftJoin([$Class->alias() => $Class->table()], [
                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                 ->where($conditions);
        }elseif($healthReportType == 'Insurance'){
            $query
                ->select([
                    $this->aliasField('staff_id'),
                    $this->aliasField('institution_id'),               
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
                    'student_name' => $query->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),
                    'first_name' =>'Users.first_name',
                    'middle_name' => 'Users.middle_name',
                    'last_name' => 'Users.last_name',
                    'user_insurance_start_date' => 'UserInsurances.start_date',
                    'user_insurance_end_date' => 'UserInsurances.end_date',
                    'user_insurance_comment' => 'UserInsurances.comment',
                    'user_insurance_provider_name' => 'InsuranceProviders.name',
                    'user_insurance_type_name' => 'InsuranceTypes.name'
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
                    // 'EducationGrades' => [
                    //     'fields' => [
                    //         'name'
                    //     ]
                    // ],
                    'Users.Genders' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'Institutions' => [
                        'fields' => [
                            'name',
                            'code'
                        ]
                    ],
                    // 'AcademicPeriods' => [
                    //     'fields' => [
                    //         'name',
                    //         'start_year'
                    //     ]
                    // ]
                ])
                ->innerJoin(
                    ['UserInsurances' => 'user_insurances'],
                    [
                        'UserInsurances.security_user_id = ' . $this->aliasField('staff_id')
                    ]
                )
                ->innerJoin(
                    ['InsuranceProviders' => 'insurance_providers'],
                    [
                        'InsuranceProviders.id = UserInsurances.insurance_provider_id'
                    ]
                )
                ->innerJoin(
                    ['InsuranceTypes' => 'insurance_types'],
                    [
                        'InsuranceTypes.id = UserInsurances.insurance_type_id'
                    ]
                )
                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('staff_id'),
                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    
                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                ])
                ->leftJoin([$Class->alias() => $Class->table()], [
                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                 ->where($conditions);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $healthReportType = $requestData->health_report_type;
        
        $extraFields = [];
        $extraFields[] = [
            'key' => 'HealthReports.code_name',
            'field' => 'code_name',
            'type' => 'string',
            'label' => __('Code')
        ];


        $extraFields[] = [
            'key' => 'HealthReports.institution_id',
            'field' => 'institution_id',
            'type' => 'string',
            'label' => __('Name')
        ];
        
        $extraFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        
        // $extraFields[] = [
        //     'key' => 'student_name',
        //     'field' => 'student_name',
        //     'type' => 'string',
        //     'label' => __('Student Name')
        // ];

        $extraFields[] = [
            'key' => 'first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' => __('First Name')
        ];

        $extraFields[] = [
            'key' => 'middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => __('Middle Name')
        ];

        $extraFields[] = [
            'key' => 'last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' => __('Last Name')
        ];
        
        $extraFields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => __('Date Of Birth')
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
        
        if($healthReportType == 'Overview'){
            $extraFields[] = [
                'key' => 'blood_type',
                'field' => 'blood_type',
                'type' => 'string',
                'label' => __('Blood Type')
            ];

            $extraFields[] = [
                'key' => 'doctor_name',
                'field' => 'doctor_name',
                'type' => 'string',
                'label' => __('Doctor Name')
            ];

            $extraFields[] = [
                'key' => 'doctor_contact',
                'field' => 'doctor_contact',
                'type' => 'string',
                'label' => __('Doctor Contact')
            ];

            $extraFields[] = [
                'key' => 'medical_facility',
                'field' => 'medical_facility',
                'type' => 'string',
                'label' => __('Medical Facility')
            ];

            $extraFields[] = [
                'key' => 'health_insurance',
                'field' => 'health_insurance',
                'type' => 'string',
                'label' => __('Health Insurance')
            ];
        }elseif($healthReportType == 'Allergies'){
            $extraFields[] = [
                'key' => 'description',
                'field' => 'description',
                'type' => 'string',
                'label' => __('Description')
            ];

            $extraFields[] = [
                'key' => 'severe',
                'field' => 'severe',
                'type' => 'string',
                'label' => __('Severe')
            ];

            $extraFields[] = [
                'key' => 'comment',
                'field' => 'comment',
                'type' => 'string',
                'label' => __('Comment')
            ];

            $extraFields[] = [
                'key' => 'health_allergy_type_name',
                'field' => 'health_allergy_type_name',
                'type' => 'string',
                'label' => __('Health Allergy Type')
            ];
        }elseif($healthReportType == 'Consultations'){
            $extraFields[] = [
                'key' => 'health_consultation_date',
                'field' => 'health_consultation_date',
                'type' => 'date',
                'label' => __('Date')
            ];
            
            $extraFields[] = [
                'key' => 'health_consultation_description',
                'field' => 'health_consultation_description',
                'type' => 'string',
                'label' => __('Description')
            ];

            $extraFields[] = [
                'key' => 'health_consultation_treatment',
                'field' => 'health_consultation_treatment',
                'type' => 'string',
                'label' => __('Treatment')
            ]; 
            
            $extraFields[] = [
                'key' => 'health_consultation_type_name',
                'field' => 'health_consultation_type_name',
                'type' => 'string',
                'label' => __('Health Consultation Type')
            ];
        }elseif($healthReportType == 'Families'){
            $extraFields[] = [
                'key' => 'current',
                'field' => 'current',
                'type' => 'string',
                'label' => __('Current')
            ];
            
            $extraFields[] = [
                'key' => 'user_health_family_comment',
                'field' => 'user_health_family_comment',
                'type' => 'string',
                'label' => __('Comment')
            ];

            $extraFields[] = [
                'key' => 'user_health_family_relationship_name',
                'field' => 'user_health_family_relationship_name',
                'type' => 'string',
                'label' => __('Health Relationship')
            ]; 
            $extraFields[] = [
                'key' => 'user_health_family_condition_name',
                'field' => 'user_health_family_condition_name',
                'type' => 'string',
                'label' => __('Health Condition')
            ]; 
        }elseif($healthReportType == 'Histories'){
            $extraFields[] = [
                'key' => 'current',
                'field' => 'current',
                'type' => 'string',
                'label' => __('Current')
            ];
            
            $extraFields[] = [
                'key' => 'user_health_history_comment',
                'field' => 'user_health_history_comment',
                'type' => 'string',
                'label' => __('Comment')
            ];

            $extraFields[] = [
                'key' => 'user_health_history_condition_name',
                'field' => 'user_health_history_condition_name',
                'type' => 'string',
                'label' => __('Health Condition')
            ]; 
        }elseif($healthReportType == 'Immunizations'){
            $extraFields[] = [
                'key' => 'user_health_immunization_current',
                'field' => 'user_health_immunization_current',
                'type' => 'date',
                'label' => __('Date')
            ];
            // POCOR-5890 starts
            /*$extraFields[] = [
                'key' => 'user_health_immunization_dosage',
                'field' => 'user_health_immunization_dosage',
                'type' => 'string',
                'label' => __('Dosage')
            ];*/

            $extraFields[] = [
                'key' => 'user_health_immunization_type_name',
                'field' => 'user_health_immunization_type_name',
                'type' => 'string',
                'label' => __('Vaccination Type')
            ]; 

            $extraFields[] = [
                'key' => 'user_health_immunization_comment',
                'field' => 'user_health_immunization_comment',
                'type' => 'string',
                'label' => __('Comment')
            ];
            // POCOR-5890 ends
        }elseif($healthReportType == 'Medications'){
            $extraFields[] = [
                'key' => 'user_health_medication_name',
                'field' => 'user_health_medication_name',
                'type' => 'string',
                'label' => __('Name')
            ];
            
            $extraFields[] = [
                'key' => 'user_health_medication_dosage',
                'field' => 'user_health_medication_dosage',
                'type' => 'string',
                'label' => __('Dosage')
            ];

            $extraFields[] = [
                'key' => 'user_health_medication_start_date',
                'field' => 'user_health_medication_start_date',
                'type' => 'date',
                'label' => __('Start Date')
            ];
            
            $extraFields[] = [
                'key' => 'user_health_medication_end_date',
                'field' => 'user_health_medication_end_date',
                'type' => 'date',
                'label' => __('End Date')
            ];
        }elseif($healthReportType == 'Tests'){
            $extraFields[] = [
                'key' => 'user_health_test_date',
                'field' => 'user_health_test_date',
                'type' => 'date',
                'label' => __('Date')
            ];
            
            $extraFields[] = [
                'key' => 'user_health_test_result',
                'field' => 'user_health_test_result',
                'type' => 'string',
                'label' => __('Result')
            ];

            $extraFields[] = [
                'key' => 'user_health_test_comment',
                'field' => 'user_health_test_comment',
                'type' => 'string',
                'label' => __('Comment')
            ];
            
            $extraFields[] = [
                'key' => 'user_health_test_type_name',
                'field' => 'user_health_test_type_name',
                'type' => 'string',
                'label' => __('Health Test Type')
            ];
        }elseif($healthReportType == 'Insurance'){
            $extraFields[] = [
                'key' => 'user_insurance_start_date',
                'field' => 'user_insurance_start_date',
                'type' => 'date',
                'label' => __('Start Date')
            ];
            
            $extraFields[] = [
                'key' => 'user_insurance_end_date',
                'field' => 'user_insurance_end_date',
                'type' => 'date',
                'label' => __('End Date')
            ];
            
            $extraFields[] = [
                'key' => 'user_insurance_provider_name',
                'field' => 'user_insurance_provider_name',
                'type' => 'string',
                'label' => __('Provider')
            ];
            
            $extraFields[] = [
                'key' => 'user_insurance_type_name',
                'field' => 'user_insurance_type_name',
                'type' => 'string',
                'label' => __('Type')
            ];

            $extraFields[] = [
                'key' => 'user_insurance_comment',
                'field' => 'user_insurance_comment',
                'type' => 'string',
                'label' => __('Comment')
            ];
        }
      
        $fields->exchangeArray($extraFields);
    }
}
