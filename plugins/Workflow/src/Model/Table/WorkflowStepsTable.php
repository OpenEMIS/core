<?php
namespace Workflow\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;

class WorkflowStepsTable extends AppTable {
	use OptionsTrait;

	// Workflow Steps - category
	const TO_DO = 1;
	const IN_PROGRESS = 2;
	const DONE = 3;

	// Workflow Actions - action
	const APPROVE = 0;
	const REJECT = 1;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Workflows', ['className' => 'Workflow.Workflows']);
		$this->hasMany('WorkflowActions', ['className' => 'Workflow.WorkflowActions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('NextWorkflowSteps', ['className' => 'Workflow.WorkflowActions', 'foreignKey' => 'next_workflow_step_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('WorkflowStepsParams', ['className' => 'Workflow.WorkflowStepsParams', 'foreignKey' => 'workflow_step_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']);

		$this->hasMany('StaffLeave', ['className' => 'Institution.StaffLeave', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSurveys', ['className' => 'Institution.InstitutionSurveys', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('TrainingSessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('TrainingSessionResults', ['className' => 'Training.TrainingSessionResults', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('StaffPositionProfiles', ['className' => 'Institution.StaffPositionProfiles', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionStaffTransfers', ['className' => 'Institution.InstitutionStaffTransfers', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionStaffReleases', ['className' => 'Institution.InstitutionStaffTransfers', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('ScholarshipApplications', ['className' => 'Scholarship.ScholarshipApplications', 'foreignKey' => 'status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('WorkflowStatuses' , [
			'className' => 'Workflow.WorkflowStatuses',
			'joinTable' => 'workflow_statuses_steps',
			'foreignKey' => 'workflow_step_id',
			'targetForeignKey' => 'workflow_status_id',
			'through' => 'Workflow.WorkflowStatusesSteps',
			'dependent' => true
		]);
		$this->belongsToMany('SecurityRoles', [
			'className' => 'Security.SecurityRoles',
			'joinTable' => 'workflow_steps_roles',
			'foreignKey' => 'workflow_step_id',
			'targetForeignKey' => 'security_role_id',
			'through' => 'Workflow.WorkflowStepsRoles',
			'dependent' => true
		]);

		$this->addBehavior('Workflow.Transfer');
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->requirePresence('name', 'create')
			->requirePresence('category')
			->requirePresence('is_editable')
			->requirePresence('is_removable')
			->requirePresence('is_system_defined');
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		// Auto insert default workflow_actions when add
		if ($entity->isNew()) {
			if ($entity->has('is_system_defined') && $entity->is_system_defined == 1) {
				$data = [
					'workflow_actions' => []
				];
			} else {
				$data = [
					'workflow_actions' => [
						[
							'name' => __('Approve'),
							'action' => self::APPROVE,
							'visible' => 1,
							'next_workflow_step_id' => 0,
							'comment_required' => 0,
							'allow_by_assignee' => 0
						],
						[
							'name' => __('Reject'),
							'action' => self::REJECT,
							'visible' => 1,
							'next_workflow_step_id' => 0,
							'comment_required' => 0,
							'allow_by_assignee' => 0
						]
					]
				];
			}
			$entity = $this->patchEntity($entity, $data);
		}
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$models = TableRegistry::get('Workflow.WorkflowModels')->find()->all();
		$broadcaster = $this;
		$listeners = [];
		foreach ($models as $key => $obj) {
			$listeners[] = TableRegistry::get($obj->model);
		}

		if (!empty($listeners)) {
			$this->dispatchEventToModels('Model.WorkflowSteps.afterSave', [$entity], $broadcaster, $listeners);
		}
	}

	public function onGetCategory(Event $event, Entity $entity) {
		$value = '';
		if (!empty($entity->category)) {
			$categoryOptions = $this->getSelectOptions('WorkflowSteps.category');
			$value = $categoryOptions[$entity->category];
		} else {
			$value = '<span>&lt;'.$this->getMessage($this->aliasField('notCategorized')).'&gt;</span>';
		}

		return $value;
	}

	public function onGetIsEditable(Event $event, Entity $entity) {
		return $entity->is_editable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function onGetIsRemovable(Event $event, Entity $entity) {
		return $entity->is_removable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('security_roles', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Security Roles')
		]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list($modelOptions, $selectedModel) = array_values($this->getModelOptions());
		list($workflowOptions, $selectedWorkflow) = array_values($this->getWorkflowOptions($selectedModel));

		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Workflow.WorkflowSteps/controls', 'data' => compact('modelOptions', 'selectedModel', 'workflowOptions', 'selectedWorkflow'), 'options' => []]
        ];
		$this->controller->set('toolbarElements', $toolbarElements);

		$query
			->contain(['SecurityRoles', 'WorkflowStepsParams'])
			->where([$this->aliasField('workflow_id') => $selectedWorkflow]);
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupFields();

		$session = $this->request->session();
		$sessionKey = $this->registryAlias() . '.warning';
		if ($session->check($sessionKey)) {
			$warningKey = $session->read($sessionKey);
			$this->Alert->warning($warningKey);
			$session->delete($sessionKey);
		}
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->matching('Workflows')
			->contain(['SecurityRoles', 'WorkflowStepsParams']);
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		unset($this->request->query['model']);
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra) {
		list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));

		if (!$isDeletable) {
			$session = $this->request->session();
			$sessionKey = $this->registryAlias() . '.warning';
			$session->write($sessionKey, $this->aliasField('restrictDelete'));

			$url = $this->ControllerAction->url('index');
			$event->stopPropagation();
			return $this->controller->redirect($url);
		}

		$extra['excludedModels'] = [$this->WorkflowActions->alias()] + $this->getExcludedModels($entity);
    }

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		if ($entity->has('is_system_defined') && !empty($entity->is_system_defined)) {
			$this->Alert->info($this->aliasField('systemDefined'));
		}
	}

	public function onUpdateFieldWorkflowModelId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'edit' || $action == 'index') {
			$attr['visible'] = false;
		} else if ($action == 'add') {
			list($modelOptions) = array_values($this->getModelOptions());

			$attr['type'] = 'select';
			$attr['options'] = $modelOptions;
			$attr['onChangeReload'] = 'changeModel';
		}

		return $attr;
	}
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {
            $workflowStepId = $data['id'];
            

            // to only add validations on edit operations for the first workflow steps for any workflows with post event rules
            if (!is_null($workflowStepId) && $workflowStepId !== '') { 
                $WorkflowRuleEventsTable = TableRegistry::get('Workflow.WorkflowRuleEvents');
                $WorkflowRulesTable = TableRegistry::get('Workflow.WorkflowRules');
                $workflowId = $data['workflow_id'];

                $firstStepEntity = $WorkflowRulesTable->getWorkflowFirstStep($workflowId);

                // validations to be done only if the editing steps is the first step
                if (!is_null($firstStepEntity) && $firstStepEntity['id'] == $workflowStepId) {
                    $availableRuleResults = $WorkflowRulesTable
                        ->find('list', [
                            'keyField' => 'event_key',
                            'valueField' => 'feature'
                        ])
                        ->select([
                            'feature' => $WorkflowRulesTable->aliasField('feature'),
                            'event_key' => $WorkflowRuleEventsTable->aliasField('event_key')
                        ])
                        ->innerJoin([$WorkflowRuleEventsTable->alias() => $WorkflowRuleEventsTable->table()], [
                            $WorkflowRulesTable->aliasField('id = ') . $WorkflowRuleEventsTable->aliasField('workflow_rule_id')
                        ])
                        ->where([
                            $WorkflowRulesTable->aliasField('workflow_id') => $workflowId
                        ])
                        ->group(['event_key', 'feature'])
                        ->all();

                    // validations will only add if the first steps has any rules associated to it
                    if (!$availableRuleResults->isEmpty()) {
                        $ruleFeatures = $availableRuleResults->toArray();

                        $securityRoleCodes = [];
                        foreach ($ruleFeatures as $eventKey => $feature) {
                            $eventOptions = $WorkflowRulesTable->getEvents($feature, false);
                            
                            if (array_key_exists($eventKey, $eventOptions)) {
                                $roleCode = $eventOptions[$eventKey]['roleCode'];

                                if (!in_array($roleCode, $securityRoleCodes)) {
                                    $securityRoleCodes[] = $roleCode;
                                }
                            }
                        }

                        // validations will only add if the first steps has nay security roles associated to it
                        if (!empty($securityRoleCodes)) {
                            $SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
                            $roleIds = $SecurityRolesTable
                                ->find('list', [
                                    'keyField' => 'id',
                                    'valueField' => 'code'
                                ])
                                ->where([$SecurityRolesTable->aliasField('code IN ') => $securityRoleCodes])
                                ->toArray();

                            $validator = $this->validator();
                            $validator->add('security_roles', 'ruleWorkflowRuleRoles', [
                                'rule' => function ($value, $globalData) use ($roleIds) {

                                    if (array_key_exists('_ids', $value)) {
                                        $selectedRoleList = $value['_ids'];

                                        if ((is_null($selectedRoleList) || $selectedRoleList === '') && !empty($roleIds)) {
                                            return false;
                                        }

                                        foreach ($roleIds as $id => $code) {
                                            if (!in_array($id, $selectedRoleList)) {
                                                return false;
                                            }
                                        }
                                    }
                                    return true;
                                },
                                'message' => __('Some of the roles setup in workflow rules does not exist.')
                            ]);
                        }
                    }
                }
            }
        }
    }

	public function onUpdateFieldWorkflowId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$selectedModel = $request->query('model');
			list($workflowOptions) = array_values($this->getWorkflowOptions($selectedModel));

			$attr['options'] = $workflowOptions;
			$attr['onChangeReload'] = true;
		} else if ($action == 'edit') {
			$entity = $attr['attr']['entity'];
			$workflow = $entity->_matchingData['Workflows'];

			$attr['type'] = 'readonly';
			$attr['value'] = $workflow->id;
			$attr['attr']['value'] = $workflow->code_name;
		}

		return $attr;
	}

