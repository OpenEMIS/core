<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;

use App\Model\Table\AppTable;

class AssessmentItemsGradingTypesTable extends AppTable {

    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('AssessmentGradingTypes', ['className' => 'Assessment.AssessmentGradingTypes', 'dependent' => true]);
        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments', 'dependent' => true]);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects', 'dependent' => true]);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods', 'dependent' => true]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data)
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