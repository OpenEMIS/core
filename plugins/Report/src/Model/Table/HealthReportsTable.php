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
        $healthReportType = $this->health_report_type;
        $requestData = json_decode($settings['process']['params']);
        $this->setAcademicPeriodID($requestData);
        $this->setInstitutionID($requestData);
        $this->setAreaList($requestData);

        $query = $this->setBasicQuery($query);
        if ($healthReportType == 'Summary') {
            $query = $this->getSummaryQuery($query);

        }

        if ($healthReportType == 'Overview') {
            $query = $this->getOverviewQuery($query);
        }

        if ($healthReportType == 'Allergies') {
            $query = $this->getAllergyQuery($query);
        }

        if ($healthReportType == 'Consultations') {
            $query = $this->getConsultationQuery($query);
        }

        if ($healthReportType == 'Family') {
            $query = $this->getFamilyQuery($query);
        }

        if ($healthReportType == 'Histories') {
            $query = $this->getHistoryQuery($query);
        }

        if ($healthReportType == 'Immunizations') {
            $query = $this->getImmunizationQuery($query);
        }

        if ($healthReportType == 'Medications') {
            $query = $this->getMedicationQuery($query);
        }


        if ($healthReportType == 'Tests') {
            $query = $this->getTestQuery($query);
        }

        if ($healthReportType == 'Insurance') {
            $query = $this->getInsuranceQuery($query);
        }

        if ($healthReportType == 'BodyMass') {
            $query = $this->getBodyMassQuery($query);
        }

        return $query;

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
        $this->setAcademicPeriodID($requestData);
        $this->setInstitutionID($requestData);
        $this->setAreaList($requestData);

        $healthReportType = $this->health_report_type;
        $extraFields = [];
        $extra_fields = $this->extra_fields;

        $updatedQuery = $this->query();
        $updatedQuery = $this->setBasicQuery($updatedQuery);
        if ($healthReportType == 'Summary') {
            $updatedQuery = $this->getSummaryQuery($updatedQuery);

        }

        if ($healthReportType == 'Overview') {
            $updatedQuery = $this->getOverviewQuery($updatedQuery);

        }

        if ($healthReportType == 'Allergies') {
            $updatedQuery = $this->getAllergyQuery($updatedQuery);
        }

        if ($healthReportType == 'Consultations') {
            $updatedQuery = $this->getConsultationQuery($updatedQuery);

        }

        if ($healthReportType == 'Family') {
            $updatedQuery = $this->getFamilyQuery($updatedQuery);

        }

        if ($healthReportType == 'Histories') {
            $updatedQuery = $this->getHistoryQuery($updatedQuery);
        }

        if ($healthReportType == 'Immunizations') {
            $updatedQuery = $this->getImmunizationQuery($updatedQuery);
        }

        if ($healthReportType == 'Medications') {
            $updatedQuery = $this->getMedicationQuery($updatedQuery);
        }

        if ($healthReportType == 'Tests') {
            $updatedQuery = $this->getTestQuery($updatedQuery);
        }

        if ($healthReportType == 'Insurance') {
            $updatedQuery = $this->getInsuranceQuery($updatedQuery);
        }

        if ($healthReportType == 'BodyMass') {
            $updatedQuery = $this->getBodyMassQuery($updatedQuery);
        }

        if ($healthReportType == 'Summary') {
            $extraFields[] = $extra_fields['area_name'];
            $extraFields[] = $extra_fields['institution_code'];
            $extraFields[] = $extra_fields['institution_name'];
            $extraFields[] = $extra_fields['institution_provider_name'];
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
            $extraFields[] = $extra_fields['allergy_count'];
            $extraFields[] = $extra_fields['allergy_types'];
            $extraFields[] = $extra_fields['allergy_severities'];
            $extraFields[] = $extra_fields['consultation_count'];
            $extraFields[] = $extra_fields['consultation_types'];
            $extraFields[] = $extra_fields['last_consultation_date'];
            $extraFields[] = $extra_fields['last_consultation_type'];
            $extraFields[] = $extra_fields['health_relationships'];
            $extraFields[] = $extra_fields['health_conditions'];
            $extraFields[] = $extra_fields['immunization_count'];
            $extraFields[] = $extra_fields['health_immunizations'];
            $extraFields[] = $extra_fields['last_immunization_date'];
            $extraFields[] = $extra_fields['medication_count'];
            $extraFields[] = $extra_fields['last_medication_name'];
            $extraFields[] = $extra_fields['last_medication_date'];
            $extraFields[] = $extra_fields['health_tests'];
            $extraFields[] = $extra_fields['test_count'];
            $extraFields[] = $extra_fields['last_test_date'];
            $extraFields[] = $extra_fields['last_test_type'];
            $extraFields[] = $extra_fields['body_mass_count'];
            $extraFields[] = $extra_fields['last_body_mass_date'];
            $extraFields[] = $extra_fields['last_body_mass_height'];
            $extraFields[] = $extra_fields['last_body_mass_weight'];
            $extraFields[] = $extra_fields['last_body_mass_index'];
        }
        if ($healthReportType != 'Summary') {
            $extraFields[] = $extra_fields['area_name'];
            $extraFields[] = $extra_fields['institution_code'];
            $extraFields[] = $extra_fields['institution_name'];
            $extraFields[] = $extra_fields['education_grade_name'];
            $extraFields[] = $extra_fields['student_class'];
            $extraFields[] = $extra_fields['openemis_no'];
            $extraFields[] = $extra_fields['student_name'];
            $extraFields[] = $extra_fields['student_gender'];
            $extraFields[] = $extra_fields['student_identity_type'];
            $extraFields[] = $extra_fields['student_identity_number'];
            $extraFields[] = $extra_fields['date_of_birth'];
        }
        if ($healthReportType == 'Overview') {
            $extraFields[] = $extra_fields['blood_type'];
            $extraFields[] = $extra_fields['doctor_name'];
            $extraFields[] = $extra_fields['doctor_contact'];
            $extraFields[] = $extra_fields['medical_facility'];
            $extraFields[] = $extra_fields['health_insurance'];
        }
        if ($healthReportType == 'Allergies') {
            $extraFields[] = $extra_fields['allergy_type'];
            $extraFields[] = $extra_fields['allergy_severe'];
            $extraFields[] = $extra_fields['allergy_description'];
            $extraFields[] = $extra_fields['allergy_comment'];
        }
        if ($healthReportType == 'Consultations') {
            $extraFields[] = $extra_fields['consultation_date'];
            $extraFields[] = $extra_fields['consultation_type'];
            $extraFields[] = $extra_fields['consultation_description'];
            $extraFields[] = $extra_fields['consultation_treatment'];
        }
        if ($healthReportType == 'Families') {
            $extraFields[] = $extra_fields['family_current'];
            $extraFields[] = $extra_fields['family_relationship'];
            $extraFields[] = $extra_fields['family_condition'];
            $extraFields[] = $extra_fields['family_comment'];
        }


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
        $this->log(__FUNCTION__, 'debug');
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
        $this->log(__FUNCTION__, 'debug');
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
        $query = $this->addHealthConsultationFields($query);
        $query = $this->addHealthFamiliesFields($query);
        $query = $this->addHealthImmunizationFields($query);
        $query = $this->addHealthMedicationFields($query);
        $query = $this->addHealthTestFields($query);
        $query = $this->addBodyMassFields($query);
        $query = $this->addGroupByUserIdCondition($query);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getNotSummaryQuery(Query $query = null)
    {
        $this->log(__FUNCTION__, 'debug');
        $query = $this->addUserBasicFields($query);
        $query = $this->addInstitutionFields($query);
        $query = $this->addAreaCondition($query);
        $query = $this->addEducationGradeField($query);
        $query = $this->addAreaField($query);
        $query = $this->addStudentIdentityTypeField($query);
        $query = $this->addStudentGenderField($query);
        $query = $this->addStudentClassField($query);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getOverviewQuery(Query $query = null)
    {
        $this->log(__FUNCTION__, 'debug');
        $query = $this->getNotSummaryQuery($query);
        $query = $this->addUserHealthFields($query);
        $query = $this->addGroupByUserIdCondition($query);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getAllergyQuery(Query $query = null)
    {
        $this->log(__FUNCTION__, 'debug');
        $query = $this->getNotSummaryQuery($query);
        $query = $this->addAllergyDetailedFields($query);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getConsultationQuery(Query $query = null)
    {
        $this->log(__FUNCTION__, 'debug');
        $query = $this->getNotSummaryQuery($query);
        $query = $this->addConsultationDetailedFields($query);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getFamilyQuery(Query $query = null)
    {
        $this->log(__FUNCTION__, 'debug');
        $query = $this->getNotSummaryQuery($query);
        $query = $this->addFamilyDetailedFields($query);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getHistoryQuery(Query $query = null)
    {
        $query = $this->getNotSummaryQuery($query);
        $query = $this->addHistoryDetailedFields($query);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getImmunizationQuery(Query $query = null)
    {
        $query = $this->getNotSummaryQuery($query);
        $query = $this->addFamilyDetailedFields($query);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getMedicationQuery(Query $query = null)
    {
        $query = $this->getNotSummaryQuery($query);
        $query = $this->addFamilyDetailedFields($query);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function getTestQuery(Query $query = null)
    {
        $query = $this->getNotSummaryQuery($query);
        $query = $this->addFamilyDetailedFields($query);
        return $query;
    }


    /**
     * @param Query $query
     * @return Query
     */
    private function getInsuranceQuery(Query $query = null)
    {
        $query = $this->getNotSummaryQuery($query);
        $query = $this->addFamilyDetailedFields($query);
        return $query;
    }


    /**
     * @param Query $query
     * @return Query
     */
    private function getBodyMassQuery(Query $query = null)
    {
        $query = $this->getNotSummaryQuery($query);
        $query = $this->addFamilyDetailedFields($query);
        return $query;
    }


    /**
     * @param Query $query
     * @return Query
     */
    private function addGroupByUserIdCondition(Query $query)
    {
        $query->group([$this->aliasField('student_id')]);
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
        ]);
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
        $this->log(__FUNCTION__, 'debug');
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
        $this->log(__FUNCTION__, 'debug');
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
        $this->log(__FUNCTION__, 'debug');
        if ($query) {
            $areaList = $this->area_list;
            if (!empty($areaList)) {
                $query
                    ->where(['Institutions.area_id IN' => $areaList])
                    ->orderAsc('Institutions.area_id');
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
        $this->log(__FUNCTION__, 'debug');
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
        $this->log(__FUNCTION__, 'debug');
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
        $this->log(__FUNCTION__, 'debug');
        $table = 'genders';
        $options = self::getRelatedOptions($table);
        $source_field = 'student_gender_id';
        $destination_field = 'student_gender';
        $query->select([$source_field => 'Users.gender_id']);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
        use ($options, $source_field, $destination_field) {
            return $results->map(function ($row) use ($options, $source_field, $destination_field) {
                if (isset($row[$source_field]) && isset($options[$row[$source_field]])) {
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
        $this->log(__FUNCTION__, 'debug');
        $options = self::getRelatedOptions('area_administratives');
        $source_field = 'student_birthplace_area_id';
        $destination_field = 'student_birthplace_area';
        $query->select([$source_field => 'Users.birthplace_area_id']);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
        use ($options, $source_field, $destination_field) {
            return $results->map(function ($row) use ($options, $source_field, $destination_field) {
                if (isset($row[$source_field]) && isset($options[$row[$source_field]])) {
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
        $this->log(__FUNCTION__, 'debug');
        $options = self::getRelatedOptions('area_administratives');
        $source_field = 'student_area_administrative_id';
        $destination_field = 'student_area_administrative';
        $query->select([$source_field => 'Institutions.area_administrative_id']);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
        use ($options, $source_field, $destination_field) {
            return $results->map(function ($row) use ($options, $source_field, $destination_field) {
                if (isset($row[$source_field]) && isset($options[$row[$source_field]])) {
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
        $this->log(__FUNCTION__, 'debug');
        $table = 'nationalities';
        $options = self::getRelatedOptions($table);
        $source_field = 'student_nationality_id';
        $destination_field = 'student_nationality';
        $query->select([$source_field => 'Users.nationality_id']);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results)
        use ($options, $source_field, $destination_field) {
            return $results->map(function ($row) use ($options, $source_field, $destination_field) {
                if (isset($row[$source_field]) && isset($options[$row[$source_field]])) {
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
        $this->log(__FUNCTION__, 'debug');
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
        $this->log(__FUNCTION__, 'debug');
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
        $this->log(__FUNCTION__, 'debug');
        if ($query) {
            $query->leftJoin(['Allergies' => 'user_health_allergies'], [
                'Allergies.security_user_id = ' . $this->aliasField('student_id')
            ])
                ->leftJoin(['AllergyTypes' => 'health_allergy_types'], [
                    'AllergyTypes.id = Allergies.health_allergy_type_id'
                ]);

            $query = $query->select([
                'allergy_count' => $query->func()->count('DISTINCT Allergies.id'),
                'allergy_severities' => $query->func()->group_concat(['DISTINCT Allergies.severe' => 'literal']),
                'allergy_types' => $query->func()->group_concat(['DISTINCT AllergyTypes.name' => 'literal']),
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
     * @param Query $query
     * @return Query
     */
    private function addAllergyDetailedFields(Query $query)
    {
        $this->log(__FUNCTION__, 'debug');
        if ($query) {
            $query->innerJoin(['Allergies' => 'user_health_allergies'], [
                'Allergies.security_user_id = ' . $this->aliasField('student_id')
            ])
                ->leftJoin(['AllergyTypes' => 'health_allergy_types'], [
                    'AllergyTypes.id = Allergies.health_allergy_type_id'
                ]);

            $query = $query->select([
                'allergy_type' => 'AllergyTypes.name',
                'allergy_severe' => 'Allergies.severe',
                'allergy_description' => 'Allergies.description',
                'allergy_comment' => 'Allergies.comment',
            ]);

            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    $allergy_severe = $row->allergy_severe;
                    $row['allergy_severe'] = str_replace(['1', '0'], ['Yes', 'No'], $allergy_severe);
                    return $row;
                });
            });

        }
        $this->extra_fields['allergy_type'] = [
            'key' => '',
            'field' => 'allergy_type',
            'type' => 'string',
            'label' => __('Allergy Type')
        ];
        $this->extra_fields['allergy_severe'] = [
            'key' => '',
            'field' => 'allergy_severe',
            'type' => 'string',
            'label' => __('Severity')
        ];
        $this->extra_fields['allergy_description'] = [
            'key' => '',
            'field' => 'allergy_description',
            'type' => 'string',
            'label' => __('Allergy Description')
        ];
        $this->extra_fields['allergy_comment'] = [
            'key' => '',
            'field' => 'allergy_comment',
            'type' => 'string',
            'label' => __('Allergy Comment')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addConsultationDetailedFields(Query $query)
    {
        $this->log(__FUNCTION__, 'debug');
        if ($query) {
            $query->innerJoin(['Consultations' => 'user_health_consultations'], [
                'Consultations.security_user_id = ' . $this->aliasField('student_id')
            ])
                ->leftJoin(['ConsultationsTypes' => 'health_consultation_types'], [
                    'ConsultationsTypes.id = Consultations.health_consultation_type_id'
                ]);

            $query = $query->select([
                'consultation_date' => 'Consultations.date',
                'consultation_type' => 'ConsultationsTypes.name',
                'consultation_description' => 'Consultations.description',
                'consultation_treatment' => 'Consultations.treatment',
            ]);

        }
        $this->extra_fields['consultation_date'] = [
            'key' => '',
            'field' => 'consultation_date',
            'type' => 'date',
            'label' => __('Consultation Date')
        ];
        $this->extra_fields['consultation_type'] = [
            'key' => '',
            'field' => 'consultation_type',
            'type' => 'string',
            'label' => __('Consultation Type')
        ];
        $this->extra_fields['consultation_description'] = [
            'key' => '',
            'field' => 'consultation_description',
            'type' => 'string',
            'label' => __('Consultation Description')
        ];
        $this->extra_fields['consultation_treatment'] = [
            'key' => '',
            'field' => 'consultation_treatment',
            'type' => 'string',
            'label' => __('Consultation Treatment')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addFamilyDetailedFields(Query $query)
    {
        $this->log(__FUNCTION__, 'debug');
        if ($query) {
            $allFamilies = TableRegistry::get('user_health_families');
            $FamilyDetailed = $allFamilies->find('all')
                ->select(['security_user_id' => 'security_user_id',
                    'family_current' => 'current',
                    'family_relationship' => 'Relationships.name',
                    'family_condition' => 'Conditions.name',
                    'family_comment' => 'comment',
                ])->leftJoin(
                    ['Relationships' => 'health_relationships'],
                    [
                        'Relationships.id = health_relationship_id'
                    ]
                )
                ->leftJoin(
                    ['Conditions' => 'health_conditions'],
                    [
                        'Conditions.id = health_condition_id'
                    ]
                );

            $query = $query->select([
                'family_current' => 'FamilyDetailed.family_current',
                'family_relationship' => 'FamilyDetailed.family_relationship',
                'family_condition' => 'FamilyDetailed.family_condition',
                'family_comment' => 'FamilyDetailed.family_comment',
            ])->innerJoin(['FamilyDetailed' => $FamilyDetailed],
                [$this->aliasField('student_id = ') . 'FamilyDetailed.security_user_id']);

        }
        $this->extra_fields['family_current'] = [
            'key' => '',
            'field' => 'family_current',
            'type' => 'string',
            'label' => __('Current (Family)')
        ];
        $this->extra_fields['family_relationship'] = [
            'key' => '',
            'field' => 'family_relationship',
            'type' => 'string',
            'label' => __('Family Relationship')
        ];
        $this->extra_fields['family_condition'] = [
            'key' => '',
            'field' => 'family_condition',
            'type' => 'string',
            'label' => __('Relationship Condition')
        ];
        $this->extra_fields['family_comment'] = [
            'key' => '',
            'field' => 'family_comment',
            'type' => 'string',
            'label' => __('Relationship Comment')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addHistoryDetailedFields(Query $query)
    {
        $this->log(__FUNCTION__, 'debug');
        if ($query) {
            $allHistories = TableRegistry::get('user_health_histories');
            $HistoryDetailed = $allHistories->find('all')
                ->select(['security_user_id' => 'security_user_id',
                    'history_current' => 'current',
                    'history_condition' => 'Conditions.name',
                    'history_comment' => 'comment',
                ])
                ->leftJoin(
                    ['Conditions' => 'health_conditions'],
                    [
                        'Conditions.id = health_condition_id'
                    ]
                );

            $query = $query->select([
                'history_current' => 'HistoryDetailed.history_current',
                'history_condition' => 'HistoryDetailed.history_condition',
                'history_comment' => 'HistoryDetailed.history_comment',
            ])->innerJoin(['HistoryDetailed' => $HistoryDetailed],
                [$this->aliasField('student_id = ') . 'HistoryDetailed.security_user_id']);

        }
        $this->extra_fields['history_current'] = [
            'key' => '',
            'field' => 'history_current',
            'type' => 'string',
            'label' => __('Current (Self)')
        ];
        $this->extra_fields['history_condition'] = [
            'key' => '',
            'field' => 'history_condition',
            'type' => 'string',
            'label' => __('Condition')
        ];
        $this->extra_fields['history_comment'] = [
            'key' => '',
            'field' => 'history_comment',
            'type' => 'string',
            'label' => __('Comment')
        ];
        return $query;
    }


    /**
     * @param Query $query
     * @return Query
     */
    private function addHealthConsultationFields(Query $query)
    {
        $this->log(__FUNCTION__, 'debug');
        if ($query) {
            $allConsultations = TableRegistry::get('user_health_consultations');
            $sumConsultations = $allConsultations->find('all')
                ->select(['security_user_id' => 'security_user_id',
                    'consultation_count' => $query->func()->count('DISTINCT user_health_consultations.id'),
                    'last_consultation_date' => $query->func()->max('user_health_consultations.date'),
                    'consultation_types' => $query->func()->group_concat(['DISTINCT ConsultationTypes.name' => 'literal']),
                ])->leftJoin(['ConsultationTypes' => 'health_consultation_types'], [
                    'ConsultationTypes.id = ' . $allConsultations->aliasField('health_consultation_type_id')
                ])
                ->group(['security_user_id']);

            $query = $query->select([
                'consultation_count' => 'sumConsultations.consultation_count',
                'consultation_types' => 'sumConsultations.consultation_types',
                'last_consultation_date' => 'sumConsultations.last_consultation_date',
            ])->leftJoin(['sumConsultations' => $sumConsultations],
                [$this->aliasField('student_id = ') . 'sumConsultations.security_user_id']);

            $query->leftJoin(['LastConsultations' => 'user_health_consultations'], [
                'LastConsultations.security_user_id = ' . $this->aliasField('student_id'),
                'LastConsultations.date = sumConsultations.last_consultation_date'
            ])
                ->leftJoin(['LastConsultationTypes' => 'health_consultation_types'], [
                    'LastConsultationTypes.id = LastConsultations.health_consultation_type_id'
                ]);

            $query = $query->select([
                'last_consultation_type' => $query->func()->max('LastConsultationTypes.name'),
            ]);


        }
        $this->extra_fields['consultation_count'] = [
            'key' => '',
            'field' => 'consultation_count',
            'type' => 'string',
            'label' => __('Consultation Count')
        ];
        $this->extra_fields['consultation_types'] = [
            'key' => '',
            'field' => 'consultation_types',
            'type' => 'string',
            'label' => __('Consultation Types')
        ];
        $this->extra_fields['last_consultation_date'] = [
            'key' => '',
            'field' => 'last_consultation_date',
            'type' => 'date',
            'label' => __('Last Consultation Date')
        ];
        $this->extra_fields['last_consultation_type'] = [
            'key' => '',
            'field' => 'last_consultation_type',
            'type' => 'string',
            'label' => __('Last Consultation Type')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addHealthFamiliesFields(Query $query)
    {
        $this->log(__FUNCTION__, 'debug');
        if ($query) {
            $allFamilies = TableRegistry::get('user_health_families');
            $sumFamilies = $allFamilies->find('all')
                ->select(['security_user_id' => 'security_user_id',
                    'health_relationships' => $query->func()->group_concat(['DISTINCT HealthRelationships.name' => 'literal']),
                    'health_conditions' => $query->func()->group_concat(['DISTINCT HealthConditions.name' => 'literal']),
                ])->leftJoin(
                    ['HealthRelationships' => 'health_relationships'],
                    [
                        'HealthRelationships.id = health_relationship_id'
                    ]
                )
                ->leftJoin(
                    ['HealthConditions' => 'health_conditions'],
                    [
                        'HealthConditions.id = health_condition_id'
                    ]
                )
                ->group(['security_user_id']);

            $query = $query->select([
                'health_relationships' => 'sumFamilies.health_relationships',
                'health_conditions' => 'sumFamilies.health_conditions',
            ])->leftJoin(['sumFamilies' => $sumFamilies],
                [$this->aliasField('student_id = ') . 'sumFamilies.security_user_id']);

        }
        $this->extra_fields['health_relationships'] = [
            'key' => '',
            'field' => 'health_relationships',
            'type' => 'string',
            'label' => __('Health Relationships')
        ];
        $this->extra_fields['health_conditions'] = [
            'key' => '',
            'field' => 'health_conditions',
            'type' => 'string',
            'label' => __('Health Conditions')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addHealthImmunizationFields(Query $query)
    {
        if ($query) {
            $allImmunizations = TableRegistry::get('user_health_immunizations');
            $sumImmunizations = $allImmunizations->find('all')
                ->select(['security_user_id' => 'security_user_id',
                    'immunization_count' => $query->func()->count('DISTINCT user_health_immunizations.id'),
                    'last_immunization_date' => $query->func()->max('user_health_immunizations.date'),
                    'health_immunizations' => $query->func()->group_concat(['DISTINCT HealthImmunizationTypes.name' => 'literal']),

                ])->leftJoin(
                    ['HealthImmunizationTypes' => 'health_immunization_types'],
                    [
                        'HealthImmunizationTypes.id = health_immunization_type_id'
                    ]
                )->group(['security_user_id']);

            $query = $query->select([
                'health_immunizations' => 'sumImmunizations.health_immunizations',
                'immunization_count' => 'sumImmunizations.immunization_count',
                'last_immunization_date' => 'sumImmunizations.last_immunization_date',
            ])->leftJoin(['sumImmunizations' => $sumImmunizations],
                [$this->aliasField('student_id = ') . 'sumImmunizations.security_user_id']);

            $query->leftJoin(['LastImmunizations' => 'user_health_immunizations'], [
                'LastImmunizations.security_user_id = ' . $this->aliasField('student_id'),
                'LastImmunizations.date = sumImmunizations.last_immunization_date'
            ])
                ->leftJoin(['LastImmunizationTypes' => 'health_immunization_types'], [
                    'LastImmunizationTypes.id = LastImmunizations.health_immunization_type_id'
                ]);

            $query = $query->select([
                'last_immunization_type' => $query->func()->max('LastImmunizationTypes.name'),
            ]);


        }
        $this->extra_fields['health_immunizations'] = [
            'key' => '',
            'field' => 'health_immunizations',
            'type' => 'string',
            'label' => __('Vaccinations')
        ];
        $this->extra_fields['immunization_count'] = [
            'key' => '',
            'field' => 'immunization_count',
            'type' => 'string',
            'label' => __('Vaccination Count')
        ];
        $this->extra_fields['last_immunization_date'] = [
            'key' => '',
            'field' => 'last_immunization_date',
            'type' => 'date',
            'label' => __('Last Vaccination Date')
        ];
        $this->extra_fields['last_immunization_type'] = [
            'key' => '',
            'field' => 'last_immunization_type',
            'type' => 'string',
            'label' => __('Last Vaccination Type')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addHealthMedicationFields(Query $query)
    {
        if ($query) {
            $allMedications = TableRegistry::get('user_health_medications');
            $sumMedications = $allMedications->find('all')
                ->select(['security_user_id' => 'security_user_id',
                    'medication_count' => $query->func()->count('DISTINCT user_health_medications.id'),
                    'last_medication_date' => $query->func()->max('user_health_medications.start_date'),

                ])->group(['security_user_id']);

            $query = $query->select([
                'medication_count' => 'sumMedications.medication_count',
                'last_medication_date' => 'sumMedications.last_medication_date',
            ])->leftJoin(['sumMedications' => $sumMedications],
                [$this->aliasField('student_id = ') . 'sumMedications.security_user_id']);

            $query->leftJoin(['LastMedications' => 'user_health_medications'], [
                'LastMedications.security_user_id = ' . $this->aliasField('student_id'),
                'LastMedications.start_date = sumMedications.last_medication_date'
            ]);

            $query = $query->select([
                'last_medication_name' => $query->func()->max('LastMedications.name'),
            ]);


        }
        $this->extra_fields['health_medications'] = [
            'key' => '',
            'field' => 'health_medications',
            'type' => 'string',
            'label' => __('Medications')
        ];
        $this->extra_fields['medication_count'] = [
            'key' => '',
            'field' => 'medication_count',
            'type' => 'string',
            'label' => __('Medication Count')
        ];
        $this->extra_fields['last_medication_date'] = [
            'key' => '',
            'field' => 'last_medication_date',
            'type' => 'date',
            'label' => __('Last Medication Start Date')
        ];

        $this->extra_fields['last_medication_name'] = [
            'key' => '',
            'field' => 'last_medication_name',
            'type' => 'string',
            'label' => __('Last Medication Name')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addHealthTestFields(Query $query)
    {
        if ($query) {
            $allTests = TableRegistry::get('user_health_tests');
            $sumTests = $allTests->find('all')
                ->select(['security_user_id' => 'security_user_id',
                    'test_count' => $query->func()->count('DISTINCT user_health_tests.id'),
                    'last_test_date' => $query->func()->max('user_health_tests.date'),
                    'health_tests' => $query->func()->group_concat(['DISTINCT HealthTestTypes.name' => 'literal']),
                ])->leftJoin(
                    ['HealthTestTypes' => 'health_test_types'],
                    [
                        'HealthTestTypes.id = health_test_type_id'
                    ]
                )->group(['security_user_id']);

            $query = $query->select([
                'health_tests' => 'sumTests.health_tests',
                'test_count' => 'sumTests.test_count',
                'last_test_date' => 'sumTests.last_test_date',
            ])->leftJoin(['sumTests' => $sumTests],
                [$this->aliasField('student_id = ') . 'sumTests.security_user_id']);

            $query->leftJoin(['LastTests' => 'user_health_tests'], [
                'LastTests.security_user_id = ' . $this->aliasField('student_id'),
                'LastTests.date = sumTests.last_test_date'
            ])
                ->leftJoin(['LastTestTypes' => 'health_test_types'], [
                    'LastTestTypes.id = LastTests.health_test_type_id'
                ]);

            $query = $query->select([
                'last_test_type' => $query->func()->max('LastTestTypes.name'),
            ]);


        }
        $this->extra_fields['health_tests'] = [
            'key' => '',
            'field' => 'health_tests',
            'type' => 'string',
            'label' => __('Health Tests')
        ];
        $this->extra_fields['test_count'] = [
            'key' => '',
            'field' => 'test_count',
            'type' => 'string',
            'label' => __('Health Test Count')
        ];
        $this->extra_fields['last_test_date'] = [
            'key' => '',
            'field' => 'last_test_date',
            'type' => 'date',
            'label' => __('Last Health Test Date')
        ];
        $this->extra_fields['last_test_type'] = [
            'key' => '',
            'field' => 'last_test_type',
            'type' => 'string',
            'label' => __('Last Health Test Type')
        ];
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addBodyMassFields(Query $query)
    {
        if ($query) {
            $academic_period_id = $this->academic_period_id;
            $allBodyMasses = TableRegistry::get('user_body_masses');
            $sumBodyMasses = $allBodyMasses->find('all')
                ->select(['security_user_id' => 'security_user_id',
                    'body_mass_count' => $query->func()->count('DISTINCT user_body_masses.id'),
                    'last_body_mass_date' => $query->func()->max('user_body_masses.date'),
                ])
                ->where(['user_body_masses.academic_period_id' => $academic_period_id])
                ->group(['security_user_id']);

            $query = $query->select([
                'body_mass_count' => 'sumBodyMasses.body_mass_count',
                'last_body_mass_date' => 'sumBodyMasses.last_body_mass_date',
            ])->leftJoin(['sumBodyMasses' => $sumBodyMasses],
                [$this->aliasField('student_id = ') . 'sumBodyMasses.security_user_id']);

            $query->leftJoin(['LastBodyMasses' => 'user_body_masses'], [
                'LastBodyMasses.security_user_id = ' . $this->aliasField('student_id'),
                'LastBodyMasses.date = sumBodyMasses.last_body_mass_date'
            ]);

            $query = $query->select([
                'last_body_mass_height' => $query->func()->max('LastBodyMasses.height'),
                'last_body_mass_weight' => $query->func()->max('LastBodyMasses.weight'),
                'last_body_mass_index' => $query->func()->max('LastBodyMasses.body_mass_index'),
            ]);


        }
        $this->extra_fields['body_mass_count'] = [
            'key' => '',
            'field' => 'body_mass_count',
            'type' => 'string',
            'label' => __('Body Mass Count')
        ];
        $this->extra_fields['last_body_mass_date'] = [
            'key' => '',
            'field' => 'last_body_mass_date',
            'type' => 'date',
            'label' => __('Last Body Mass Date')
        ];
        $this->extra_fields['last_body_mass_height'] = [
            'key' => '',
            'field' => 'last_body_mass_height',
            'type' => 'string',
            'label' => __('Last Measured Height')
        ];
        $this->extra_fields['last_body_mass_weight'] = [
            'key' => '',
            'field' => 'last_body_mass_weight',
            'type' => 'string',
            'label' => __('Last Measured Weight')
        ];
        $this->extra_fields['last_body_mass_index'] = [
            'key' => '',
            'field' => 'last_body_mass_index',
            'type' => 'string',
            'label' => __('Last Measured Body Mass Index')
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
