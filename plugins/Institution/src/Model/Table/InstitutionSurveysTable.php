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

	public $module = 'Institution.Institutions';
	public $attachWorkflow = true;	// indicate whether the model require workflow
	public $hasWorkflow = false;	// indicate whether workflow is setup

	public $openStatusId = null;
	public $closedStatusId = null;

	private $workflowEvents = [];

	public function initialize(array $config) {
		$this->table('institution_site_surveys');
		parent::initialize($config);
		
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->addBehavior('Survey.Survey', [
			'module' => $this->module
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
    	$events['Workflow.getFilterOptions'] = 'getWorkflowFilterOptions';
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

	public function getWorkflowFilterOptions(Event $event) {
		$CustomModules = $this->SurveyForms->CustomModules;
		$module = $this->module;
		$list = $this->SurveyForms
			->find('list')
			->matching('CustomModules', function($q) use ($CustomModules, $module) {
				return $q->where([$CustomModules->aliasField('model') => $module]);
			})
			->toArray();

		return $list;
	}

    public function getWorkflowEvents(Event $event) {
    	foreach ($this->workflowEvents as $key => $attr) {
    		$this->workflowEvents[$key]['text'] = __($attr['text']);
    	}

    	return $this->workflowEvents;
    }

    public function onGetStatusId(Event $event, Entity $entity) {
		return '<span class="status highlight">' . $entity->status->name . '</span>';
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
			if ($this->hasWorkflow) {
				$selectedFilter = $this->ControllerAction->getVar('selectedFilter');
				if ($selectedFilter != -1) {
					$workflow = $this->getWorkflow($this->registryAlias(), null, $selectedFilter);
					if (!empty($workflow)) {
						foreach ($workflow->workflow_steps as $workflowStep) {
							if ($workflowStep->stage == 0) {	// Open
								$this->openStatusId = $workflowStep->id;
							} else if ($workflowStep->stage == 2) {	// Closed
								$this->closedStatusId = $workflowStep->id;
							}
						}
					}
				}
			}
		}

		$this->ControllerAction->field('description');
		$fieldOrder = ['status_id', 'survey_form_id', 'description', 'academic_period_id'];
		$selectedStatus = $this->ControllerAction->getVar('selectedStatus');

		if (is_null($selectedStatus) || $selectedStatus == -1) {
			$this->buildSurveyRecords();
			$this->ControllerAction->field('last_modified');
			$fieldOrder[] = 'last_modified';
		} else {
			if ($selectedStatus == $this->openStatusId) {	// Open
				$this->buildSurveyRecords();
				$this->ControllerAction->field('to_be_completed_by');
				$fieldOrder[] = 'to_be_completed_by';
			} else if ($selectedStatus == $this->closedStatusId) {	// Closed
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

		foreach ($surveyForms as $surveyFormId => $surveyForm) {
			$openStatusId = null;
			$workflow = $this->getWorkflow($this->registryAlias(), null, $surveyFormId);
			if (!empty($workflow)) {
				foreach ($workflow->workflow_steps as $workflowStep) {
					if ($workflowStep->stage == 0) {
						$openStatusId = $workflowStep->id;
						break;
					}
				}

				// Update all New Survey to Expired by Institution Id
				$this->updateAll(['status_id' => self::EXPIRED],
					[
						'institution_site_id' => $institutionId,
						'status_id' => $openStatusId
					]
				);

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
								'status_id' => $openStatusId,
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
							// Update Expired Survey back to Open
							$this->updateAll(['status_id' => $openStatusId],
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
