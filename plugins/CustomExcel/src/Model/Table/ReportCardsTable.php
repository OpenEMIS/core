<?php
namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;

class ReportCardsTable extends AppTable
{
    private $fileType = 'xlsx';

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
                'InstitutionClasses',
                'InstitutionSubjectStudents',
                'StudentBehaviours',
                'InstitutionStudentAbsences',
                'CompetencyTemplates',
                'CompetencyPeriods',
                'CompetencyItems',
                'CompetencyCriterias',
                'StudentCompetencyResults',
                'Assessments',
                'AssessmentPeriods',
                'AssessmentItems',
                'AssessmentItemResults'
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
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionClasses'] = 'onExcelTemplateInitialiseInstitutionClasses';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionSubjectStudents'] = 'onExcelTemplateInitialiseInstitutionSubjectStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentBehaviours'] = 'onExcelTemplateInitialiseStudentBehaviours';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentAbsences'] = 'onExcelTemplateInitialiseInstitutionStudentAbsences';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyTemplates'] = 'onExcelTemplateInitialiseCompetencyTemplates';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyPeriods'] = 'onExcelTemplateInitialiseCompetencyPeriods';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyItems'] = 'onExcelTemplateInitialiseCompetencyItems';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyCriterias'] = 'onExcelTemplateInitialiseCompetencyCriterias';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentCompetencyResults'] = 'onExcelTemplateInitialiseStudentCompetencyResults';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessments'] = 'onExcelTemplateInitialiseAssessments';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentPeriods'] = 'onExcelTemplateInitialiseAssessmentPeriods';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItems'] = 'onExcelTemplateInitialiseAssessmentItems';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItemResults'] = 'onExcelTemplateInitialiseAssessmentItemResults';
        $events['ExcelTemplates.Model.afterRenderExcelTemplate'] = 'afterRenderExcelTemplate';
        return $events;
    }

    public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        if (!$StudentsReportCards->exists($params)) {
            // insert student report card record if it does not exist
            $params['status'] = $StudentsReportCards::IN_PROGRESS;
            $newEntity = $StudentsReportCards->newEntity($params);
            $StudentsReportCards->save($newEntity);
        } else {
            // update status to in progress if record exists
            $StudentsReportCards->updateAll(['status' => $StudentsReportCards::IN_PROGRESS], $params);
        }
    }

    public function onExcelTemplateAfterGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $studentReportCardData = $StudentsReportCards->find()
            ->contain(['Institutions', 'ReportCards', 'Students'])
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
            'file_name' => $fileName,
            'file_content' => $fileContent
        ], $params);
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
            'report_card_id' => $params['report_card_id']
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

            $entity = $StudentsReportCards->find()
                ->contain([
                    'Students' => ['BirthplaceAreas', 'MainNationalities', 'AddressAreas'],
                    'EducationGrades'
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

                $BodyMasses = TableRegistry::get('Institution.BodyMasses');
                $bodyMassData = $BodyMasses->find()
                    ->where([
                        $BodyMasses->aliasField('user_id') => $studentId,
                        $BodyMasses->aliasField('date >= ') => $reportCardStartDate,
                        $BodyMasses->aliasField('date <= ') => $reportCardEndDate,
                    ])
                    ->order([
                        $BodyMasses->aliasField('date') => 'DESC'
                    ])
                    ->first();

                if (!empty($bodyMassData)) {
                    $entity->body_mass = $bodyMassData;
                    $entity->body_mass->date = $entity->body_mass->date->format($dateFormat);
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
            $entity = $StudentGuardians->find()
                    ->contain(['Users', 'GuardianRelations'])
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
            $entity = $Institutions->get($params['institution_id']);
            return $entity;
        }
    }

    public function onExcelTemplateInitialisePrincipal(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Staff = TableRegistry::get('Institution.Staff');
            $SecurityRoles = TableRegistry::get('Security.SecurityRoles');
            $principalRoleId = $SecurityRoles->getPrincipalRoleId();

            $entity = $Staff->find()
                ->innerJoinWith('SecurityGroupUsers')
                ->contain('Users')
                ->where([
                    $Staff->aliasField('institution_id') => $params['institution_id'],
                    'SecurityGroupUsers.security_role_id' => $principalRoleId
                ])
                ->first();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionClasses(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_class_id', $params)) {
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $entity = $InstitutionClasses->get($params['institution_class_id'], ['contain' => ['Staff']]);
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

            $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
            $studentAbsenceResults = $InstitutionStudentAbsences
                ->find('inDateRange', ['start_date' => $extra['report_card_start_date'], 'end_date' => $extra['report_card_end_date']])
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
                    $InstitutionStudentAbsences->aliasField('student_id') => $params['student_id']
                ])
                ->formatResults(function (ResultSetInterface $results) {
                    return $results->map(function ($row) {
                        $startDate = $row['start_date'];
                        $endDate = $row['end_date'];
                        $interval = $endDate->diff($startDate);
                        // plus 1 day because if absence for the same day, interval diff return zero
                        $row['number_of_days'] = $interval->days + 1;
                        return $row;
                    });
                })
                ->hydrate(false)
                ->all();

            $AbsenceTypes = TableRegistry::get('Institution.AbsenceTypes');
            $absenceTypes = $AbsenceTypes->getCodeList();

             $results = [];
             foreach($absenceTypes as $key => $code) {
                // initialize all types as 0
                $results[$code]['number_of_days'] = 0;
             }

            // sum all number_of_days a student absence in an academic period
            foreach ($studentAbsenceResults as $key => $obj) {
                $numberOfDays = $obj['number_of_days'];
                $absenceType = $absenceTypes[$obj['absence_type_id']];
                $results[$absenceType]['number_of_days'] += $numberOfDays;
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

    public function onExcelTemplateInitialiseStudentCompetencyResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('competency_templates_ids', $extra) && !empty($extra['competency_templates_ids']) && array_key_exists('competency_periods_ids', $extra) && !empty($extra['competency_periods_ids'])  && array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $StudentCompetencyResults = TableRegistry::get('Institution.StudentCompetencyResults');

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
        if (array_key_exists('assessment_id', $extra)) {
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');

            $entity = $AssessmentItems->find()
                ->contain(['EducationSubjects'])
                ->where([$AssessmentItems->aliasField('assessment_id') => $extra['assessment_id']])
                ->order(['EducationSubjects.order'])
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAssessmentItemResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_class_id', $params) && array_key_exists('assessment_id', $extra) && array_key_exists('assessment_period_ids', $extra) && !empty($extra['assessment_period_ids']) && array_key_exists('institution_id', $params) && array_key_exists('student_id', $params) && array_key_exists('report_card_education_grade_id', $extra) && array_key_exists('academic_period_id', $params)) {
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');

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
                ->where([
                    $AssessmentItemResults->aliasField('assessment_id') => $extra['assessment_id'],
                    $AssessmentItemResults->aliasField('assessment_period_id IN ') => $extra['assessment_period_ids'],
                    $AssessmentItemResults->aliasField('institution_id') => $params['institution_id'],
                    $AssessmentItemResults->aliasField('student_id') => $params['student_id'],
                    $AssessmentItemResults->aliasField('education_grade_id') => $extra['report_card_education_grade_id'],
                    $AssessmentItemResults->aliasField('academic_period_id') => $params['academic_period_id'],
                ])
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
}
