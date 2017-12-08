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
}
