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
    private $groupAssessmentPeriodCount = 0;

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
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        $this->hasMany('SubjectStudents', [
            'className' => 'Institution.InstitutionSubjectStudents',
            'foreignKey' => ['institution_class_id', 'student_id'],
            'bindingKey' => ['institution_class_id', 'student_id']
        ]);

        $this->addBehavior('CustomExcel.ExcelReport', [
            'templateTable' => 'ReportCard.ReportCards',
            'templateTableKey' => 'report_card_id',
            'format' => 'pdf',
            'save' => true,
            'variables' => [
                'ReportCards',
                'InstitutionStudentsReportCards',
                'ClassStudents',
                'Institutions',
                'InstitutionClasses',
                'StudentBehaviours',
                'InstitutionStudentAbsences',
                'CompetencyTemplates',
                'CompetencyItems',
                'CompetencyCriterias',
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
        $events['ExcelTemplates.Model.onExcelTemplateSave'] = 'onExcelTemplateSave';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseReportCards'] = 'onExcelTemplateInitialiseReportCards';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentsReportCards'] = 'onExcelTemplateInitialiseInstitutionStudentsReportCards';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseClassStudents'] = 'onExcelTemplateInitialiseClassStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutions'] = 'onExcelTemplateInitialiseInstitutions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionClasses'] = 'onExcelTemplateInitialiseInstitutionClasses';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseStudentBehaviours'] = 'onExcelTemplateInitialiseStudentBehaviours';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentAbsences'] = 'onExcelTemplateInitialiseInstitutionStudentAbsences';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyTemplates'] = 'onExcelTemplateInitialiseCompetencyTemplates';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyItems'] = 'onExcelTemplateInitialiseCompetencyItems';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseCompetencyCriterias'] = 'onExcelTemplateInitialiseCompetencyCriterias';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessments'] = 'onExcelTemplateInitialiseAssessments';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentPeriods'] = 'onExcelTemplateInitialiseAssessmentPeriods';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItems'] = 'onExcelTemplateInitialiseAssessmentItems';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItemResults'] = 'onExcelTemplateInitialiseAssessmentItemResults';
        return $events;
    }

    public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        if (!$StudentsReportCards->exists($params)) {
            $params['status'] = $StudentsReportCards::IN_PROGRESS;
            $newEntity = $StudentsReportCards->newEntity($params);
            $StudentsReportCards->save($newEntity);
        } else {
            $StudentsReportCards->updateAll(['status' => $StudentsReportCards::IN_PROGRESS], $params);
        }
    }

    public function onExcelTemplateSave(Event $event, array $params, ArrayObject $extra)
    {
        $StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
        $status = $StudentsReportCards::GENERATED;
        $filepath = $extra['file_path'];
        $fileName = basename($filepath);
        $fileContent = file_get_contents($filepath);

        $StudentsReportCards->updateAll([
            'status' => $status,
            'file_name' => $fileName,
            'file_content' => $fileContent
        ], $params);
    }

    public function onExcelTemplateInitialiseReportCards(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('report_card_id', $params)) {
            $ReportCards = TableRegistry::get('ReportCard.ReportCards');
            $entity = $ReportCards->get($params['report_card_id'], [
                    'contain' => ['AcademicPeriods', 'EducationGrades']
                ]);

            $extra['report_card_start_date'] = $entity->start_date;
            $extra['report_card_end_date'] = $entity->end_date;
            $extra['report_card_education_grade_id'] = $entity->education_grade_id;
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutionStudentsReportCards(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('report_card_id', $params) && array_key_exists('student_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $StudentsReportCards = TableRegistry::get('Institution.InstitutionStudentsReportCards');
            $entity = $StudentsReportCards->find()
                ->contain(['StudentsReportCardsComments.EducationSubjects', 'StudentsReportCardsComments.CommentCodes'])
                ->where([
                    $StudentsReportCards->aliasField('report_card_id') => $params['report_card_id'],
                    $StudentsReportCards->aliasField('student_id') => $params['student_id'],
                    $StudentsReportCards->aliasField('institution_id') => $params['institution_id'],
                    $StudentsReportCards->aliasField('academic_period_id') => $params['academic_period_id'],
                    $StudentsReportCards->aliasField('education_grade_id') => $extra['report_card_education_grade_id']
                ])
                ->first();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseClassStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_class_id', $params)) {
            $entity = $this->find()
                ->contain([
                    'Users' => ['BirthplaceAreas', 'MainNationalities'],
                    'EducationGrades'
                ])
                ->where([
                    $this->aliasField('institution_class_id') => $params['institution_class_id'],
                    $this->aliasField('student_id') => $params['student_id']
                ])
                ->first();
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

    public function onExcelTemplateInitialiseInstitutionClasses(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_class_id', $params)) {
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $entity = $InstitutionClasses->get($params['institution_class_id'], [
                    'contain' => ['Staff']
                ])
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseStudentBehaviours(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('student_id', $params)) {
            $StudentBehaviours = TableRegistry::get('Institution.StudentBehaviours');
            $entity = $StudentBehaviours->find()
                ->contain('StudentBehaviourCategories')
                ->where([
                    $StudentBehaviours->aliasField('student_id') => $params['student_id'],
                    $StudentBehaviours->aliasField('institution_id') => $params['institution_id'],
                    $StudentBehaviours->aliasField('date_of_behaviour > ') => $extra['report_card_start_date'],
                    $StudentBehaviours->aliasField('date_of_behaviour < ') => $extra['report_card_end_date'],
                ])
                ->toArray();
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseInstitutionStudentAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_class_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params) && array_key_exists('student_id', $params)) {

            $AbsenceTypes = TableRegistry::get('Institution.AbsenceTypes');
            $absenceTypes = $AbsenceTypes->getCodeList();

            $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
            $studentAbsenceResults = $InstitutionStudentAbsences
                ->find('InDateRange', ['start_date' => $extra['report_card_start_date'], 'end_date' => $extra['report_card_end_date']])
                ->innerJoin(
                    [$this->alias() => $this->table()],
                    [
                        $this->aliasField('institution_id = ') . $InstitutionStudentAbsences->aliasField('institution_id'),
                        $this->aliasField('student_id = ') . $InstitutionStudentAbsences->aliasField('student_id'),
                        $this->aliasField('institution_class_id') => $params['institution_class_id']
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

            // sum all number_of_days a student absence in an academic period
            $results = [];
            foreach ($studentAbsenceResults as $key => $obj) {
                $numberOfDays = $obj['number_of_days'];
                $absenceType = $absenceTypes[$obj['absence_type_id']];
                if (isset($results[$absenceType])) {
                    $results[$absenceType]['number_of_days'] += $numberOfDays;
                } else {
                    $results[$absenceType]['number_of_days'] = $numberOfDays;
                }
            }
            return $results;
        }
    }

    public function onExcelTemplateInitialiseCompetencyTemplates(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('report_card_education_grade_id', $extra) && array_key_exists('academic_period_id', $params)) {
            $CompetencyTemplates = TableRegistry::get('Competency.CompetencyTemplates');
            $entity = $CompetencyTemplates->find()
                ->where([
                    $CompetencyTemplates->aliasField('academic_period_id') => $params['academic_period_id'],
                    $CompetencyTemplates->aliasField('education_grade_id') => $extra['report_card_education_grade_id']
                ]);

            $extra['competency_templates_ids'] = $entity->extract('id')->toArray();
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseCompetencyItems(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('competency_templates_ids', $extra) && array_key_exists('academic_period_id', $params)) {
            $CompetencyItems = TableRegistry::get('Competency.CompetencyItems');
            $entity = $CompetencyItems->find()
                ->where([
                    $CompetencyItems->aliasField('academic_period_id') => $params['academic_period_id'],
                    $CompetencyItems->aliasField('competency_template_id IN ') => $extra['competency_templates_ids']
                ])->toArray();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseCompetencyCriterias(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('competency_templates_ids', $extra) && array_key_exists('academic_period_id', $params)) {
            $CompetencyCriterias = TableRegistry::get('Competency.CompetencyCriterias');
            $entity = $CompetencyCriterias->find()
                ->where([
                    $CompetencyCriterias->aliasField('academic_period_id') => $params['academic_period_id'],
                    $CompetencyCriterias->aliasField('competency_template_id IN ') => $extra['competency_templates_ids']
                ])->toArray();

            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAssessments(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('report_card_education_grade_id', $extra) && array_key_exists('academic_period_id', $params)) {
            $Assessments = TableRegistry::get('Assessment.Assessments');
            $entity = $Assessments->find()
                ->where([
                    $Assessments->aliasField('academic_period_id') => $params['academic_period_id'],
                    $Assessments->aliasField('education_grade_id') => $extra['report_card_education_grade_id']
                ])
                ->first();
            $extra['assessment_id'] = $entity->id;
            return $entity;
        }
    }

    public function onExcelTemplateInitialiseAssessmentPeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $extra)) {
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            $entity = $AssessmentPeriods->find()
                ->where([
                    $AssessmentPeriods->aliasField('assessment_id') => $extra['assessment_id'],
                    $AssessmentPeriods->aliasField('start_date') . ' >=' => $extra['report_card_start_date'],
                    $AssessmentPeriods->aliasField('end_date') . ' <=' => $extra['report_card_end_date']
                ])
                ->hydrate(false)
                ->all();

            $extra['assessment_period_ids'] = $entity->extract('id')->toArray();
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
                ->order(['EducationSubjects.order', 'EducationSubjects.code', 'EducationSubjects.name'])
                ->hydrate(false)
                ->all();

            $extra['assessment_period_subjects'] = $entity->extract('education_subject_id')->toArray();
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseAssessmentItemResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_class_id', $params) && array_key_exists('assessment_id', $extra) && array_key_exists('institution_id', $params)) {
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $entity = $AssessmentItemResults->find()
                ->innerJoin(
                    [$this->alias() => $this->table()],
                    [
                        $this->aliasField('institution_id = ') . $AssessmentItemResults->aliasField('institution_id'),
                        $this->aliasField('academic_period_id = ') . $AssessmentItemResults->aliasField('academic_period_id'),
                        $this->aliasField('student_id = ') . $AssessmentItemResults->aliasField('student_id'),
                        $this->aliasField('institution_class_id') => $params['institution_class_id']
                    ]
                )
                ->contain(['AssessmentGradingOptions.AssessmentGradingTypes'])
                ->where([
                    $AssessmentItemResults->aliasField('assessment_id') => $extra['assessment_id'],
                    $AssessmentItemResults->aliasField('institution_id') => $params['institution_id'],
                    $AssessmentItemResults->aliasField('student_id') => $params['student_id'],
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
                ->hydrate(false)
                ->toArray();

            return $entity;
        }
    }
}
