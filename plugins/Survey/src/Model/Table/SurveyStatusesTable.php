<?php
namespace Survey\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

class SurveyStatusesTable extends AppTable {
	private $_contain = ['AcademicPeriods'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->hasMany('SurveyStatusPeriods', ['className' => 'Survey.SurveyStatusPeriods', 'foreignKey' => 'survey_status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('AcademicPeriods', [
			'className' => 'AcademicPeriod.AcademicPeriods',
			'joinTable' => 'survey_status_periods',
			'foreignKey' => 'survey_status_id',
			'targetForeignKey' => 'academic_period_id'
		]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('academic_period_level');
		$this->ControllerAction->field('academic_periods');

		$this->ControllerAction->setFieldOrder([
			'survey_form_id', 'date_enabled', 'date_disabled', 'academic_period_level', 'academic_periods'
		]);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Survey.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
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
		list(, , $formOptions) = array_values($this->getSelectOptions());

		$this->fields['survey_form_id']['type'] = 'select';
		$this->fields['survey_form_id']['options'] = $formOptions;
	}

	public function onUpdateFieldAcademicPeriodLevel(Event $event, array $attr, $action, Request $request) {
		$AcademicPeriodLevels = TableRegistry::get('AcademicPeriod.AcademicPeriodLevels');
		$levelOptions = $AcademicPeriodLevels->getList()->toArray();

		$attr['options'] = $levelOptions;
		$attr['onChangeReload'] = 'changePeriod';
		if ($action != 'add') {
			$attr['visible'] = false;
		}
		return $attr;
	}

	public function onUpdateFieldAcademicPeriods(Event $event, array $attr, $action, Request $request) {
		$selectedLevel = key($this->fields['academic_period_level']['options']);
		if ($request->is('post')) {
			$selectedLevel = $request->data($this->aliasField('academic_period_level'));
		}

		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodOptions = $AcademicPeriods
			->find('list')
			->find('visible')
			->find('order')
			->where([$AcademicPeriods->aliasField('academic_period_level_id') => $selectedLevel])
			->toArray();
		
		$attr['type'] = 'chosenSelect';
		$attr['options'] = $periodOptions;
		return $attr;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, , , $selectedForm) = array_values($this->getSelectOptions());
		$entity->survey_form_id = $selectedForm;

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
		$moduleOptions = $CustomModules
			->find('list')
			->where([$CustomModules->aliasField('parent_id') => 0])
			->toArray();
		$selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

		$formOptions = $this->SurveyForms
			->find('list')
			->where([$this->SurveyForms->aliasField('custom_module_id') => $selectedModule])
			->toArray();
		$selectedForm = isset($query['form']) ? $query['form'] : key($formOptions);

		return compact('moduleOptions', 'selectedModule', 'formOptions', 'selectedForm');
	}
}
