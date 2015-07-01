<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class InstitutionSurveysTable extends AppTable {
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

		$this->addBehavior('CustomField.Record', [
			'moduleKey' => null,
			'fieldKey' => 'survey_question_id',
			'formKey' => 'survey_form_id',
			'tableColumnKey' => 'survey_table_column_id',
			'tableRowKey' => 'survey_table_row_id',
			'recordKey' => 'institution_site_survey_id',
			'fieldValueKey' => ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellKey' => ['className' => 'Institution.InstitutionSurveyTableCells', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);
	}

	public function buildSurveyRecords() {
		$CustomModules = $this->SurveyForms->CustomModules;
		$customModuleResults = $CustomModules
			->find('all')
			->select([
				$CustomModules->aliasField('id'),
				$CustomModules->aliasField('filter')
			])
			->where([
				$CustomModules->aliasField('model') => $this->request->params['plugin'] .'.'. $this->request->params['controller']
			])
			->first();
		$customModuleId = $customModuleResults->id;
		$todayDate = date("Y-m-d");

		$institutionId = null;
		$session = $this->controller->request->session();
		if ($session->check('Institutions.id')) {
			$institutionId = $session->read('Institutions.id');
		}
		$SurveyStatuses = $this->SurveyForms->SurveyStatuses;
		$SurveyStatusPeriods = $this->SurveyForms->SurveyStatuses->SurveyStatusPeriods;

		$surveyForms = $this->SurveyForms
			->find('list')
			->where([$this->SurveyForms->aliasField('custom_module_id') => $customModuleId])
			->toArray();

		//delete all New Survey by Institution Id and reinsert
		$this->deleteAll([
			$this->aliasField('institution_site_id') => $institutionId,
			$this->aliasField('status') => 0
		]);

		foreach ($surveyForms as $surveyFormId => $surveyForm) {
			$surveyStatuesIds = $SurveyStatuses
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->where([
					$SurveyStatuses->aliasField('survey_form_id') => $surveyFormId,
					$SurveyStatuses->aliasField('date_disabled >=') => $todayDate
				])
				->toArray();

			$academicPeriodIds = $SurveyStatusPeriods
				->find('list', ['keyField' => 'academic_period_id', 'valueField' => 'academic_period_id'])
				->where([$SurveyStatusPeriods->aliasField('survey_status_id IN') => $surveyStatuesIds])
				->toArray();

			foreach ($academicPeriodIds as $key => $academicPeriodId) {
				if (!is_null($institutionId)) {
					$results = $this
						->find('all')
						->where([
							$this->aliasField('academic_period_id') => $academicPeriodId,
							$this->aliasField('survey_form_id') => $surveyFormId,
							$this->aliasField('institution_site_id') => $institutionId
						])
						->all();

					if ($results->isEmpty()) {
						$InstitutionSurvey = $this->newEntity();
						$InstitutionSurvey->status = 0;
						$InstitutionSurvey->academic_period_id = $academicPeriodId;
						$InstitutionSurvey->survey_form_id = $surveyFormId;
						$InstitutionSurvey->institution_site_id = $institutionId;

						if ($this->save($InstitutionSurvey)) {
						} else {
							$this->log($InstitutionSurvey->errors(), 'debug');
						}
					}
				}
			}
		}
	}

	public function reject() {
		$this->ControllerAction->autoRender = false;
		$request = $this->ControllerAction->request;

		$id = $request->params['pass'][1];

		$InstitutionSurvey = $this->newEntity();
		$InstitutionSurvey->id = $id;
		$InstitutionSurvey->status = 1;

		if ($this->save($InstitutionSurvey)) {
			$this->Alert->success('InstitutionSurvey.reject.success');
		} else {
			$this->Alert->success('InstitutionSurvey.reject.failed');
			$this->log($InstitutionSurvey->errors(), 'debug');
		}
		$action = $this->ControllerAction->buttons['index']['url'];
		$action['status'] = 2;
		return $this->controller->redirect($action);
	}

	public function onGetSurveyFormId(Event $event, Entity $entity) {
		list(, $selectedStatus) = array_values($this->getSelectOptions());

		return $event->subject()->Html->link($entity->survey_form->name, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => $this->alias,
			'edit',
			$entity->id,
			'status' => $selectedStatus
		]);
	}

	public function onGetDescription(Event $event, Entity $entity) {
		return $entity->survey_form->description;
	}

	public function onGetLastModified(Event $event, Entity $entity) {
		return $entity->modified;
	}

	public function onGetToBeCompletedBy(Event $event, Entity $entity) {
		$academicPeriodId = $entity->academic_period_id;
		$surveyFormId = $entity->survey_form->id;

		$SurveyStatuses = $this->SurveyForms->SurveyStatuses;
		$results = $SurveyStatuses
			->find('all')
			->select([
				$SurveyStatuses->aliasField('date_disabled')
			])
			->where([
				$SurveyStatuses->aliasField('survey_form_id') => $surveyFormId
			])
			->join([
				'table' => 'survey_status_periods',
				'alias' => 'SurveyStatusPeriods',
				'conditions' => [
					'SurveyStatusPeriods.survey_status_id =' . $SurveyStatuses->aliasField('id'),
					'SurveyStatusPeriods.academic_period_id' => $academicPeriodId
				]
			])
			->first()
			->toArray();

		return $results['date_disabled'];
	}

	public function onGetCompletedOn(Event $event, Entity $entity) {
		return $entity->modified;
	}

	public function indexOnInitializeButtons(Event $event, $buttons) {
		list(, $selectedStatus) = array_values($this->getSelectOptions());
		if ($selectedStatus == 0) {
			unset($buttons['delete']);
		} else if ($selectedStatus == 1) {
		} else if ($selectedStatus == 2) {
			unset($buttons['edit']);
			unset($buttons['delete']);
			$buttons['reject'] = [
				'class' => 'fa fa-trash',
				'url' => [
					'plugin' => $this->controller->plugin,
					'controller' => $this->controller->name,
					'action' => $this->alias,
					'reject'
				]
			];
		}
		return $buttons;
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

        if ($selectedStatus == 0) {	//New
        	$this->ControllerAction->field('description');
			$this->ControllerAction->field('to_be_completed_by');

			$this->ControllerAction->setFieldOrder([
				'survey_form_id', 'description', 'academic_period_id', 'to_be_completed_by'
			]);

			$this->buildSurveyRecords();
        } else if ($selectedStatus == 1) {	//Draft
			$this->ControllerAction->field('description');
			$this->ControllerAction->field('last_modified');
			$this->ControllerAction->field('to_be_completed_by');

			$this->ControllerAction->setFieldOrder([
				'survey_form_id', 'description', 'academic_period_id', 'last_modified', 'to_be_completed_by'
			]);
        } else if ($selectedStatus == 2) {	//Completed
			$this->ControllerAction->field('description');
			$this->ControllerAction->field('completed_on');

			$this->ControllerAction->setFieldOrder([
				'survey_form_id', 'description', 'academic_period_id', 'completed_on'
			]);
        }

        $this->fields['status']['visible'] = false;
	}

	public function indexBeforePaginate(Event $event, Request $request, array $options) {
		list(, $selectedStatus) = array_values($this->getSelectOptions());
		$options['conditions'][$this->aliasField('status')] = $selectedStatus;
		$options['order'] = [
			$this->AcademicPeriods->aliasField('order')
		];

		return $options;
	}

	public function addEditBeforeAction(Event $event) {
		$this->fields['status']['type'] = 'hidden';
		$this->fields['academic_period_id']['type'] = 'hidden';
		$this->fields['survey_form_id']['type'] = 'hidden';
	}

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$status = $data[$this->alias()]['status'] == 0 ? 1 : 2;
		$data[$this->alias()]['status'] = $status;
    }

	public function getSelectOptions() {
		//Return all required options and their key
		$statusOptions = $this->status;
		$selectedStatus = !is_null($this->request->query('status')) ? $this->request->query('status') : key($statusOptions);

		return compact('statusOptions', 'selectedStatus');
	}
}
