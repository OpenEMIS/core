<?php
namespace CustomExcel\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\AppTable;

class AssessmentResultsTable extends AppTable
{
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
            'foreignKey' => [
                'institution_class_id',
                'student_id'
            ],
            'bindingKey' => [
                'institution_class_id',
                'student_id'
            ]
        ]);

        $this->addBehavior('CustomExcel.ExcelReport', [
            'variables' => [
                'Assessments',
                'AssessmentItems',
                'AssessmentItemsGradingTypes',
                'AssessmentItemResults',
                'AssessmentPeriods',
                'ClassStudents',
                'Institutions',
                'InstitutionClasses',
                'InstitutionStudentAbsences'
            ]
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessments'] = 'onExcelTemplateInitialiseAssessments';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItems'] = 'onExcelTemplateInitialiseAssessmentItems';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItemsGradingTypes'] = 'onExcelTemplateInitialiseAssessmentItemsGradingTypes';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentItemResults'] = 'onExcelTemplateInitialiseAssessmentItemResults';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseAssessmentPeriods'] = 'onExcelTemplateInitialiseAssessmentPeriods';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseClassStudents'] = 'onExcelTemplateInitialiseClassStudents';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutions'] = 'onExcelTemplateInitialiseInstitutions';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionClasses'] = 'onExcelTemplateInitialiseInstitutionClasses';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseInstitutionStudentAbsences'] = 'onExcelTemplateInitialiseInstitutionStudentAbsences';
        return $events;
    }

    public function onExcelTemplateInitialiseAssessments(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params)) {
            $Assessments = TableRegistry::get('Assessment.Assessments');
            $entity = $Assessments->get($params['assessment_id'], [
                'contain' => ['AcademicPeriods', 'EducationGrades']
            ]);
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseAssessmentItems(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params)) {
            $AssessmentItems = TableRegistry::get('Assessment.AssessmentItems');
            $results = $AssessmentItems->find()
                ->contain(['EducationSubjects'])
                ->where([$AssessmentItems->aliasField('assessment_id') => $params['assessment_id']])
                ->hydrate(false)
                ->all();
            return $results->toArray();
        }
    }

    public function onExcelTemplateInitialiseAssessmentItemResults(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('class_id', $params) && array_key_exists('assessment_id', $params) && array_key_exists('institution_id', $params)) {
            $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
            $results = $AssessmentItemResults->find()
                ->innerJoin(
                    [$this->alias() => $this->table()],
                    [
                        $this->aliasField('institution_id = ') . $AssessmentItemResults->aliasField('institution_id'),
                        $this->aliasField('academic_period_id = ') . $AssessmentItemResults->aliasField('academic_period_id'),
                        $this->aliasField('student_id = ') . $AssessmentItemResults->aliasField('student_id'),
                        $this->aliasField('institution_class_id') => $params['class_id']
                    ]
                )
                ->contain(['AssessmentGradingOptions.AssessmentGradingTypes'])
                ->where([
                    $AssessmentItemResults->aliasField('assessment_id') => $params['assessment_id'],
                    $AssessmentItemResults->aliasField('institution_id') => $params['institution_id']
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
                ->all();
            return $results->toArray();
        }
    }

    public function onExcelTemplateInitialiseAssessmentItemsGradingTypes(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params)) {
            $AssessmentItemsGradingTypes = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');
            $results = $AssessmentItemsGradingTypes->find()
                ->contain(['AssessmentGradingTypes', 'AssessmentPeriods', 'EducationSubjects'])
                ->where([$AssessmentItemsGradingTypes->aliasField('assessment_id') => $params['assessment_id']])
                ->formatResults(function (ResultSetInterface $results) {
                    return $results->map(function ($row) {
                        $resultType = $row['assessment_grading_type']['result_type'];
                        $max = $row['assessment_grading_type']['max'];

                        switch ($resultType) {
                            case 'MARKS':
                            case 'GRADES':
                                $row['assessment_grading_type']['max_formatted'] = number_format($max, 2);
                                break;
                            case 'DURATION':
                                if (strlen($max) > 0) {
                                    $duration = number_format($max/60, 2);

                                    list($minutes, $seconds) = explode(".", $duration, 2);
                                    $row['assessment_grading_type']['max_formatted'] = $minutes . " : " . $seconds;
                                    break;
                                }
                            default:
                                $row['assessment_grading_type']['max_formatted'] = number_format($max, 2);
                                break;
                        }

                        return $row;
                    });
                })
                ->hydrate(false)
                ->all();
            return $results->toArray();
        }
    }

    public function onExcelTemplateInitialiseAssessmentPeriods(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('assessment_id', $params)) {
            $AssessmentPeriods = TableRegistry::get('Assessment.AssessmentPeriods');
            $results = $AssessmentPeriods->find()
                ->where([$AssessmentPeriods->aliasField('assessment_id') => $params['assessment_id']])
                ->hydrate(false)
                ->all();
            return $results->toArray();
        }
    }

    public function onExcelTemplateInitialiseClassStudents(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('class_id', $params)) {
            $entity = $this->find()
                ->contain([
                    'Users' => [
                        'BirthplaceAreas', 'MainNationalities'
                    ]
                ])
                ->where([$this->aliasField('institution_class_id') => $params['class_id']])
                ->order(['Users.first_name', 'Users.last_name'])
                // ->hydrate(false)
                ->all();
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutions(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('institution_id', $params)) {
            $Institutions = TableRegistry::get('Institution.Institutions');
            $entity = $Institutions->get($params['institution_id'], [
                'contain' => ['Areas', 'AreaAdministratives']
            ]);

            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutionClasses(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('class_id', $params)) {
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $entity = $InstitutionClasses->get($params['class_id']);
            return $entity->toArray();
        }
    }

    public function onExcelTemplateInitialiseInstitutionStudentAbsences(Event $event, array $params, ArrayObject $extra)
    {
        if (array_key_exists('class_id', $params) && array_key_exists('assessment_id', $params) && array_key_exists('institution_id', $params) && array_key_exists('academic_period_id', $params)) {
            $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
            $query = $InstitutionStudentAbsences->find()
                ->find('academicPeriod', ['academic_period_id' => $params['academic_period_id']])
                ->innerJoin(
                    [$this->alias() => $this->table()],
                    [
                        $this->aliasField('institution_id = ') . $InstitutionStudentAbsences->aliasField('institution_id'),
                        $this->aliasField('student_id = ') . $InstitutionStudentAbsences->aliasField('student_id'),
                        $this->aliasField('institution_class_id') => $params['class_id']
                    ]
                )
                ->where([
                    $InstitutionStudentAbsences->aliasField('institution_id') => $params['institution_id']
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
                ->hydrate(false);

            return $query->toArray();
        }
    }
}
