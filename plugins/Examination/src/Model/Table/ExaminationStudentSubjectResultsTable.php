<?php
namespace Examination\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Utility\Security;

use App\Model\Table\AppTable;

class ExaminationStudentSubjectResultsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('ExaminationSubjects', ['className' => 'Examination.ExaminationSubjects']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationGradingOptions', ['className' => 'Examination.ExaminationGradingOptions']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['index', 'add'],
            'StudentExaminationResults' => ['index']
        ]);
        $this->addBehavior('CompositeKey');
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $this->getExamGrading($entity);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // delete record if user removes the mark or grade
        $marks = $entity->marks;
        $grade = $entity->examination_grading_option_id;
        if (is_null($marks) && is_null($grade)) {
            $this->delete($entity);
        }

        // save total marks
        $listeners = [TableRegistry::get('Examination.ExaminationCentresExaminationsSubjectsStudents')];
        $this->dispatchEventToModels('Model.ExaminationResults.afterSave', [$entity], $this, $listeners);
    }

    public function findResults(Query $query, array $options) {
        $academicPeriodId = $options['academic_period_id'];
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $studentId = -1;
        if ($session->check('Student.ExaminationResults.student_id')) {
            $studentId = $session->read('Student.ExaminationResults.student_id');
        }
        //POCOR-6761 start
        $stdID = $session->read('Student.ExaminationResults.student_id');
        if($stdID==''){
            $studentId = $session->read('Auth.User.id');
        }
        //POCOR-6761 end
        return $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('marks'),
                $this->aliasField('examination_grading_option_id'),
                $this->aliasField('student_id'),
                $this->aliasField('examination_id'),
                $this->aliasField('examination_subject_id'),
                $this->aliasField('education_subject_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id'),
                $this->Examinations->aliasField('code'),
                $this->Examinations->aliasField('name'),
                $this->Examinations->aliasField('education_grade_id'),
                $this->ExaminationSubjects->aliasField('code'),
                $this->ExaminationSubjects->aliasField('name'),
                $this->ExaminationSubjects->aliasField('weight'),
                $this->EducationSubjects->aliasField('code'),
                $this->EducationSubjects->aliasField('name'),
                $this->EducationSubjects->aliasField('order'),
                $this->ExaminationGradingOptions->aliasField('code'),
                $this->ExaminationGradingOptions->aliasField('name'),
                $this->ExaminationGradingOptions->aliasField('examination_grading_type_id'),
            ])
            ->contain('ExaminationGradingOptions') //POCOR-6761
            ->contain('ExaminationGradingOptions.ExaminationGradingTypes') //POCOR-6761
            ->innerJoinWith('Examinations')
            ->innerJoinWith('ExaminationSubjects')
            ->leftJoinWith('EducationSubjects')
            //->innerJoinWith('ExaminationGradingOptions')
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('student_id') => $studentId,
                $this->ExaminationSubjects->aliasField('weight > ') => 0
            ])
            ->order([
                $this->EducationSubjects->aliasField('order'),
                $this->ExaminationSubjects->aliasField('code'),
                $this->ExaminationSubjects->aliasField('name')
            ]);
    }

    private function getExamGrading(Entity $entity)
    {
        $ExaminationSubjects = TableRegistry::get('Examination.ExaminationSubjects');
        $examItemEntity = $ExaminationSubjects
            ->find()
            ->contain(['ExaminationGradingTypes.GradingOptions'])
            ->where([
                $ExaminationSubjects->aliasField('examination_id') => $entity->examination_id,
                $ExaminationSubjects->aliasField('id') => $entity->examination_subject_id
            ])
            ->first();

        if ($examItemEntity->has('examination_grading_type')) {
            $resultType = $examItemEntity->examination_grading_type->result_type;
            if ($resultType == 'MARKS') {
                if ($examItemEntity->examination_grading_type->has('grading_options') && !empty($examItemEntity->examination_grading_type->grading_options)) {
                    foreach ($examItemEntity->examination_grading_type->grading_options as $key => $obj) {
                        if ($entity->marks >= $obj->min && $entity->marks <= $obj->max) {
                            $entity->examination_grading_option_id = $obj->id;
                        }
                    }
                }
                $entity->total_mark = round($entity->marks * $examItemEntity->weight, 2);
            } else if ($resultType == 'GRADES') {
                $entity->total_mark = NULL;
            }
        }
    }

    public function getExaminationStudentSubjectResults($academicPeriodId, $examinationId, $studentId) {
        $results = $this
            ->find()
            ->contain(['ExaminationGradingOptions'])
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('examination_id') => $examinationId,
                $this->aliasField('student_id') => $studentId
            ])
            ->select(['grade_name' => 'ExaminationGradingOptions.name', 'grade_code' => 'ExaminationGradingOptions.code', 'examination_subject_id' => $this->aliasField('examination_subject_id')])
            ->autoFields(true)
            ->hydrate(false)
            ->toArray();

        $returnArray = [];
        foreach ($results as $result) {
            $returnArray[$studentId][$result['examination_subject_id']] = [
                'marks' => $result['marks'],
                'grade_name' => $result['grade_name'],
                'grade_code' => $result['grade_code']
            ];
        }
        return $returnArray;
    }
}
