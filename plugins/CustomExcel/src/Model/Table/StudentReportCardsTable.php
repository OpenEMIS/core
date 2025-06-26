<?php

namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use App\Model\Table\AppTable;
use Cake\Http\ServerRequest;
use Cake\Datasource\ConnectionManager;//POCOR-8658

class StudentReportCardsTable extends AppTable
{
    private $fileType = 'xlsx';
    //private $fileType = 'pdf';

    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id', 'joinType' => 'INNER']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id', 'joinType' => 'INNER']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->addBehavior('CustomExcel.StudentExcelReport', [
            'templateTable' => 'ProfileTemplate.StudentTemplates',
            'templateTableKey' => 'student_profile_template_id',
            'format' => $this->fileType,
            'download' => false,
            'wrapText' => true,
            'lockSheets' => true,
            'variables' => [
                'Profiles',
                'Institutions',
                'StudentUsers',
                'StudentDemographics',
                'StudentContacts',
                'StudentNationalities',
                'StudentAreas',
                'StudentRisks',
                'StudentClasses',
                'StudentSubjects',
                'StudentExtracurriculars',
                'StudentAwards',
                'StudentBehaviours',
                'StudentAbsences',
                'StudentTotalAbsences',
                'StudentCounsellings',
                'StudentHealths',
                'StudentHealthConsultations',
                'StudentGuardians',
                'StudentHouses',
                'UserSpecialNeedsAssessments', //6680
                'UserContacts', //6680
                'StudentMoterDetails', //6680
                'InstitutionSubjectStudentsWithName', //POCOR-7316
                'AssessmentPeriods', //POCOR-7316
                'AssessmentItemResults', //POCOR-7316
                'InstitutionStudentGradeGpa', //POCOR-8222
                'CompetencyTemplates', //POCOR-8878
                'CompetencyPeriods', //POCOR-8878
                'CompetencyItems', //POCOR-8878
                'Assessments', //POCOR-8878
                'AssessmentPeriods', //POCOR-8878
                'SubjectTeacher', //POCOR-8878
                'OutcomeTemplates',
                'OutcomePeriods',
                'OutcomeSubjects',
                'StudentOutcomeSubjectComments',
                'OutcomeCriterias',
                'ClassAndLevelRanking'//POCOR-8658
            ]
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onExcelTemplateBeforeGenerate'] = 'onExcelTemplateBeforeGenerate';
        $events['ExcelTemplates.Model.onExcelTemplateAfterGenerate'] = 'onExcelTemplateAfterGenerate';
        $events['ExcelTemplates.Model.afterRenderExcelTemplate'] = 'afterRenderExcelTemplate';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseProfiles'] = 'onExcelTemplateInitialiseProfiles';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutions'] = 'onExcelTemplateInitialiseInstitutions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentUsers'] = 'onExcelTemplateInitialiseStudentUsers';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentDemographics'] = 'onExcelTemplateInitialiseStudentDemographics';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentContacts'] = 'onExcelTemplateInitialiseStudentContacts';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentNationalities'] = 'onExcelTemplateInitialiseStudentNationalities';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentAreas'] = 'onExcelTemplateInitialiseStudentAreas';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentRisks'] = 'onExcelTemplateInitialiseStudentRisks';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentClasses'] = 'onExcelTemplateInitialiseStudentClasses';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentSubjects'] = 'onExcelTemplateInitialiseStudentSubjects';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentExtracurriculars'] = 'onExcelTemplateInitialiseStudentExtracurriculars';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentAwards'] = 'onExcelTemplateInitialiseStudentAwards';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentBehaviours'] = 'onExcelTemplateInitialiseStudentBehaviours';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentAbsences'] = 'onExcelTemplateInitialiseStudentAbsences';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentTotalAbsences'] = 'onExcelTemplateInitialiseStudentTotalAbsences';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentCounsellings'] = 'onExcelTemplateInitialiseStudentCounsellings';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentHealths'] = 'onExcelTemplateInitialiseStudentHealths';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentHealthConsultations'] = 'onExcelTemplateInitialiseStudentHealthConsultations';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentGuardians'] = 'onExcelTemplateInitialiseStudentGuardians';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentHouses'] = 'onExcelTemplateInitialiseStudentHouses';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseUserSpecialNeedsAssessments'] = 'onExcelTemplateInitialiseUserSpecialNeedsAssessments'; //6680
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseUserContacts'] = 'onExcelTemplateInitialiseUserContacts'; //6680
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentMoterDetails'] = 'onExcelTemplateInitialiseStudentMoterDetails'; //6680
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionSubjectStudentsWithName'] = 'onExcelTemplateInitialiseInstitutionSubjectStudentsWithName'; //POCOR-7316
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentPeriods'] = 'onExcelTemplateInitialiseAssessmentPeriods'; //POCOR-7316
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItemResults'] = 'onExcelTemplateInitialiseAssessmentItemResults'; //POCOR-7316
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentGradeGpa'] = 'onExcelTemplateInitialiseInstitutionStudentGradeGpa'; //POCOR-8222

        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyTemplates'] = 'onExcelTemplateInitialiseCompetencyTemplates';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyPeriods'] = 'onExcelTemplateInitialiseCompetencyPeriods';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyItems'] = 'onExcelTemplateInitialiseCompetencyItems';

        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessments'] = 'onExcelTemplateInitialiseAssessments';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseSubjectTeacher'] = 'onExcelTemplateInitialiseSubjectTeacher';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseOutcomeTemplates'] = 'onExcelTemplateInitialiseOutcomeTemplates';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseOutcomePeriods'] = 'onExcelTemplateInitialiseOutcomePeriods';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseOutcomeSubjects'] = 'onExcelTemplateInitialiseOutcomeSubjects';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentOutcomeSubjectComments'] = 'onExcelTemplateInitialiseStudentOutcomeSubjectComments';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseOutcomeCriterias'] = 'onExcelTemplateInitialiseOutcomeCriterias';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseClassAndLevelRanking'] = 'onExcelTemplateInitialiseClassAndLevelRanking';//POCOR-8658
        return $events;
    }

    public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $InstitutionStudentsProfileTemplates = TableRegistry::get('Institution.InstitutionStudentsProfileTemplates');
        if (!$InstitutionStudentsProfileTemplates->exists($params)) {
            // insert staff report card record if it does not exist
            $params['status'] = $InstitutionStudentsProfileTemplates::IN_PROGRESS;
            $params['started_on'] = date('Y-m-d H:i:s');
            $newEntity = $InstitutionStudentsProfileTemplates->newEntity($params);
            $InstitutionStudentsProfileTemplates->save($newEntity);
        } else {
            // update status to in progress if record exists
            $InstitutionStudentsProfileTemplates->updateAll([
                'status' => $InstitutionStudentsProfileTemplates::IN_PROGRESS,
                'started_on' => date('Y-m-d H:i:s')
            ], $params);
        }
    }

    public function onExcelTemplateAfterGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $InstitutionStudentsProfileTemplates = TableRegistry::get('Institution.InstitutionStudentsProfileTemplates');
        $StudentReportCardData = $InstitutionStudentsProfileTemplates
            ->find()
            ->select([
                $InstitutionStudentsProfileTemplates->aliasField('academic_period_id'),
                $InstitutionStudentsProfileTemplates->aliasField('student_id'),
                $InstitutionStudentsProfileTemplates->aliasField('institution_id'),
                $InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id')
            ])
            ->contain([
                'AcademicPeriods' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'StudentTemplates' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'Students' => [
                    'fields' => [
                        'openemis_no',
                        'first_name',
                        'middle_name',
                        'third_name',
                        'last_name',
                        'preferred_name'
                    ]
                ]
            ])
            ->where([
                $InstitutionStudentsProfileTemplates->aliasField('academic_period_id') => $params['academic_period_id'],
                $InstitutionStudentsProfileTemplates->aliasField('institution_id') => $params['institution_id'],
                $InstitutionStudentsProfileTemplates->aliasField('student_profile_template_id') => $params['student_profile_template_id'],
                $InstitutionStudentsProfileTemplates->aliasField('student_id') => $params['student_id'],
                $InstitutionStudentsProfileTemplates->aliasField('education_grade_id') => $params['education_grade_id'],
            ])
            ->first();

        // set filename
        $fileName = $StudentReportCardData->institution->code . '_' . $StudentReportCardData->student_template->code . '_' . $StudentReportCardData->student->openemis_no . '_' . $StudentReportCardData->student->name . '.' . $this->fileType;
        $filepath = $extra['file_path'];
        $fileContent = file_get_contents($filepath);
        $status = $InstitutionStudentsProfileTemplates::GENERATED;

        // save file
        $InstitutionStudentsProfileTemplates->updateAll([
            'status' => $status,
            'completed_on' => date('Y-m-d H:i:s'),
            'file_name' => $fileName,
            'file_content' => $fileContent
        ], $params);

        // delete staff report card process
        $StudentReportCardProcesses = TableRegistry::Get('ReportCard.StudentReportCardProcesses');
        $StudentReportCardProcesses->deleteAll([
            'student_profile_template_id' => $params['student_profile_template_id'],
            'institution_id' => $params['institution_id'],
            'education_grade_id' => $params['education_grade_id'],
            'student_id' => $params['student_id']
        ]);
    }

    public function afterRenderExcelTemplate(Event $event, ArrayObject $extra, $controller)
    {
        $params = $extra['params'];
        $url = [
            'plugin' => 'ProfileTemplate',
            'controller' => 'ProfileTemplates',
            'action' => 'StudentProfiles',
            'index',
            'institution_id' => $params['institution_id'],
            'education_grade_id' => $params['education_grade_id'],
            'student_profile_template_id' => $params['student_profile_template_id'],
            'academic_period_id' => $params['academic_period_id']
        ];

        $event->stopPropagation();
        return $controller->redirect($url);
    }

    public function onExcelTemplateInitialiseProfiles(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['student_profile_template_id'])) {
            $StudentTemplates = TableRegistry::get('ProfileTemplate.StudentTemplates');
            $entity = $StudentTemplates->get($params['student_profile_template_id'], ['contain' => ['AcademicPeriods']]);

            $extra['report_card_start_date'] = $entity->start_date;
            $extra['report_card_end_date'] = $entity->end_date;

            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutions(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id'])) {
            $Institutions = TableRegistry::get('Institution.Institutions');
            $entity = $Institutions->get($params['institution_id'], ['contain' => ['AreaAdministratives', 'Types']]);
            //POCOR-7316 start
            $result = [];
            $result = [
                'name' => $entity->name,
                'address' => $entity->address,
                'contact' => $entity->telephone,
                'area' => $entity['area_administrative']->name,

            ];
            return $result;
            //POCOR-7316 end

        }
    }

    public function onExcelTemplateInitialiseStudentUsers(Event $event, array $params, ArrayObject $extra)
    {

        if (isset($params['institution_id']) && isset($params['education_grade_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $Student = TableRegistry::get('Institution.InstitutionClassStudents');

            $entity = $Student
                ->find()
                ->select([
                    'id' => 'Users.id', //6680
                    'address_area_id' => 'Users.address_area_id', //6680
                    'first_name' => 'Users.first_name',
                    'last_name' => 'Users.last_name',
                    'middle_name' => 'Users.middle_name', //POCOR-7316
                    'third_name' => 'Users.third_name', //POCOR-7316
                    'email' => 'Users.email',
                    'photo_content' => 'Users.photo_content',
                    'address' => 'Users.address',
                    'date_of_birth' => 'Users.date_of_birth',
                    'identity_number' => 'Users.identity_number',
                    'gender' => 'Genders.name',
                    'openemis_no' => 'Users.openemis_no', //add openemis_no in report POCOR-6321
                ])
                /*->contain([
                    'Users' => [
                        'fields' => [
                            'identity_number',
                            'first_name',
                            'last_name',
                            'middle_name',//POCOR_7316
                            'third_name',//POCOR-7316
                            'photo_content',
                            'email',
                            'address',
                            'date_of_birth',
                            'openemis_no',//add openemis_no in report POCOR-6321
                        ]
                    ]
                ])
                ->matching('Users.Genders')*/
                ->join([
                    'Users' => [
                        'table' => 'security_users',
                        'type' => 'INNER',
                        'conditions' => 'Users.id = InstitutionClassStudents.student_id'
                    ],
                    'Genders' => [
                        'table' => 'genders',
                        'type' => 'INNER',
                        'conditions' => 'Genders.id = Users.gender_id'
                    ]
                ])
                ->where([
                    $Student->aliasField('institution_id') => $params['institution_id'],
                    $Student->aliasField('academic_period_id') => $params['academic_period_id'],
                    $Student->aliasField('education_grade_id') => $params['education_grade_id'],
                    $Student->aliasField('student_id') => $params['student_id'],

                ])
                ->first();
            //6680 starts
            $identity_number_value = '';
            if (!empty($entity)) {
                $UserIdentities = TableRegistry::get('User.Identities');
                $UserIdentitiesEntity = $UserIdentities
                    ->find()
                    ->select([
                        'id' => $UserIdentities->aliasField('id'),
                        'number' => $UserIdentities->aliasField('number'),
                        'name' => 'IdentityTypes.name',
                    ])
                    ->innerJoin(
                        [' IdentityTypes' => ' identity_types'],
                        [
                            'IdentityTypes.id =' . $UserIdentities->aliasField('identity_type_id')
                        ]
                    )
                    ->where([
                        $UserIdentities->aliasField('security_user_id') => $entity->id,
                    ])
                    ->first();

                if (!empty($UserIdentitiesEntity)) {
                    $identity_number_value = $UserIdentitiesEntity->name . ' { ' . $UserIdentitiesEntity->number . ' } ';
                }

                $area_name = '';
                if (!empty($entity->address_area_id)) {
                    $selectedArea = $entity->address_area_id;
                    $areaIds = [];
                    $allgetArea = $this->getParent($selectedArea, $areaIds);

                    $selectedArea1[] = $selectedArea;
                    if (!empty($allgetArea)) {
                        $allselectedAreas = array_merge($selectedArea1, $allgetArea);
                    } else {
                        $allselectedAreas = $selectedArea1;
                    }

                    $Areas = TableRegistry::get('Area.AreaAdministratives');
                    $AreasRecords = $Areas
                        ->find()->select([$Areas->aliasField('name')])
                        ->where([$Areas->aliasField('id IN') => $allselectedAreas])
                        ->disableHydration() // POCOR-8533
                        ->order([$Areas->aliasField('id DESC')])->toArray();
                    if (!empty($AreasRecords)) {
                        $area_name_array = [];
                        foreach ($AreasRecords as $key => $value) {
                            $area_name_array[$key] = $value['name'];
                        }
                        $area_name = implode(' / ', $area_name_array);
                    }
                }
            }
            $result = [];
            $result = [
                'name' => $entity->first_name . ' ' . $entity->last_name,
                'first_name' => $entity->first_name, //POCOR_7316
                'last_name' => $entity->last_name, //POCOR_7316
                'middle_name' => $entity->middle_name, //POCOR_7316
                'third_name' => $entity->third_name, //POCOR_7316
                'identity_number' => $identity_number_value,
                'photo_content' => $entity->photo_content,
                'email' => $entity->email,
                'address' => $entity->address,
                'date_of_birth' => $entity->date_of_birth,
                'gender' => $entity->gender,
                'openemis_no' => $entity->openemis_no, //add openemis_no in report POCOR-6321
                'age' => date_diff(date_create($entity->date_of_birth), date_create('today'))->y . ' Year',
                'permanent_address' => $area_name,
            ]; //6680 ends

            return $result;
        }
    }
    //6680 starts
    public function getParent($id, $idArray)
    {
        $Areas = TableRegistry::get('Area.AreaAdministratives');
        $result = $Areas->find()->where([$Areas->aliasField('id') => $id])->toArray();
        foreach ($result as $key => $value) {
            $idArray[] = $value['parent_id'];
            $idArray = $this->getParent($value['parent_id'], $idArray);
        }
        return $idArray;
    } //6680 ends

    public function onExcelTemplateInitialiseStudentDemographics(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['education_grade_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $Student = TableRegistry::get('Institution.InstitutionClassStudents');

            $entity = $Student
                ->find()
                ->select([
                    'demographic_type_name' => 'DemographicTypes.name',
                ])
                ->innerJoin(
                    ['UserDemographics' => 'user_demographics'],
                    [
                        'UserDemographics.security_user_id =' . $Student->aliasField('student_id')
                    ]
                )
                ->leftJoin(
                    ['DemographicTypes' => 'demographic_types'],
                    [
                        'DemographicTypes.id = UserDemographics.demographic_types_id'
                    ]
                )
                ->where([
                    $Student->aliasField('institution_id') => $params['institution_id'],
                    $Student->aliasField('academic_period_id') => $params['academic_period_id'],
                    $Student->aliasField('education_grade_id') => $params['education_grade_id'],
                    $Student->aliasField('student_id') => $params['student_id'],
                ])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentContacts(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $UserContacts = TableRegistry::get('User.Contacts');

            $entity = $UserContacts
                ->find()
                ->select([
                    'contact' => $UserContacts->aliasField('value'),
                ])
                ->where([
                    $UserContacts->aliasField('security_user_id') => $params['student_id'],
                    $UserContacts->aliasField('preferred') => 1,
                ])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentNationalities(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $UserNationalities = TableRegistry::get('User.UserNationalities');

            $entity = $UserNationalities
                ->find()
                ->select([
                    'name' => 'Nationalities.name',
                ])
                ->innerJoin(
                    ['Nationalities' => 'nationalities'],
                    [
                        'Nationalities.id =' . $UserNationalities->aliasField('nationality_id')
                    ]
                )
                ->where([
                    $UserNationalities->aliasField('security_user_id') => $params['student_id'],
                ])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentAreas(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $SecurityUsers = TableRegistry::get('security_users');

            $entity = $SecurityUsers
                ->find()
                ->select([
                    'area_administrative_name' => 'AreaAdministratives.name',
                    'area_administrative_level' => 'AreaAdministrativeLevels.name',
                ])
                ->innerJoin(
                    ['AreaAdministratives' => 'area_administratives'],
                    [
                        'AreaAdministratives.id =' . $SecurityUsers->aliasField('address_area_id')
                    ]
                )
                ->innerJoin(
                    ['AreaAdministrativeLevels' => 'area_administrative_levels'],
                    [
                        'AreaAdministrativeLevels.id = AreaAdministratives.area_administrative_level_id'
                    ]
                )
                ->where([
                    $SecurityUsers->aliasField('id') => $params['student_id'],
                ])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentRisks(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $InstitutionStudentRisks = TableRegistry::get('Institution.InstitutionStudentRisks');
            $StudentRisksCriterias = TableRegistry::get('Institution.StudentRisksCriterias');

            $InstitutionStudentRiskData = $InstitutionStudentRisks
                ->find()
                ->select([
                    'id' => $InstitutionStudentRisks->aliasField('id'),
                    'total_risk' => $InstitutionStudentRisks->aliasField('total_risk')
                ])
                ->where([
                    $InstitutionStudentRisks->aliasField('student_id') => $params['student_id'],
                ])
                ->toArray();

            $entity = [];
            foreach ($InstitutionStudentRiskData as $value) {
                $studentRisksCriteriasResults = $StudentRisksCriterias->find()
                    ->select([
                        'criteria' => 'RiskCriterias.criteria',
                    ])
                    ->contain(['RiskCriterias'])
                    ->where([
                        $StudentRisksCriterias->aliasField('institution_student_risk_id') => $value->id,
                        $StudentRisksCriterias->aliasField('value') . ' IS NOT NULL'
                    ])
                    ->toArray();
                $criteriaArray = [];
                $criteria = '';
                foreach ($studentRisksCriteriasResults as $data) {
                    $criteriaArray[] = $data->criteria;
                }
                $criteria = implode(",", $criteriaArray);
                $entity[] = [
                    'id' => $value->id,
                    'total_risk' => $value->total_risk,
                    'criteria' => $criteria,
                ];
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentClasses(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['education_grade_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $StudentRisksCriterias = TableRegistry::get('Institution.StudentRisksCriterias');
            $InstitutionStudentProgrammes = TableRegistry::get('Student.InstitutionStudentProgrammes');

            $entity = $InstitutionClassStudents
                ->find()
                ->select([
                    'id' => $InstitutionClassStudents->aliasField('id'),
                    'name' => 'InstitutionClasses.name',
                    'education_grade' => 'EducationGrades.name',
                    'academic_period' => 'AcademicPeriods.name',
                    'start_date' => 'InstitutionStudents.start_date',
                    'end_date' => 'InstitutionStudents.end_date',
                    'status' => 'StudentStatuses.name',
                    'student_candidate_number' => 'InstitutionStudentProgrammes.registration_number', //POCOR-8871
                ])
                ->contain(['InstitutionClasses', 'EducationGrades', 'AcademicPeriods', 'StudentStatuses'])
                ->innerJoin(
                    ['InstitutionStudents' => 'institution_students'],
                    [
                        'InstitutionStudents.student_id =' . $InstitutionClassStudents->aliasField('student_id'),
                        'InstitutionStudents.academic_period_id =' . $InstitutionClassStudents->aliasField('academic_period_id'),
                        'InstitutionStudents.education_grade_id =' . $InstitutionClassStudents->aliasField('education_grade_id')
                    ]
                )
                //POCOR-8871 start
                ->innerJoin(
                    ['StudentEducationGrades' => 'education_grades'],
                    [
                        'StudentEducationGrades.id =' . $InstitutionClassStudents->aliasField('education_grade_id'),
                    ]
                )
                ->leftJoin(
                    ['InstitutionStudentProgrammes' => 'institution_student_programmes'],
                    [
                        'InstitutionStudentProgrammes.student_id =' . $InstitutionClassStudents->aliasField('student_id'),
                        'InstitutionStudentProgrammes.institution_id =' . $InstitutionClassStudents->aliasField('institution_id'),
                        'InstitutionStudentProgrammes.education_programme_id = StudentEducationGrades.education_programme_id'
                    ]
                )
                //POCOR-8871 end
                ->where([
                    $InstitutionClassStudents->aliasField('student_id') => $params['student_id'],
                    //$InstitutionClassStudents->aliasField('academic_period_id') => $params['academic_period_id'],//POCOR-5191
                    //$InstitutionClassStudents->aliasField('education_grade_id') => $params['education_grade_id'],//POCOR-5191
                    $InstitutionClassStudents->aliasField('institution_id') => $params['institution_id'],
                ])
                ->order(['InstitutionStudents.end_date' => 'DESC'])
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentSubjects(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['education_grade_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $InstitutionSubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');

            $entity = $InstitutionSubjectStudents
                ->find()
                ->select([
                    'id' => 'InstitutionSubjects.id',
                    'name' => 'InstitutionSubjects.name',
                ])
                ->join([
                    'InstitutionSubjects' => [
                        'table' => 'institution_subjects',
                        'type' => 'INNER',
                        'conditions' => 'InstitutionSubjectStudents.institution_subject_id = InstitutionSubjects.id'
                    ]
                ])
                ->where([
                    $InstitutionSubjectStudents->aliasField('student_id') => $params['student_id'],
                    $InstitutionSubjectStudents->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionSubjectStudents->aliasField('education_grade_id') => $params['education_grade_id'],
                    $InstitutionSubjectStudents->aliasField('institution_id') => $params['institution_id'],
                ])
                ->toArray();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentExtracurriculars(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['academic_period_id']) && isset($params['student_id'])) {
            $Extracurriculars = TableRegistry::getTableLocator()->get('Student.StudentExtracurriculars');

            $entity = $Extracurriculars
                ->find('all')
                ->select([
                    'id' => 'StudentExtracurriculars.id',
                    'name' => 'StudentExtracurriculars.name',
                ])
                ->where([
                    'StudentExtracurriculars.security_user_id' => $params['student_id'],
                    'StudentExtracurriculars.academic_period_id' => $params['academic_period_id'],
                ])
                ->toArray();
            return $entity;
        }
    }
    public function onExcelTemplateInitialiseStudentAwards(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['student_id'])) {
            $UserAwards = TableRegistry::get('user_awards');

            $result = $UserAwards
                ->find()
                ->select([
                    'id' => $UserAwards->aliasField('id'),
                    'award' => $UserAwards->aliasField('award'), //POCOR-7316
                    'date' => $UserAwards->aliasField('issue_date') //POCOR-7316
                ])
                ->where([
                    $UserAwards->aliasField('security_user_id') => $params['student_id'],
                ])
                ->toArray();
            //POCOR-7316 starts
            $entity = [];
            $i = 1;
            foreach ($result as $row) {
                $entity[] = [
                    'id' => $i,
                    'name' => $row['award'],
                    'date' => $row['date']
                ];
                $i++;
            }
            //POCOR-7316 ends
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentBehaviours(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $StudentBehaviours = TableRegistry::get('student_behaviours');
            $StaffUser = TableRegistry::get('User.Users'); //POCOR-5191
            $entity = $StudentBehaviours
                ->find()
                ->select([
                    'id' => $StudentBehaviours->aliasField('id'),
                    'description' => $StudentBehaviours->aliasField('description'),
                    'action' => $StudentBehaviours->aliasField('action'),
                    'date_of_behaviour' => $StudentBehaviours->aliasField('date_of_behaviour'),
                    'time_of_behaviour' => $StudentBehaviours->aliasField('time_of_behaviour'),
                    'category_name' => 'StudentBehaviourCategories.name',
                    'action_taken_by' => $StaffUser->find()->func()->concat([
                        'Users.first_name' => 'literal',
                        " ",
                        'Users.last_name' => 'literal'
                    ]),
                ])
                ->LeftJoin(
                    [$StaffUser->getAlias() => $StaffUser->getTable()],
                    [
                        $StaffUser->aliasField('id = ') . $StudentBehaviours->aliasField('modified_user_id')
                    ]

                )
                ->innerJoin(
                    ['StudentBehaviourCategories' => 'student_behaviour_categories'],
                    [
                        'StudentBehaviourCategories.id =' . $StudentBehaviours->aliasField('student_behaviour_category_id'),
                    ]
                )
                ->where([
                    $StudentBehaviours->aliasField('student_id') => $params['student_id'],
                    //$StudentBehaviours->aliasField('academic_period_id') => $params['academic_period_id'],//POCOR-5191
                    $StudentBehaviours->aliasField('institution_id') => $params['institution_id'],
                ])
                ->order([$StudentBehaviours->aliasField('date_of_behaviour') => 'DESC']) //POCOR-5191
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['education_grade_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');

            $absencesData = $InstitutionStudentAbsences
                ->find()
                ->select([
                    'id' => $InstitutionStudentAbsences->aliasField('id'),
                    'date' => $InstitutionStudentAbsences->aliasField('date'),
                    'month' => 'MONTH(date)',
                ])
                ->where([
                    $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id'],
                    $InstitutionStudentAbsences->aliasField('education_grade_id') => $params['education_grade_id'],
                    $InstitutionStudentAbsences->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                ])
                ->where([
                    $InstitutionStudentAbsences->aliasField('absence_type_id IN') => [1, 2],
                ])
                ->order('month')
                ->toArray();

            $months = array(
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December'
            );

            $monthData = [];
            $entity = [];
            foreach ($absencesData as $data) {
                foreach ($months as $key => $val) {
                    if (!empty($months[$data->month])) {
                        if ($key == $data->month) {
                            $monthData[$val][] = $data->id;
                        } else {
                            $monthData[$val][] = '';
                        }
                    }
                }
            }
            foreach ($monthData as $month => $absences) {
                $number_of_days = [];
                foreach ($absences as $absence) {
                    if (!empty($absence)) {
                        $number_of_days[] = $absence;
                    }
                }
                $entity[] = [
                    'month' => $month,
                    'number_of_days' => count($number_of_days),
                ];
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentTotalAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['education_grade_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');

            $totalExcusedAbsences = $InstitutionStudentAbsences
                ->find()
                ->where([
                    $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id'],
                    $InstitutionStudentAbsences->aliasField('education_grade_id') => $params['education_grade_id'],
                    $InstitutionStudentAbsences->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                ])
                ->where([
                    $InstitutionStudentAbsences->aliasField('absence_type_id') => 1,
                ])
                ->count();

            $totalUnxcusedAbsences = $InstitutionStudentAbsences
                ->find()
                ->where([
                    $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id'],
                    $InstitutionStudentAbsences->aliasField('education_grade_id') => $params['education_grade_id'],
                    $InstitutionStudentAbsences->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                ])
                ->where([
                    $InstitutionStudentAbsences->aliasField('absence_type_id') => 2,
                ])
                ->count();

            $totalLate = $InstitutionStudentAbsences
                ->find()
                ->where([
                    $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id'],
                    $InstitutionStudentAbsences->aliasField('education_grade_id') => $params['education_grade_id'],
                    $InstitutionStudentAbsences->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                ])
                ->where([
                    $InstitutionStudentAbsences->aliasField('absence_type_id') => 3,
                ])
                ->count();
            //POCOR-5191::Start ----    cases table
            $Cases = TableRegistry::get('institution_cases');
            $CasesRecords = TableRegistry::get('institution_case_records');
            $studentAbsences = $InstitutionStudentAbsences->find()->where([
                $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id'],
                $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
            ])->toArray();
            $studentAbsencesIdss = [];
            foreach ($studentAbsences as $k => $student) {
                $studentAbsencesIdss[$k] = $student->id;
            }

            if (!empty($studentAbsencesIdss)) {
                $CasesRecordsData = $CasesRecords->find()->where([
                    $CasesRecords->aliasField('record_id in') => $studentAbsencesIdss
                ])
                    ->group(['institution_case_id'])
                    ->toArray();

                $CasesRecordsIds = [];
                foreach ($CasesRecordsData as $ki => $CasesRecordsData1) {
                    $CasesRecordsIds[$ki] = $CasesRecordsData1->institution_case_id;
                }

                $StaffUser = TableRegistry::get('User.Users');
                if (!empty($CasesRecordsIds)) { // POCOR-7789 for allow report generation if case id is empty
                    $caseData = $Cases
                        ->find()
                        ->select([
                            'id' => $Cases->aliasField('id'),
                            'title' => $Cases->aliasField('title'),
                            'status' => 'WorkflowSteps.name',

                            'assignee' => $StaffUser->find()->func()->concat([
                                'Users.first_name' => 'literal',
                                " ",
                                'Users.last_name' => 'literal'
                            ]),
                            'created' => $Cases->aliasField('created'),

                        ])
                        ->LeftJoin(
                            ['WorkflowSteps' => 'workflow_steps'],
                            [
                                'WorkflowSteps.id =' . $Cases->aliasField('status_id'),
                            ]
                        )
                        ->LeftJoin(
                            [$StaffUser->getAlias() => $StaffUser->getTable()],
                            [
                                $StaffUser->aliasField('id = ') . $Cases->aliasField('assignee_id')
                            ]

                        )
                        ->where([
                            $Cases->aliasField('id in') => $CasesRecordsIds,
                        ])
                        ->toArray();
                    foreach ($caseData as $ky => $caseData1) {
                        $comments = $Cases->find()->select([
                            'institution_id' => $Cases->aliasField('institution_id'),
                            'id' => $Cases->aliasField('id'),
                            'case_number' => $Cases->aliasField('case_number'),
                            'title' => $Cases->aliasField('title'),
                            'comment' => 'WorkflowTransitions.comment'
                        ])
                            ->InnerJoin(
                                ['WorkflowSteps' => 'workflow_steps'],
                                [
                                    'WorkflowSteps.id =' . $Cases->aliasField('status_id'),
                                ]
                            )
                            ->InnerJoin(
                                ['Workflows' => 'workflows'],
                                [
                                    'Workflows.id = WorkflowSteps.workflow_id'
                                ]
                            )
                            ->InnerJoin(
                                ['WorkflowModels' => 'workflow_models'],
                                [
                                    'WorkflowModels.id = Workflows.workflow_model_id'
                                ]
                            )
                            ->InnerJoin(
                                ['WorkflowTransitions' => 'workflow_transitions'],
                                [
                                    'WorkflowTransitions.workflow_model_id = WorkflowModels.id'
                                ]
                            )
                            ->where([
                                'institution_id' => $params['institution_id'],
                                $Cases->aliasField('id') => $caseData1['id'],
                            ])
                            ->toArray();
                        $comm = '';
                        foreach ($comments as $kyu => $comment) {
                            $comm .= $comment->comment . ",";
                        }
                        $comm1 = rtrim($comm, ',');
                        $caseData[$ky]['action_taken'] = $comm1;
                    }
                }
            }



            //POCOR-5191 :: End

            $entity = [
                'total_excused_absences' => $totalExcusedAbsences,
                'total_unexcused_absences' => $totalUnxcusedAbsences,
                'total_late' => $totalLate,
                'total_number_of_absences' => ($totalExcusedAbsences + $totalUnxcusedAbsences),
            ];
            if (!empty($caseData)) { //POCOR-7789
                foreach ($caseData as $ky => $caseData1) {
                    $entity[$ky] = $caseData1;
                }
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentCounsellings(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['education_grade_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $Counsellings = TableRegistry::get('Institution.Counsellings');

            $entity = $Counsellings
                ->find()
                ->select([
                    'id' => $Counsellings->aliasField('id'),
                    'date' => $Counsellings->aliasField('date'),
                    'intervention' => $Counsellings->aliasField('intervention'),
                    'description' => $Counsellings->aliasField('description'),
                    'guidance_type' => 'GuidanceTypes.name',
                ])
                ->contain(['GuidanceTypes'])
                ->where([
                    $Counsellings->aliasField('student_id') => $params['student_id'],
                ])
                ->toArray();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentHealths(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $UserHealths = TableRegistry::get('Health.Healths');

            $entity = $UserHealths
                ->find()
                ->select([
                    'blood_type' => $UserHealths->aliasField('blood_type'),
                    'doctor_name' => $UserHealths->aliasField('doctor_name'),
                    'doctor_contact' => $UserHealths->aliasField('doctor_contact'),
                    'medical_facility' => $UserHealths->aliasField('medical_facility'),
                    'health_insurance' => $UserHealths->aliasField('health_insurance'),
                ])
                ->where([
                    $UserHealths->aliasField('security_user_id') => $params['student_id'],
                ])
                ->first();

            if (!empty($entity) && ($entity->health_insurance == 0)) {
                $entity['health_insurance'] = 'No';
            }
            if (!empty($entity->health_insurance) && ($entity->health_insurance == 1)) {
                $entity['health_insurance'] = 'Yes';
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentHealthConsultations(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $Consultations = TableRegistry::get('Health.Consultations');

            $entity = $Consultations
                ->find()
                ->select([
                    'id' => $Consultations->aliasField('id'),
                    'date' => $Consultations->aliasField('date'),
                    'description' => $Consultations->aliasField('description'),
                    'treatment' => $Consultations->aliasField('treatment'),
                    'consultation_type' => 'ConsultationTypes.name',
                ])
                ->contain(['ConsultationTypes'])
                ->where([
                    $Consultations->aliasField('security_user_id') => $params['student_id'],
                ])
                ->toArray();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentGuardians(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $Guardians = TableRegistry::get('Guardian.Students');

            $guardianData = $Guardians
                ->find()
                ->select([
                    'id' => $Guardians->aliasField('id'),
                    'relation' => 'GuardianRelations.name',
                    'first_name' => 'StudentUser.first_name',
                    'last_name' => 'StudentUser.last_name',
                    'contact' => 'Contacts.value',
                ])
                ->contain(['StudentUser', 'GuardianRelations'])
                ->leftJoin(
                    ['Contacts' => 'user_contacts'],
                    [
                        'Contacts.security_user_id =' . $Guardians->aliasField('guardian_id'),
                    ]
                )
                ->where([
                    $Guardians->aliasField('student_id') => $params['student_id'],
                ])
                ->order($Guardians->aliasField('created'))
                ->limit(2)
                ->toArray();

            $i = 1;
            $entity = [];
            foreach ($guardianData as $value) {
                $entity['relation' . $i] = $value->relation;
                $entity['name' . $i] = $value->first_name . ' ' . $value->last_name;
                $entity['contact' . $i] = $value->contact;
                $i++;
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentHouses(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['education_grade_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $institutionAssociationStudent = TableRegistry::get('Institution.InstitutionAssociationStudent');

            $entity = $institutionAssociationStudent
                ->find()
                ->select([
                    'id' => $institutionAssociationStudent->aliasField('id'),
                    'name' => 'InstitutionAssociations.name',
                ])
                ->innerJoin(
                    ['InstitutionAssociations' => 'institution_associations'],
                    [
                        'InstitutionAssociations.id =' . $institutionAssociationStudent->aliasField('institution_association_id'),
                    ]
                )
                ->where([
                    $institutionAssociationStudent->aliasField('security_user_id') => $params['student_id'],
                    $institutionAssociationStudent->aliasField('academic_period_id') => $params['academic_period_id'],
                    $institutionAssociationStudent->aliasField('education_grade_id') => $params['education_grade_id'],
                ])
                ->first();
            return $entity;
        }
    }
    //6680 starts
    public function onExcelTemplateInitialiseUserSpecialNeedsAssessments(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['education_grade_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $UserSpecialNeedsAssessmentsTbl = TableRegistry::get('user_special_needs_assessments');
            $SpecialNeedTypesTbl = TableRegistry::get('special_need_types');
            $Student = TableRegistry::get('Institution.InstitutionClassStudents');

            $entity = $Student
                ->find()
                ->select([
                    'id' => $SpecialNeedTypesTbl->aliasField('id'),
                    'special_need_type' => $SpecialNeedTypesTbl->aliasField('name')
                ])
                ->innerJoin(
                    [$UserSpecialNeedsAssessmentsTbl->getAlias() => $UserSpecialNeedsAssessmentsTbl->getTable()],
                    [
                        $UserSpecialNeedsAssessmentsTbl->aliasField('security_user_id =') . $Student->aliasField('student_id')
                    ]
                )
                ->leftJoin(
                    [$SpecialNeedTypesTbl->getAlias() => $SpecialNeedTypesTbl->getTable()],
                    [
                        $SpecialNeedTypesTbl->aliasField('id =') . $UserSpecialNeedsAssessmentsTbl->aliasField('special_need_type_id')

                    ]
                )
                ->where([
                    $Student->aliasField('institution_id') => $params['institution_id'],
                    $Student->aliasField('academic_period_id') => $params['academic_period_id'],
                    $Student->aliasField('education_grade_id') => $params['education_grade_id'],
                    $Student->aliasField('student_id') => $params['student_id'],
                ])
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseUserContacts(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $UserContacts = TableRegistry::get('User.Contacts');

            $entity = $UserContacts
                ->find()
                ->select([
                    'id' => $UserContacts->aliasField('id'),
                    'contact' => $UserContacts->aliasField('value'),
                ])
                ->where([
                    $UserContacts->aliasField('security_user_id') => $params['student_id'],
                    $UserContacts->aliasField('preferred') => 1,
                ])
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentMoterDetails(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['student_id'])) {
            $Guardians = TableRegistry::get('Guardian.Students');
            $guardianData = $Guardians
                ->find()
                ->select([
                    'id' => $Guardians->aliasField('id'),
                    'relation' => 'GuardianRelations.name',
                    'first_name' => 'StudentUser.first_name',
                    'last_name' => 'StudentUser.last_name',
                    'contact' => 'Contacts.value',
                ])
                ->contain(['StudentUser', 'GuardianRelations'])
                ->leftJoin(
                    ['Contacts' => 'user_contacts'],
                    [
                        'Contacts.security_user_id =' . $Guardians->aliasField('guardian_id'),
                        'Contacts.preferred =' . 1
                    ]
                )
                ->where([
                    $Guardians->aliasField('student_id') => $params['student_id'],
                    'GuardianRelations.name' => 'Mother',
                    'Contacts.preferred' => 1
                ])
                ->order($Guardians->aliasField('created'))
                ->limit(1)
                ->toArray();
            $entity = [];
            foreach ($guardianData as $value) {
                $entity['mother_relation'] = $value->relation;
                $entity['mother_name'] = $value->first_name . ' ' . $value->last_name;
                $entity['mother_contact'] = $value->contact;
            }
            return $entity;
        }
    } //6680 ends
    //POCOR 7316 starts
    public function onExcelTemplateInitialiseInstitutionSubjectStudentsWithName(Event $event, array $params, ArrayObject $extra)
    {

        if (isset($params['student_id']) && isset($params['institution_id'])) {
            //POCOR-8435 start(for outcome excel result)
            $SubjectStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStudents');
            $Assessments = TableRegistry::getTableLocator()->get('Assessment.Assessments');
            $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
            $InstitutionOutcomeResults = TableRegistry::getTableLocator()->get('Institution.InstitutionOutcomeResults');
            $OutcomeGradingOptions = TableRegistry::getTableLocator()->get('Outcome.OutcomeGradingOptions');
            $OutcomePeriods = TableRegistry::getTableLocator()->get('Outcome.OutcomePeriods');
            $EducationGradeSubjects = TableRegistry::getTableLocator()->get('Education.EducationGradesSubjects');
            $institutionStudentProgrammes = TableRegistry::getTableLocator()->get('Institution.institutionStudentProgrammes');

            $subjectObj = $SubjectStudents->find()
                ->select([
                    "assessment_id" => $Assessments->aliasField('id'),
                    "academic_period_name" => 'AcademicPeriods.name',
                    "institution_name" => 'Institutions.name',
                    "academic_period_id" => 'AcademicPeriods.id',
                    "education_programme_name" => 'EducationProgrammes.name',
                    "education_programme_code" => 'EducationProgrammes.code',//POCOR-9062
                    "education_programme_id" => 'EducationProgrammes.id',
                    "education_grade_name" => 'EducationGrades.name',
                    "education_grade_code" => 'EducationGrades.code',
                    "education_grade_id" => 'EducationGrades.id',
                    "education_level_id" => 'EducationLevels.id',
                    "education_level_name" => 'EducationLevels.name',
                    "education_subject_code" => 'EducationSubjects.code',
                    "institution_subject_name" => 'InstitutionSubjects.name',
                    "institution_subject_id" => 'InstitutionSubjects.id',
                    "education_subject_name" => 'EducationSubjects.name',
                    "education_subject_id" => $SubjectStudents->aliasField('education_subject_id'),
                    "total_mark" => $SubjectStudents->aliasField('total_mark'),
                    "result_type" => $EducationGradeSubjects->aliasField('result_type'),
                    "requirement" => $EducationGradeSubjects->aliasField('requirement'),
                    "start_date" => $InstitutionStudents->aliasField('start_date'),
                    "outcome_result" => $SubjectStudents->aliasField('outcome_result'),
                    "student_candidate_number" => $institutionStudentProgrammes->aliasField('registration_number'),
                ])
                ->join([
                    'Institutions' => [
                        'table' => 'institutions',
                        'type' => 'INNER',
                        'conditions' => 'Institutions.id = ' . $SubjectStudents->aliasField('institution_id')
                    ],
                    'EducationSubjects' => [
                        'table' => 'education_subjects',
                        'type' => 'INNER',
                        'conditions' => 'EducationSubjects.id = ' . $SubjectStudents->aliasField('education_subject_id')
                    ],
                    'InstitutionSubjects' => [
                        'table' => 'institution_subjects',
                        'type' => 'INNER',
                        'conditions' => 'InstitutionSubjects.id = ' . $SubjectStudents->aliasField('institution_subject_id')
                    ],
                    'AcademicPeriods' => [
                        'table' => 'academic_periods',
                        'type' => 'INNER',
                        'conditions' => 'AcademicPeriods.id = ' . $SubjectStudents->aliasField('academic_period_id')
                    ],
                    'EducationGrades' => [
                        'table' => 'education_grades',
                        'type' => 'INNER',
                        'conditions' => 'EducationGrades.id = ' . $SubjectStudents->aliasField('education_grade_id')
                    ],
                    'StudentStatuses' => [
                        'table' => 'student_statuses',
                        'type' => 'INNER',
                        'conditions' => 'StudentStatuses.id = ' . $SubjectStudents->aliasField('student_status_id')
                    ],
                    'EducationProgrammes' => [
                        'table' => 'education_programmes',
                        'type' => 'INNER',
                        'conditions' => 'EducationGrades.education_programme_id = EducationProgrammes.id'
                    ],
                    'EducationCycles' => [
                        'table' => 'education_cycles',
                        'type' => 'INNER',
                        'conditions' => 'EducationCycles.id = EducationProgrammes.education_cycle_id'
                    ],
                    'EducationLevels' => [
                        'table' => 'education_levels',
                        'type' => 'INNER',
                        'conditions' => 'EducationLevels.id = EducationCycles.education_level_id'
                    ],
                ])
                ->leftJoin([$Assessments->getAlias() => $Assessments->getTable()], [//POCOR-9051
                    $Assessments->aliasField('academic_period_id') . ' = ' . $SubjectStudents->aliasField('academic_period_id'),
                    $Assessments->aliasField('education_grade_id') . ' = ' . $SubjectStudents->aliasField('education_grade_id')
                ])
                ->leftJoin([$EducationGradeSubjects->getAlias() => $EducationGradeSubjects->getTable()], [
                    $EducationGradeSubjects->aliasField('education_subject_id') . ' = ' . $SubjectStudents->aliasField('education_subject_id'),
                    $EducationGradeSubjects->aliasField('education_grade_id') . ' = ' . $SubjectStudents->aliasField('education_grade_id')
                ])
                ->leftJoin([$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()], [
                    $InstitutionStudents->aliasField('student_id') . ' = ' . $SubjectStudents->aliasField('student_id'),
                    $InstitutionStudents->aliasField('institution_id') . ' = ' .  $SubjectStudents->aliasField('institution_id')
                ])
                ->leftJoin([$institutionStudentProgrammes->getAlias() => $institutionStudentProgrammes->getTable()], [
                    $institutionStudentProgrammes->aliasField('student_id') . ' = ' . $SubjectStudents->aliasField('student_id'),
                    $institutionStudentProgrammes->aliasField('institution_id') . ' = ' . $SubjectStudents->aliasField('institution_id'),
                    $institutionStudentProgrammes->aliasField('education_programme_id') . ' = EducationGrades.education_programme_id',
                ])
                ->where([
                    $SubjectStudents->aliasField('student_id') => $params['student_id'],
                    $SubjectStudents->aliasField('institution_id') => $params['institution_id'],
                    $SubjectStudents->aliasField('academic_period_id') => $params['academic_period_id'],
                    'StudentStatuses.id IN' => [1, 6, 7, 8]
                ])
                ->group([$SubjectStudents->aliasField('education_subject_id')])
                ->toArray();

            $assessment_ids = [];
            $institution_subject_student = [];
            if (!empty($subjectObj)) {
                $i = 1;
                foreach ($subjectObj as  $subject) {
                    $id = $i;
                    $outcomePeriodDetails = [];
                    $InstitutionOutcomeResultsDetails = $InstitutionOutcomeResults->find()->where([
                        $InstitutionOutcomeResults->aliasField('student_id') . ' = ' .  $params['student_id'],
                        $InstitutionOutcomeResults->aliasField('institution_id') . ' = ' .  $params['institution_id'],
                        $InstitutionOutcomeResults->aliasField('education_subject_id') . ' = ' . $subject['education_subject_id'],
                        $InstitutionOutcomeResults->aliasField('education_grade_id') . ' = ' . $subject['education_grade_id'],
                        $InstitutionOutcomeResults->aliasField('academic_period_id') . ' = ' . $subject['academic_period_id']
                    ])->first();

                    if ($InstitutionOutcomeResultsDetails) {
                        $outcomePeriodDetails = $OutcomePeriods->find()->where(['id' => $InstitutionOutcomeResultsDetails->outcome_period_id])->first();
                    }

                    $entity[] = [
                        'id' => $id,
                        'assessment_id' => $subject['assessment_id'],
                        "academic_period_name" => $subject["academic_period_name"],
                        "institution_name" => $subject["institution_name"],
                        "education_programme_name" => $subject["education_programme_name"],
                        "education_programme_code" => $subject["education_programme_code"], //POCOR-9062
                        "education_grade_name" => $subject["education_grade_name"],
                        "institution_subject_name" => $subject["institution_subject_name"],
                        "education_subject_name" => $subject["education_subject_name"],
                        "name" => $subject["institution_subject_name"],
                        "education_subject_code" => $subject["education_subject_code"],
                        "education_level_name" => $subject["education_level_name"],
                        "subjectName" => $subject["education_subject_name"],
                        "education_subject_id" => $subject["education_subject_id"],
                        "total_mark" => $subject["total_mark"],
                        "result_type" => $subject["result_type"],
                        "requirement" => $subject['requirement'],
                        "start_date" => $subject['start_date'],
                        // "outcome_period_start_date" => $outcomePeriodDetails->start_date ? $outcomePeriodDetails->start_date->format('d/m/Y') : "",
                        // "outcome_period_end_date" => $outcomePeriodDetails->end_date ? $outcomePeriodDetails->end_date->format('d/m/Y') : "",
                        //Commented above line as per comment on POCOR-9108
                        "outcome_period_start_date" => "",
                        "outcome_period_end_date" => "",
                        "outcome_result" => $subject['outcome_result'] ? $subject['outcome_result'] : $subject["total_mark"],
                        "student_candidate_number" => $subject['student_candidate_number'],

                    ];
                    //POCOR-8435 end(for outcome excel result)
                    if (!in_array($subject['assessment_id'], $assessment_ids)) {
                        $assessment_ids[] = $subject['assessment_id'];
                    }
                    $institution_subject_student[] = [
                        'id' => $id,
                        'assessment_id' => $subject['assessment_id'],
                        "academic_period_id" => $subject["academic_period_id"],
                        "education_programme_id" => $subject["education_programme_id"],
                        "education_grade_id" => $subject["education_grade_id"],
                        "institution_subject_id" => $subject["institution_subject_id"],
                        "education_subject_id" => $subject["education_subject_id"],
                    ];

                    $i++;
                }
                $extra['assessment_ids'] =  $assessment_ids;
                $extra['institution_subject_student'] = $institution_subject_student;
            }


            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAssessmentPeriods(Event $event, array $params, ArrayObject $extra)
    {

        if (isset($extra['assessment_ids'])) {
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');

            $entity = $AssessmentPeriods->find()
                ->where([
                    $AssessmentPeriods->aliasField('assessment_id IN ') => $extra['assessment_ids'],
                ])
                ->order([$AssessmentPeriods->aliasField('start_date')])
                ->toArray();

            if (count($entity) > 0) {
                $extra['assessment_period'] = $entity;
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAssessmentItemResults(Event $event, array $params, ArrayObject $extra)
    {


        if (isset($params['student_id']) && isset($params['institution_id']) && isset($extra['assessment_period']) && isset($extra['institution_subject_student'])) {
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $entity = [];
            $institution_subject_student = $extra['institution_subject_student'];
            $entity = [];

            foreach ($institution_subject_student as $row) {

                $AssessmentResultObj = $AssessmentItemResults->find()
                    ->where([
                        $AssessmentItemResults->aliasField('student_id') => $params['student_id'],
                        $AssessmentItemResults->aliasField('institution_id') => $params['institution_id'],
                        $AssessmentItemResults->aliasField('assessment_id') => $row['assessment_id'],
                        $AssessmentItemResults->aliasField('education_subject_id') => $row['education_subject_id'],
                        $AssessmentItemResults->aliasField('academic_period_id') => $row['academic_period_id'],
                    ])
                    ->toArray();

                if ($AssessmentResultObj != []) {
                    foreach ($AssessmentResultObj as $res) {

                        $entity[] = [
                            "id" => $row['id'],
                            "assessment_period_id" => $res['assessment_period_id'],
                            "education_subject_id" => $res['education_subject_id'],
                            "marks_formatted" => number_format($res['marks'], 2)
                        ];
                    }
                }
            }
            return $entity;
        }
    }

    /**
     * POCOR-8222
     * Initializes the Excel template for Institution Student Grade and GPA.
     * This method is triggered when preparing the student grade and GPA data for the institution in the Excel template.
     *
     * @param Event $event The event that triggered the method.
     * @param array $params Parameters passed to the event, potentially containing specific details about the institution or student.
     * @param ArrayObject $extra Additional data or context passed with the event, which may be used for further customization of the template.
     */
    public function onExcelTemplateInitialiseInstitutionStudentGradeGpa(Event $event, array $params, ArrayObject $extra)
    {
        $entity = null;
        if (!empty($params['student_id']) && !empty($params['institution_id']) && !empty($params['academic_period_id'])) {
            $educationSubjects = TableRegistry::get('Education.EducationSubjects');
            $academicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $educationGrades = TableRegistry::get('Education.EducationGrades');
            $StudentsGpa = TableRegistry::get('Institution.InstitutionStudentsGpa');
            $GradesGpa = TableRegistry::get('Gpa.EducationGradesGpa');
            $result = $StudentsGpa->find()
                ->select([
                    'gpa' => $StudentsGpa->aliasField('gpa'),
                    'cumulative_gpa' => $StudentsGpa->aliasField('cumulative_gpa'),
                    'academic_period' => $academicPeriod->aliasField('name'),
                    'education_grade' => $educationGrades->aliasField('name'),
                    'start_date' => $GradesGpa->aliasField('start_date'),
                    'end_date' => $GradesGpa->aliasField('end_date'),
                    'student_id' => $StudentsGpa->aliasField('student_id'),
                ])
                ->LeftJoin(
                    [$academicPeriod->getAlias() => $academicPeriod->getTable()],
                    [
                        $academicPeriod->aliasField('id = ') . $StudentsGpa->aliasField('academic_period_id')
                    ]
                )
                ->LeftJoin(
                    [$educationGrades->getAlias() => $educationGrades->getTable()],
                    [
                        $educationGrades->aliasField('id = ') . $StudentsGpa->aliasField('education_grade_id')
                    ]
                )
                ->LeftJoin(
                    [$GradesGpa->getAlias() => $GradesGpa->getTable()],
                    [
                        $GradesGpa->aliasField('education_grade_id = ') . $StudentsGpa->aliasField('education_grade_id')
                    ]
                )
                ->where([
                    $StudentsGpa->aliasField('student_id') => $params['student_id'],
                    $StudentsGpa->aliasField('institution_id') => $params['institution_id'],
                    $GradesGpa->aliasField('gpa_grading_type_id IS NOT') => NULL,
                    $StudentsGpa->aliasField('cumulative_gpa IS NOT') => NULL //POCOR-9144
                ])->group([$StudentsGpa->aliasField('education_grade_id')])
                ->toArray();
            $entity = [];
            $i = 1;
            foreach ($result as $row) {
                $entity[] = [
                    'id' => $i,
                    'academic_period' => $row['academic_period'],
                    'education_grade' => $row['education_grade'],
                    'gpa' => $row['gpa'],
                    'cumulative' => $row['cumulative_gpa'],
                    'start_date' => $row['start_date'],  // Null if not a valid date
                    'end_date' => $row['end_date'],      // Null if not a valid date
                ];
                $i++;
            }
        }
        return $entity;
    }


    /**
     * POCOR-8878
     * Initializes the Excel template for Institution Student data teacher.
     * This method is triggered when preparing the student data the institution in the Excel template.
     *
     * @param Event $event The event that triggered the method.
     * 
     * */
    public function onExcelTemplateInitialiseCompetencyTemplates(Event $event, array $params, ArrayObject $extra)
    {

        if (isset($params['academic_period_id']) && isset($params['education_grade_id'])) {
            $CompetencyTemplates = TableRegistry::get('Competency.CompetencyTemplates');

            $query = $CompetencyTemplates->find()
                ->innerJoinWith('Periods')
                ->where([
                    'CompetencyTemplates.academic_period_id' => $params['academic_period_id'],
                    'CompetencyTemplates.education_grade_id' => $params['education_grade_id'],
                ])
                ->group(['CompetencyTemplates.id']); 

            $results = $query->toArray();

            if (!empty($results)) {
                $extra['competency_templates_ids'] = array_column($results, 'id');
            }

            return $results;
        }
    }

    /**
     * POCOR-8878
     * Initializes the Excel template for Institution Student subject teacher.
     * This method is triggered when preparing the student data the institution in the Excel template.
     *
     * @param Event $event The event that triggered the method.
     * 
     * */
    public function onExcelTemplateInitialiseCompetencyPeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['academic_period_id']) && isset($extra['competency_templates_ids']) && !empty($extra['competency_templates_ids'])) {
            $CompetencyPeriods = TableRegistry::get('Competency.CompetencyPeriods');

            $entity = $CompetencyPeriods->find()
                ->where([
                    $CompetencyPeriods->aliasField('academic_period_id') => $params['academic_period_id'],
                    $CompetencyPeriods->aliasField('competency_template_id IN ') => $extra['competency_templates_ids']
                ]);

            if ($entity->count() > 0) {
                $extra['competency_periods_ids'] = $entity->all()->extract('id')->toArray();
            }
            return $entity->toArray();
        }
    }

    /**
     * POCOR-8878
     * Initializes the Excel template for Institution Student subject teacher.
     * This method is triggered when preparing the student data the institution in the Excel template.
     *
     * @param Event $event The event that triggered the method.
     * 
     * */
    public function onExcelTemplateInitialiseCompetencyItems(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['academic_period_id']) && isset($extra['competency_templates_ids']) && !empty($extra['competency_templates_ids']) && isset($extra['competency_periods_ids']) && !empty($extra['competency_periods_ids'])) {
            $CompetencyItems = TableRegistry::get('Competency.CompetencyItems');
            $entity = $CompetencyItems->find()
                ->select(['competency_period_id' => 'Periods.id'])
                ->innerJoinWith('Periods')
                ->where([
                    $CompetencyItems->aliasField('academic_period_id') => $params['academic_period_id'],
                    $CompetencyItems->aliasField('competency_template_id IN ') => $extra['competency_templates_ids'],
                    'Periods.id IN' => $extra['competency_periods_ids']
                ])
                ->enableAutoFields(true)
                ->toArray();
            return $entity;
        }
    }

    /**
     * POCOR-8878
     * Initializes the Excel template for Institution Student subject teacher.
     * This method is triggered when preparing the student data the institution in the Excel template.
     *
     * @param Event $event The event that triggered the method.
     * 
     * */
    public function onExcelTemplateInitialiseAssessments(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['academic_period_id']) && isset($params['student_id'])) {
            $Assessments = TableRegistry::get('Assessment.Assessments');

            $entity = $Assessments->find()
                ->where([
                    $Assessments->aliasField('academic_period_id') => $params['academic_period_id'],
                    $Assessments->aliasField('education_grade_id') => $params['education_grade_id'],
                ])
                ->first();

            if (!empty($entity)) {
                $extra['assessment_id'] = $entity->id;
            }
            return $entity;
        }
    }
    
    /**
     * POCOR-8878
     * Initializes the Excel template for Institution Student subject teacher.
     * This method is triggered when preparing the student data the institution in the Excel template.
     *
     * @param Event $event The event that triggered the method.
     * 
     * */
    public function onExcelTemplateInitialiseSubjectTeacher(Event $event, array $params, ArrayObject $extra)
    {

        if (isset($extra['assessment_id']) && isset($params['student_id'])) {
            $SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
            $AssessmentItemData = $SubjectStudents->find()
                ->where([
                    $SubjectStudents->aliasField('student_id') => $params['student_id'],
                    $SubjectStudents->aliasField('institution_id') => $params['institution_id'],
                    $SubjectStudents->aliasField('academic_period_id') => $params['academic_period_id'],
                    $SubjectStudents->aliasField('education_grade_id') => $params['education_grade_id']
                ])
                ->contain([
                    'EducationSubjects', 'InstitutionSubjects'
                ])
                ->enableHydration(false)
                ->toArray();

            if (empty($AssessmentItemData)) {
                $entity = [];
                return $entity;
            }

            $Staff = TableRegistry::get('Institution.InstitutionStaff'); 
            $endAssignment = TableRegistry::get('Staff.StaffStatuses')->findByCode('END_OF_ASSIGNMENT')->first()->id; 
            $StudentSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');

            foreach ($AssessmentItemData as $value) {
                $StudentSubjectStaffData = $StudentSubjectStaff->find()
                    ->select([
                        'staff_id' => $StudentSubjectStaff->aliasField('staff_id'),
                        'institution_subject_id' => $StudentSubjectStaff->aliasField('institution_subject_id'),
                        'first_name' => 'SecurityUsers.first_name',
                        'last_name' => 'SecurityUsers.last_name',
                        'preferred_name' => 'SecurityUsers.preferred_name',
                        'gender_id' => 'SecurityUsers.gender_id',
                    ])
                    ->innerJoin([$Staff->getAlias() => $Staff->getTable()],
                        [$Staff->aliasField('staff_id = ') . $StudentSubjectStaff->aliasField('staff_id')])
                    ->innerJoin(['SecurityUsers' => 'security_users'], [
                        'SecurityUsers.id = ' . $StudentSubjectStaff->aliasField('staff_id')
                    ])
                    ->where([
                        $StudentSubjectStaff->aliasField('institution_subject_id') => $value['institution_subject_id'],
                        $Staff->aliasField('staff_status_id IS NOT') => $endAssignment,
                        $Staff->aliasField('institution_id') => $params['institution_id']
                    ])
                    ->group(['staff_id'])
                    ->toArray();
                $name = [];
                foreach ($StudentSubjectStaffData as $data) {
                    if (isset($data->gender_id)) {
                        if ($data->gender_id == '1') {
                            $gender = "Male";
                        } else {
                            $gender = "Female";
                        }
                    }
                    $name[] = $data->first_name . ' ' . $data->last_name . ' , ' . $data->preferred_name . ' , ' . $gender;
                }
                $entity[] = [
                    'education_subject_id' => $value['education_subject_id'],
                    'name' => implode(",", $name)
                ];
            }

            return $entity;
        }
    }


    public function onExcelTemplateInitialiseOutcomeTemplates(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['academic_period_id'])) {
            $OutcomeTemplates = TableRegistry::get('Outcome.OutcomeTemplates');

            $entity = $OutcomeTemplates
                ->find()
                ->innerJoinWith('Periods')
                ->where([
                    $OutcomeTemplates->aliasField('academic_period_id') => $params['academic_period_id'],
                    $OutcomeTemplates->aliasField('education_grade_id') => $params['education_grade_id']
                ])
                ->group($OutcomeTemplates->aliasField('id'));

            if ($entity->count() > 0) {
                $extra['outcome_templates_ids'] = $entity->all()->extract('id')->toArray();
            }
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseOutcomePeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['academic_period_id']) && isset($extra['outcome_templates_ids']) && !empty($extra['outcome_templates_ids'])) {
            $OutcomePeriods = TableRegistry::get('Outcome.OutcomePeriods');

            $entity = $OutcomePeriods->find()
                ->where([
                    $OutcomePeriods->aliasField('academic_period_id') => $params['academic_period_id'],
                    $OutcomePeriods->aliasField('outcome_template_id IN ') => $extra['outcome_templates_ids']
                ]);

            if ($entity->count() > 0) {
                $extra['outcome_periods_ids'] = $entity->all()->extract('id')->toArray();
            }
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseOutcomeSubjects(Event $event, array $params, ArrayObject $extra)
    {

        if (isset($params['institution_id']) && isset($params['academic_period_id']) && isset($params['education_grade_id']) && isset($extra['outcome_periods_ids']) && !empty($extra['outcome_periods_ids'])) {
            $studentId = $params['student_id'];
            $EducationSubjects = TableRegistry::get('Education.EducationSubjects');
            $OutcomePeriods = TableRegistry::get('Outcome.OutcomePeriods');
            $mergeEntity = [];

            $entity = $EducationSubjects
                ->find()
                ->find('visible')
                ->find('order')
                ->select([
                    'outcome_template_id' => $OutcomePeriods->aliasField('outcome_template_id'),
                    'outcome_period_id' => $OutcomePeriods->aliasField('id')
                ])
                //POCOR-5056 starts
                ->innerJoinWith('InstitutionSubjects.SubjectStudents', function ($q) use ($studentId) {
                    return $q->where(['SubjectStudents.student_id' => $studentId]);
                })
                //POCOR-5056 ends
                ->leftJoin([$OutcomePeriods->getAlias() => $OutcomePeriods->getTable()], [
                    $OutcomePeriods->aliasField('id IN ') => $extra['outcome_periods_ids']
                ])
                ->where([
                    'InstitutionSubjects.education_grade_id' => $params['education_grade_id'],
                    'InstitutionSubjects.institution_id' => $params['institution_id'],
                    'InstitutionSubjects.academic_period_id' => $params['academic_period_id'],
                ])
                ->formatResults(function (ResultSetInterface $results) {
                    return $results->map(function ($row) {
                        $row->education_subject_id = $row->id;
                        $hashString = [];
                        $hashString[] = $row->outcome_template_id;
                        $hashString[] = $row->education_subject_id;
                        $row->id = Security::hash(implode(',', $hashString), 'sha256');
                        return $row;
                    });
                })
                ->enableAutoFields(true);

            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseOutcomeCriterias(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($params['academic_period_id']) && isset($extra['outcome_templates_ids']) && !empty($extra['outcome_templates_ids'])) {
            $OutcomeCriterias = TableRegistry::get('Outcome.OutcomeCriterias');

            $entity = $OutcomeCriterias->find()
                ->where([
                    $OutcomeCriterias->aliasField('academic_period_id') => $params['academic_period_id'],
                    $OutcomeCriterias->aliasField('outcome_template_id IN ') => $extra['outcome_templates_ids']
                ])
                ->formatResults(function (ResultSetInterface $results) {
                    return $results->map(function ($row) {
                        $hashString = [];
                        $hashString[] = $row->outcome_template_id;
                        $hashString[] = $row->education_subject_id;
                        $row->outcome_subject_id = Security::hash(implode(',', $hashString), 'sha256');
                        return $row;
                    });
                })
                ->enableAutoFields(true);

            return $entity->toArray();

        }
    }


    public function onExcelTemplateInitialiseStudentOutcomeSubjectComments(Event $event, array $params, ArrayObject $extra)
    {
        if (isset($extra['outcome_templates_ids']) && !empty($extra['outcome_templates_ids']) && isset($extra['outcome_periods_ids']) && !empty($extra['outcome_periods_ids']) && isset($params['student_id']) && isset($params['institution_id']) && isset($params['academic_period_id'])) {

            $OutcomeSubjectComments = TableRegistry::get('Institution.InstitutionOutcomeSubjectComments');
            $entity = $OutcomeSubjectComments->find()
                ->where([
                    $OutcomeSubjectComments->aliasField('outcome_template_id IN ') => $extra['outcome_templates_ids'],
                    $OutcomeSubjectComments->aliasField('outcome_period_id IN ') => $extra['outcome_periods_ids'],
                    $OutcomeSubjectComments->aliasField('student_id') => $params['student_id'],
                    $OutcomeSubjectComments->aliasField('institution_id') => $params['institution_id'],
                    $OutcomeSubjectComments->aliasField('academic_period_id') => $params['academic_period_id'],
                ])->toArray();
                $entity[] = [
                        
                        'comments' => $entity['comments'],
                    ];

            return $entity;
        }
    }
    //POCOR-8658 starts
    public function onExcelTemplateInitialiseClassAndLevelRanking(Event $event, array $params, ArrayObject $extra)
    {
        if (empty($params['academic_period_id']) && empty($params['student_id'])) {
            return [];
        }
        $studentId = $params['student_id'];
        $academicPeriodId = $params['academic_period_id'];

        $connection = ConnectionManager::get('default');
        $studentsData = $connection->execute("SELECT
            academic_periods.name AS 'academic_period',
            institutions.name AS 'institution_name',
            institutions.code AS 'institution_code',
            education_grades.name AS 'education_grade',
            institution_classes.name AS 'class_name',
            security_users.openemis_no AS 'student_openemis_no',
            security_users.first_name AS 'student_first_name',
            security_users.last_name AS 'student_last_name',
            institution_subjects.name AS 'institution_subject',
            CASE
                WHEN institution_subject_students.total_mark IS NOT NULL THEN institution_subject_students.total_mark
            ELSE ''
                END AS 'total_mark',
            RANK() OVER ( PARTITION BY institution_classes.name, institution_subjects.name ORDER BY institution_subject_students.total_mark DESC) AS 'class_ranking',
            RANK() OVER ( PARTITION BY institutions.name, education_grades.name, institution_subjects.name ORDER BY institution_subject_students.total_mark DESC) AS 'level_ranking'
        FROM institution_subject_students
        INNER JOIN institution_classes ON institution_classes.id = institution_subject_students.institution_class_id
        INNER JOIN security_users ON security_users.id = institution_subject_students.student_id
        INNER JOIN institution_subjects ON institution_subjects.id = institution_subject_students.institution_subject_id
        INNER JOIN institutions ON institutions.id = institution_subject_students.institution_id
        INNER JOIN education_grades ON education_grades.id = institution_subject_students.education_grade_id
        INNER JOIN academic_periods ON academic_periods.id = institution_subject_students.academic_period_id
        WHERE academic_periods.id = " . $academicPeriodId . " AND institution_subject_students.student_id = " . $studentId . " AND LENGTH(institution_subject_students.total_mark) > 0
        ORDER BY institutions.name, education_grades.name, institution_subjects.name, 'Level Ranking', 'Class Ranking';")->fetchAll(\PDO::FETCH_ASSOC);

        $entity = $result = [];
        if (!empty($studentsData)) {
            foreach ($studentsData as $key => $data) {
                $result = [
                    'id' => $key,
                    'institution_name' => !empty($data['institution_name']) ? $data['institution_name'] : '',
                    'academic_period' => !empty($data['academic_period']) ? $data['academic_period'] : '',
                    'institution_code' => !empty($data['institution_code']) ? $data['institution_code'] : '',
                    'education_grade' => !empty($data['education_grade']) ? $data['education_grade'] : '',
                    'class_name' => !empty($data['class_name']) ? $data['class_name'] : '',
                    'student_openemis_no' => !empty($data['student_openemis_no']) ? $data['student_openemis_no'] : '',
                    'student_first_name' => !empty($data['student_first_name']) ? $data['student_first_name'] : '',
                    'student_last_name' => !empty($data['student_last_name']) ? $data['student_last_name'] : '',
                    'institution_subject' => !empty($data['institution_subject']) ? $data['institution_subject'] : '',
                    'total_mark' => !empty($data['total_mark']) ? $data['total_mark'] : '',
                    'class_ranking' => !empty($data['class_ranking']) ? $data['class_ranking'] : '',
                    'level_ranking' => !empty($data['level_ranking']) ? $data['level_ranking'] : ''
                ];
                $entity[] = $result;
            }
        }
        return $entity;
    }
    //POCOR-8658 ends
}
