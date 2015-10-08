<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;

class InstitutionSurveysTable extends AppTable {
	use OptionsTrait;
	use MessagesTrait;

	// Survey Status
	const EXPIRED = -1;
	const NEW_SURVEY = 0;
	const DRAFT = 1;
	const COMPLETED = 2;

	private $workflowEvents = [
		[
			'value' => 'Workflow.onApprove',
			'text' => 'On approve, update Survey Status from Draft to Completed.',
			'method' => 'OnApprove'
		],
		[
			'value' => 'Workflow.OnReject',
			'text' => 'On reject, update Survey Status from Completed to Draft.',
			'method' => 'OnReject'
		]
	];

	public function initialize(array $config) {
		$this->table('institution_site_surveys');
		parent::initialize($config);
		
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->addBehavior('Survey.Survey', [
			'module' => 'Institution.Institutions'
		]);
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
		$this->addBehavior('AcademicPeriod.AcademicPeriod');
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	$events['Workflow.getEvents'] = 'getWorkflowEvents';
    	foreach ($this->workflowEvents as $event) {
    		$events[$event['value']] = $event['method'];
    	}
    	return $events;
    }

    public function getWorkflowEvents(Event $event) {
    	return $this->workflowEvents;
    }

	public function buildSurveyRecords($institutionId=null) {
		if (is_null($institutionId)) {
			$session = $this->controller->request->session();
			if ($session->check('Institution.Institutions.id')) {
				$institutionId = $session->read('Institution.Institutions.id');
			}
		}

		$surveyForms = $this->getForms();
		$todayDate = date("Y-m-d");
		$SurveyStatuses = $this->SurveyForms->SurveyStatuses;
		$SurveyStatusPeriods = $this->SurveyForms->SurveyStatuses->SurveyStatusPeriods;

		// Update all New Survey to Expired by Institution Id
		$this->updateAll(['status' => self::EXPIRED],
			[
				'institution_site_id' => $institutionId,
				'status' => self::NEW_SURVEY
			]
		);

		foreach ($surveyForms as $surveyFormId => $surveyForm) {
			$surveyStatusIds = $SurveyStatuses
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->where([
					$SurveyStatuses->aliasField('survey_form_id') => $surveyFormId,
					$SurveyStatuses->aliasField('date_disabled >=') => $todayDate
				])
				->toArray();

			$academicPeriodIds = $SurveyStatusPeriods
				->find('list', ['keyField' => 'academic_period_id', 'valueField' => 'academic_period_id'])
				->where([$SurveyStatusPeriods->aliasField('survey_status_id IN') => $surveyStatusIds])
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
						// Insert New Survey if not found
						$InstitutionSurvey = $this->newEntity();
						$InstitutionSurvey->status = self::NEW_SURVEY;
						$InstitutionSurvey->academic_period_id = $academicPeriodId;
						$InstitutionSurvey->survey_form_id = $surveyFormId;
						$InstitutionSurvey->institution_site_id = $institutionId;

						if ($this->save($InstitutionSurvey)) {
						} else {
							$this->log($InstitutionSurvey->errors(), 'debug');
						}
					} else {
						// Update Expired Survey back to New
						$this->updateAll(['status' => self::NEW_SURVEY],
							[
								'academic_period_id' => $academicPeriodId,
								'survey_form_id' => $surveyFormId,
								'institution_site_id' => $institutionId,
								'status' => self::EXPIRED
							]
						);
					}
				}
			}
		}
	}

	public function onGetStatus(Event $event, Entity $entity) {
		list($statusOptions) = array_values($this->_getSelectOptions());
		return $statusOptions[$entity->status];
	}

	public function onGetSurveyFormId(Event $event, Entity $entity) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());

		if ($selectedStatus != self::COMPLETED) {
			if ($this->AccessControl->check([$this->controller->name, 'Surveys', 'edit'])) {
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
	}

	public function onGetDescription(Event $event, Entity $entity) {
		$surveyFormId = $entity->survey_form->id;
		return $this->SurveyForms->get($surveyFormId)->description;
	}

	public function onGetLastModified(Event $event, Entity $entity) {
		return $this->formatDateTime($entity->modified);
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
			->all();

		$value = '<i class="fa fa-minus"></i>';
		if (!$results->isEmpty()) {
			$dateDisabled = $results->first()->date_disabled;
			$value = $this->formatDate($dateDisabled);
		}

		return $value;
	}

	public function onGetCompletedOn(Event $event, Entity $entity) {
		return $this->formatDateTime($entity->modified);
	}

	public function indexBeforeAction(Event $event) {
		list($statusOptions, $selectedStatus) = array_values($this->_getSelectOptions());
		$tabElements = [];

		if ($this->AccessControl->check([$this->controller->name, 'NewSurveys', 'view'])) {
			$tabElements['New'] = [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys?status='.self::NEW_SURVEY],
				'text' => __('New')
			];
			$tabElements['Draft'] = [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys?status='.self::DRAFT],
				'text' => __('Draft')
			];
		}

		if ($this->AccessControl->check([$this->controller->name, 'CompletedSurveys', 'view'])) {
			$tabElements['Completed'] = [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys?status='.self::COMPLETED],
				'text' => __('Completed')
			];
		}

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $statusOptions[$selectedStatus]);

        if ($selectedStatus == self::NEW_SURVEY) {	//New
        	$this->ControllerAction->field('description');
			$this->ControllerAction->field('to_be_completed_by');

			$this->ControllerAction->setFieldOrder([
				'survey_form_id', 'description', 'academic_period_id', 'to_be_completed_by'
			]);

			$this->buildSurveyRecords();
        } else if ($selectedStatus == self::DRAFT) {	//Draft
			$this->ControllerAction->field('description');
			$this->ControllerAction->field('last_modified');
			$this->ControllerAction->field('to_be_completed_by');

			$this->ControllerAction->setFieldOrder([
				'survey_form_id', 'description', 'academic_period_id', 'last_modified', 'to_be_completed_by'
			]);
        } else if ($selectedStatus == self::COMPLETED) {	//Completed
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

		$options['auto_contain'] = false;
		$query->contain(['AcademicPeriods', 'SurveyForms']);

		$query
			->where([$this->aliasField('status') => $selectedStatus])
			->order([$this->AcademicPeriods->aliasField('order')]);
	}

	public function viewBeforeAction(Event $event) {
		// Do not show Survey Status if Workflow is applied
		if ($this->hasBehavior('Workflow')) {
			$this->ControllerAction->field('status', ['visible' => false]);
		}
	}

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->field('status');
		$this->fields['academic_period_id']['type'] = 'hidden';
		$this->fields['survey_form_id']['type'] = 'hidden';
	}

	public function onUpdateFieldStatus(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'hidden';
		$attr['attr']['survey-status'] = 1;

		return $attr;
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$surveyRecord = $this->get($id);

		if ($surveyRecord->status == self::COMPLETED) {
			$entity = $this->newEntity(['id' => $id, 'status' => self::DRAFT], ['validate' => false]);
			if ($this->save($entity)) {
				$this->Alert->success('InstitutionSurveys.reject.success');
			} else {
				$this->Alert->success('InstitutionSurveys.reject.failed');
				$this->log($entity->errors(), 'debug');
			}

			$event->stopPropagation();
			// $action = $this->ControllerAction->buttons['index']['url'];
			$action = $this->ControllerAction->url('index');
			$action['status'] = self::COMPLETED;
			return $this->controller->redirect($action);
		}
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());

		if ($selectedStatus == self::COMPLETED) {	//Completed
			if ($action == 'view') {
				if (isset($toolbarButtons['edit'])) {
					unset($toolbarButtons['edit']);
				}
			}
		}
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		list(, $selectedStatus) = array_values($this->_getSelectOptions());
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

		if ($selectedStatus == self::NEW_SURVEY) {	// New
			// unset($buttons['view']);
			unset($buttons['remove']);
		} else if ($selectedStatus == self::COMPLETED) {	// Completed
			unset($buttons['edit']);
		}

		return $buttons;
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		$cancelButton = $buttons[1];
		$buttons[0] = [
			'name' => '<i class="fa fa-check"></i> ' . __('Save As Draft'),
			'attr' => ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'save', 'onClick' => '$(\'input:hidden[survey-status='.self::DRAFT.']\').val('.self::DRAFT.');']
		];
		$buttons[1] = [
			'name' => '<i class="fa fa-check"></i> ' . __('Submit'),
			'attr' => ['class' => 'btn btn-default', 'div' => false, 'name' => 'submit', 'value' => 'save', 'onClick' => '$(\'input:hidden[survey-status='.self::DRAFT.']\').val('.self::COMPLETED.');']
		];
		$buttons[2] = $cancelButton;
	}

	public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$url = null;
		if ($entity->status == self::DRAFT) {
			$url = $this->ControllerAction->url('edit');
			$this->Alert->success('InstitutionSurveys.save.draft', ['reset' => true]);
		} else if ($entity->status == self::COMPLETED) {
			// To trigger from Open to Pending For Approval
			$this->setNextTransitions($entity);
			// End

			$url = $this->ControllerAction->url('index');
			$this->Alert->success('InstitutionSurveys.save.final', ['reset' => true]);
		}

		if (!is_null($url)) {
			$event->stopPropagation();
			return $this->controller->redirect($url);
		}
	}

	public function onApprove(Event $event, $id=null) {
		$this->updateAll(
			['status' => self::COMPLETED],
			['id' => $id]
		);

		$this->Alert->success('InstitutionSurveys.save.final', ['reset' => true]);
	}

	public function OnReject(Event $event, $id=null) {
		$this->updateAll(
			['status' => self::DRAFT],
			['id' => $id]
		);

		$this->Alert->success('InstitutionSurveys.reject.success', ['reset' => true]);
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$statusOptions = $this->getSelectOptions('Surveys.status');
		$selectedStatus = $this->queryString('status', $statusOptions);

		// If do not have access to Survey - New but have access to Survey - Completed, then set selectedStatus to COMPLETED
		if (!$this->AccessControl->check([$this->controller->name, 'NewSurveys', 'view'])) {
			if ($this->AccessControl->check([$this->controller->name, 'CompletedSurveys', 'view'])) {
				$selectedStatus = self::COMPLETED;
				$this->request->query['status'] = $selectedStatus;
			}
		}

		return compact('statusOptions', 'selectedStatus');
	}
}
