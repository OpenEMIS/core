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

	// Default Status
	const EXPIRED = -1;

	public $attachWorkflow = true;	// indicate whether the model require workflow
	public $hasWorkflow = false;	// indicate whether workflow is setup

	public $openStepId = null;
	public $closedStepId = null;

	private $workflowEvents = [];

	public function initialize(array $config) {
		$this->table('institution_site_surveys');
		parent::initialize($config);
		
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
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
			// 'formFilterClass' => ['className' => 'CustomField.CustomFormsFilters'],
			'recordKey' => 'institution_site_survey_id',
			'fieldValueClass' => ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'Institution.InstitutionSurveyTableCells', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Workflow.getEvents'] = 'getWorkflowEvents';
    	$events['Workflow.afterTransition'] = 'workflowAfterTransition';
    	foreach ($this->workflowEvents as $event) {
    		$events[$event['value']] = $event['method'];
    	}

    	return $events;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
    	$this->updateStatusId($entity);

    	$currentAction = $this->ControllerAction->action();
    	if ($currentAction == 'edit') {
			$url = $this->ControllerAction->url($currentAction);
			$event->stopPropagation();
			return $this->controller->redirect($url);
    	}
	}

    public function getWorkflowEvents(Event $event) {
    	foreach ($this->workflowEvents as $key => $attr) {
    		$this->workflowEvents[$key]['text'] = __($attr['text']);
    	}

    	return $this->workflowEvents;
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
		$SurveyStatusPeriods = $this->SurveyForms->SurveyStatuses->SurveyStatusPeriods;

		$results = $SurveyStatuses
			->find()
			->select([
				$SurveyStatuses->aliasField('date_disabled')
			])
			->innerJoin(
				[$SurveyStatusPeriods->alias() => $SurveyStatusPeriods->table()],
				[
					$SurveyStatusPeriods->aliasField('survey_status_id = ') . $SurveyStatuses->aliasField('id'),
					$SurveyStatusPeriods->aliasField('academic_period_id') => $academicPeriodId
				]
			)
			->where([
				$SurveyStatuses->aliasField('survey_form_id') => $surveyFormId
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
		// Retrieve from here because will be reset in beforeAction of WorkflowBehavior
		$this->attachWorkflow = $this->controller->Workflow->attachWorkflow;
		$this->hasWorkflow = $this->controller->Workflow->hasWorkflow;
		// End

		if ($this->attachWorkflow) {
			$stepOptions = [];
			if ($this->hasWorkflow) {
				$stepOptions = $this->ControllerAction->getVar('stepOptions');
				$WorkflowSteps = $this->Statuses;
				if (is_null($this->openStepId)) {
					$openStep = $WorkflowSteps
						->find()
						->where([
							$WorkflowSteps->aliasField('id IN') => array_keys($stepOptions),
							$WorkflowSteps->aliasField('stage') => 0	// Open
						])
						->first();

					if (!empty($openStep)) {
						$this->openStepId = $openStep->id;
					}
				}

				if (is_null($this->closedStepId)) {
					$closedStep = $WorkflowSteps
						->find()
						->where([
							$WorkflowSteps->aliasField('id IN') => array_keys($stepOptions),
							$WorkflowSteps->aliasField('stage') => 2	// Closed
						])
						->first();

					if (!empty($closedStep)) {
						$this->closedStepId = $closedStep->id;
					}
				}
			}

			if (empty($stepOptions)) {
				$stepOptions = ['' => $this->ControllerAction->Alert->getMessage('general.select.noOptions')];
			}
		}

		$this->ControllerAction->field('status_id', [
			'visible' => false
		]);
		$this->ControllerAction->field('description');
		$fieldOrder = ['survey_form_id', 'description', 'academic_period_id'];
		$selectedStep = $this->ControllerAction->getVar('selectedStep');

		if (is_null($selectedStep)) {
			$this->ControllerAction->field('to_be_completed_by');
			$fieldOrder[] = 'to_be_completed_by';
		} else {
			if ($selectedStep == $this->openStepId) {	// Open
				$this->buildSurveyRecords();
				$this->ControllerAction->field('to_be_completed_by');
				$fieldOrder[] = 'to_be_completed_by';
			} else if ($selectedStep == $this->closedStepId) {	// Closed
				$this->ControllerAction->field('completed_on');
				$fieldOrder[] = 'completed_on';
			} else {
				$this->ControllerAction->field('last_modified');
				$this->ControllerAction->field('to_be_completed_by');
				$fieldOrder[] = 'last_modified';
				$fieldOrder[] = 'to_be_completed_by';
			}
		}
		$this->ControllerAction->setFieldOrder($fieldOrder);
	}

	public function viewBeforeAction(Event $event) {
		$this->ControllerAction->field('status_id');
		$this->ControllerAction->field('academic_period_id');
		$this->ControllerAction->field('survey_form_id');
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('status_id', [
			'attr' => ['value' => $entity->status_id]
		]);
		$this->ControllerAction->field('academic_period_id', [
			'attr' => ['value' => $entity->academic_period_id]
		]);
		$this->ControllerAction->field('survey_form_id', [
			'attr' => ['value' => $entity->survey_form_id]
		]);
	}

	public function onUpdateFieldStatusId(Event $event, array $attr, $action, $request) {
		if ($action == 'view') {
			$attr['type'] = 'hidden';
		} else if ($action == 'edit') {
			$statusOptions = $this->getWorkflowStepList();
			$statusId = $attr['attr']['value'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $statusOptions[$statusId];
		}

		return $attr;
	}

	public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request) {
		if ($action == 'view') {
			$attr['type'] = 'select';
		} else if ($action == 'edit') {
			$periodOptions = $this->AcademicPeriods->getList(['withLevels' => false]);
			$periodId = $attr['attr']['value'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $periodOptions[$periodId];
		}

		return $attr;
	}

	public function onUpdateFieldSurveyFormId(Event $event, array $attr, $action, $request) {
		if ($action == 'view') {
			$attr['type'] = 'select';
		} else if ($action == 'edit') {
			$formOptions = $this->getForms();
			$formId = $attr['attr']['value'];

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $formOptions[$formId];
		}

		return $attr;
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
		$openStepId = $this->openStepId;

		// Update all New Survey to Expired by Institution Id
		$this->updateAll(['status_id' => self::EXPIRED],
			[
				'institution_site_id' => $institutionId,
				'status_id' => $openStepId
			]
		);

		foreach ($surveyForms as $surveyFormId => $surveyForm) {
			$periodResults = $SurveyStatusPeriods
				->find()
				->matching($this->AcademicPeriods->alias())
				->matching($SurveyStatuses->alias(), function($q) use ($SurveyStatuses, $surveyFormId, $todayDate) {
					return $q
						->where([
							$SurveyStatuses->aliasField('survey_form_id') => $surveyFormId,
							$SurveyStatuses->aliasField('date_disabled >=') => $todayDate
						]);
				})
				->all();

			foreach ($periodResults as $obj) {
				$periodId = $obj->academic_period_id;
				if (!is_null($institutionId)) {
					$where = [
						$this->aliasField('academic_period_id') => $periodId,
						$this->aliasField('survey_form_id') => $surveyFormId,
						$this->aliasField('institution_site_id') => $institutionId
					];

					$results = $this
						->find('all')
						->where($where)
						->all();

					if ($results->isEmpty()) {
						// Insert New Survey if not found
						$surveyData = [
							'status_id' => $openStepId,
							'academic_period_id' => $periodId,
							'survey_form_id' => $surveyFormId,
							'institution_site_id' => $institutionId
						];

						$surveyEntity = $this->newEntity($surveyData, ['validate' => false]);
						if ($this->save($surveyEntity)) {
						} else {
							$this->log($surveyEntity->errors(), 'debug');
						}
					} else {
						// Update Expired Survey back to New
						$this->updateAll(['status_id' => $openStepId],
							[
								'academic_period_id' => $periodId,
								'survey_form_id' => $surveyFormId,
								'institution_site_id' => $institutionId,
								'status_id' => self::EXPIRED
							]
						);
					}
				}
			}
		}
	}

	public function workflowAfterTransition(Event $event, $id=null) {
		$entity = $this->get($id);
		$this->updateStatusId($entity);
	}

	public function updateStatusId(Entity $entity) {
		if($this->hasBehavior('Workflow')) {
			$workflowRecord = $this->getRecord($this->registryAlias(), $entity);
			if (!empty($workflowRecord)) {
				$this->updateAll(
					['status_id' => $workflowRecord->workflow_step_id],
					['id' => $entity->id]
				);
			}
		}
	}
}