	public function onUpdateFieldName(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$entity = $attr['attr']['entity'];

			list($isEditable) = array_values($this->checkIfCanEditOrDelete($entity));
			if (!$isEditable) {
				$attr['attr']['disabled'] = 'disabled';
			} else {
				$attr['attr']['disabled'] = '';
			}
		}

		return $attr;
	}

	public function onUpdateFieldSecurityRoles(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
	        $securityRoleOptions = $this->SecurityRoles
	        	->find('list')
	        	->toArray();

	        $attr['options'] = $securityRoleOptions;
		}

		return $attr;
	}

	public function onUpdateFieldCategory(Event $event, array $attr, $action, Request $request) {
		$categoryOptions = $this->getSelectOptions('WorkflowSteps.category');
		if ($action == 'view' || $action == 'add') {
			$attr['type'] = 'select';
			$attr['options'] = $categoryOptions;
		} else if ($action == 'edit') {
			$entity = $attr['attr']['entity'];

			list($isEditable) = array_values($this->checkIfCanEditOrDelete($entity));
			if (!$isEditable) {
				$attr['type'] = 'readonly';
				$attr['value'] = $entity->category;
				$attr['attr']['value'] = $categoryOptions[$entity->category];
			} else {
				$attr['type'] = 'select';
				$attr['options'] = $this->getSelectOptions('WorkflowSteps.category');
			}
		}

		return $attr;
	}

	public function onUpdateFieldIsEditable(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'add' || $action == 'edit') {
			$attr['type'] = 'select';
			$attr['select'] = false;
			$attr['options'] = $this->getSelectOptions('general.yesno');
		}

		return $attr;
	}

	public function onUpdateFieldIsRemovable(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'add' || $action == 'edit') {
			$attr['type'] = 'select';
			$attr['select'] = false;
			$attr['options'] = $this->getSelectOptions('general.yesno');
		}

		return $attr;
	}

	public function onUpdateFieldIsSystemDefined(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['value'] = 0;
		}

		return $attr;
	}

	public function addEditOnChangeModel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['model']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('workflow_model_id', $request->data[$this->alias()])) {
					$request->query['model'] = $request->data[$this->alias()]['workflow_model_id'];
				}
			}
		}
	}

	private function setupFields(Entity $entity = null) {
		$attr = [];
		if (!is_null($entity)) {
			$attr['attr'] = ['entity' => $entity];
		}

		$this->ControllerAction->field('workflow_model_id');
		$this->ControllerAction->field('workflow_id', $attr);
		$this->ControllerAction->field('name', $attr);
		$this->ControllerAction->field('category', $attr);
		$this->ControllerAction->field('is_editable');
		$this->ControllerAction->field('is_removable');
		$this->ControllerAction->field('is_system_defined', ['type' => 'hidden']);

		$this->ControllerAction->setFieldOrder(['workflow_model_id', 'workflow_id', 'name', 'security_roles', 'category', 'is_editable', 'is_removable']);
	}

	private function checkIfCanEditOrDelete($entity) {
		$isEditable = true;
    	$isDeletable = true;

    	// not allow to edit name and delete for To Do, In Progress & Done
    	if ($entity->has('is_system_defined') && !empty($entity->is_system_defined)) {
			$isEditable = false;
    		$isDeletable = false;
		}

    	return compact('isEditable', 'isDeletable');
	}

	public function getModelOptions() {
		$modelOptions = $this->Workflows->WorkflowModels
			->find('list')
			->toArray();
		$selectedModel = $this->queryString('model', $modelOptions);

		return compact('modelOptions', 'selectedModel');
	}

	public function getWorkflowOptions($selectedModel=null) {
		$workflowOptions = $this->Workflows
			->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
			->where([
				$this->Workflows->aliasField('workflow_model_id') => $selectedModel
			])
			->order([
				$this->Workflows->aliasField('code')
			])
			->toArray();
		$selectedWorkflow = $this->queryString('workflow', $workflowOptions);

		return compact('workflowOptions', 'selectedWorkflow');
	}

	public function getExcludedModels(Entity $entity) {
		// defaultList should be updated when there are new workflow models added
		$defaultList = [
			$this->StaffLeave->registryAlias() => $this->StaffLeave->alias(),
			$this->InstitutionSurveys->registryAlias() => $this->InstitutionSurveys->alias(),
			$this->TrainingCourses->registryAlias() => $this->TrainingCourses->alias(),
			$this->TrainingSessions->registryAlias() => $this->TrainingSessions->alias(),
			$this->TrainingSessionResults->registryAlias() => $this->TrainingSessionResults->alias(),
			$this->TrainingNeeds->registryAlias() => $this->TrainingNeeds->alias(),
			$this->InstitutionPositions->registryAlias() => $this->InstitutionPositions->alias(),
			$this->StaffPositionProfiles->registryAlias() => $this->StaffPositionProfiles->alias(),
			$this->InstitutionStaffTransfers->registryAlias() => $this->InstitutionStaffTransfers->alias(),
			$this->InstitutionStaffReleases->registryAlias() => $this->InstitutionStaffReleases->alias(),
			$this->WorkflowStepsParams->registryAlias() => $this->WorkflowStepsParams->alias()
		];

		$statusId = $entity->id;
		$workflowStepEntity = $this
			->find()
			->matching('Workflows.WorkflowModels')
			->where([$this->aliasField('id') => $statusId])
			->first();

		$workflowModelEntity = $workflowStepEntity->_matchingData['WorkflowModels'];
		$model = TableRegistry::get($workflowModelEntity->model);

		if (array_key_exists($model->registryAlias(), $defaultList)) {
			unset($defaultList[$model->registryAlias()]);
		}

		$list = array_values($defaultList);

		return $list;
	}
}
