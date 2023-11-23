<?php

namespace Report\Model\Table;

use ArrayObject;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

use App\Model\Table\AppTable;

class HealthReportsTable extends AppTable
{
    private $institution_id;
    private $academic_period_id;
    private $area_list;
    private $health_report_type;
    private $extra_fields = [];

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
        $this->addBehavior('Report.AreaList');//POCOR-7827-new
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $this->log(__FUNCTION__, 'debug');
        $requestData = json_decode($settings['process']['params']);
        $this->setAcademicPeriodID($requestData);
        $this->setInstitutionID($requestData);
        $this->setAreaList($requestData);
        $healthReportType = $this->health_report_type;
        $query = $this->setBasicQuery($query);
//        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;
//        $Class = TableRegistry::get('Institution.InstitutionClasses');
//        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');

        if ($healthReportType == 'Summary') {

            $query = $this->getSummaryQuery($query);
            $this->log($query->sql(), 'debug');
        }
        return $query;
//        $conditions = [];
//        if ($healthReportType == 'Overview') {
//
//            $conditions[$this->aliasField('student_status_id')] = '1';
//
//            $query
//                ->select([
//                    $this->aliasField('student_id'),
//                    $this->aliasField('education_grade_id'),
//                    $this->aliasField('institution_id'),
//                    $this->aliasField('academic_period_id'),
//                    'class_name' => 'InstitutionClasses.name',
//                    'code_name' => 'Institutions.code',
////                    'student_name' => $query->func()->concat([
////                        'Users.first_name' => 'literal',
////                        " ",
////                        'Users.middle_name' => 'literal',
////                        " ",
////                        'Users.third_name' => 'literal',
////                        " ",
////                        'Users.last_name' => 'literal'
////                    ]),
//                    'first_name' => 'Users.first_name',
//                    'middle_name' => 'Users.middle_name',
//                    'third_name' => 'Users.third_name',
//                    'last_name' => 'Users.last_name',
//                    'blood_type' => 'UserHealths.blood_type',
//                    'doctor_name' => 'UserHealths.doctor_name',
//                    'doctor_contact' => 'UserHealths.doctor_contact',
//                    'medical_facility' => 'UserHealths.medical_facility',
//                    'health_insurance' => 'UserHealths.health_insurance'
//                ])
//                ->contain([
//                    'Users' => [
//                        'fields' => [
//                            'openemis_no' => 'Users.openemis_no',
//                            'Users.first_name',
//                            'Users.middle_name',
//                            'Users.third_name',
//                            'Users.last_name',
//                            'date_of_birth' => 'Users.date_of_birth',
//                            'identity_number' => 'Users.identity_number',
//                            //'identity_type' => 'Users.identity_type_id'
//                        ]
//                    ],
//                    'EducationGrades' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Users.Genders' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Institutions' => [
//                        'fields' => [
//                            'name',
//                            'code'
//                        ]
//                    ],
//                    'AcademicPeriods' => [
//                        'fields' => [
//                            'name',
//                            'start_year'
//                        ]
//                    ]
//                ])
//                ->innerJoin(
//                    ['UserHealths' => 'user_healths'],
//                    [
//                        'UserHealths.security_user_id = ' . $this->aliasField('student_id')
//                    ]
//                )
//                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
//                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
//                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
//                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
//                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
//                    $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
//                ])
//                ->leftJoin([$Class->alias() => $Class->table()], [
//                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
//                ])
//                ->where($conditions);
//
//        } elseif ($healthReportType == 'Allergies') {
//            $query
//                ->select([
//                    $this->aliasField('student_id'),
//                    $this->aliasField('education_grade_id'),
//                    $this->aliasField('institution_id'),
//                    $this->aliasField('academic_period_id'),
//                    'class_name' => 'InstitutionClasses.name',
//                    'code_name' => 'Institutions.code',
////                    'student_name' => $query->func()->concat([
////                        'Users.first_name' => 'literal',
////                        " ",
////                        'Users.middle_name' => 'literal',
////                        " ",
////                        'Users.third_name' => 'literal',
////                        " ",
////                        'Users.last_name' => 'literal'
////                    ]),
//                    // 'name' => $query->func()->concat([
//                    //     'Users.first_name' => 'literal',
//                    //     " ",
//                    //     'Users.middle_name' => 'literal',
//                    //     " ",
//                    //     'Users.last_name' => 'literal'
//                    // ]),
//                    'first_name' => 'Users.first_name',
//                    'middle_name' => 'Users.middle_name',
//                    'third_name' => 'Users.third_name',
//                    'last_name' => 'Users.last_name',
//                    'description' => 'UserHealthAllergies.description',
//                    'severe' => 'UserHealthAllergies.severe',
//                    'comment' => 'UserHealthAllergies.comment',
//                    'health_allergy_type_id' => 'UserHealthAllergies.health_allergy_type_id',
//                    'health_allergy_type_name' => 'HealthAllergyTypes.name'
//                ])
//                ->contain([
//                    'Users' => [
//                        'fields' => [
//                            'openemis_no' => 'Users.openemis_no',
//                            'Users.first_name',
//                            'Users.middle_name',
//                            'Users.third_name',
//                            'Users.last_name',
//                            'date_of_birth' => 'Users.date_of_birth',
//                            'identity_number' => 'Users.identity_number',
//                            'identity_type' => 'Users.identity_type_id'
//                        ]
//                    ],
//                    'EducationGrades' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Users.Genders' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Institutions' => [
//                        'fields' => [
//                            'name',
//                            'code'
//                        ]
//                    ],
//                    'AcademicPeriods' => [
//                        'fields' => [
//                            'name',
//                            'start_year'
//                        ]
//                    ]
//                ])
//                ->innerJoin(
//                    ['UserHealthAllergies' => 'user_health_allergies'],
//                    [
//                        'UserHealthAllergies.security_user_id = ' . $this->aliasField('student_id')
//                    ]
//                )
//                ->innerJoin(
//                    ['HealthAllergyTypes' => 'health_allergy_types'],
//                    [
//                        'HealthAllergyTypes.id = UserHealthAllergies.health_allergy_type_id'
//                    ]
//                )
//                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
//                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
//                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
//                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
//                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
//                    $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
//                ])
//                ->leftJoin([$Class->alias() => $Class->table()], [
//                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
//                ])
//                ->where($conditions);
//        } elseif ($healthReportType == 'Consultations') {
//            $query
//                ->select([
//                    $this->aliasField('student_id'),
//                    $this->aliasField('education_grade_id'),
//                    $this->aliasField('institution_id'),
//                    $this->aliasField('academic_period_id'),
//                    'class_name' => 'InstitutionClasses.name',
//                    'code_name' => 'Institutions.code',
////                    'student_name' => $query->func()->concat([
////                        'Users.first_name' => 'literal',
////                        " ",
////                        'Users.middle_name' => 'literal',
////                        " ",
////                        'Users.third_name' => 'literal',
////                        " ",
////                        'Users.last_name' => 'literal'
////                        ]),
//                    'first_name' => 'Users.first_name',
//                    'middle_name' => 'Users.middle_name',
//                    'third_name' => 'Users.third_name',
//                    'last_name' => 'Users.last_name',
//                    'health_consultation_date' => 'UserHealthConsultations.date',
//                    'health_consultation_description' => 'UserHealthConsultations.description',
//                    'health_consultation_treatment' => 'UserHealthConsultations.treatment',
//                    'health_consultation_type_id' => 'UserHealthConsultations.health_consultation_type_id',
//                    'health_consultation_type_name' => 'HealthConsultationTypes.name'
//                ])
//                ->contain([
//                    'Users' => [
//                        'fields' => [
//                            'openemis_no' => 'Users.openemis_no',
//                            'Users.first_name',
//                            'Users.middle_name',
//                            'Users.third_name',
//                            'Users.last_name',
//                            'date_of_birth' => 'Users.date_of_birth',
//                            'identity_number' => 'Users.identity_number',
//                            'identity_type' => 'Users.identity_type_id'
//                        ]
//                    ],
//                    'EducationGrades' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Users.Genders' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Institutions' => [
//                        'fields' => [
//                            'name',
//                            'code'
//                        ]
//                    ],
//                    'AcademicPeriods' => [
//                        'fields' => [
//                            'name',
//                            'start_year'
//                        ]
//                    ]
//                ])
//                ->innerJoin(
//                    ['UserHealthConsultations' => 'user_health_consultations'],
//                    [
//                        'UserHealthConsultations.security_user_id = ' . $this->aliasField('student_id')
//                    ]
//                )
//                ->innerJoin(
//                    ['HealthConsultationTypes' => 'health_consultation_types'],
//                    [
//                        'HealthConsultationTypes.id = UserHealthConsultations.health_consultation_type_id'
//                    ]
//                )
//                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
//                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
//                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
//                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
//                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
//                    $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
//                ])
//                ->leftJoin([$Class->alias() => $Class->table()], [
//                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
//                ])
//                ->where($conditions);
//        } elseif ($healthReportType == 'Families') {
//            $query
//                ->select([
//                    $this->aliasField('student_id'),
//                    $this->aliasField('education_grade_id'),
//                    $this->aliasField('institution_id'),
//                    $this->aliasField('academic_period_id'),
//                    'class_name' => 'InstitutionClasses.name',
//                    'code_name' => 'Institutions.code',
////                    'student_name' => $query->func()->concat([
////                        'Users.first_name' => 'literal',
////                        " ",
////                        'Users.middle_name' => 'literal',
////                        " ",
////                        'Users.third_name' => 'literal',
////                        " ",
////                        'Users.last_name' => 'literal'
////                        ]),
//                    'first_name' => 'Users.first_name',
//                    'middle_name' => 'Users.middle_name',
//                    'third_name' => 'Users.third_name',
//                    'last_name' => 'Users.last_name',
//                    'current' => 'UserHealthFamilies.current',
//                    'user_health_family_comment' => 'UserHealthFamilies.comment',
//                    'user_health_family_relationship_name' => 'HealthRelationships.name',
//                    'user_health_family_condition_name' => 'HealthConditions.name'
//                ])
//                ->contain([
//                    'Users' => [
//                        'fields' => [
//                            'openemis_no' => 'Users.openemis_no',
//                            'Users.first_name',
//                            'Users.middle_name',
//                            'Users.third_name',
//                            'Users.last_name',
//                            'date_of_birth' => 'Users.date_of_birth',
//                            'identity_number' => 'Users.identity_number',
//                            'identity_type' => 'Users.identity_type_id'
//                        ]
//                    ],
//                    'EducationGrades' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Users.Genders' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Institutions' => [
//                        'fields' => [
//                            'name',
//                            'code'
//                        ]
//                    ],
//                    'AcademicPeriods' => [
//                        'fields' => [
//                            'name',
//                            'start_year'
//                        ]
//                    ]
//                ])
//                ->innerJoin(
//                    ['UserHealthFamilies' => 'user_health_families'],
//                    [
//                        'UserHealthFamilies.security_user_id = ' . $this->aliasField('student_id')
//                    ]
//                )
//                ->innerJoin(
//                    ['HealthRelationships' => 'health_relationshipshealth_relationships'],
//                    [
//                        'HealthRelationships.id = UserHealthFamilies.health_relationship_id'
//                    ]
//                )
//                ->innerJoin(
//                    ['HealthConditions' => 'health_conditions'],
//                    [
//                        'HealthConditions.id = UserHealthFamilies.health_condition_id'
//                    ]
//                )
//                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
//                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
//                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
//                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
//                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
//                    $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
//                ])
//                ->leftJoin([$Class->alias() => $Class->table()], [
//                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
//                ])
//                ->where($conditions);
//        } elseif ($healthReportType == 'Histories') {
//            $query
//                ->select([
//                    $this->aliasField('student_id'),
//                    $this->aliasField('education_grade_id'),
//                    $this->aliasField('institution_id'),
//                    $this->aliasField('academic_period_id'),
//                    'class_name' => 'InstitutionClasses.name',
//                    'code_name' => 'Institutions.code',
////                    'student_name' => $query->func()->concat([
////                        'Users.first_name' => 'literal',
////                        " ",
////                        'Users.middle_name' => 'literal',
////                        " ",
////                        'Users.third_name' => 'literal',
////                        " ",
////                        'Users.last_name' => 'literal'
////                        ]),
//                    'first_name' => 'Users.first_name',
//                    'middle_name' => 'Users.middle_name',
//                    'third_name' => 'Users.third_name',
//                    'last_name' => 'Users.last_name',
//                    'current' => 'UserHealthHistories.current',
//                    'user_health_history_comment' => 'UserHealthHistories.comment',
//                    'user_health_history_condition_name' => 'HealthConditions.name'
//                ])
//                ->contain([
//                    'Users' => [
//                        'fields' => [
//                            'openemis_no' => 'Users.openemis_no',
//                            'Users.first_name',
//                            'Users.middle_name',
//                            'Users.third_name',
//                            'Users.last_name',
//                            'date_of_birth' => 'Users.date_of_birth',
//                            'identity_number' => 'Users.identity_number',
//                            'identity_type' => 'Users.identity_type_id'
//                        ]
//                    ],
//                    'EducationGrades' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Users.Genders' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Institutions' => [
//                        'fields' => [
//                            'name',
//                            'code'
//                        ]
//                    ],
//                    'AcademicPeriods' => [
//                        'fields' => [
//                            'name',
//                            'start_year'
//                        ]
//                    ]
//                ])
//                ->innerJoin(
//                    ['UserHealthHistories' => 'user_health_histories'],
//                    [
//                        'UserHealthHistories.security_user_id = ' . $this->aliasField('student_id')
//                    ]
//                )
//                ->innerJoin(
//                    ['HealthConditions' => 'health_conditions'],
//                    [
//                        'HealthConditions.id = UserHealthHistories.health_condition_id'
//                    ]
//                )
//                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
//                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
//                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
//                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
//                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
//                    $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
//                ])
//                ->leftJoin([$Class->alias() => $Class->table()], [
//                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
//                ])
//                ->where($conditions);
//        } elseif ($healthReportType == 'Immunizations') {
//            $query
//                ->select([
//                    $this->aliasField('student_id'),
//                    $this->aliasField('education_grade_id'),
//                    $this->aliasField('institution_id'),
//                    $this->aliasField('academic_period_id'),
//                    'class_name' => 'InstitutionClasses.name',
//                    'code_name' => 'Institutions.code',
////                    'student_name' => $query->func()->concat([
////                        'Users.first_name' => 'literal',
////                        " ",
////                        'Users.middle_name' => 'literal',
////                        " ",
////                        'Users.third_name' => 'literal',
////                        " ",
////                        'Users.last_name' => 'literal'
////                        ]),
//                    'first_name' => 'Users.first_name',
//                    'middle_name' => 'Users.middle_name',
//                    'third_name' => 'Users.third_name',
//                    'last_name' => 'Users.last_name',
//                    'user_health_immunization_current' => 'UserHealthImmunizations.date',
//                    'user_health_immunization_comment' => 'UserHealthImmunizations.comment',
//                    'user_health_immunization_dosage' => 'UserHealthImmunizations.dosage',
//                    'user_health_immunization_type_name' => 'HealthImmunizationTypes.name'
//                ])
//                ->contain([
//                    'Users' => [
//                        'fields' => [
//                            'openemis_no' => 'Users.openemis_no',
//                            'Users.first_name',
//                            'Users.middle_name',
//                            'Users.third_name',
//                            'Users.last_name',
//                            'date_of_birth' => 'Users.date_of_birth',
//                            'identity_number' => 'Users.identity_number',
//                            'identity_type' => 'Users.identity_type_id'
//                        ]
//                    ],
//                    'EducationGrades' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Users.Genders' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Institutions' => [
//                        'fields' => [
//                            'name',
//                            'code'
//                        ]
//                    ],
//                    'AcademicPeriods' => [
//                        'fields' => [
//                            'name',
//                            'start_year'
//                        ]
//                    ]
//                ])
//                ->innerJoin(
//                    ['UserHealthImmunizations' => 'user_health_immunizations'],
//                    [
//                        'UserHealthImmunizations.security_user_id = ' . $this->aliasField('student_id')
//                    ]
//                )
//                ->innerJoin(
//                    ['HealthImmunizationTypes' => 'health_immunization_types'],
//                    [
//                        'HealthImmunizationTypes.id = UserHealthImmunizations.health_immunization_type_id'
//                    ]
//                )
//                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
//                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
//                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
//                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
//                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
//                    $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
//                ])
//                ->leftJoin([$Class->alias() => $Class->table()], [
//                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
//                ])
//                ->where($conditions);
//        } elseif ($healthReportType == 'Medications') {
//            $query
//                ->select([
//                    $this->aliasField('student_id'),
//                    $this->aliasField('education_grade_id'),
//                    $this->aliasField('institution_id'),
//                    $this->aliasField('academic_period_id'),
//                    'class_name' => 'InstitutionClasses.name',
//                    'code_name' => 'Institutions.code',
////                    'student_name' => $query->func()->concat([
////                        'Users.first_name' => 'literal',
////                        " ",
////                        'Users.middle_name' => 'literal',
////                        " ",
////                        'Users.third_name' => 'literal',
////                        " ",
////                        'Users.last_name' => 'literal'
////                        ]),
//                    'first_name' => 'Users.first_name',
//                    'middle_name' => 'Users.middle_name',
//                    'third_name' => 'Users.third_name',
//                    'last_name' => 'Users.last_name',
//                    'user_health_medication_name' => 'UserHealthMedications.name',
//                    'user_health_medication_dosage' => 'UserHealthMedications.dosage',
//                    'user_health_medication_start_date' => 'UserHealthMedications.start_date',
//                    'user_health_medication_end_date' => 'UserHealthMedications.end_date'
//                ])
//                ->contain([
//                    'Users' => [
//                        'fields' => [
//                            'openemis_no' => 'Users.openemis_no',
//                            'Users.first_name',
//                            'Users.middle_name',
//                            'Users.third_name',
//                            'Users.last_name',
//                            'date_of_birth' => 'Users.date_of_birth',
//                            'identity_number' => 'Users.identity_number',
//                            'identity_type' => 'Users.identity_type_id'
//                        ]
//                    ],
//                    'EducationGrades' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Users.Genders' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Institutions' => [
//                        'fields' => [
//                            'name',
//                            'code'
//                        ]
//                    ],
//                    'AcademicPeriods' => [
//                        'fields' => [
//                            'name',
//                            'start_year'
//                        ]
//                    ]
//                ])
//                ->innerJoin(
//                    ['UserHealthMedications' => 'user_health_medications'],
//                    [
//                        'UserHealthMedications.security_user_id = ' . $this->aliasField('student_id')
//                    ]
//                )
//                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
//                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
//                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
//                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
//                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
//                    $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
//                ])
//                ->leftJoin([$Class->alias() => $Class->table()], [
//                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
//                ])
//                ->where($conditions);
//        } elseif ($healthReportType == 'Tests') {
//            $query
//                ->select([
//                    $this->aliasField('student_id'),
//                    $this->aliasField('education_grade_id'),
//                    $this->aliasField('institution_id'),
//                    $this->aliasField('academic_period_id'),
//                    'class_name' => 'InstitutionClasses.name',
//                    'code_name' => 'Institutions.code',
////                    'student_name' => $query->func()->concat([
////                        'Users.first_name' => 'literal',
////                        " ",
////                        'Users.middle_name' => 'literal',
////                        " ",
////                        'Users.third_name' => 'literal',
////                        " ",
////                        'Users.last_name' => 'literal'
////                        ]),
//                    'first_name' => 'Users.first_name',
//                    'middle_name' => 'Users.middle_name',
//                    'third_name' => 'Users.third_name',
//                    'last_name' => 'Users.last_name',
//                    'user_health_test_date' => 'UserHealthTests.date',
//                    'user_health_test_result' => 'UserHealthTests.result',
//                    'user_health_test_comment' => 'UserHealthTests.comment',
//                    'user_health_test_type_name' => 'HealthTestTypes.name'
//                ])
//                ->contain([
//                    'Users' => [
//                        'fields' => [
//                            'openemis_no' => 'Users.openemis_no',
//                            'Users.first_name',
//                            'Users.middle_name',
//                            'Users.third_name',
//                            'Users.last_name',
//                            'date_of_birth' => 'Users.date_of_birth',
//                            'identity_number' => 'Users.identity_number',
//                            'identity_type' => 'Users.identity_type_id'
//                        ]
//                    ],
//                    'EducationGrades' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Users.Genders' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Institutions' => [
//                        'fields' => [
//                            'name',
//                            'code'
//                        ]
//                    ],
//                    'AcademicPeriods' => [
//                        'fields' => [
//                            'name',
//                            'start_year'
//                        ]
//                    ]
//                ])
//                ->innerJoin(
//                    ['UserHealthTests' => 'user_health_tests'],
//                    [
//                        'UserHealthTests.security_user_id = ' . $this->aliasField('student_id')
//                    ]
//                )
//                ->innerJoin(
//                    ['HealthTestTypes' => 'health_test_types'],
//                    [
//                        'HealthTestTypes.id = UserHealthTests.health_test_type_id'
//                    ]
//                )
//                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
//                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
//                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
//                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
//                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
//                    $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
//                ])
//                ->leftJoin([$Class->alias() => $Class->table()], [
//                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
//                ])
//                ->where($conditions);
//        } elseif ($healthReportType == 'Insurance') {
//            $query
//                ->select([
//                    $this->aliasField('student_id'),
//                    $this->aliasField('education_grade_id'),
//                    $this->aliasField('institution_id'),
//                    $this->aliasField('academic_period_id'),
//                    'class_name' => 'InstitutionClasses.name',
//                    'code_name' => 'Institutions.code',
////                    'student_name' => $query->func()->concat([
////                        'Users.first_name' => 'literal',
////                        " ",
////                        'Users.middle_name' => 'literal',
////                        " ",
////                        'Users.third_name' => 'literal',
////                        " ",
////                        'Users.last_name' => 'literal'
////                        ]),
//                    'first_name' => 'Users.first_name',
//                    'middle_name' => 'Users.middle_name',
//                    'third_name' => 'Users.third_name',
//                    'last_name' => 'Users.last_name',
//                    'user_insurance_start_date' => 'UserInsurances.start_date',
//                    'user_insurance_end_date' => 'UserInsurances.end_date',
//                    'user_insurance_comment' => 'UserInsurances.comment',
//                    'user_insurance_provider_name' => 'InsuranceProviders.name',
//                    'user_insurance_type_name' => 'InsuranceTypes.name'
//                ])
//                ->contain([
//                    'Users' => [
//                        'fields' => [
//                            'openemis_no' => 'Users.openemis_no',
//                            'Users.first_name',
//                            'Users.middle_name',
//                            'Users.third_name',
//                            'Users.last_name',
//                            'date_of_birth' => 'Users.date_of_birth',
//                            'identity_number' => 'Users.identity_number',
//                            'identity_type' => 'Users.identity_type_id'
//                        ]
//                    ],
//                    'EducationGrades' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Users.Genders' => [
//                        'fields' => [
//                            'name'
//                        ]
//                    ],
//                    'Institutions' => [
//                        'fields' => [
//                            'name',
//                            'code'
//                        ]
//                    ],
//                    'AcademicPeriods' => [
//                        'fields' => [
//                            'name',
//                            'start_year'
//                        ]
//                    ]
//                ])
//                ->innerJoin(
//                    ['UserInsurances' => 'user_insurances'],
//                    [
//                        'UserInsurances.security_user_id = ' . $this->aliasField('student_id')
//                    ]
//                )
//                ->innerJoin(
//                    ['InsuranceProviders' => 'insurance_providers'],
//                    [
//                        'InsuranceProviders.id = UserInsurances.insurance_provider_id'
//                    ]
//                )
//                ->innerJoin(
//                    ['InsuranceTypes' => 'insurance_types'],
//                    [
//                        'InsuranceTypes.id = UserInsurances.insurance_type_id'
//                    ]
//                )
//                ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
//                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
//                    $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
//                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
//                    $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
//                    $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
//                ])
//                ->leftJoin([$Class->alias() => $Class->table()], [
//                    $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
//                ])
//                ->where($conditions);
//        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $this->setHealthReportType($requestData);
        $healthReportType = $this->health_report_type;
        $query = $this->query();
        if ($healthReportType == 'Summary') {
            $this->getSummaryQuery($query);
        }
        $extraFields = [];
        $extra_fields = $this->extra_fields;
//        $this->log(__CLASS__, 'debug');
//        $this->log(__FUNCTION__, 'debug');
//        $this->log($extra_fields, 'debug');
        $extraFields[] = $extra_fields['institution_code'];
        if ($healthReportType == 'Summary') {
            $extraFields[] = $extra_fields['institution_name'];
            $extraFields[] = $extra_fields['institution_provider_name'];
            $extraFields[] = $extra_fields['area_name'];
            $extraFields[] = $extra_fields['education_grade_name'];
            $extraFields[] = $extra_fields['student_identity_type'];
            $extraFields[] = $extra_fields['student_identity_number'];
            $extraFields[] = $extra_fields['openemis_no'];
            $extraFields[] = $extra_fields['student_name'];
            $extraFields[] = $extra_fields['student_gender'];
            $extraFields[] = $extra_fields['date_of_birth'];
            $extraFields[] = $extra_fields['student_area_administrative'];
            $extraFields[] = $extra_fields['student_birthplace_area'];
            $extraFields[] = $extra_fields['student_class'];
            $extraFields[] = $extra_fields['blood_type'];
            $extraFields[] = $extra_fields['doctor_name'];
            $extraFields[] = $extra_fields['doctor_contact'];
            $extraFields[] = $extra_fields['medical_facility'];
            $extraFields[] = $extra_fields['health_insurance'];
            $extraFields[] = $extra_fields['allergy_type'];
            $extraFields[] = $extra_fields['allergy_severity'];
            $extraFields[] = $extra_fields['allergy_description'];

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

//        if ($healthReportType != 'Summary') {
//
//            $extraFields[] = [
//                'key' => 'HealthReports.institution_id',
//                'field' => 'institution_id',
//                'type' => 'string',
//                'label' => __('Institution')
//            ];
//
//            $extraFields[] = [
//                'key' => 'HealthReports.education_grade_id',
//                'field' => 'education_grade_id',
//                'type' => 'string',
//                'label' => __('Education Grade')
//            ];
//
//            $extraFields[] = [
//                'key' => 'InstitutionClasses.name',
//                'field' => 'class_name',
//                'type' => 'string',
//                'label' => ''
//            ];
//
//            $extraFields[] = [
//                'key' => 'openemis_no',
//                'field' => 'openemis_no',
//                'type' => 'string',
//                'label' => __('OpenEMIS ID')
//            ];
//
//            $extraFields[] = [
//                'key' => 'student_name',
//                'field' => 'student_name',
//                'type' => 'string',
//                'label' => __('Student Name')
//            ];
//
//            // $extraFields[] = [
//            //     'key' => 'first_name',
//            //     'field' => 'first_name',
//            //     'type' => 'string',
//            //     'label' => __('First Name')
//            // ];
//
//            // $extraFields[] = [
//            //     'key' => 'middle_name',
//            //     'field' => 'middle_name',
//            //     'type' => 'string',
//            //     'label' => __('Middle Name')
//            // ];
//
//            // $extraFields[] = [
//            //     'key' => 'last_name',
//            //     'field' => 'last_name',
//            //     'type' => 'string',
//            //     'label' => __('Last Name')
//            // ];
//            $extraFields[] = [
//                'key' => 'Users.date_of_birth',
//                'field' => 'date_of_birth',
//                'type' => 'date',
//                'label' => __('Date Of Birth')
//            ];
//
//            // $extraFields[] = [
//            //     'key' => 'Users.identity_type_id',
//            //     'field' => 'identity_type',
//            //     'type' => 'string',
//            //     'label' => __('Identity Type')
//            // ];
//
//            $extraFields[] = [
//                'key' => 'Users.identity_number',
//                'field' => 'identity_number',
//                'type' => 'string',
//                'label' => __('Identity Number')
//            ];
//        }
//        if ($healthReportType == 'Overview') {
//            $extraFields[] = [
//                'key' => 'blood_type',
//                'field' => 'blood_type',
//                'type' => 'string',
//                'label' => __('Blood Type')
//            ];
//
//            $extraFields[] = [
//                'key' => 'doctor_name',
//                'field' => 'doctor_name',
//                'type' => 'string',
//                'label' => __('Doctor Name')
//            ];
//
//            $extraFields[] = [
//                'key' => 'doctor_contact',
//                'field' => 'doctor_contact',
//                'type' => 'string',
//                'label' => __('Doctor Contact')
//            ];
//
//            $extraFields[] = [
//                'key' => 'medical_facility',
//                'field' => 'medical_facility',
//                'type' => 'string',
//                'label' => __('Medical Facility')
//            ];
//
//            $extraFields[] = [
//                'key' => 'health_insurance',
//                'field' => 'health_insurance',
//                'type' => 'string',
//                'label' => __('Health Insurance')
//            ];
//        }
//        elseif ($healthReportType == 'Allergies') {
//            $extraFields[] = [
//                'key' => 'description',
//                'field' => 'description',
//                'type' => 'string',
//                'label' => __('Description')
//            ];
//
//            $extraFields[] = [
//                'key' => 'severe',
//                'field' => 'severe',
//                'type' => 'string',
//                'label' => __('Severe')
//            ];
//
//            $extraFields[] = [
//                'key' => 'comment',
//                'field' => 'comment',
//                'type' => 'string',
//                'label' => __('Comment')
//            ];
//
//            $extraFields[] = [
//                'key' => 'health_allergy_type_name',
//                'field' => 'health_allergy_type_name',
//                'type' => 'string',
//                'label' => __('Health Allergy Type')
//            ];
//        }
//        elseif ($healthReportType == 'Consultations') {
//            $extraFields[] = [
//                'key' => 'health_consultation_date',
//                'field' => 'health_consultation_date',
//                'type' => 'date',
//                'label' => __('Date')
//            ];
//
//            $extraFields[] = [
//                'key' => 'health_consultation_description',
//                'field' => 'health_consultation_description',
//                'type' => 'string',
//                'label' => __('Description')
//            ];
//
//            $extraFields[] = [
//                'key' => 'health_consultation_treatment',
//                'field' => 'health_consultation_treatment',
//                'type' => 'string',
//                'label' => __('Treatment')
//            ];
//
//            $extraFields[] = [
//                'key' => 'health_consultation_type_name',
//                'field' => 'health_consultation_type_name',
//                'type' => 'string',
//                'label' => __('Health Consultation Type')
//            ];
//        }
//        elseif ($healthReportType == 'Families') {
//            $extraFields[] = [
//                'key' => 'current',
//                'field' => 'current',
//                'type' => 'string',
//                'label' => __('Current')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_health_family_comment',
//                'field' => 'user_health_family_comment',
//                'type' => 'string',
//                'label' => __('Comment')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_health_family_relationship_name',
//                'field' => 'user_health_family_relationship_name',
//                'type' => 'string',
//                'label' => __('Health Relationship')
//            ];
//            $extraFields[] = [
//                'key' => 'user_health_family_condition_name',
//                'field' => 'user_health_family_condition_name',
//                'type' => 'string',
//                'label' => __('Health Condition')
//            ];
//        }
//        elseif ($healthReportType == 'Histories') {
//            $extraFields[] = [
//                'key' => 'current',
//                'field' => 'current',
//                'type' => 'string',
//                'label' => __('Current')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_health_history_comment',
//                'field' => 'user_health_history_comment',
//                'type' => 'string',
//                'label' => __('Comment')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_health_history_condition_name',
//                'field' => 'user_health_history_condition_name',
//                'type' => 'string',
//                'label' => __('Health Condition')
//            ];
//        }
//        elseif ($healthReportType == 'Immunizations') {
//            $extraFields[] = [
//                'key' => 'user_health_immunization_current',
//                'field' => 'user_health_immunization_current',
//                'type' => 'date',
//                'label' => __('Date')
//            ];
//            // POCOR-5890 starts
//            /*$extraFields[] = [
//                'key' => 'user_health_immunization_dosage',
//                'field' => 'user_health_immunization_dosage',
//                'type' => 'string',
//                'label' => __('Dosage')
//            ];*/
//
//            $extraFields[] = [
//                'key' => 'user_health_immunization_type_name',
//                'field' => 'user_health_immunization_type_name',
//                'type' => 'string',
//                'label' => __('Vaccination Type')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_health_immunization_comment',
//                'field' => 'user_health_immunization_comment',
//                'type' => 'string',
//                'label' => __('Comment')
//            ];
//            // POCOR-5890 ends
//        }
//        elseif ($healthReportType == 'Medications') {
//            $extraFields[] = [
//                'key' => 'user_health_medication_name',
//                'field' => 'user_health_medication_name',
//                'type' => 'string',
//                'label' => __('Name')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_health_medication_dosage',
//                'field' => 'user_health_medication_dosage',
//                'type' => 'string',
//                'label' => __('Dosage')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_health_medication_start_date',
//                'field' => 'user_health_medication_start_date',
//                'type' => 'date',
//                'label' => __('Start Date')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_health_medication_end_date',
//                'field' => 'user_health_medication_end_date',
//                'type' => 'date',
//                'label' => __('End Date')
//            ];
//        }
//        elseif ($healthReportType == 'Tests') {
//            $extraFields[] = [
//                'key' => 'user_health_test_date',
//                'field' => 'user_health_test_date',
//                'type' => 'date',
//                'label' => __('Date')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_health_test_result',
//                'field' => 'user_health_test_result',
//                'type' => 'string',
//                'label' => __('Result')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_health_test_comment',
//                'field' => 'user_health_test_comment',
//                'type' => 'string',
//                'label' => __('Comment')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_health_test_type_name',
//                'field' => 'user_health_test_type_name',
//                'type' => 'string',
//                'label' => __('Health Test Type')
//            ];
//        }
//        elseif ($healthReportType == 'Insurance') {
//            $extraFields[] = [
//                'key' => 'user_insurance_start_date',
//                'field' => 'user_insurance_start_date',
//                'type' => 'date',
//                'label' => __('Start Date')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_insurance_end_date',
//                'field' => 'user_insurance_end_date',
//                'type' => 'date',
//                'label' => __('End Date')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_insurance_provider_name',
//                'field' => 'user_insurance_provider_name',
//                'type' => 'string',
//                'label' => __('Provider')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_insurance_type_name',
//                'field' => 'user_insurance_type_name',
//                'type' => 'string',
//                'label' => __('Type')
//            ];
//
//            $extraFields[] = [
//                'key' => 'user_insurance_comment',
//                'field' => 'user_insurance_comment',
//                'type' => 'string',
//                'label' => __('Comment')
//            ];
//        }
//        else

        $fields->exchangeArray($extraFields);
    }


