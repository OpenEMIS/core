<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;

class InstitutionCompetencyResultsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Students', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('CompetencyTemplates', ['className' => 'Competency.CompetencyTemplates', 'foreignKey' => ['competency_template_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);
        $this->belongsTo('CompetencyItems', ['className' => 'Competency.CompetencyItems', 'foreignKey' => ['competency_item_id', 'academic_period_id', 'competency_template_id'], 'bindingKey' => ['id', 'academic_period_id', 'competency_template_id']]);
        $this->belongsTo('CompetencyCriterias', ['className' => 'Competency.CompetencyCriterias', 'foreignKey' => ['competency_criteria_id', 'academic_period_id', 'competency_item_id', 'competency_template_id'], 'bindingKey' => ['id', 'academic_period_id', 'competency_item_id', 'competency_template_id']]);
        $this->belongsTo('CompetencyPeriods', ['className' => 'Competency.CompetencyPeriods', 'foreignKey' => ['competency_period_id', 'academic_period_id'], 'bindingKey' => ['id', 'academic_period_id']]);
        $this->belongsTo('CompetencyGradingOptions', ['className' => 'Competency.CompetencyGradingOptions']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentCompetencies' => ['index', 'add']
        ]);
        $this->addBehavior('CompositeKey');
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // do not save new record if result is empty and comments is empty - update for POCOR4466
        $gradingOption = $entity->competency_grading_option_id;
        $comments = $entity->comments;

        if ($entity->isNew() && empty($gradingOption) && $comments === '') {
            return false;
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // delete record if user removes result and comments is empty - update for POCOR4466
        $gradingOption = $entity->competency_grading_option_id;
        $comments = $entity->comments;

        if (empty($gradingOption) && $comments === ''){
            $this->delete($entity);
        }
    }

    public function findStudentResults(Query $query, array $options)
    {
        $competencyTemplateId = $options['competency_template_id'];
        $competencyPeriodId = $options['competency_period_id'];
        $competencyItemId = $options['competency_item_id'];
        $institutionId = $options['institution_id'];
        $academicPeriodId = $options['academic_period_id'];
        $studentId = $options['student_id'];
        return $query
            ->where([
                $this->aliasField('competency_template_id') => $competencyTemplateId,
                $this->aliasField('competency_period_id') => $competencyPeriodId,
                $this->aliasField('competency_item_id') => $competencyItemId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('student_id') => $studentId
            ]);
    }
}
