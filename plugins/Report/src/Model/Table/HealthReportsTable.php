<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

use App\Model\Table\AppTable;

class HealthReportsTable extends AppTable
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
        $areaId = $requestData->area_education_id;
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;

        $Class = TableRegistry::get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');

        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }

        if (!empty($institutionId) && $institutionId == '-1') {
            $conditions[$ClassStudents->aliasField('student_status_id != ')] = '1';
        }

        if (!empty($institutionId) && $institutionId != '-1') {
            $conditions['Institutions.id'] = $institutionId;
        }
        if ($areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId;
        }
        if ($healthReportType == 'Summary') {
            $conditions[$this->aliasField('student_status_id')] = '1';
            $query
                ->select([
                    $this->aliasField('student_id'),
                    $this->aliasField('academic_period_id'),
                    'first_name' =>'Users.first_name',
                    'middle_name' => 'Users.middle_name',
                    'third_name' => 'Users.third_name',
                    'last_name' => 'Users.last_name',
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
                    'institution_name' => 'Institutions.name',
                    'education_grade_name' => 'EducationGrades.name',
                    'institution_providers' => 'InstitutionProviders.name',
                    'areas_name' => 'Areas.name',
                    'identity_type' => 'Users.identity_type_id',
                    'genders' => 'Genders.name',
                    'birthplace_area' => 'BirthplaceAreas.name',
                    'area_administratives_name' => 'AreaAdministratives.name',
                    'nationalities' => 'Nationalities.name',
                    'blood_type' => 'UserHealths.blood_type',
                    'doctor_name' => 'UserHealths.doctor_name',
                    'doctor_contact' => 'UserHealths.doctor_contact',
                    'medical_facility' => 'UserHealths.medical_facility',
                    'health_insurance' => 'UserHealths.health_insurance',
                    'health_allergy_type_name' => 'HealthAllergyTypes.name',
                    'allergies_description' => 'UserHealthAllergies.description',
                    'health_consultation_treatment' => 'UserHealthConsultations.treatment',
                    'health_relationships' => 'HealthRelationships.name',
                    'health_conditions' => 'HealthConditions.name',
                    'health_immunization_types' => 'HealthImmunizationTypes.name',
                    'user_health_medications_start' => 'UserHealthMedications.start_date',
                    'user_health_medications_end' => 'UserHealthMedications.end_date',
                    'health_test_types' => 'HealthTestTypes.name',
                    'user_health_tests_date' => 'UserHealthTests.date',
                    'body_mass_height' => 'UserBodyMasses.height',
                    'body_mass_weight' => 'UserBodyMasses.weight',
                    'body_mass_index' => 'UserBodyMasses.body_mass_index',
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
                    'Users.Genders' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'AcademicPeriods' => [
                        'fields' => [
                            'name',
                            'start_year'
                        ]
                    ],
                    'Institutions.Areas' => [
                    'fields' => [
                        'Areas.name',
                        'Areas.code'
                    ]
                    ],
                    'Users.BirthplaceAreas' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'Institutions.AreaAdministratives' => [
                        'fields' => [
                            'AreaAdministratives.name',
                            'AreaAdministratives.code'
                        ]
                    ],
                ])
                ->leftJoin(['EducationGrades' => 'education_grades'], [
                    'EducationGrades.id = ' . $this->aliasField('education_grade_id')
                ])
                ->leftJoin(['InstitutionClassStudents' => 'institution_class_students'], [
                    'InstitutionClassStudents.student_id = ' . $this->aliasField('student_id')  
                ])
                ->leftJoin(['InstitutionClasses' => 'institution_classes'], [
                    'InstitutionClasses.id = ' . 'InstitutionClassStudents.institution_class_id'
                ])
                ->innerJoin(['InstitutionClassGrades'=>'institution_class_grades'], [
                    'InstitutionClassGrades.institution_class_id = '.  'InstitutionClasses.id',
                    'AND' => [
                        'EducationGrades.id = '.  'InstitutionClassGrades.education_grade_id',
                    ]
                ])
                ->leftJoin(['UserNationalities' => 'user_nationalities'], [
                'UserNationalities.security_user_id = ' . $this->aliasfield('student_id'),
                ])
                ->leftJoin(['Nationalities' => 'nationalities'], [
                   'Nationalities.id = UserNationalities.nationality_id',
                ])
                ->leftJoin(['UserIdentity' => 'user_identities'], [
                    'UserIdentity.security_user_id = ' . $this->aliasfield('student_id'),
                ])
                ->leftJoin(['IdentityTypes' => 'identity_types'], [
                    'IdentityTypes.id = UserIdentity.identity_type_id',
                    // 'AND' => [
                    //     'IdentityTypes.id = UserIdentity.identity_type_id',
                    // ]
                ])
                ->leftJoin(['Institutions' => 'institutions'], [
                    'Institutions.id = ' . $this->aliasfield('institution_id')
                ])
                ->leftJoin(['InstitutionProviders' => 'institution_providers'], [
                    'InstitutionProviders.id = ' . 'Institutions.institution_provider_id'
                ])
                ->leftJoin(['UserHealths' => 'user_healths'], [
                    'UserHealths.security_user_id = ' . $this->aliasfield('student_id')
                ])
                ->leftJoin(['UserHealthAllergies' => 'user_health_allergies'], [
                    'UserHealthAllergies.security_user_id = ' . $this->aliasfield('student_id')
                ])
                ->leftJoin(['UserHealthAllergies' => 'user_health_allergies'], [
                    'UserHealthAllergies.security_user_id = ' . $this->aliasfield('student_id')
                ])
                ->leftJoin(['HealthAllergyTypes' => 'health_allergy_types'], [
                    'HealthAllergyTypes.id = UserHealthAllergies.health_allergy_type_id'
                ])
                ->leftJoin(
                    ['UserHealthConsultations' => 'user_health_consultations'],
                    [
                        'UserHealthConsultations.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->leftJoin(
                    ['UserHealthFamilies' => 'user_health_families'],
                    [
                        'UserHealthFamilies.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->leftJoin(
                    ['HealthRelationships' => 'health_relationships'],
                    [
                        'HealthRelationships.id = UserHealthFamilies.health_relationship_id'
                    ]
                )
                ->leftJoin(
                    ['HealthConditions' => 'health_conditions'],
                    [
                        'HealthConditions.id = UserHealthFamilies.health_condition_id'
                    ]
                )
                ->leftJoin(
                    ['UserHealthImmunizations' => 'user_health_immunizations'],
                    [
                        'UserHealthImmunizations.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->leftJoin(
                    ['HealthImmunizationTypes' => 'health_immunization_types'],
                    [
                        'HealthImmunizationTypes.id = UserHealthImmunizations.health_immunization_type_id'
                    ]
                )
                ->leftJoin(
                    ['UserHealthMedications' => 'user_health_medications'],
                    [
                        'UserHealthMedications.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->leftJoin(
                    ['UserHealthTests' => 'user_health_tests'],
                    [
                        'UserHealthTests.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->leftJoin(
                    ['HealthTestTypes' => 'health_test_types'],
                    [
                        'HealthTestTypes.id = UserHealthTests.health_test_type_id'
                    ]
                )
                ->leftJoin(
                    ['UserBodyMasses' => 'user_body_masses'],
                    [
                        'UserBodyMasses.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->where($conditions);
            // echo "<pre>"; print_r($query->sql()); die();
        }
        if($healthReportType == 'Overview'){
            
			$conditions[$this->aliasField('student_status_id')] = '1';
            
			$query
                ->select([
                    $this->aliasField('student_id'),
                    $this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    $this->aliasField('academic_period_id'),
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
//                    'student_name' => $query->func()->concat([
//                        'Users.first_name' => 'literal',
//                        " ",
//                        'Users.middle_name' => 'literal',
//                        " ",
//                        'Users.third_name' => 'literal',
//                        " ",
//                        'Users.last_name' => 'literal'
//                    ]),
                     'first_name' =>'Users.first_name',
                     'middle_name' => 'Users.middle_name',
                     'third_name' => 'Users.third_name',
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
                            //'identity_type' => 'Users.identity_type_id'
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
                            'name',
                            'code'
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
                    ['UserHealths' => 'user_healths'],
                    [
                        'UserHealths.security_user_id = ' . $this->aliasField('student_id')
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
                 ->where($conditions);

        }elseif($healthReportType == 'Allergies'){
            $query
                ->select([
                    $this->aliasField('student_id'),
                    $this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    $this->aliasField('academic_period_id'),
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
//                    'student_name' => $query->func()->concat([
//                        'Users.first_name' => 'literal',
//                        " ",
//                        'Users.middle_name' => 'literal',
//                        " ",
//                        'Users.third_name' => 'literal',
//                        " ",
//                        'Users.last_name' => 'literal'
//                    ]),
                    // 'name' => $query->func()->concat([
                    //     'Users.first_name' => 'literal',
                    //     " ",
                    //     'Users.middle_name' => 'literal',
                    //     " ",
                    //     'Users.last_name' => 'literal'
                    // ]),
                     'first_name' =>'Users.first_name',
                     'middle_name' => 'Users.middle_name',
                    'third_name' => 'Users.third_name',
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
                            'name',
                            'code'
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
                    ['UserHealthAllergies' => 'user_health_allergies'],
                    [
                        'UserHealthAllergies.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->innerJoin(
                    ['HealthAllergyTypes' => 'health_allergy_types'],
                    [
                        'HealthAllergyTypes.id = UserHealthAllergies.health_allergy_type_id'
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
                 ->where($conditions);
        }elseif($healthReportType == 'Consultations'){
            $query
                ->select([
                    $this->aliasField('student_id'),
                    $this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    $this->aliasField('academic_period_id'),
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
//                    'student_name' => $query->func()->concat([
//                        'Users.first_name' => 'literal',
//                        " ",
//                        'Users.middle_name' => 'literal',
//                        " ",
//                        'Users.third_name' => 'literal',
//                        " ",
//                        'Users.last_name' => 'literal'
//                        ]),
                     'first_name' =>'Users.first_name',
                     'middle_name' => 'Users.middle_name',
                    'third_name' => 'Users.third_name',
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
                            'name',
                            'code'
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
                    ['UserHealthConsultations' => 'user_health_consultations'],
                    [
                        'UserHealthConsultations.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->innerJoin(
                    ['HealthConsultationTypes' => 'health_consultation_types'],
                    [
                        'HealthConsultationTypes.id = UserHealthConsultations.health_consultation_type_id'
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
                 ->where($conditions);
        }elseif($healthReportType == 'Families'){
            $query
                ->select([
                    $this->aliasField('student_id'),
                    $this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    $this->aliasField('academic_period_id'),
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
//                    'student_name' => $query->func()->concat([
//                        'Users.first_name' => 'literal',
//                        " ",
//                        'Users.middle_name' => 'literal',
//                        " ",
//                        'Users.third_name' => 'literal',
//                        " ",
//                        'Users.last_name' => 'literal'
//                        ]),
                     'first_name' =>'Users.first_name',
                     'middle_name' => 'Users.middle_name',
                    'third_name' => 'Users.third_name',
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
                            'name',
                            'code'
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
                    ['UserHealthFamilies' => 'user_health_families'],
                    [
                        'UserHealthFamilies.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->innerJoin(
                    ['HealthRelationships' => 'health_relationshipshealth_relationships'],
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
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                    $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
                ])
                ->leftJoin([$Class->alias() => $Class->table()], [
                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                 ->where($conditions);
        }elseif($healthReportType == 'Histories'){
            $query
                ->select([
                    $this->aliasField('student_id'),
                    $this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    $this->aliasField('academic_period_id'),
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
//                    'student_name' => $query->func()->concat([
//                        'Users.first_name' => 'literal',
//                        " ",
//                        'Users.middle_name' => 'literal',
//                        " ",
//                        'Users.third_name' => 'literal',
//                        " ",
//                        'Users.last_name' => 'literal'
//                        ]),
                     'first_name' =>'Users.first_name',
                     'middle_name' => 'Users.middle_name',
                    'third_name' => 'Users.third_name',
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
                            'name',
                            'code'
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
                    ['UserHealthHistories' => 'user_health_histories'],
                    [
                        'UserHealthHistories.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->innerJoin(
                    ['HealthConditions' => 'health_conditions'],
                    [
                        'HealthConditions.id = UserHealthHistories.health_condition_id'
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
                 ->where($conditions);
        }elseif($healthReportType == 'Immunizations'){
            $query
                ->select([
                    $this->aliasField('student_id'),
                    $this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    $this->aliasField('academic_period_id'),
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
//                    'student_name' => $query->func()->concat([
//                        'Users.first_name' => 'literal',
//                        " ",
//                        'Users.middle_name' => 'literal',
//                        " ",
//                        'Users.third_name' => 'literal',
//                        " ",
//                        'Users.last_name' => 'literal'
//                        ]),
                     'first_name' =>'Users.first_name',
                     'middle_name' => 'Users.middle_name',
                    'third_name' => 'Users.third_name',
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
                            'name',
                            'code'
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
                    ['UserHealthImmunizations' => 'user_health_immunizations'],
                    [
                        'UserHealthImmunizations.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->innerJoin(
                    ['HealthImmunizationTypes' => 'health_immunization_types'],
                    [
                        'HealthImmunizationTypes.id = UserHealthImmunizations.health_immunization_type_id'
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
                 ->where($conditions);
        }elseif($healthReportType == 'Medications'){
            $query
                ->select([
                    $this->aliasField('student_id'),
                    $this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    $this->aliasField('academic_period_id'),
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
//                    'student_name' => $query->func()->concat([
//                        'Users.first_name' => 'literal',
//                        " ",
//                        'Users.middle_name' => 'literal',
//                        " ",
//                        'Users.third_name' => 'literal',
//                        " ",
//                        'Users.last_name' => 'literal'
//                        ]),
                     'first_name' =>'Users.first_name',
                     'middle_name' => 'Users.middle_name',
                    'third_name' => 'Users.third_name',
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
                            'name',
                            'code'
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
                    ['UserHealthMedications' => 'user_health_medications'],
                    [
                        'UserHealthMedications.security_user_id = ' . $this->aliasField('student_id')
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
                 ->where($conditions);
        }elseif($healthReportType == 'Tests'){
            $query
                ->select([
                    $this->aliasField('student_id'),
                    $this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    $this->aliasField('academic_period_id'),
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
//                    'student_name' => $query->func()->concat([
//                        'Users.first_name' => 'literal',
//                        " ",
//                        'Users.middle_name' => 'literal',
//                        " ",
//                        'Users.third_name' => 'literal',
//                        " ",
//                        'Users.last_name' => 'literal'
//                        ]),
                     'first_name' =>'Users.first_name',
                     'middle_name' => 'Users.middle_name',
                    'third_name' => 'Users.third_name',
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
                            'name',
                            'code'
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
                    ['UserHealthTests' => 'user_health_tests'],
                    [
                        'UserHealthTests.security_user_id = ' . $this->aliasField('student_id')
                    ]
                )
                ->innerJoin(
                    ['HealthTestTypes' => 'health_test_types'],
                    [
                        'HealthTestTypes.id = UserHealthTests.health_test_type_id'
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
                 ->where($conditions);
        }elseif($healthReportType == 'Insurance'){
            $query
                ->select([
                    $this->aliasField('student_id'),
                    $this->aliasField('education_grade_id'),
                    $this->aliasField('institution_id'),
                    $this->aliasField('academic_period_id'),
                    'class_name' => 'InstitutionClasses.name',
                    'code_name' => 'Institutions.code',
//                    'student_name' => $query->func()->concat([
//                        'Users.first_name' => 'literal',
//                        " ",
//                        'Users.middle_name' => 'literal',
//                        " ",
//                        'Users.third_name' => 'literal',
//                        " ",
//                        'Users.last_name' => 'literal'
//                        ]),
                     'first_name' =>'Users.first_name',
                     'middle_name' => 'Users.middle_name',
                    'third_name' => 'Users.third_name',
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
                            'name',
                            'code'
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
                    ['UserInsurances' => 'user_insurances'],
                    [
                        'UserInsurances.security_user_id = ' . $this->aliasField('student_id')
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
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                    $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
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
        if ($healthReportType != 'Summary') {         

            $extraFields[] = [
                'key' => 'HealthReports.institution_id',
                'field' => 'institution_id',
                'type' => 'string',
                'label' => __('Institution')
            ];

            $extraFields[] = [
                'key' => 'HealthReports.education_grade_id',
                'field' => 'education_grade_id',
                'type' => 'string',
                'label' => __('Education Grade')
            ];

            $extraFields[] = [
                'key' => 'InstitutionClasses.name',
                'field' => 'class_name',
                'type' => 'string',
                'label' => ''
            ];

            $extraFields[] = [
                'key' => 'openemis_no',
                'field' => 'openemis_no',
                'type' => 'string',
                'label' => __('OpenEMIS ID')
            ];

            $extraFields[] = [
                'key' => 'student_name',
                'field' => 'student_name',
                'type' => 'string',
                'label' => __('Student Name')
            ];

            // $extraFields[] = [
            //     'key' => 'first_name',
            //     'field' => 'first_name',
            //     'type' => 'string',
            //     'label' => __('First Name')
            // ];

            // $extraFields[] = [
            //     'key' => 'middle_name',
            //     'field' => 'middle_name',
            //     'type' => 'string',
            //     'label' => __('Middle Name')
            // ];

            // $extraFields[] = [
            //     'key' => 'last_name',
            //     'field' => 'last_name',
            //     'type' => 'string',
            //     'label' => __('Last Name')
            // ];
            $extraFields[] = [
                'key' => 'Users.date_of_birth',
                'field' => 'date_of_birth',
                'type' => 'date',
                'label' => __('Date Of Birth')
            ];

            // $extraFields[] = [
            //     'key' => 'Users.identity_type_id',
            //     'field' => 'identity_type',
            //     'type' => 'string',
            //     'label' => __('Identity Type')
            // ];

            $extraFields[] = [
                'key' => 'Users.identity_number',
                'field' => 'identity_number',
                'type' => 'string',
                'label' => __('Identity Number')
            ];
        }
        

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
        }elseif($healthReportType == 'Summary'){
            $extraFields[] = [
                'key' => 'HealthReports.institution_name',
                'field' => 'institution_name',
                'type' => 'string',
                'label' => __('Institution')
            ];
            $extraFields[] = [
                'key' => 'HealthReports.institution_providers',
                'field' => 'institution_providers',
                'type' => 'string',
                'label' => __('Institution Providers')
            ];

            $extraFields[] = [
                'key' => 'Areas.name',
                'field' => 'areas_name',
                'type' => 'string',
                'label' => __('Area')
            ];

            $extraFields[] = [
                'key' => 'HealthReports.education_grade_name',
                'field' => 'education_grade_name',
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
                'key' => 'openemis_no',
                'field' => 'openemis_no',
                'type' => 'string',
                'label' => __('OpenEMIS ID')
            ];

            $extraFields[] = [
                'key' => 'student_name',
                'field' => 'student_name',
                'type' => 'string',
                'label' => __('Student Name')
            ];

            $extraFields[] = [
                'key' => 'Genders.name',
                'field' => 'genders',
                'type' => 'string',
                'label' => __('Genders')
            ];

            $extraFields[] = [
                'key' => 'Users.date_of_birth',
                'field' => 'date_of_birth',
                'type' => 'date',
                'label' => __('Date Of Birth')
            ];

            $extraFields[] = [
                'key' => 'AreaAdministratives.name',
                'field' => 'area_administratives_name',
                'type' => 'string',
                'label' => __('Area Administratives')
            ];

            $extraFields[] = [
                'key' => 'BirthplaceAreas.name',
                'field' => 'birthplace_area',
                'type' => 'string',
                'label' => __('Birthplace Area Administratives')
            ];

            $extraFields[] = [
                'key' => 'HealthReports.nationalities',
                'field' => 'nationalities',
                'type' => 'string',
                'label' => __('Nationalities')
            ];

            $extraFields[] = [
                'key' => 'InstitutionClasses.name',
                'field' => 'class_name',
                'type' => 'string',
                'label' => ''
            ];

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

            $extraFields[] = [
                'key' => 'health_allergy_type_name',
                'field' => 'health_allergy_type_name',
                'type' => 'string',
                'label' => __('Health Allergy Type')
            ];

            $extraFields[] = [
                'key' => 'allergies_description',
                'field' => 'allergies_description',
                'type' => 'string',
                'label' => __('Allergies Description')
            ];

            $extraFields[] = [
                'key' => 'health_consultation_treatment',
                'field' => 'health_consultation_treatment',
                'type' => 'string',
                'label' => __('Treatment')
            ];

            $extraFields[] = [
                'key' => 'HealthRelationships.name',
                'field' => 'health_relationships',
                'type' => 'string',
                'label' => __('Health Relationships')
            ];

            $extraFields[] = [
                'key' => 'HealthConditions.name',
                'field' => 'health_conditions',
                'type' => 'string',
                'label' => __('Health Conditions')
            ];

            $extraFields[] = [
                'key' => 'HealthImmunizationTypes.name',
                'field' => 'health_immunization_types',
                'type' => 'string',
                'label' => __('Health Immunization Types Name')
            ];

            $extraFields[] = [
                'key' => 'UserHealthMedications.start_date',
                'field' => 'user_health_medications_start',
                'type' => 'string',
                'label' => __('User Health Medications Start Date')
            ];

            $extraFields[] = [
                'key' => 'UserHealthMedications.end_date',
                'field' => 'user_health_medications_end',
                'type' => 'string',
                'label' => __('User Health Medications End Date')
            ];

            $extraFields[] = [
                'key' => 'HealthTestTypes.name',
                'field' => 'health_test_types',
                'type' => 'string',
                'label' => __('Health Test Types Name')
            ];

            $extraFields[] = [
                'key' => 'UserHealthTests.date',
                'field' => 'user_health_tests_date',
                'type' => 'string',
                'label' => __('User Health Tests Date')
            ];

            $extraFields[] = [
                'key' => 'UserBodyMasses.height',
                'field' => 'body_mass_height',
                'type' => 'string',
                'label' => __('Body Mass Height')
            ];

            $extraFields[] = [
                'key' => 'UserBodyMasses.weight',
                'field' => 'body_mass_weight',
                'type' => 'string',
                'label' => __('Body Mass Weight')
            ];

            $extraFields[] = [
                'key' => 'UserBodyMasses.body_mass_index',
                'field' => 'body_mass_index',
                'type' => 'string',
                'label' => __('Body Mass Index')
            ];
        }

        $fields->exchangeArray($extraFields);
    }

    public function onExcelGetStudentName(Event $event, Entity $entity)
    {
        //cant use $this->Users->get() since it will load big data and cause memory allocation problem
        $studentName = [];
        ($entity->first_name) ? $studentName[] = $entity->first_name : '';
        ($entity->middle_name) ? $studentName[] = $entity->middle_name : '';
        ($entity->third_name) ? $studentName[] = $entity->third_name : '';
        ($entity->last_name) ? $studentName[] = $entity->last_name : '';

        return implode(' ', $studentName);
    }
}