    public function onExcelGetSevere(Event $event, Entity $entity)
    {
        $severe = ($entity->severe == 1) ? 'Yes' : 'No';
        return $severe;
    }

    public function onExcelGetCurrent(Event $event, Entity $entity)
    {
        $current = ($entity->current == 1) ? 'Yes' : 'No';
        return $current;
    }

    /**
     * function to set inner global academic period id
     * @param $requestData
     */
    private function setAcademicPeriodID($requestData)
    {
        $academicPeriodId = $requestData->academic_period_id;

        $this->academic_period_id = $academicPeriodId;
    }

    /**
     * @param $requestData
     * function to set inner global institution id
     */
    private function setInstitutionID($requestData)
    {
        $institutionId = $requestData->institution_id;
        if (empty($institutionId) or $institutionId == 0) {
            $institutionId = -1;
        }
        $this->institution_id = $institutionId;
    }

    /**
     * @param $requestData
     * function to set inner global area list, to use in Institutions
     */
    private function setAreaList($requestData)
    {
        //POCOR-7827-new start
        $areaId = $requestData->area_education_id;
        $areaLevelId = $requestData->area_level_id; //POCOR-7827-new
        $areaList = [];
        if (
            $areaLevelId > 1 && $areaId > 1
        ) {
            $areaList = $this->getAreaList($areaLevelId, $areaId);
        } elseif ($areaLevelId > 1) {
            $areaList = $this->getAreaList($areaLevelId, 0);
        } elseif ($areaId > 1) {
            $areaList = $this->getAreaList(0, $areaId);
        }
        if (!empty($areaList)) {
            $this->area_list = $areaList;
        }
        //POCOR-7827-new end

    }

