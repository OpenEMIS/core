<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class InstitutionOutcomeSubjectCommentsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('OutcomeTemplates', [
            'className' => 'Outcome.OutcomeTemplates',
            'foreignKey' => ['outcome_template_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id']
        ]);
        $this->belongsTo('OutcomePeriods', [
            'className' => 'Outcome.OutcomePeriods',
            'foreignKey' => ['outcome_period_id', 'academic_period_id'],
            'bindingKey' => ['id', 'academic_period_id']
        ]);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('CompositeKey');

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentOutcomes' => ['index', 'add']
        ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // do not save new record if comment is empty
        $comments = $entity->comments;
        if ($entity->isNew() && $comments === '') {
            return false;
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // delete record if user removes comment
        $comments = $entity->comments;
        if ($comments === '') {
            $this->delete($entity);
        }
    }

    public function findStudentComments(Query $query, array $options)
    {
        $studentId = $options['student_id'];
        $outcomeTemplateId = $options['outcome_template_id'];
        $outcomePeriodId = $options['outcome_period_id'];
        $educationGradeId = $options['education_grade_id'];
        $educationSubjectId = $options['education_subject_id'];
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];

        return $query
            ->where([
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('outcome_template_id') => $outcomeTemplateId,
                $this->aliasField('outcome_period_id') => $outcomePeriodId,
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('education_subject_id') => $educationSubjectId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ]);
    }
}
