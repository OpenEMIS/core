<?php
namespace Assessment\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
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

		$this->belongsTo('AssessmentGradingTypes', ['className' => 'Assessment.AssessmentGradingTypes']);
		$this->addBehavior('Reorder', ['filter' => 'assessment_grading_type_id']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list($gradingTypeOptions, $selectedGradingType) = array_values($this->_getSelectOptions());

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
		$query->where([$this->aliasField('assessment_grading_type_id') => $selectedGradingType]);
	}

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->field('assessment_grading_type_id');
		$this->ControllerAction->setFieldOrder(['assessment_grading_type_id', 'code', 'name']);
	}

	public function onUpdateFieldAssessmentGradingTypeId(Event $event, array $attr, $action, Request $request) {
		list($gradingTypeOptions) = array_values($this->_getSelectOptions());
		$attr['options'] = $gradingTypeOptions;

		return $attr;
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$gradingTypeOptions = $this->AssessmentGradingTypes->getList()->toArray();
		$selectedGradingType = $this->queryString('grading_type_id', $gradingTypeOptions);
		$this->advancedSelectOptions($gradingTypeOptions, $selectedGradingType);

		return compact('gradingTypeOptions', 'selectedGradingType');
	}
}
