<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Utility\Text;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;

class AssessmentItemsGradingTypesTable extends AppTable {

    public function initialize(array $config): void {
        parent::initialize($config);
        $this->belongsTo('AssessmentGradingTypes', ['className' => 'Assessment.AssessmentGradingTypes', 'dependent' => true]);
        $this->belongsTo('AssessmentsAlt', ['className' => 'Assessment.Assessments', 'dependent' => true]); //POCOR-8507 association names are unique
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects', 'dependent' => true]);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods', 'dependent' => true]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'Results' => ['index'],
            'OpenEMIS_Classroom' => ['index']
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator
        ->requirePresence('assessment_grading_type_id');
       
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $entity->id = Text::uuid();
        }
    }

    public function getAssessmentGradeTypes($assessmentId) {
        $gradeTypes = $this->find('list', [
                'keyField' => 'period_id',
                'groupField' => 'subject_id',
                'valueField' => 'type'
            ])
            ->matching('AssessmentGradingTypes')
            ->select([
                'period_id' => $this->aliasField('assessment_period_id'),
                'subject_id' => $this->aliasField('education_subject_id'),
                'type' => 'AssessmentGradingTypes.result_type'
            ])
            ->where([$this->aliasField('assessment_id') => $assessmentId])
            ->toArray();
        return $gradeTypes;
    }
}