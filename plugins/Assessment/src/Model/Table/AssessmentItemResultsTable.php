<?php
namespace Assessment\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;

class AssessmentItemResultsTable extends AppTable {
    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
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
        $entity->id = Text::uuid();
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
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->AssessmentPeriods->aliasField('code'),
                $this->AssessmentPeriods->aliasField('name'),
                $this->AssessmentPeriods->aliasField('weight')
            ])
            ->innerJoinWith('Assessments')
            ->innerJoinWith('EducationSubjects')
            ->innerJoinWith('AssessmentGradingOptions')
            ->innerJoinWith('Institutions')
            ->innerJoinWith('AssessmentPeriods')
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('student_id') => $studentId
            ])
            ->order([
                $this->Institutions->aliasField('code'), $this->Institutions->aliasField('name'),
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
    public function getAssessmentItemResults($institutionId, $academicPeriodId, $assessmentId, $subjectId) {
        $results = $this
            ->find()
            ->contain(['AssessmentGradingOptions'])
            ->where([
                $this->aliasField('institution_id') => $institutionId,
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
            $returnArray[$result['institution_id']][$result['student_id']][$subjectId][$result['assessment_period_id']] = [
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
}
