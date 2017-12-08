<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;

class InstitutionOutcomeResultsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('OutcomeGradingOptions', ['className' => 'Outcome.OutcomeGradingOptions']);
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
        $this->belongsTo('OutcomeCriterias', [
            'className' => 'Outcome.OutcomeCriterias',
            'foreignKey' => ['outcome_criteria_id', 'outcome_template_id', 'academic_period_id'],
            'bindingKey' => ['id', 'outcome_template_id', 'academic_period_id']
        ]);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);

        $this->addBehavior('CompositeKey');
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // do not save new record if result is empty
        $gradingOption = $entity->outcome_grading_option_id;
        if ($entity->isNew() && empty($gradingOption)) {
            return false;
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // delete record if user removes result
        $gradingOption = $entity->outcome_grading_option_id;
        if (empty($gradingOption)) {
            $this->delete($entity);
        }
    }
}
