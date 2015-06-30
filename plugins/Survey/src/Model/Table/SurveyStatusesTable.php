<?php
namespace Survey\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

class SurveyStatusesTable extends AppTable {
	private $_contain = ['AcademicPeriods'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->belongsTo('AcademicPeriodLevels', ['className' => 'AcademicPeriod.AcademicPeriodLevels']);
		$this->belongsToMany('AcademicPeriods', [
			'className' => 'AcademicPeriod.AcademicPeriods',
			'joinTable' => 'survey_status_periods',
			'foreignKey' => 'survey_status_id',
			'targetForeignKey' => 'academic_period_id'
		]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->addField('academic_periods', [
			'type' => 'chosenSelect',
			'fieldNameKey' => 'academic_periods',
			'fieldName' => $this->alias() . '.academic_periods._ids',
			'placeholder' => __('Select Academic Periods'),
			'order' => 5,
			'visible' => true
		]);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Survey.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Request $request, array $options) {
		$options['contain'] = array_merge($options['contain'], $this->_contain);
		return $options;
	}

	public function viewBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain = array_merge($contain, $this->_contain);
		return compact('query', 'contain');
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list(, , $formOptions, , $levelOptions) = array_values($this->getSelectOptions());

		$this->fields['survey_form_id']['type'] = 'select';
		$this->fields['survey_form_id']['options'] = $formOptions;

		$this->fields['academic_period_level_id']['type'] = 'select';
		$this->fields['academic_period_level_id']['options'] = $levelOptions;
		$this->fields['academic_period_level_id']['onChangeReload'] = true;

		$this->setFieldOrder();
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		//Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = $this->_contain;

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$selectedLevel = $entity->academic_period_level_id;

		$AcademicPeriods = $this->AcademicPeriodLevels->AcademicPeriods;
		$periodOptions = $AcademicPeriods->find('list')->find('visible')->find('order')->where([$AcademicPeriods->aliasField('academic_period_level_id') => $selectedLevel])->toArray();

		$this->fields['academic_periods']['options'] = $periodOptions;

		return $entity;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, , , $selectedForm, , $selectedLevel) = array_values($this->getSelectOptions());

		$entity->survey_form_id = $selectedForm;
		$entity->academic_period_level_id = $selectedLevel;

		return $entity;
	}

	public function editBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain = array_merge($contain, $this->_contain);
		return compact('query', 'contain');
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$query = $this->request->query;

		$CustomModules = $this->SurveyForms->CustomModules;
		$moduleOptions = $CustomModules->find('list')->where([$CustomModules->aliasField('parent_id') => 0])->toArray();
		$selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

		$formOptions = $this->SurveyForms->find('list')->where([$this->SurveyForms->aliasField('custom_module_id') => $selectedModule])->toArray();
		$selectedForm = isset($query['form']) ? $query['form'] : key($formOptions);

		$levelOptions = $this->AcademicPeriodLevels->find('list')->toArray();
		$selectedLevel = isset($query['level']) ? $query['level'] : key($levelOptions);

		return compact('moduleOptions', 'selectedModule', 'formOptions', 'selectedForm', 'levelOptions', 'selectedLevel');
	}

	public function setFieldOrder() {
		$order = 1;
		$this->ControllerAction->setFieldOrder('survey_form_id', $order++);
		$this->ControllerAction->setFieldOrder('date_enabled', $order++);
		$this->ControllerAction->setFieldOrder('date_disabled', $order++);
		$this->ControllerAction->setFieldOrder('academic_period_level_id', $order++);
		$this->ControllerAction->setFieldOrder('academic_periods', $order++);
	}
}
