<?php
namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use App\Model\Table\AppTable;

class ReportCardsTable extends AppTable
{
    private $fileType = 'xlsx';
    //private $fileType = 'pdf';

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'next_institution_class_id']);

        $this->addBehavior('CustomExcel.ExcelReport', [
            'templateTable' => 'ReportCard.ReportCards',
            'templateTableKey' => 'report_card_id',
            'format' => $this->fileType,
            'download' => false,
            'wrapText' => true,
            'lockSheets' => true,
            'variables' => [
                'ReportCards',
                'InstitutionStudentsReportCards',
                'FirstGuardian',
                'Extracurriculars',
                'Awards',
                'Admissions',
                'InstitutionStudentsReportCardsComments',
                'Institutions',
                'Principal',
                'DeputyPrincipal',
                'InstitutionClasses',
                'InstitutionSubjectStudents',
                'InstitutionSubjectStudentsWithName',
                'StudentBehaviours',
                'InstitutionStudentAbsences',
                'CompetencyTemplates',
                'CompetencyPeriods',
                'CompetencyItems',
                'CompetencyCriterias',
                'StudentCompetencyPeriodComments',
                'StudentCompetencyItemComments',
                'CompetencyCriteriasWithResults',
                'StudentCompetencyResults',
                'Assessments',
                'AssessmentPeriods',
                'AssessmentItems',
                'AssessmentItemsStudentSubjects',
                'AssessmentItemsWithResults',
                'AssessmentItemResults',
                'OutcomeTemplates',
                'OutcomePeriods',
                'OutcomeSubjects',
                'StudentOutcomeSubjectComments',
                'OutcomeCriterias',
                'StudentOutcomeResults',
                'GroupAssessmentPeriods',
                'GroupAssessmentItemResults',
                'AssessmentTermResults',
                'NextClassSubjects',
                'StudentNextYearClass'
            ]
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onExcelTemplateBeforeGenerate'] = 'onExcelTemplateBeforeGenerate';
        $events['ExcelTemplates.Model.onExcelTemplateAfterGenerate'] = 'onExcelTemplateAfterGenerate';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseReportCards'] = 'onExcelTemplateInitialiseReportCards';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentsReportCards'] = 'onExcelTemplateInitialiseInstitutionStudentsReportCards';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseFirstGuardian'] = 'onExcelTemplateInitialiseFirstGuardian';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseExtracurriculars'] = 'onExcelTemplateInitialiseExtracurriculars';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAwards'] = 'onExcelTemplateInitialiseAwards';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAdmissions'] = 'onExcelTemplateInitialiseAdmissions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentsReportCardsComments'] = 'onExcelTemplateInitialiseInstitutionStudentsReportCardsComments';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutions'] = 'onExcelTemplateInitialiseInstitutions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialisePrincipal'] = 'onExcelTemplateInitialisePrincipal';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseDeputyPrincipal'] = 'onExcelTemplateInitialiseDeputyPrincipal';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionClasses'] = 'onExcelTemplateInitialiseInstitutionClasses';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionSubjectStudents'] = 'onExcelTemplateInitialiseInstitutionSubjectStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionSubjectStudentsWithName'] = 'onExcelTemplateInitialiseInstitutionSubjectStudentsWithName';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentBehaviours'] = 'onExcelTemplateInitialiseStudentBehaviours';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentAbsences'] = 'onExcelTemplateInitialiseInstitutionStudentAbsences';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyTemplates'] = 'onExcelTemplateInitialiseCompetencyTemplates';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyPeriods'] = 'onExcelTemplateInitialiseCompetencyPeriods';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyItems'] = 'onExcelTemplateInitialiseCompetencyItems';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyCriterias'] = 'onExcelTemplateInitialiseCompetencyCriterias';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentCompetencyPeriodComments'] = 'onExcelTemplateInitialiseStudentCompetencyPeriodComments';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentCompetencyItemComments'] = 'onExcelTemplateInitialiseStudentCompetencyItemComments';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyCriteriasWithResults'] = 'onExcelTemplateInitialiseCompetencyCriteriasWithResults';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentCompetencyResults'] = 'onExcelTemplateInitialiseStudentCompetencyResults';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessments'] = 'onExcelTemplateInitialiseAssessments';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentPeriods'] = 'onExcelTemplateInitialiseAssessmentPeriods';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItems'] = 'onExcelTemplateInitialiseAssessmentItems';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItemsStudentSubjects'] = 'onExcelTemplateInitialiseAssessmentItemsStudentSubjects';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItemsWithResults'] = 'onExcelTemplateInitialiseAssessmentItemsWithResults';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItemResults'] = 'onExcelTemplateInitialiseAssessmentItemResults';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseGroupAssessmentItemResults'] = 'onExcelTemplateInitialiseGroupAssessmentItemResults';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseGroupAssessmentPeriods'] = 'onExcelTemplateInitialiseGroupAssessmentPeriods';
        $events['ExcelTemplates.Model.afterRenderExcelTemplate'] = 'afterRenderExcelTemplate';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseOutcomeTemplates'] = 'onExcelTemplateInitialiseOutcomeTemplates';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseOutcomePeriods'] = 'onExcelTemplateInitialiseOutcomePeriods';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseOutcomeSubjects'] = 'onExcelTemplateInitialiseOutcomeSubjects';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentOutcomeSubjectComments'] = 'onExcelTemplateInitialiseStudentOutcomeSubjectComments';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseOutcomeCriterias'] = 'onExcelTemplateInitialiseOutcomeCriterias';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentOutcomeResults'] = 'onExcelTemplateInitialiseStudentOutcomeResults';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentTermResults'] = 'onExcelTemplateInitialiseAssessmentTermResults';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseNextClassSubjects'] = 'onExcelTemplateInitialiseNextClassSubjects';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentNextYearClass'] = 'onExcelTemplateInitialiseStudentNextYearClass';
        return $events;
    }

    public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        if (!$StudentsReportCards->exists($params)) {
            // insert student report card record if it does not exist
            $params['status'] = $StudentsReportCards::IN_PROGRESS;
            $params['started_on'] = date('Y-m-d H:i:s');
            $newEntity = $StudentsReportCards->newEntity($params);
            $StudentsReportCards->save($newEntity);
        } else {
            // update status to in progress if record exists
            $StudentsReportCards->updateAll([
                'status' => $StudentsReportCards::IN_PROGRESS,
                'started_on' => date('Y-m-d H:i:s')
            ], $params);
        }
    }

    public function onExcelTemplateAfterGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $studentReportCardData = $StudentsReportCards
            ->find()
            ->select([
                $StudentsReportCards->aliasField('id'),
                $StudentsReportCards->aliasField('academic_period_id'),
                $StudentsReportCards->aliasField('institution_id'),
                $StudentsReportCards->aliasField('institution_class_id'),
                $StudentsReportCards->aliasField('student_id'),
                $StudentsReportCards->aliasField('report_card_id')
            ])
            ->contain([
                'Institutions' => [
                    'fields' => [
                        'code',
                        'name'
                    ]
                ],
                'ReportCards' => [
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
                $StudentsReportCards->aliasField('academic_period_id') => $params['academic_period_id'],
                $StudentsReportCards->aliasField('institution_id') => $params['institution_id'],
                $StudentsReportCards->aliasField('institution_class_id') => $params['institution_class_id'],
                $StudentsReportCards->aliasField('student_id') => $params['student_id'],
                $StudentsReportCards->aliasField('report_card_id') => $params['report_card_id']
            ])
            ->first();

        // set filename
        $fileName = $studentReportCardData->institution->code . '_' . $studentReportCardData->report_card->code. '_' . $studentReportCardData->student->openemis_no . '_' . $studentReportCardData->student->name . '.' . $this->fileType;
        $filepath = $extra['file_path'];
        $fileContent = file_get_contents($filepath);
        $status = $StudentsReportCards::GENERATED;

        // save file
        $StudentsReportCards->updateAll([
            'status' => $status,
            'completed_on' => date('Y-m-d H:i:s'),
            'file_name' => $fileName,
            'file_content' => $fileContent
        ], $params);

        // delete report card process
        $ReportCardProcesses = TableRegistry::Get('ReportCard.ReportCardProcesses');
        $ReportCardProcesses->deleteAll([
            'report_card_id' => $params['report_card_id'],
            'institution_class_id' => $params['institution_class_id'],
            'student_id' => $params['student_id']
        ]);
    }

    public function afterRenderExcelTemplate(Event $event, ArrayObject $extra, $controller)
    {
        $params = $extra['params'];
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'ReportCardStatuses',
            'index',
            'class_id' => $params['institution_class_id'],
            'report_card_id' => $params['report_card_id'],
            'academic_period_id' => $params['academic_period_id']
        ];

        $event->stopPropagation();
        return $controller->redirect($url);
    }

    public function onExcelTemplateInitialiseReportCards(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('report_card_id', $params)) {
            $ReportCards = TableRegistry::get('ReportCard.ReportCards');
            $entity = $ReportCards->get($params['report_card_id'], ['contain' => ['AcademicPeriods', 'EducationGrades.EducationProgrammes.EducationCycles.EducationLevels']]);

            $extra['report_card_start_date'] = $entity->start_date;
            $extra['report_card_end_date'] = $entity->end_date;
            $extra['report_card_education_grade_id'] = $entity->education_grade_id;

            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutionStudentsReportCards(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('report_card_id', $params) && array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('report_card_education_grade_id', $extra)) {
            $StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $dateFormat = $ConfigItems->value('date_format');

            $entity = $StudentsReportCards
                ->find()
                ->select([
                    $StudentsReportCards->aliasField('id'),
                    $StudentsReportCards->aliasField('status'),
                    $StudentsReportCards->aliasField('principal_comments'),
                    $StudentsReportCards->aliasField('homeroom_teacher_comments'),
                    $StudentsReportCards->aliasField('report_card_id'),
                    $StudentsReportCards->aliasField('student_id'),
                    $StudentsReportCards->aliasField('institution_id'),
                    $StudentsReportCards->aliasField('academic_period_id'),
                    $StudentsReportCards->aliasField('education_grade_id'),
                    $StudentsReportCards->aliasField('institution_class_id')
                ])
                ->contain([
                    'Students' => [
                        'fields' => [
                            'openemis_no',
                            'first_name',
                            'middle_name',
                            'third_name',
                            'last_name',
                            'preferred_name',
                            'email',
                            'address',
                            'postal_code',
                            'address_area_id',
                            'birthplace_area_id',
                            'gender_id',
                            'date_of_birth',
                            'date_of_death',
                            'nationality_id',
                            'identity_type_id',
                            'identity_number'
                        ]
                    ],
                    'Students.MainNationalities' => [
                        'fields' => [
                            'name'
                        ]
                    ],
                    'Students.AddressAreas' => [
                        'fields' => [
                            'code',
                            'name'
                        ]
                    ],
                    'Students.BirthplaceAreas' => [
                        'fields' => [
                            'code',
                            'name'
                        ]
                    ],
                    'EducationGrades' => [
                        'fields' => [
                            'code',
                            'name',
                            'admission_age',
                            'education_stage_id',
                            'education_programme_id'
                        ]
                    ]
                ])
                ->where([
                    $StudentsReportCards->aliasField('report_card_id') => $params['report_card_id'],
                    $StudentsReportCards->aliasField('student_id') => $params['student_id'],
                    $StudentsReportCards->aliasField('institution_id') => $params['institution_id'],
                    $StudentsReportCards->aliasField('academic_period_id') => $params['academic_period_id'],
                    $StudentsReportCards->aliasField('education_grade_id') => $extra['report_card_education_grade_id']
                ])
                ->first();

            if (!empty($entity) && $entity->has('student')) {
                $birthdate = $entity->student->date_of_birth;
                $entity->student->date_of_birth = $birthdate->format($dateFormat);

                // POCOR-4156 body masses data
                $reportCardStartDate = $extra['report_card_start_date'];
                $reportCardEndDate = $extra['report_card_end_date'];
                $studentId = $entity->student_id;

                $UserBodyMasses = TableRegistry::get('User.UserBodyMasses');
                $userBodyMassData = $UserBodyMasses->find()
                    ->where([
                        $UserBodyMasses->aliasField('security_user_id') => $studentId,
                        $UserBodyMasses->aliasField('date >= ') => $reportCardStartDate,
                        $UserBodyMasses->aliasField('date <= ') => $reportCardEndDate,
                    ])
                    ->order([
                        $UserBodyMasses->aliasField('date') => 'DESC',
                        $UserBodyMasses->aliasField('created') => 'DESC'
                    ])
                    ->first();

                if (!empty($userBodyMassData)) {
                    $entity->student->height = $userBodyMassData->height;
                    $entity->student->weight = $userBodyMassData->weight;
                    $entity->student->body_mass_index = $userBodyMassData->body_mass_index;
                }
                // end POCOR-4156 body masses data
            }

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseFirstGuardian(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_id', $params)) {
            $StudentGuardians = TableRegistry::get('Student.Guardians');
            $entity = $StudentGuardians
                    ->find()
                    ->select([
                        $StudentGuardians->aliasField('id'),
                        $StudentGuardians->aliasField('student_id'),
                        $StudentGuardians->aliasField('guardian_id'),
                        $StudentGuardians->aliasField('guardian_relation_id')
                    ])
                    ->contain([
                        'Users' => [
                            'fields' => [
                                'openemis_no',
                                'first_name',
                                'middle_name',
                                'third_name',
                                'last_name',
                                'preferred_name',
                                'email',
                                'address',
                                'postal_code'
                            ]
                        ],
                        'GuardianRelations' => [
                            'fields' => [
                                'name'
                            ]
                        ]
                    ])
                    ->where([
                        $StudentGuardians->aliasField('student_id') => $params['student_id']
                    ])
                    ->formatResults(function (ResultSetInterface $results) {
                        return $results->map(function ($row) {
                            $guardianId = $row['guardian_id'];

                            $row['contact'] = [];

                            $UserContacts = TableRegistry::get('User.Contacts');
                            $userContactResults = $UserContacts
                                ->find()
                                ->contain(['ContactTypes.ContactOptions'])
                                ->where([
                                    $UserContacts->aliasField('security_user_id') => $guardianId
                                ])
                                ->all();
                            if (!$userContactResults->isEmpty()) {
                                $firstContact = $userContactResults->first();
                                $row['contact'] = $firstContact;
                            }

                            return $row;
                        });
                    })
                    ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseExtracurriculars(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_id', $params) && array_key_exists('report_card_start_date', $extra) && array_key_exists('report_card_end_date', $extra)) {

            $Extracurriculars = TableRegistry::get('Student.Extracurriculars');
            $entity = $Extracurriculars->find()
                ->contain('ExtracurricularTypes')
                ->where([
                    $Extracurriculars->aliasField('security_user_id') => $params['student_id'],
                    'OR' => [
                        [
                            $Extracurriculars->aliasField('end_date') . ' IS NOT NULL',
                            $Extracurriculars->aliasField('start_date') . ' <=' => $extra['report_card_start_date'],
                            $Extracurriculars->aliasField('end_date') . ' >=' => $extra['report_card_start_date']
                        ],
                        [
                            $Extracurriculars->aliasField('end_date') . ' IS NOT NULL',
                            $Extracurriculars->aliasField('start_date') . ' <=' => $extra['report_card_end_date'],
                            $Extracurriculars->aliasField('end_date') . ' >=' => $extra['report_card_end_date']
                        ],
                        [
                            $Extracurriculars->aliasField('end_date') . ' IS NOT NULL',
                            $Extracurriculars->aliasField('start_date') . ' >=' => $extra['report_card_start_date'],
                            $Extracurriculars->aliasField('end_date') . ' <=' => $extra['report_card_end_date']
                        ]
                    ],
                ])
                ->toArray();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAwards(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_id', $params) && array_key_exists('report_card_start_date', $extra) && array_key_exists('report_card_end_date', $extra)) {

            $Awards = TableRegistry::get('User.Awards');

            $query = $Awards->find();
            $dateFormat = $query->func()->date_format([
                            'issue_date' => 'literal',
                            "'%m/%d/%y'" => 'literal'
                        ]);

            $entity = $Awards->find()
                    ->select([
                        'award_date' => $dateFormat
                    ])
                    ->where([
                        $Awards->aliasField('security_user_id') => $params['student_id'],
                        $Awards->aliasField('issue_date >= ') => $extra['report_card_start_date'],
                        $Awards->aliasField('issue_date <= ') => $extra['report_card_end_date']
                    ])
                    ->autoFields(true)
                    ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAdmissions(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('report_card_education_grade_id', $extra)) {

            $InstitutionStudents = TableRegistry::get('Institution.Students');

            $query = $InstitutionStudents->find();
            $dateFormat = $query->func()->date_format([
                            'start_date' => 'literal',
                            "'%m/%d/%y'" => 'literal'
                        ]);

            $entity = $InstitutionStudents->find()
                    ->select([
                        'admission_date' => $dateFormat
                    ])
                    ->where([
                        $InstitutionStudents->aliasField('student_id') => $params['student_id'],
                        $InstitutionStudents->aliasField('academic_period_id') => $params['academic_period_id'],
                        $InstitutionStudents->aliasField('institution_id') => $params['institution_id'],
                        $InstitutionStudents->aliasField('education_grade_id') => $extra['report_card_education_grade_id'],
                    ])
                    ->autoFields(true)
                    ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionStudentsReportCardsComments(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('report_card_id', $params) && array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('report_card_education_grade_id', $extra)) {
            $StudentsReportCardsComments = TableRegistry::get('Institution.InstitutionStudentsReportCardsComments');
            $ReportCardSubjects = TableRegistry::get('ReportCard.ReportCardSubjects');

            $entity = $StudentsReportCardsComments->find()
                ->select(['comment_code_name' => 'CommentCodes.name'])
                ->leftJoinWith('CommentCodes')
                ->innerJoin([$ReportCardSubjects->alias() => $ReportCardSubjects->table()], [
                    $ReportCardSubjects->aliasField('report_card_id = ') .  $StudentsReportCardsComments->aliasField('report_card_id'),
                    $ReportCardSubjects->aliasField('education_grade_id = ') .  $StudentsReportCardsComments->aliasField('education_grade_id'),
                    $ReportCardSubjects->aliasField('education_subject_id = ') .  $StudentsReportCardsComments->aliasField('education_subject_id'),
                ])
                ->where([
                    $StudentsReportCardsComments->aliasField('report_card_id') => $params['report_card_id'],
                    $StudentsReportCardsComments->aliasField('student_id') => $params['student_id'],
                    $StudentsReportCardsComments->aliasField('institution_id') => $params['institution_id'],
                    $StudentsReportCardsComments->aliasField('academic_period_id') => $params['academic_period_id'],
                    $StudentsReportCardsComments->aliasField('education_grade_id') => $extra['report_card_education_grade_id']
                ])
                ->autoFields(true)
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutions(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Institutions = TableRegistry::get('Institution.Institutions');
            $entity = $Institutions->get($params['institution_id'], ['contain' => ['Providers', 'Areas', 'AreaAdministratives']]);
            return $entity;
        }
    }

    public function onExcelTemplateInitialisePrincipal(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Staff = TableRegistry::get('Institution.Staff');
            $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
            $principalRoleId = $SecurityRoles->getPrincipalRoleId();

            $entity = $Staff
                ->find()
                ->select([
                    $Staff->aliasField('id'),
                    $Staff->aliasField('FTE'),
                    $Staff->aliasField('start_date'),
                    $Staff->aliasField('start_year'),
                    $Staff->aliasField('end_date'),
                    $Staff->aliasField('end_year'),
                    $Staff->aliasField('staff_id'),
                    $Staff->aliasField('security_group_user_id')
                ])
                ->innerJoinWith('SecurityGroupUsers')
                ->contain([
                    'Users' => [
                        'fields' => [
                            'openemis_no',
                            'first_name',
                            'middle_name',
                            'third_name',
                            'last_name',
                            'preferred_name',
                            'email',
                            'address',
                            'postal_code'
                        ]
                    ]
                ])
                ->where([
                    $Staff->aliasField('institution_id') => $params['institution_id'],
                    'SecurityGroupUsers.security_role_id' => $principalRoleId
                ])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseDeputyPrincipal(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Staff = TableRegistry::get('Institution.Staff');
            $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
            $deputyPrincipalRoleId = $SecurityRoles->getDeputyPrincipalRoleId();

            $entity = $Staff
                ->find()
                ->select([
                    $Staff->aliasField('id'),
                    $Staff->aliasField('FTE'),
                    $Staff->aliasField('start_date'),
                    $Staff->aliasField('start_year'),
                    $Staff->aliasField('end_date'),
                    $Staff->aliasField('end_year'),
                    $Staff->aliasField('staff_id'),
                    $Staff->aliasField('security_group_user_id')
                ])
                ->innerJoinWith('SecurityGroupUsers')
                ->contain([
                    'Users' => [
                        'fields' => [
                            'openemis_no',
                            'first_name',
                            'middle_name',
                            'third_name',
                            'last_name',
                            'preferred_name',
                            'email',
                            'address',
                            'postal_code'
                        ]
                    ]
                ])
                ->where([
                    $Staff->aliasField('institution_id') => $params['institution_id'],
                    'SecurityGroupUsers.security_role_id' => $deputyPrincipalRoleId
                ])
                ->first();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionClasses(Event $event, array $params, ArrayObject $extra)
    {
        
        if (array_key_exists('institution_class_id', $params)) {
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $entity = $InstitutionClasses->get($params['institution_class_id'], [
                'contain' => [
                    'Staff' => [
                        'fields' => [
                            'openemis_no',
                            'first_name',
                            'middle_name',
                            'third_name',
                            'last_name',
                            'preferred_name',
                            'email',
                            'address',
                            'postal_code'
                        ]
                    ],
                    'ClassesSecondaryStaff.SecondaryStaff' => [
                        'fields' => [
                            'openemis_no',
                            'first_name',
                            'middle_name',
                            'third_name',
                            'last_name',
                            'preferred_name',
                            'email',
                            'address',
                            'postal_code'
                        ]
                    ]
                ]
            ]);
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionSubjectStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_id', $params) && array_key_exists('institution_class_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('report_card_education_grade_id', $extra)) {
            $SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
            $entity = $SubjectStudents->find()
                ->where([
                    $SubjectStudents->aliasField('student_id') => $params['student_id'],
                    $SubjectStudents->aliasField('institution_class_id') => $params['institution_class_id'],
                    $SubjectStudents->aliasField('institution_id') => $params['institution_id'],
                    $SubjectStudents->aliasField('academic_period_id') => $params['academic_period_id'],
                    $SubjectStudents->aliasField('education_grade_id') => $extra['report_card_education_grade_id']
                ])
                ->hydrate(false)
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionSubjectStudentsWithName(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_id', $params) && array_key_exists('institution_class_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('report_card_education_grade_id', $extra)) {
            $SubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
            $entity = $SubjectStudents->find()
                ->where([
                    $SubjectStudents->aliasField('student_id') => $params['student_id'],
                    $SubjectStudents->aliasField('institution_class_id') => $params['institution_class_id'],
                    $SubjectStudents->aliasField('institution_id') => $params['institution_id'],
                    $SubjectStudents->aliasField('academic_period_id') => $params['academic_period_id'],
                    $SubjectStudents->aliasField('education_grade_id') => $extra['report_card_education_grade_id']
                ])
                ->contain([
                    'EducationSubjects'
                ])
                ->hydrate(false)
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentBehaviours(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('report_card_start_date', $extra) && array_key_exists('report_card_end_date', $extra)) {
            $StudentBehaviours = TableRegistry::get('Institution.StudentBehaviours');

            $entity = $StudentBehaviours->find()
                ->contain('StudentBehaviourCategories')
                ->where([
                    $StudentBehaviours->aliasField('student_id') => $params['student_id'],
                    $StudentBehaviours->aliasField('institution_id') => $params['institution_id'],
                    $StudentBehaviours->aliasField('date_of_behaviour >= ') => $extra['report_card_start_date'],
                    $StudentBehaviours->aliasField('date_of_behaviour <= ') => $extra['report_card_end_date']
                ])
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionStudentAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_class_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('student_id', $params) && array_key_exists('report_card_start_date', $extra) && array_key_exists('report_card_end_date', $extra)) {

            $startDate = $extra['report_card_start_date']->format('Y-m-d');
            $endDate = $extra['report_card_end_date']->format('Y-m-d');

            $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
            $studentAbsenceResults = $InstitutionStudentAbsences
                ->find()
                // ->find('inDateRange', ['start_date' => $extra['report_card_start_date'], 'end_date' => $extra['report_card_end_date']])
                ->innerJoin(
                    [$this->alias() => $this->table()],
                    [
                        $this->aliasField('institution_class_id') => $params['institution_class_id'],
                        $this->aliasField('institution_id = ') . $InstitutionStudentAbsences->aliasField('institution_id'),
                        $this->aliasField('student_id = ') . $InstitutionStudentAbsences->aliasField('student_id'),
                    ]
                )
                ->where([
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id'],
                    $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id'],
                    $InstitutionStudentAbsences->aliasField('date') . ' >= ' => $startDate,
                    $InstitutionStudentAbsences->aliasField('date') . ' <= ' => $endDate,
                ])
                ->hydrate(false)
                ->all();

            $AbsenceTypes = TableRegistry::get('Institution.AbsenceTypes');
            $absenceTypes = $AbsenceTypes->getCodeList();

            $results = [];
            foreach ($absenceTypes as $key => $code) {
                // initialize all types as 0
                $results[$code]['number_of_days'] = 0;
            }
            $results['TOTAL_ABSENCE']['number_of_days'] = 0;

            // sum all number_of_days a student absence in an academic period
            foreach ($studentAbsenceResults as $key => $obj) {
                $absenceType = $absenceTypes[$obj['absence_type_id']];

                if (in_array($absenceType, ['EXCUSED', 'UNEXCUSED'])) {
                    $results['TOTAL_ABSENCE']['number_of_days'] += 1;
                }

                $results[$absenceType]['number_of_days'] += 1;
            }
            return $results;
        }
    }

    public function onExcelTemplateInitialiseCompetencyTemplates(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('academic_period_id', $params) && array_key_exists('report_card_education_grade_id', $extra) && array_key_exists('report_card_start_date', $extra) && array_key_exists('report_card_end_date', $extra)) {
            $CompetencyTemplates = TableRegistry::get('Competency.CompetencyTemplates');

            // only get competency templates that have periods within the report card date
            $entity = $CompetencyTemplates->find()
                ->innerJoinWith('Periods')
                ->where([
                    $CompetencyTemplates->aliasField('academic_period_id') => $params['academic_period_id'],
                    $CompetencyTemplates->aliasField('education_grade_id') => $extra['report_card_education_grade_id'],
                    'Periods.start_date >= ' => $extra['report_card_start_date'],
                    'Periods.end_date <= ' => $extra['report_card_end_date']
                ])
                ->group($CompetencyTemplates->aliasField('id'));

            if ($entity->count() > 0) {
                $extra['competency_templates_ids'] = $entity->extract('id')->toArray();
            }
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseCompetencyPeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('academic_period_id', $params) && array_key_exists('competency_templates_ids', $extra) && !empty($extra['competency_templates_ids']) && array_key_exists('report_card_start_date', $extra) && array_key_exists('report_card_end_date', $extra)) {
            $CompetencyPeriods = TableRegistry::get('Competency.CompetencyPeriods');

            $entity = $CompetencyPeriods->find()
                ->where([
                    $CompetencyPeriods->aliasField('academic_period_id') => $params['academic_period_id'],
                    $CompetencyPeriods->aliasField('competency_template_id IN ') => $extra['competency_templates_ids'],
                    $CompetencyPeriods->aliasField('start_date >= ') => $extra['report_card_start_date'],
                    $CompetencyPeriods->aliasField('end_date <= ') => $extra['report_card_end_date']
                ]);

            if ($entity->count() > 0) {
                $extra['competency_periods_ids'] = $entity->extract('id')->toArray();
            }
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseCompetencyItems(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('academic_period_id', $params) && array_key_exists('competency_templates_ids', $extra) && !empty($extra['competency_templates_ids']) && array_key_exists('competency_periods_ids', $extra) && !empty($extra['competency_periods_ids'])) {
            $CompetencyItems = TableRegistry::get('Competency.CompetencyItems');

            // only get items in periods within the report card date
            $entity = $CompetencyItems->find()
                ->select(['competency_period_id' => 'Periods.id'])
                ->innerJoinWith('Periods')
                ->where([
                    $CompetencyItems->aliasField('academic_period_id') => $params['academic_period_id'],
                    $CompetencyItems->aliasField('competency_template_id IN ') => $extra['competency_templates_ids'],
                    'Periods.id IN' => $extra['competency_periods_ids']
                ])
                ->autoFields(true)
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseCompetencyCriterias(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('academic_period_id', $params) && array_key_exists('competency_templates_ids', $extra) && !empty($extra['competency_templates_ids']) && array_key_exists('competency_periods_ids', $extra) && !empty($extra['competency_periods_ids'])) {
            $CompetencyCriterias = TableRegistry::get('Competency.CompetencyCriterias');

            // only get criterias linked to items in periods within the report card date
            $entity = $CompetencyCriterias->find()
                ->select(['competency_period_id' => 'Periods.id'])
                ->innerJoinWith('Items.Periods')
                ->where([
                    $CompetencyCriterias->aliasField('academic_period_id') => $params['academic_period_id'],
                    $CompetencyCriterias->aliasField('competency_template_id IN ') => $extra['competency_templates_ids'],
                    'Periods.id IN' => $extra['competency_periods_ids']
                ])
                ->autoFields(true)
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentCompetencyPeriodComments(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('competency_templates_ids', $extra) && !empty($extra['competency_templates_ids']) && array_key_exists('competency_periods_ids', $extra) && !empty($extra['competency_periods_ids'])  && array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $CompetencyPeriodComments = TableRegistry::get('Institution.InstitutionCompetencyPeriodComments');

            $entity = $CompetencyPeriodComments->find()
                ->where([
                    $CompetencyPeriodComments->aliasField('competency_template_id IN ') => $extra['competency_templates_ids'],
                    $CompetencyPeriodComments->aliasField('competency_period_id IN ') => $extra['competency_periods_ids'],
                    $CompetencyPeriodComments->aliasField('student_id') => $params['student_id'],
                    $CompetencyPeriodComments->aliasField('institution_id') => $params['institution_id'],
                    $CompetencyPeriodComments->aliasField('academic_period_id') => $params['academic_period_id'],
                ])
                ->toArray();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentCompetencyItemComments(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('competency_templates_ids', $extra) && !empty($extra['competency_templates_ids']) && array_key_exists('competency_periods_ids', $extra) && !empty($extra['competency_periods_ids'])  && array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $CompetencyItemComments = TableRegistry::get('Institution.InstitutionCompetencyItemComments');

            $entity = $CompetencyItemComments->find()
                ->where([
                    $CompetencyItemComments->aliasField('competency_template_id IN ') => $extra['competency_templates_ids'],
                    $CompetencyItemComments->aliasField('competency_period_id IN ') => $extra['competency_periods_ids'],
                    $CompetencyItemComments->aliasField('student_id') => $params['student_id'],
                    $CompetencyItemComments->aliasField('institution_id') => $params['institution_id'],
                    $CompetencyItemComments->aliasField('academic_period_id') => $params['academic_period_id'],
                ])
                ->toArray();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseCompetencyCriteriasWithResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('competency_templates_ids', $extra) && !empty($extra['competency_templates_ids']) && array_key_exists('competency_periods_ids', $extra) && !empty($extra['competency_periods_ids']) && array_key_exists('academic_period_id', $params)) {
            $CompetencyCriterias = TableRegistry::get('Competency.CompetencyCriterias');

            // only get criterias linked to items in periods within the report card date
            $entity = $CompetencyCriterias->find()
                ->select(['competency_period_id' => 'CompetencyPeriods.id'])
                ->innerJoin(
                    ['InstitutionCompetencyResults' => 'institution_competency_results'],
                    [
                        $CompetencyCriterias->aliasField('id = ') . 'InstitutionCompetencyResults.competency_criteria_id',
                        $CompetencyCriterias->aliasField('academic_period_id = ') . 'InstitutionCompetencyResults.academic_period_id',
                        $CompetencyCriterias->aliasField('competency_item_id = ') . 'InstitutionCompetencyResults.competency_item_id',
                        $CompetencyCriterias->aliasField('competency_template_id = ') . 'InstitutionCompetencyResults.competency_template_id',
                    ]
                )
                ->innerJoin(
                    ['CompetencyItems' => 'competency_items'],
                    [
                        $CompetencyCriterias->aliasField('competency_item_id = ') . 'CompetencyItems.id',
                        $CompetencyCriterias->aliasField('competency_template_id = ') . 'CompetencyItems.competency_template_id',
                        $CompetencyCriterias->aliasField('academic_period_id = ') . 'CompetencyItems.academic_period_id'
                    ]
                )
                ->innerJoin(
                    ['CompetencyItemsPeriods' => 'competency_items_periods'],
                    [
                        'CompetencyItemsPeriods.competency_item_id = CompetencyItems.id',
                        'CompetencyItemsPeriods.competency_template_id = CompetencyItems.competency_template_id',
                        'CompetencyItemsPeriods.academic_period_id = CompetencyItems.academic_period_id'
                    ]
                )
                ->innerJoin(
                    ['CompetencyPeriods' => 'competency_periods'],
                    [
                        'CompetencyPeriods.id = CompetencyItemsPeriods.competency_period_id',
                        'CompetencyPeriods.id = InstitutionCompetencyResults.competency_period_id',
                        'CompetencyPeriods.academic_period_id = CompetencyItemsPeriods.academic_period_id'
                    ]
                )
                ->where([
                    'InstitutionCompetencyResults.competency_grading_option_id > 0',
                    'InstitutionCompetencyResults.student_id = ' . $params['student_id'],
                    $CompetencyCriterias->aliasField('competency_template_id IN ') => $extra['competency_templates_ids'],
                    'CompetencyPeriods.id IN' => $extra['competency_periods_ids'],
                    'InstitutionCompetencyResults.institution_id = ' . $params['institution_id'],
                    $CompetencyCriterias->aliasField('academic_period_id') => $params['academic_period_id']
                ])
                ->autoFields(true)
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentCompetencyResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('competency_templates_ids', $extra) && !empty($extra['competency_templates_ids']) && array_key_exists('competency_periods_ids', $extra) && !empty($extra['competency_periods_ids'])  && array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $StudentCompetencyResults = TableRegistry::get('Institution.InstitutionCompetencyResults');

            $entity = $StudentCompetencyResults->find()
                ->contain('CompetencyGradingOptions')
                ->where([
                    $StudentCompetencyResults->aliasField('competency_template_id IN ') => $extra['competency_templates_ids'],
                    $StudentCompetencyResults->aliasField('competency_period_id IN ') => $extra['competency_periods_ids'],
                    $StudentCompetencyResults->aliasField('student_id') => $params['student_id'],
                    $StudentCompetencyResults->aliasField('institution_id') => $params['institution_id'],
                    $StudentCompetencyResults->aliasField('academic_period_id') => $params['academic_period_id'],
                ])
                ->toArray();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAssessments(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('academic_period_id', $params) && array_key_exists('report_card_education_grade_id', $extra)) {
            $Assessments = TableRegistry::get('Assessment.Assessments');

            $entity = $Assessments->find()
                ->where([
                    $Assessments->aliasField('academic_period_id') => $params['academic_period_id'],
                    $Assessments->aliasField('education_grade_id') => $extra['report_card_education_grade_id']
                ])
                ->first();

            if (!empty($entity)) {
                $extra['assessment_id'] = $entity->id;
            }
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAssessmentPeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $extra) && array_key_exists('report_card_start_date', $extra) && array_key_exists('report_card_end_date', $extra)) {
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');

            $entity = $AssessmentPeriods->find()
                ->where([
                    $AssessmentPeriods->aliasField('assessment_id') => $extra['assessment_id'],
                    $AssessmentPeriods->aliasField('start_date >= ') => $extra['report_card_start_date'],
                    $AssessmentPeriods->aliasField('end_date <= ') => $extra['report_card_end_date']
                ])
                ->order([$AssessmentPeriods->aliasField('start_date')]);

            if ($entity->count() > 0) {
                $extra['assessment_period_ids'] = $entity->extract('id')->toArray();
            }
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseAssessmentItems(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $extra) && array_key_exists('institution_class_id', $params)) {
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
            $StudentSubjects = TableRegistry::get('Institution.InstitutionSubjectStudents');
            $entity = $AssessmentItems
                ->find('assessmentItemsInClass', [
                    'assessment_id' => $extra['assessment_id'],
                    'class_id' => $params['institution_class_id']
                ])
                //POCOR-5056 starts
                ->innerJoin([$StudentSubjects->alias() => $StudentSubjects->table()], [
                    $StudentSubjects->aliasField('education_subject_id = ') . $AssessmentItems->aliasField('education_subject_id')
                ])
                ->where([$StudentSubjects->aliasField('student_id') => $params['student_id']])
                //POCOR-5056 end

                ->toArray();
            
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAssessmentItemsStudentSubjects(Event $event, array $params, ArrayObject $extra)
    {
        if(array_key_exists('institution_class_id', $params) && array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('education_grade_id', $params) && array_key_exists('academic_period_id', $params)) {

            // To get the Assessment Item that template selected subject
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
            $query = $AssessmentItemResults->find();

            $selectedColumns = [
                $AssessmentItemResults->aliasField('education_subject_id'),
                'academic_term_value' => '(
                    CASE
                    WHEN AssessmentPeriods.academic_term <> \'\' THEN AssessmentPeriods.academic_term
                        ELSE AssessmentPeriods.name
                        END
                    )',
            ];
           
            $subjectList = $AssessmentItems
                ->find('list', [
                    'keyField' => 'education_subject_id',
                    'valueField' => 'education_subject_id'
                ])
                ->find('assessmentItemsInClass', [
                    'assessment_id' => $extra['assessment_id'],
                    'class_id' => $params['institution_class_id']
                ])
                ->toArray();
            
            // to only process the query if the class has subjects
            $conditions = [];
            if (!empty($subjectList)) {
                $conditions = [
                    $AssessmentItemResults->aliasField('assessment_id') => $extra['assessment_id'],
                    $AssessmentItemResults->aliasField('assessment_period_id IN ') => $extra['assessment_period_ids'],
                    $AssessmentItemResults->aliasField('institution_id') => $params['institution_id'],
                    $AssessmentItemResults->aliasField('student_id') => $params['student_id'],
                    $AssessmentItemResults->aliasField('education_grade_id') => $extra['report_card_education_grade_id'],
                    $AssessmentItemResults->aliasField('academic_period_id') => $params['academic_period_id'],
                    $AssessmentItemResults->aliasField('education_subject_id IN') => $subjectList
                ];
            } else {
                $conditions = ['1 = 0'];
            }

            $entity = $query
                ->select($selectedColumns)
                ->contain(['AssessmentPeriods', 'EducationSubjects'])
                ->where($conditions)
                ->group([
                    $AssessmentItemResults->aliasField('education_subject_id')
                ])
                ->hydrate(false)
                ->toArray();

            // To get the student subject based on the template selected subject
            $StudentSubjects = TableRegistry::get('Student.StudentSubjects');
            $studentRegisteredSubjectAndInsideTemplate = [];
            foreach ($entity as $value) {
                $studentSubjectsQuery = $StudentSubjects->find();
                $studentSubjectsEntity = $studentSubjectsQuery
                    ->where([
                        $StudentSubjects->aliasField('institution_class_id') => $params['institution_class_id'],
                        $StudentSubjects->aliasField('student_id') => $params['student_id'],
                        $StudentSubjects->aliasField('institution_id') => $params['institution_id'],
                        $StudentSubjects->aliasField('education_grade_id') => $params['education_grade_id'],
                        $StudentSubjects->aliasField('education_subject_id') => $value['education_subject_id'],
                        $StudentSubjects->aliasField('academic_period_id') => $params['academic_period_id']
                    ])
                    ->contain([
                        'InstitutionSubjects'
                    ])
                    ->hydrate(false)
                    ->all();

                if(!$studentSubjectsEntity->isEmpty()) {
                    array_push($studentRegisteredSubjectAndInsideTemplate, $studentSubjectsEntity->first());
                }
            }
            return $studentRegisteredSubjectAndInsideTemplate;
        }
    }

    public function onExcelTemplateInitialiseAssessmentItemsWithResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_class_id', $params) && array_key_exists('assessment_id', $extra) && array_key_exists('assessment_period_ids', $extra) && !empty($extra['assessment_period_ids']) && array_key_exists('institution_id', $params) && array_key_exists('student_id', $params) && array_key_exists('report_card_education_grade_id', $extra) && array_key_exists('academic_period_id', $params)) {

            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
            
            $entity = $AssessmentItems->find()
                ->find('assessmentItemsInClass', [
                    'assessment_id' => $extra['assessment_id'],
                    'class_id' => $params['institution_class_id']
                ])
                ->innerJoin(
                    ['AssessmentItemResults' => 'assessment_item_results'],
                    [
                        $AssessmentItems->aliasField('assessment_id = ') . 'AssessmentItemResults.assessment_id',
                        $AssessmentItems->aliasField('education_subject_id = ') . 'AssessmentItemResults.education_subject_id'
                    ]
                )
                ->where([
                    'AssessmentItemResults.marks IS NOT NULL',
                    'AssessmentItemResults.student_id = ' . $params['student_id'],
                    'AssessmentItemResults.education_grade_id = ' . $extra['report_card_education_grade_id'],
                    'AssessmentItemResults.academic_period_id = ' . $params['academic_period_id'],
                    'AssessmentItemResults.assessment_period_id IN ' => $extra['assessment_period_ids'],
                    'AssessmentItemResults.institution_id = ' . $params['institution_id']
                ])
                ->distinct()
                ->toArray();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAssessmentItemResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_class_id', $params) && array_key_exists('assessment_id', $extra) && array_key_exists('assessment_period_ids', $extra) && !empty($extra['assessment_period_ids']) && array_key_exists('institution_id', $params) && array_key_exists('student_id', $params) && array_key_exists('report_card_education_grade_id', $extra) && array_key_exists('academic_period_id', $params)) {
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');

            $subjectList = $AssessmentItems
                ->find('list', [
                    'keyField' => 'education_subject_id',
                    'valueField' => 'education_subject_id'
                ])
                ->find('assessmentItemsInClass', [
                    'assessment_id' => $extra['assessment_id'],
                    'class_id' => $params['institution_class_id']
                ])
                ->toArray();

            // to only process the query if the class has subjects
            $conditions = [];
            if (!empty($subjectList)) {
                $conditions = [
                    $AssessmentItemResults->aliasField('assessment_id') => $extra['assessment_id'],
                    $AssessmentItemResults->aliasField('assessment_period_id IN ') => $extra['assessment_period_ids'],
                    $AssessmentItemResults->aliasField('institution_id') => $params['institution_id'],
                    $AssessmentItemResults->aliasField('student_id') => $params['student_id'],
                    $AssessmentItemResults->aliasField('education_grade_id') => $extra['report_card_education_grade_id'],
                    $AssessmentItemResults->aliasField('academic_period_id') => $params['academic_period_id'],
                    $AssessmentItemResults->aliasField('education_subject_id IN') => $subjectList
                ];
            } else {
                $conditions = ['1 = 0'];
            }

            $entity = $AssessmentItemResults->find()
                ->innerJoin(
                    [$this->alias() => $this->table()],
                    [
                        $this->aliasField('institution_id = ') . $AssessmentItemResults->aliasField('institution_id'),
                        $this->aliasField('academic_period_id = ') . $AssessmentItemResults->aliasField('academic_period_id'),
                        $this->aliasField('education_grade_id = ') . $AssessmentItemResults->aliasField('education_grade_id'),
                        $this->aliasField('student_id = ') . $AssessmentItemResults->aliasField('student_id'),
                        $this->aliasField('institution_class_id') => $params['institution_class_id']
                    ]
                )
                ->contain(['AssessmentGradingOptions.AssessmentGradingTypes'])
                ->where($conditions)
                ->formatResults(function (ResultSetInterface $results) {
                    return $results->map(function ($row) {
                        $resultType = $row['assessment_grading_option']['assessment_grading_type']['result_type'];

                        switch ($resultType) {
                            case 'MARKS':
                                $row['marks_formatted'] = number_format($row['marks'], 2);
                                break;
                            case 'GRADES':
                                $row['marks_formatted'] = $row['assessment_grading_option']['code'] . ' - ' . $row['assessment_grading_option']['name'];
                                break;
                            case 'DURATION':
                                if (strlen($row['marks']) > 0) {
                                    $duration = number_format($row['marks'], 2);

                                    list($minutes, $seconds) = explode(".", $duration, 2);
                                    $row['marks_formatted'] = $minutes . " : " . $seconds;
                                    break;
                                }
                            default:
                                $row['marks_formatted'] = '';
                                break;
                        }

                        return $row;
                    });
                })
                ->toArray();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseOutcomeTemplates(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('academic_period_id', $params) && array_key_exists('report_card_education_grade_id', $extra) && array_key_exists('report_card_start_date', $extra) && array_key_exists('report_card_end_date', $extra)) {
            $OutcomeTemplates = TableRegistry::get('Outcome.OutcomeTemplates');

            $entity = $OutcomeTemplates
                ->find()
                ->innerJoinWith('Periods')
                ->where([
                    $OutcomeTemplates->aliasField('academic_period_id') => $params['academic_period_id'],
                    $OutcomeTemplates->aliasField('education_grade_id') => $extra['report_card_education_grade_id'],
                    'Periods.start_date >= ' => $extra['report_card_start_date'],
                    'Periods.end_date <= ' => $extra['report_card_end_date']
                ])
                ->group($OutcomeTemplates->aliasField('id'));

            if ($entity->count() > 0) {
                $extra['outcome_templates_ids'] = $entity->extract('id')->toArray();
            }
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseOutcomePeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('academic_period_id', $params) && array_key_exists('outcome_templates_ids', $extra) && !empty($extra['outcome_templates_ids']) && array_key_exists('report_card_start_date', $extra) && array_key_exists('report_card_end_date', $extra)) {
            $OutcomePeriods = TableRegistry::get('Outcome.OutcomePeriods');

            $entity = $OutcomePeriods->find()
                ->where([
                    $OutcomePeriods->aliasField('academic_period_id') => $params['academic_period_id'],
                    $OutcomePeriods->aliasField('outcome_template_id IN ') => $extra['outcome_templates_ids'],
                    $OutcomePeriods->aliasField('start_date >= ') => $extra['report_card_start_date'],
                    $OutcomePeriods->aliasField('end_date <= ') => $extra['report_card_end_date']
                ]);

            if ($entity->count() > 0) {
                $extra['outcome_periods_ids'] = $entity->extract('id')->toArray();
            }
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseOutcomeSubjects(Event $event, array $params, ArrayObject $extra)
    {

        if (array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('education_grade_id', $params) && array_key_exists('institution_class_id', $params) &&array_key_exists('outcome_periods_ids', $extra) && !empty($extra['outcome_periods_ids'])) {

            $classId = $params['institution_class_id'];
            $studentId  = $params['student_id'];
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
                ->innerJoinWith('InstitutionSubjects.ClassSubjects', function ($q) use ($classId) {
                    return $q->where(['ClassSubjects.institution_class_id' => $classId]);
                })
                //POCOR-5056 starts
                ->innerJoinWith('InstitutionSubjects.SubjectStudents', function ($q) use ($studentId) {
                    return $q->where(['SubjectStudents.student_id' => $studentId]);
                })
                //POCOR-5056 ends
                ->leftJoin([$OutcomePeriods->alias() => $OutcomePeriods->table()], [
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
                ->autoFields(true);
            
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseOutcomeCriterias(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('academic_period_id', $params) && array_key_exists('outcome_templates_ids', $extra) && !empty($extra['outcome_templates_ids'])) {
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
                ->autoFields(true);

            return $entity->toArray();

        }
    }


    public function onExcelTemplateInitialiseStudentOutcomeSubjectComments(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('outcome_templates_ids', $extra) && !empty($extra['outcome_templates_ids']) && array_key_exists('outcome_periods_ids', $extra) && !empty($extra['outcome_periods_ids'])  && array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {

            $OutcomeSubjectComments = TableRegistry::get('Institution.InstitutionOutcomeSubjectComments');
            $entity = $OutcomeSubjectComments->find()
                ->where([
                    $OutcomeSubjectComments->aliasField('outcome_template_id IN ') => $extra['outcome_templates_ids'],
                    $OutcomeSubjectComments->aliasField('outcome_period_id IN ') => $extra['outcome_periods_ids'],
                    $OutcomeSubjectComments->aliasField('student_id') => $params['student_id'],
                    $OutcomeSubjectComments->aliasField('institution_id') => $params['institution_id'],
                    $OutcomeSubjectComments->aliasField('academic_period_id') => $params['academic_period_id'],
                ]);

            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseStudentOutcomeResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('outcome_templates_ids', $extra) && !empty($extra['outcome_templates_ids']) && array_key_exists('outcome_periods_ids', $extra) && !empty($extra['outcome_periods_ids'])  && array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $StudentOutcomeResults = TableRegistry::get('Institution.InstitutionOutcomeResults');

            $entity = $StudentOutcomeResults->find()
                ->contain('OutcomeGradingOptions')
                ->where([
                    $StudentOutcomeResults->aliasField('outcome_template_id IN ') => $extra['outcome_templates_ids'],
                    $StudentOutcomeResults->aliasField('outcome_period_id IN ') => $extra['outcome_periods_ids'],
                    $StudentOutcomeResults->aliasField('student_id') => $params['student_id'],
                    $StudentOutcomeResults->aliasField('institution_id') => $params['institution_id'],
                    $StudentOutcomeResults->aliasField('academic_period_id') => $params['academic_period_id'],
                ]);

            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseGroupAssessmentItemResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_class_id', $params) && array_key_exists('assessment_id', $extra) && array_key_exists('assessment_period_ids', $extra) && !empty($extra['assessment_period_ids']) && array_key_exists('institution_id', $params) && array_key_exists('student_id', $params) && array_key_exists('report_card_education_grade_id', $extra) && array_key_exists('academic_period_id', $params)) {
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
            $query = $AssessmentItemResults->find();

            $selectedColumns = [
                $AssessmentItemResults->aliasField('education_subject_id'),
                'marks' => $query->newExpr('SUM(AssessmentItemResults.marks * AssessmentPeriods.weight)'),
                'academic_term_value' => '(
                    CASE
                    WHEN AssessmentPeriods.academic_term <> \'\' THEN AssessmentPeriods.academic_term
                        ELSE AssessmentPeriods.name
                        END
                    )',
            ];

            $subjectList = $AssessmentItems
                ->find('list', [
                    'keyField' => 'education_subject_id',
                    'valueField' => 'education_subject_id'
                ])
                ->find('assessmentItemsInClass', [
                    'assessment_id' => $extra['assessment_id'],
                    'class_id' => $params['institution_class_id']
                ])
                ->toArray();

            // to only process the query if the class has subjects
            $conditions = [];
            if (!empty($subjectList)) {
                $conditions = [
                    $AssessmentItemResults->aliasField('assessment_id') => $extra['assessment_id'],
                    $AssessmentItemResults->aliasField('assessment_period_id IN ') => $extra['assessment_period_ids'],
                    $AssessmentItemResults->aliasField('institution_id') => $params['institution_id'],
                    $AssessmentItemResults->aliasField('student_id') => $params['student_id'],
                    $AssessmentItemResults->aliasField('education_grade_id') => $extra['report_card_education_grade_id'],
                    $AssessmentItemResults->aliasField('academic_period_id') => $params['academic_period_id'],
                    $AssessmentItemResults->aliasField('education_subject_id IN') => $subjectList
                ];
            } else {
                $conditions = ['1 = 0'];
            }

            $entity = $query
                ->select($selectedColumns)
                ->contain(['AssessmentPeriods', 'EducationSubjects'])
                ->where($conditions)
                ->group([
                    $AssessmentItemResults->aliasField('education_subject_id'),
                    'academic_term_value'
                ])
                ->formatResults(function (ResultSetInterface $results) {
                    return $results->map(function ($row) {
                        $row['marks_formatted'] = number_format($row['marks'], 2);
                        return $row;
                    });
                })
                ->hydrate(false)
                ->toArray();

            $averageResult = [];
            foreach ($entity as $array) {
                $educationSubjectId = $array['education_subject_id'];
                if (array_key_exists($educationSubjectId, $averageResult)) {
                    ++$averageResult[$educationSubjectId]['count'];
                    $averageResult[$educationSubjectId]['total_marks'] += $array['marks'];
                } else {
                    $averageResult[$educationSubjectId] = [
                        'count' => 1,
                        'total_marks' => $array['marks']
                    ];
                }
            }

            foreach ($averageResult as $key => $value) {
                $entity[] = [
                    'education_subject_id' => $key,
                    'academic_term_value' => __('Average'),
                    'marks_formatted' => number_format($value['total_marks'] / $value['count'], 2)
                ];
            }

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseGroupAssessmentPeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $extra)) {
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');

            $selectedColumns = [
                'academic_term_value' => '(
                    CASE
                    WHEN ' . $AssessmentPeriods->aliasField('academic_term <> \'\'') . ' THEN ' . $AssessmentPeriods->aliasField('academic_term') . '
                        ELSE ' . $AssessmentPeriods->aliasField('name') . '
                        END
                    )'
            ];

            $entity = $AssessmentPeriods
                ->find()
                ->select($selectedColumns)
                ->where([$AssessmentPeriods->aliasField('assessment_id') => $extra['assessment_id']])
                ->group([('academic_term_value')])
                ->order([$AssessmentPeriods->aliasField('start_date')])
                ->hydrate(false)
                ->toArray();

            $entity[] = [
                'academic_term_value' => __('Average')
            ];

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAssessmentTermResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_class_id', $params) && array_key_exists('assessment_id', $extra) && array_key_exists('assessment_period_ids', $extra) && !empty($extra['assessment_period_ids']) && array_key_exists('institution_id', $params) && array_key_exists('student_id', $params) && array_key_exists('report_card_education_grade_id', $extra) && array_key_exists('academic_period_id', $params)) {
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
            $query = $AssessmentItemResults->find();

            $selectedColumns = [
                $AssessmentItemResults->aliasField('education_subject_id'),
                'marks' => $query->newExpr('SUM(AssessmentItemResults.marks * AssessmentPeriods.weight)'),
                'academic_term_value' => '(
                    CASE
                    WHEN AssessmentPeriods.academic_term <> \'\' THEN AssessmentPeriods.academic_term
                        ELSE AssessmentPeriods.code
                        END
                    )',
                'academic_term_name' => '(
                    CASE
                    WHEN AssessmentPeriods.academic_term <> \'\' THEN AssessmentPeriods.academic_term
                        ELSE AssessmentPeriods.name
                        END
                    )'
            ];

            $subjectList = $AssessmentItems
                ->find('list', [
                    'keyField' => 'education_subject_id',
                    'valueField' => 'education_subject_id'
                ])
                ->find('assessmentItemsInClass', [
                    'assessment_id' => $extra['assessment_id'],
                    'class_id' => $params['institution_class_id']
                ])
                ->toArray();

            // to only process the query if the class has subjects
            $conditions = [];
            if (!empty($subjectList)) {
                $conditions = [
                    $AssessmentItemResults->aliasField('assessment_id') => $extra['assessment_id'],
                    $AssessmentItemResults->aliasField('assessment_period_id IN ') => $extra['assessment_period_ids'],
                    $AssessmentItemResults->aliasField('institution_id') => $params['institution_id'],
                    $AssessmentItemResults->aliasField('student_id') => $params['student_id'],
                    $AssessmentItemResults->aliasField('education_grade_id') => $extra['report_card_education_grade_id'],
                    $AssessmentItemResults->aliasField('academic_period_id') => $params['academic_period_id'],
                    $AssessmentItemResults->aliasField('education_subject_id IN') => $subjectList
                ];
            } else {
                $conditions = ['1 = 0'];
            }

            $entity = $query
                ->select($selectedColumns)
                ->contain(['AssessmentPeriods', 'EducationSubjects'])
                ->where($conditions)
                ->group([
                    'academic_term_value',
                    $AssessmentItemResults->aliasField('education_subject_id')
                ])
                ->formatResults(function (ResultSetInterface $results) {
                    return $results->map(function ($row) {
                        $row['marks_formatted'] = number_format($row['marks'], 2);
                        return $row;
                    });
                })
                ->hydrate(false)
                ->toArray();

            $tempResult = [];
            foreach ($entity as $array) {
                $termCode = $array['academic_term_value'];
                if (array_key_exists($termCode, $tempResult)) {
                    ++$tempResult[$termCode]['count'];
                    $tempResult[$termCode]['total_marks'] += $array['marks'];
                } else {
                    $tempResult[$termCode] = [
                        'count' => 1,
                        'total_marks' => $array['marks'],
                        'academic_term_value' => $array['academic_term_value'],
                        'academic_term_name' => $array['academic_term_name']
                    ];
                }
            }

            $result = [];
            foreach ($tempResult as $key => $value) {
                $result[$key] = [
                    'academic_term_value' => $value['academic_term_value'],
                    'academic_term_name' => $value['academic_term_name'],
                    'total_marks' => number_format($value['total_marks'], 2),
                    'average_marks' => number_format($value['total_marks'] / $value['count'], 2)
                ];
            }

            return $result;
        }
    }

    public function onExcelTemplateInitialiseNextClassSubjects(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_id', $params) && array_key_exists('institution_class_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('report_card_education_grade_id', $extra)) {

            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');

            $nextAcademicPeriodId = $AcademicPeriods->getNextAcademicPeriodId($params['academic_period_id']);
            $studentId = $params['student_id'];
            $institutionId = $params['institution_id'];

            $InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');

            $institutionSubjectStudentsEntities = $InstitutionSubjectStudents->find()
                ->select([
                    $InstitutionSubjectStudents->InstitutionSubjects->aliasField('name')
                ])
                ->where([
                    $InstitutionSubjectStudents->aliasField('student_id') => $studentId,
                    $InstitutionSubjectStudents->aliasField('academic_period_id') => $nextAcademicPeriodId,
                    $InstitutionSubjectStudents->aliasField('institution_id') => $institutionId,
                ])
                ->contain('InstitutionSubjects')
                ->hydrate(false)
                ->all();

            if (!$institutionSubjectStudentsEntities->isEmpty()) {
                    foreach ($institutionSubjectStudentsEntities->toArray() as $key => $value) {
                        $result[$key] = [
                            'name' => $value['InstitutionSubjects']['name']
                        ];
                    }

                    return $result;
                }

                return null;
        }
    }
    
    //  POCOR-4988
    public function onExcelTemplateInitialiseStudentNextYearClass(Event $event, array $params, ArrayObject $extra)
    {
        
        $condition =  array_key_exists('student_id', $params) 
                      && array_key_exists('institution_class_id', $params) 
                      && array_key_exists('institution_id', $params) 
                      && array_key_exists('academic_period_id', $params)
                      && array_key_exists('report_card_education_grade_id', $extra);
        
        if ($condition) {
            $studentId = $params['student_id'];
            $institutionId = $params['institution_id'];
            $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $institutionClassStudentsEntities = $InstitutionClassStudents->find()
                ->select(['InstitutionClasses.name'])
                ->where([
                    $InstitutionClassStudents->aliasField('student_id') => $studentId,
                    $InstitutionClassStudents->aliasField('academic_period_id') => $params['academic_period_id'],
                    $InstitutionClassStudents->aliasField('institution_id') => $institutionId,
                ])
                ->innerJoin(['InstitutionClasses' => 'institution_classes'], [
                    'InstitutionClasses.id = '.$InstitutionClassStudents->aliasField('next_institution_class_id')
                ])                
                ->hydrate(false)
                ->first();               
            $result['name'] = $institutionClassStudentsEntities['InstitutionClasses']['name'];
            return $result;
        }
    }
}