    /**
     * @param $requestData
     * function to set inner global health report type
     */
    private function setHealthReportType($requestData)
    {
        $this->health_report_type = $requestData->health_report_type;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function setBasicQuery(Query $query)
    {

        $academic_period_id = $this->academic_period_id;

        $condition = [
            $this->aliasField('academic_period_id') => $academic_period_id,
        ];

        $institution_id = $this->institution_id;
        if ($institution_id > 0) {
            $condition[$this->aliasField('institution_id')] = $institution_id;
        }

        //select current students only in current academic year
        $currentAcademicPeriod = $this->AcademicPeriods->getCurrent();
        if ($academic_period_id == $currentAcademicPeriod) {
            $condition[$this->aliasField('student_status_id')] = 1;
        }


        $query
            ->select(
                [
                    $this->aliasField('id'),
                    $this->aliasField('student_id'),
                    $this->aliasField('institution_id'),
                    $this->aliasField('student_status_id'),
                    $this->aliasField('academic_period_id'),
                ]
            )
            ->where([
                $condition
            ])
            ->order([
                $this->aliasField('institution_id') => 'ASC',
                $this->aliasField('student_id') => 'ASC'
            ]);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */

    private function getSummaryQuery(Query $query = null)
    {
        $query = $this->addUserBasicFields($query);
        $query = $this->addInstitutionFields($query);
        $query = $this->addAreaCondition($query);
        $query = $this->addEducationGradeField($query);
        $query = $this->addInstitutionProviderField($query);
        $query = $this->addAreaField($query);
        $query = $this->addStudentIdentityTypeField($query);
        $query = $this->addStudentGenderField($query);
        $query = $this->addStudentBirthplaceAreaField($query);
        $query = $this->addAreaAdministrativeField($query);
        $query = $this->addStudentNationalityField($query);
        $query = $this->addStudentClassField($query);
        $query = $this->addUserHealthFields($query);
        $query = $this->addAllergyFields($query);
//        $query = $this->addHealthConsultationFields($query);
        $query
            ->select([
                'health_allergy_type' => 'HealthAllergyTypes.name',
                'health_allergy_description' => 'UserHealthAllergies.description',
                'health_consultation_date' => 'UserHealthConsultations.treatment',
                'health_consultation_description' => 'UserHealthConsultations.treatment',
                'health_consultation_treatment' => 'UserHealthConsultations.treatment',
                'health_relationship' => 'HealthRelationships.name',
                'health_condition' => 'HealthConditions.name',
                'health_immunization_type' => 'HealthImmunizationTypes.name',
                'user_health_medications_start' => 'UserHealthMedications.start_date',
                'user_health_medications_end' => 'UserHealthMedications.end_date',
                'health_test_type' => 'HealthTestTypes.name',
                'user_health_tests_date' => 'UserHealthTests.date',
                'body_mass_height' => 'UserBodyMasses.height',
                'body_mass_weight' => 'UserBodyMasses.weight',
                'body_mass_index' => 'UserBodyMasses.body_mass_index',
                'body_mass_date' => 'UserBodyMasses.body_mass_index',
            ])
            ->leftJoin(['UserHealthAllergies' => 'user_health_allergies'], [
                'UserHealthAllergies.security_user_id = ' . $this->aliasField('student_id')
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
            );
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addUserBasicFields(Query $query)
    {

            $query->leftJoin(['Users' => 'security_users'], [
                $this->aliasField('student_id = ') . 'Users.id'
            ]);

            $query = $query->select([
                'first_name' => 'Users.first_name',
                'middle_name' => 'Users.middle_name',
                'third_name' => 'Users.third_name',
                'last_name' => 'Users.last_name',
                'openemis_no' => 'Users.openemis_no',
                'date_of_birth' => 'Users.date_of_birth',
                'student_address' => 'Users.address',
                'student_identity_number' => 'Users.identity_number',
            ])
                ->groupBy($this->aliasField('student_id = '));
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    $studentName = [];
                    ($row->first_name) ? $studentName[] = $row->first_name : '';
                    ($row->middle_name) ? $studentName[] = $row->middle_name : '';
                    ($row->third_name) ? $studentName[] = $row->third_name : '';
                    ($row->last_name) ? $studentName[] = $row->last_name : '';
                    $row['student_name'] = implode(' ', $studentName);
                    return $row;
                });
            });

        $this->extra_fields['student_identity_number'] = [
            'key' => '',
            'field' => 'student_identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $this->extra_fields['student_name'] = [
            'key' => '',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student Name')
        ];
        $this->extra_fields['openemis_no'] = [
            'key' => '',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $this->extra_fields['date_of_birth'] = [
            'key' => '',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => __('Date Of Birth')
        ];
        $this->extra_fields['student_address'] = [
            'key' => '',
            'field' => 'student_address',
            'type' => 'string',
            'label' => __('Address')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addInstitutionFields(Query $query)
    {
        $this->log(__FUNCTION__, 'debug');
        if ($query) {
            $query->innerJoin(['Institutions' => 'institutions'], [
                $this->aliasField('institution_id') . ' = ' . 'Institutions.id'
            ]);
            $query = $query->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
            ]);
        }
        $this->extra_fields['institution_code'] = [
            'key' => '',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Code')
        ];
        $this->extra_fields['institution_name'] = [
            'key' => '',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Name')
        ];

        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addEducationGradeField(Query $query)
    {
        if ($query) {
            $query->leftJoin(['EducationGrades' => 'education_grades'], [
                $this->aliasField('education_grade_id') . ' = ' . 'EducationGrades.id'
            ]);
            $query = $query->select([
                'education_grade_name' => 'EducationGrades.name',
            ]);
        }
        $this->extra_fields['education_grade_name'] = [
            'key' => '',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addInstitutionProviderField(Query $query)
    {
        if ($query) {
            $query->innerJoin(['InstitutionProviders' => 'institution_providers'], [
                'InstitutionProviders.id = ' . 'Institutions.institution_provider_id'
            ]);

            $query = $query->select([
                'institution_provider_name' => 'InstitutionProviders.name',
            ]);
        }
        $this->extra_fields['institution_provider_name'] = [
            'key' => '',
            'field' => 'institution_provider_name',
            'type' => 'string',
            'label' => __('Institution Provider')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addAreaCondition(Query $query)
    {
        if ($query) {
            $areaList = $this->area_list;
            if (!empty($areaList)) {
                $query->where(['Institutions.area_id IN' => $areaList]);
            }
        }
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addAreaField(Query $query)
    {
        if ($query) {
            $query->innerJoin(['Areas' => 'areas'],
                [
                    'Areas.id = ' . 'Institutions.area_id'
                ]);

            $query = $query->select([
                'area_name' => 'Areas.name',
            ]);
        }
        $this->extra_fields['area_name'] = [
            'key' => '',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area')
        ];
        return $query;
    }

    private function addStudentIdentityTypeField(Query $query)
    {

        $table = 'identity_types';
        $options = self::getRelatedOptions($table);
        $source_field = 'student_identity_type_id';
        $destination_field = 'student_identity_type';
        if ($query) {
            $query->select([$source_field => 'Users.identity_type_id']);
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
            use ($options, $source_field, $destination_field) {
                return $results->map(function ($row) use ($options, $source_field, $destination_field) {
                    if (isset($row[$source_field])) {
                        if (isset($options[$row[$source_field]])) {
                            $row[$destination_field] = $options[$row[$source_field]];
                        }
                    }
                    return $row;
                });
            });
        }
        $this->extra_fields['student_identity_type'] = [
            'key' => '',
            'field' => $destination_field,
            'type' => 'string',
            'label' => __('Identity Type')
        ];
        return $query;
    }

    private function addStudentGenderField(Query $query)
    {
        $table = 'genders';
        $options = self::getRelatedOptions($table);
        $source_field = 'student_gender_id';
        $destination_field = 'student_gender';
        $query->select([$source_field => 'Users.gender_id']);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
        use ($options, $source_field, $destination_field) {
            return $results->map(function ($row) use ($options, $source_field, $destination_field) {
                if(isset($row[$source_field]) && isset($options[$row[$source_field]])) {
                    $row[$destination_field] = $options[$row[$source_field]];
                }
                return $row;
            });
        });
        $this->extra_fields['student_gender'] = [
            'key' => '',
            'field' => $destination_field,
            'type' => 'string',
            'label' => __('Gender')
        ];
        return $query;
    }

    private function addStudentBirthplaceAreaField(Query $query)
    {
        $options = self::getRelatedOptions('area_administratives');
        $source_field = 'student_birthplace_area_id';
        $destination_field = 'student_birthplace_area';
        $query->select([$source_field => 'Users.birthplace_area_id']);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
        use ($options, $source_field, $destination_field) {
            return $results->map(function ($row) use ($options, $source_field, $destination_field) {
                if(isset($row[$source_field]) && isset($options[$row[$source_field]])) {
                    $row[$destination_field] = $options[$row[$source_field]];
                }
                return $row;
            });
        });
        $this->extra_fields['student_birthplace_area'] = [
            'key' => '',
            'field' => $destination_field,
            'type' => 'string',
            'label' => __('Birthplace Area')
        ];
        return $query;
    }

    private function addAreaAdministrativeField(Query $query)
    {
        $options = self::getRelatedOptions('area_administratives');
        $source_field = 'student_area_administrative_id';
        $destination_field = 'student_area_administrative';
        $query->select([$source_field => 'Institutions.area_administrative_id']);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
        use ($options, $source_field, $destination_field) {
            return $results->map(function ($row) use ($options, $source_field, $destination_field) {
                if(isset($row[$source_field]) && isset($options[$row[$source_field]])) {
                    $row[$destination_field] = $options[$row[$source_field]];
                }
                return $row;
            });
        });
        $this->extra_fields['student_area_administrative'] = [
            'key' => '',
            'field' => $destination_field,
            'type' => 'string',
            'label' => __('Administrative Area')
        ];

        return $query;
    }

    private function addStudentNationalityField(Query $query)
    {

        $table = 'nationalities';
        $options = self::getRelatedOptions($table);
        $source_field = 'student_nationality_id';
        $destination_field = 'student_nationality';
        $query->select([$source_field => 'Users.nationality_id']);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
        use ($options, $source_field, $destination_field) {
            return $results->map(function ($row) use ($options, $source_field, $destination_field) {
                if(isset($row[$source_field]) && isset($options[$row[$source_field]])) {
                    $row[$destination_field] = $options[$row[$source_field]];
                }
                return $row;
            });
        });
        $this->extra_fields['student_nationality'] = [
            'key' => '',
            'field' => $destination_field,
            'type' => 'string',
            'label' => __('Nationality')
        ];

        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addStudentClassField(Query $query)
    {
        $classes = TableRegistry::get('institution_classes');
        $class_students = TableRegistry::get('institution_class_students');
        $query->leftJoin([$class_students->alias() => $class_students->table()], [
            $class_students->aliasField('student_id = ') . $this->aliasField('student_id'),
            $class_students->aliasField('institution_id = ') . $this->aliasField('institution_id'),
            $class_students->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
            $class_students->aliasField('student_status_id = ') . $this->aliasField('student_status_id'),
            $class_students->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
        ])
            ->leftJoin([$classes->alias() => $classes->table()], [
                $classes->aliasField('id = ') . $class_students->aliasField('institution_class_id')
            ]);
        $query = $query->select([
            'student_class' => $classes->aliasField('name')]);
        $this->extra_fields['student_class'] = [
            'key' => '',
            'field' => 'student_class',
            'type' => 'string',
            'label' => __('Class')
        ];

        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addUserHealthFields(Query $query)
    {
        $query->leftJoin(['UserHealths' => 'user_healths'], [
            'UserHealths.security_user_id = ' . $this->aliasField('student_id')
        ]);

        $query
            ->select([
                'blood_type' => 'UserHealths.blood_type',
                'doctor_name' => 'UserHealths.doctor_name',
                'doctor_contact' => 'UserHealths.doctor_contact',
                'medical_facility' => 'UserHealths.medical_facility',
                'health_insurance' => 'UserHealths.health_insurance',
            ])->order([
                'UserHealths.created' => 'DESC',
                'UserHealths.modified' => 'DESC',
            ]);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $row['health_insurance'] = ($row->health_insurance == 1) ? 'Yes' : 'No';
                return $row;
            });
        });

        $this->extra_fields['blood_type'] = [
            'key' => '',
            'field' => 'blood_type',
            'type' => 'string',
            'label' => __('Blood Type')
        ];
        $this->extra_fields['doctor_name'] = [
            'key' => '',
            'field' => 'doctor_name',
            'type' => 'string',
            'label' => __('Doctor Name')
        ];
        $this->extra_fields['doctor_contact'] = [
            'key' => '',
            'field' => 'doctor_contact',
            'type' => 'string',
            'label' => __('Doctor Contact')
        ];
        $this->extra_fields['medical_facility'] = [
            'key' => '',
            'field' => 'medical_facility',
            'type' => 'string',
            'label' => __('Medical Facility')
        ];
        $this->extra_fields['health_insurance'] = [
            'key' => '',
            'field' => 'health_insurance',
            'type' => 'string',
            'label' => __('Health Insurance')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addAllergyFields(Query $query)
    {
        if ($query) {
            $query->leftJoin(['Allergies' => 'user_health_allergies'], [
                'Allergies.security_user_id = ' . $this->aliasField('student_id')
            ])
                ->leftJoin(['AllergyTypes' => 'health_allergy_types'], [
                    'AllergyTypes.id = Allergies.health_allergy_type_id'
                ]);

            $query = $query->select([
                'allergy_count' => $query->func()->count('AllergyTypes.id'),
                'allergy_severities' => $query->func()->group_concat(['DISTINCT Allergies.severe' => 'literal',
                    ", "
                ]),
                'allergy_types' =>  $query->func()->group_concat(['DISTINCT AllergyTypes.name' => 'literal',
                    ", "
                ]),
            ]);
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    $allergy_severities = $row->allergy_severities;
                    $row['allergy_severities'] = str_replace(['1', '0'], ['Yes', 'No'], $allergy_severities);
                    return $row;
                });
            });

        }
        $this->extra_fields['allergy_count'] = [
            'key' => '',
            'field' => 'allergy_count',
            'type' => 'string',
            'label' => __('Allergy Count')
        ];
        $this->extra_fields['allergy_types'] = [
            'key' => '',
            'field' => 'allergy_types',
            'type' => 'string',
            'label' => __('Allergy Types')
        ];
        $this->extra_fields['allergy_severities'] = [
            'key' => '',
            'field' => 'allergy_severities',
            'type' => 'string',
            'label' => __('Allergy Severities')
        ];
        return $query;
    }

    /**
     * @param $tableName
     * @param string $order
     * @param array $where
     * @return array|null
     */
    private static function getRelatedOptions($tableName, $order = '`order`', $where = [])
    {
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->find('list')
                ->select(['id', 'name'])
                ->where($where)
                ->orderAsc($order);
            $options = $related->toArray();
            $options = array_unique($options);
            return $options;
        } catch (RecordNotFoundException $e) {
            null;
        }
        return null;
    }


}
