<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class InstitutionSurveysTable extends AppTable {
	private $_contain = ['AcademicPeriods', 'SurveyForms', 'Institutions'];

	private $status = [
		0 => 'New',
		1 => 'Draft',
		2 => 'Completed',
	];

	public function initialize(array $config) {
		$this->table('institution_site_surveys');
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);

		$this->hasMany('CustomFieldValues', ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'Institution.InstitutionSurveyTableCells', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->addBehavior('CustomField.Record', [
			'recordKey' => 'institution_site_survey_id',
			'moduleKey' => null,
			'fieldKey' => 'survey_question_id',
			'formKey' => 'survey_form_id',
			'tableColumnKey' => 'survey_table_column_id',
			'tableRowKey' => 'survey_table_row_id'
		]);


	}

	public function indexBeforeAction(Event $event) {
		list($statusOptions, $selectedStatus) = array_values($this->getSelectOptions());

		$tabElements = [
			'New' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys?status=0'],
				'text' => __('New')
			],
			'Draft' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys?status=1'],
				'text' => __('Draft')
			],
			'Completed' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys?status=2'],
				'text' => __('Completed')
			]
		];

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $statusOptions[$selectedStatus]);
	}

	public function indexBeforePaginate(Event $event, Request $request, array $options) {
		list(, $selectedStatus) = array_values($this->getSelectOptions());

		$options['contain'] = array_merge($options['contain'], $this->_contain);
		$options['conditions'][$this->aliasField('status')] = $selectedStatus;

		return $options;
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		if ($this->behaviors()->hasMethod('viewAfterAction')) {
			list($entity) = array_values($this->behaviors()->call('viewAfterAction', [$event, $entity]));
		}

		return $entity;
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($statusOptions, , $formOptions) = array_values($this->getSelectOptions());

		$this->fields['status']['type'] = 'select';
		$this->fields['status']['options'] = $statusOptions;

		$this->fields['academic_period_id']['type'] = 'select';

		$this->fields['survey_form_id']['type'] = 'select';
		$this->fields['survey_form_id']['options'] = $formOptions;
		$this->fields['survey_form_id']['onChangeReload'] = true;
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		if ($this->behaviors()->hasMethod('addEditAfterAction')) {
			list($entity) = array_values($this->behaviors()->call('addEditAfterAction', [$event, $entity]));
		}
		return $entity;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, , , $selectedForm) = array_values($this->getSelectOptions());
		$entity->survey_form_id = $selectedForm;

		return $entity;
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$statusOptions = $this->status;
		$selectedStatus = !is_null($this->request->query('status')) ? $this->request->query('status') : key($statusOptions);

		$CustomModules = $this->SurveyForms->CustomModules;
		$customModuleResults = $CustomModules
			->find('all')
			->select([
				$CustomModules->aliasField('id'),
				$CustomModules->aliasField('field_option')
			])
			->where([
				$CustomModules->aliasField('model') => $this->request->params['controller']
			])
			->first();
		$customModuleId = $customModuleResults->id;
		
		$formOptions = $this->SurveyForms
			->find('list')
			->where([$this->SurveyForms->aliasField('custom_module_id') => $customModuleId])
			->toArray();
		$selectedForm = key($formOptions);

		return compact('statusOptions', 'selectedStatus', 'formOptions', 'selectedForm');
	}
}
