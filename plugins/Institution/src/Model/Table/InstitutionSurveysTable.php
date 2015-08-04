<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;

class InstitutionSurveysTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('institution_site_surveys');
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->addBehavior('CustomField.Record', [
			'moduleKey' => null,
			'fieldKey' => 'survey_question_id',
			'tableColumnKey' => 'survey_table_column_id',
			'tableRowKey' => 'survey_table_row_id',
			'formKey' => 'survey_form_id',
			// 'filterKey' => 'custom_filter_id',
			'formFieldClass' => ['className' => 'Survey.SurveyFormsQuestions'],
			// 'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
			'recordKey' => 'institution_site_survey_id',
			'fieldValueClass' => ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'Institution.InstitutionSurveyTableCells', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
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
		$entity = $this->newEntity(['id' => $id, 'status' => 1], ['validate' => false]);

		if ($this->save($entity)) {
			$this->Alert->success('InstitutionSurveys.reject.success');
		} else {
			$this->Alert->success('InstitutionSurveys.reject.failed');
			$this->log($entity->errors(), 'debug');
		}
		$action = $this->ControllerAction->buttons['index']['url'];
		$action['status'] = 2;
		return $this->controller->redirect($action);
	}

	public function onGetStatus(Event $event, Entity $entity) {
		list($statusOptions) = array_values($this->_getSelectOptions());
		return $statusOptions[$entity->status];
	}

	public function onGetSurveyFormId(Event $event, Entity $entity) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());

		if ($selectedStatus != 2) {
			return $event->subject()->Html->link($entity->survey_form->name, [
				'plugin' => $this->controller->plugin,
				'controller' => $this->controller->name,
				'action' => $this->alias,
				'edit',
				$entity->id,
				'status' => $selectedStatus
			]);
		}
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
			->find()
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
			->first();

		$dateDisabled = null;
		if (!is_null($results)) {
			$data = $results->toArray();
			$dateDisabled = $data['date_disabled'];
		}

		return $dateDisabled;
	}

	public function onGetCompletedOn(Event $event, Entity $entity) {
		return $entity->modified;
	}

	public function indexBeforeAction(Event $event) {
		list($statusOptions, $selectedStatus) = array_values($this->_getSelectOptions());

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

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());

		$query
			->where([$this->aliasField('status') => $selectedStatus])
			->order([$this->AcademicPeriods->aliasField('order')]);
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());

		if ($selectedStatus == 2) {	//Completed
			if ($action == 'view') {
				unset($toolbarButtons['edit']);	
			}
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		if ($selectedStatus == 0) {	//New
			unset($buttons['view']);
			unset($buttons['remove']);
		} else if ($selectedStatus == 2) {	//Completed
			$rejectBtn = ['reject' => $buttons['view']];
			$rejectBtn['reject']['url']['action'] = $this->alias;
			$rejectBtn['reject']['url'][0] = 'reject';
			$rejectBtn['reject']['url'][1] = $entity->id;
			$rejectBtn['reject']['label'] = '<i class="fa fa-trash"></i>' . __('Reject');

			unset($buttons['edit']);
			unset($buttons['remove']);
			$buttons = array_merge($buttons, $rejectBtn);
		}

		return $buttons;
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

	public function _getSelectOptions() {
		//Return all required options and their key
		$statusOptions = $this->getSelectOptions('Surveys.status');
		$selectedStatus = $this->queryString('status', $statusOptions);

		return compact('statusOptions', 'selectedStatus');
	}
}
