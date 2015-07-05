<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;

class AssessmentGradingOptionsTable extends AppTable {
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		
		// $this->hasMany('AssessmentItems', ['className' => 'Assessment.AssessmentItems']);
		$this->belongsTo('AssessmentGradingTypes', ['className' => 'Assessment.AssessmentGradingTypes']);

		$this->addBehavior('Reorder', ['filter' => 'assessment_grading_type_id']);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$gradingTypeOptions = $this->AssessmentGradingTypes->getList()->toArray();

		if (!empty($gradingTypeOptions)) {
			$toolbarElements = [
				['name' => 'Assessment.GradingOptions/controls', 'data' => [], 'options' => []]
			];
			$this->controller->set('toolbarElements', $toolbarElements);
			$this->controller->set('gradingTypeOptions', $gradingTypeOptions);
		} else {
			$this->Alert->warning('Assessments.noGradingTypes');
		}

		$this->ControllerAction->field('assessment_grading_type_id', ['visible' => false]);
		$selectedType = $this->queryString('grading_type_id', $gradingTypeOptions);
		$options['conditions'][$this->aliasField('assessment_grading_type_id')] = $selectedType;
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('assessment_grading_type_id', ['type' => 'select']);

		$this->ControllerAction->setFieldOrder(['assessment_grading_type_id', 'code', 'name']);
	}
}
