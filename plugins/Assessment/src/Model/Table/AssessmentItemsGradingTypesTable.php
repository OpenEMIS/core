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
        $this->belongsTo('AssessmentItems', ['className' => 'Assessment.AssessmentItems', 'dependent' => true]);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods', 'dependent' => true]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data)
    {
        if ($entity->isNew()) {
            $entity->id = Text::uuid();
        }
	}
}