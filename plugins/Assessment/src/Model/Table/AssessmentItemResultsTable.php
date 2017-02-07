<?php
namespace Assessment\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Text;

class AssessmentItemResultsTable extends AppTable {
    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AssessmentGradingOptions', ['className' => 'Assessment.AssessmentGradingOptions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index', 'add']
        ]);
        $this->addBehavior('Indexes.Indexes');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.InstitutionStudentIndexes.calculateIndexValue'] = 'institutionStudentIndexCalculateIndexValue';
        return $events;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
        if ($entity->isNew()) {
            $entity->id = Text::uuid();
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $listeners = [
            TableRegistry::get('Institution.InstitutionSubjectStudents')
        ];

        $this->dispatchEventToModels('Model.AssessmentResults.afterSave', [$entity], $this, $listeners);
    }

    public function findResults(Query $query, array $options) {
        $academicPeriodId = $options['academic_period_id'];
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $studentId = -1;
        if ($session->check('Student.Results.student_id')) {
            $studentId = $session->read('Student.Results.student_id');
        }

        return $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('marks'),
                $this->aliasField('assessment_grading_option_id'),
                $this->aliasField('student_id'),
                $this->aliasField('assessment_id'),
                $this->aliasField('education_subject_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('assessment_period_id'),
                $this->Assessments->aliasField('code'),
                $this->Assessments->aliasField('name'),
                $this->Assessments->aliasField('education_grade_id'),
                $this->EducationSubjects->aliasField('code'),
                $this->EducationSubjects->aliasField('name'),
                $this->AssessmentGradingOptions->aliasField('code'),
                $this->AssessmentGradingOptions->aliasField('name'),
                $this->AssessmentGradingOptions->aliasField('assessment_grading_type_id'),
                $this->AssessmentPeriods->aliasField('code'),
                $this->AssessmentPeriods->aliasField('name'),
                $this->AssessmentPeriods->aliasField('weight')
            ])
            ->innerJoinWith('Assessments')
            ->innerJoinWith('EducationSubjects')
            ->innerJoinWith('AssessmentGradingOptions')
            ->innerJoinWith('AssessmentPeriods')
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('student_id') => $studentId
            ])
            ->order([
                $this->Assessments->aliasField('code'), $this->Assessments->aliasField('name')
            ]);
    }

    /**
     *  Function to get the assessment results base on the institution id and the academic period
     *
     *  @param integer $institutionId The institution id
     *  @param integer $academicPeriodId The academic period id
     *
     *  @return array The assessment results group field - institution id, key field - student id
     *      value field - assessment item id with array containing marks, grade name and grade code
     */
    public function getAssessmentItemResults($academicPeriodId, $assessmentId, $subjectId) {
        $results = $this
            ->find()
            ->contain(['AssessmentGradingOptions'])
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('assessment_id') => $assessmentId,
                $this->aliasField('education_subject_id') => $subjectId
            ])
            ->select(['grade_name' => 'AssessmentGradingOptions.name', 'grade_code' => 'AssessmentGradingOptions.code'])
            ->autoFields(true)
            ->hydrate(false)
            ->toArray();
        $returnArray = [];
        foreach ($results as $result) {
            $returnArray[$result['student_id']][$subjectId][$result['assessment_period_id']] = [
                    'marks' => $result['marks'],
                    'grade_name' => $result['grade_name'],
                    'grade_code' => $result['grade_code']
                ];
        }
        return $returnArray;
    }

    public function institutionStudentIndexCalculateIndexValue(Event $event, ArrayObject $params)
    {
        $institutionId = $params['institution_id'];
        $studentId = $params['student_id'];
        $academicPeriodId = $params['academic_period_id'];
        $criteriaName = $params['criteria_name'];

        $valueIndex = $this->getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName);

        return $valueIndex;
    }

    public function getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName)
    {
        $results = $this
            ->find()
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
            ])
            ->all();

        $getValueIndex = 0;
        foreach ($results as $key => $resultsObj) {
            $getValueIndex = $getValueIndex + $resultsObj->marks;
        }

        return $getValueIndex;
    }

    public function getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName)
    {
        $results = $this
            ->find()
            ->contain(['Assessments', 'EducationSubjects'])
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->all();

        $referenceDetails = [];
        foreach ($results as $key => $obj) {
            $assessmentName = $obj->assessment->name;
            $educationSubjectName = $obj->education_subject->name;
            $marks = !is_null($obj->marks) ? $obj->marks : 'null';

            $referenceDetails[$obj->assessment_id] = __($assessmentName) . ' - ' . __($educationSubjectName) . ' (' . $marks . ')';
        }

        // tooltip only receieved string to be display
        $reference = '';
        foreach ($referenceDetails as $key => $referenceDetailsObj) {
            $reference = $reference . $referenceDetailsObj . '<br/>';
        }

        return $reference;
    }

    public function getTotalMarks($studentId, $academicPeriodId, $educationSubjectId, $educationGradeId) {
        $query = $this->find();
        $totalMarks = $query
            ->select([
                'calculated_total' => $query->newExpr('SUM(AssessmentItemResults.marks * AssessmentPeriods.weight)')
            ])
            ->matching('Assessments')
            ->matching('AssessmentPeriods')
            ->matching('AssessmentGradingOptions.AssessmentGradingTypes')
            ->where([
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('education_subject_id') => $educationSubjectId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->AssessmentGradingOptions->AssessmentGradingTypes->aliasField('result_type') => 'MARKS',
            ])
            ->group([
                $this->aliasField('student_id'),
                $this->aliasField('assessment_id'),
                $this->aliasField('education_subject_id')
            ])
            ->first();

        return $totalMarks;
    }
}
