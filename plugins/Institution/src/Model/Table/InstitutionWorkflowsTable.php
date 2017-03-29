<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class InstitutionWorkflowsTable extends ControllerActionTable
{
    // Workflow Actions - action
    const APPROVE = 0;
    const REJECT = 1;

	private $features = [
		'StaffBehaviours' => [
			'className' => 'Institution.StaffBehaviours'
		],
		'StudentBehaviours' => [
			'className' => 'Institution.StudentBehaviours'
		]
	];
    
    private $workflowEvents = [
        [
            'value' => 'Workflow.onAssignBack',
            'text' => 'Assign Back to Creator',
            'description' => 'Performing this action will assign the current record back to creator.',
            'method' => 'onAssignBack'
        ]
    ];

	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps']);
		$this->belongsTo('Assignees', ['className' => 'User.Users']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

		$this->belongsToMany('LinkedRecords', [
			'className' => 'Institution.StaffBehaviours',
			'joinTable' => 'institution_workflows_records',
			'foreignKey' => 'institution_workflow_id',
			'targetForeignKey' => 'record_id',
			'through' => 'Institution.InstitutionWorkflowsRecords',
			'dependent' => true
		]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
	}

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.processWorkflow'] = 'processWorkflow';
        $events['Workflow.afterTransition'] = 'workflowAfterTransition';
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        foreach($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function processWorkflow(Event $mainEvent, ArrayObject $extra)
    {
        if ($this->request->is(['post', 'put'])) {
            $requestData = $this->request->data;

            $WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');
            $WorkflowActions = TableRegistry::get('Workflow.WorkflowActions');
            $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');

            $workflowModelId = $requestData[$WorkflowTransitions->alias()]['workflow_model_id'];
            $workflowModelEntity = $WorkflowModels->get($workflowModelId);
            $subject = TableRegistry::get($workflowModelEntity->model);

            // Trigger workflow before save event here
            $event = $subject->dispatchEvent('Workflow.beforeTransition', [$requestData], $subject);
            if ($event->isStopped()) { return $event->result; }
            // End

            // Insert into workflow_transitions.
            $workflowTransitionEntity = $WorkflowTransitions->newEntity($requestData, ['validate' => false]);
            $recordId = $workflowTransitionEntity->model_reference;

            if ($WorkflowTransitions->save($workflowTransitionEntity)) {
                $this->Alert->success('general.edit.success', ['reset' => true]);

                // Trigger workflow after save event here
                $event = $this->dispatchEvent('Workflow.afterTransition', [$recordId, $requestData], $this);
                if ($event->isStopped()) { return $event->result; }
                // End

                // Trigger event here
                $workflowActionEntity = $WorkflowActions->get($workflowTransitionEntity->workflow_action_id);

                if (!empty($workflowActionEntity->event_key)) {
                    $eventKeys = explode(",", $workflowActionEntity->event_key);

                    foreach ($eventKeys as $eventKey) {
                        $event = $this->dispatchEvent($eventKey, [$recordId, $workflowTransitionEntity], $this);
                        if ($event->isStopped()) { return $event->result; }
                    }
                }
                // End
            } else {
                Log::write('debug', $workflowTransitionEntity->errors());
                $this->Alert->error('general.edit.failed', ['reset' => true]);
            }

            $mainEvent->stopPropagation();
            return $this->controller->redirect($this->url('view'));
        }
    }

    public function workflowAfterTransition(Event $event, $id=null, $requestData)
    {
        try {
            $entity = $this->get($id);
            $this->setStatusId($entity, $requestData);
            $this->setAssigneeId($entity, $requestData);
            $this->save($entity);
        } catch (RecordNotFoundException $e) {
            Log::write('debug', $e->getMessage());
        }
    }

    private function setStatusId(Entity $entity, $requestData)
    {
        $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');

        if (array_key_exists($WorkflowTransitions->alias(), $requestData)) {
            if (array_key_exists('workflow_step_id', $requestData[$WorkflowTransitions->alias()])) {
                $statusId = $requestData[$WorkflowTransitions->alias()]['workflow_step_id'];
                $entity->status_id = $statusId;
            }
        }
    }

    private function setAssigneeId(Entity $entity, $requestData)
    {
        $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');

        if (array_key_exists($WorkflowTransitions->alias(), $requestData)) {
            if (array_key_exists('assignee_id', $requestData[$WorkflowTransitions->alias()]) && !empty($requestData[$WorkflowTransitions->alias()]['assignee_id'])) {
                $assigneeId = $requestData[$WorkflowTransitions->alias()]['assignee_id'];
            } else {
                $assigneeId = 0;
            }
            $entity->assignee_id = $assigneeId;
        }
    }

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onAssignBack(Event $event, $id, Entity $workflowTransitionEntity)
    {
        try {
            $entity = $this->get($id);
            $this->setAssigneeAsCreator($entity);
            $this->save($entity);
        } catch (RecordNotFoundException $e) {
            Log::write('debug', $e->getMessage());
        }
    }

    public function setAssigneeAsCreator(Entity $entity)
    {
        if ($entity->has('created_user_id')) {
            $entity->assignee_id = $entity->created_user_id;
        }
    }

    public function onGetFeature(Event $event, Entity $entity)
    {
        $featureOptions = $this->getFeatureOptions();
        return $featureOptions[$entity->feature];
    }

    public function onGetStatusId(Event $event, Entity $entity)
    {
        return '<span class="status highlight">' . $entity->status->name . '</span>';
    }

    public function onGetAssigneeId(Event $event, Entity $entity)
    {
        return empty($entity->assignee_id) ? '<span>&lt;'.$this->getMessage('general.unassigned').'&gt;</span>' : '';
    }

	public function onGetLinkedRecords(Event $event, Entity $entity)
	{
		$linkedRecords = [];
		if ($entity->has('linked_records')) {
			foreach ($entity->linked_records as $key => $obj) {
				$linkedRecords[] = $obj['description'];
			}
		}

		return !empty($linkedRecords) ? implode(", ", $linkedRecords) : '';
	}

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $featureOptions = $this->getFeatureOptions();
        $selectedFeature = !is_null($this->request->query('feature')) ? $this->request->query('feature') : key($featureOptions);
        $filterOptions = [];
        $selectedFilter = '';
        $statusOptions = [];
        $selectedStatus = '';

        $extra['selectedFeature'] = $selectedFeature;
        $extra['selectedFilter'] = $selectedFilter;
        $extra['selectedStatus'] = $selectedStatus;

        $extra['elements']['control'] = [
            'name' => 'Institution.Workflows/controls',
            'data' => [
                'featureOptions'=> $featureOptions,
                'selectedFeature'=> $selectedFeature,
                'filterOptions'=> $filterOptions,
                'selectedFilter'=> $selectedFilter,
                'statusOptions'=> $statusOptions,
                'selectedStatus' => $selectedStatus
            ],
            'order' => 3
        ];

        $this->setFieldOrder(['feature', 'status_id', 'assignee_id', 'title']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $where = [];
        if (isset($extra['selectedFeature']) && !empty($extra['selectedFeature'])) {
            $where[$this->aliasField('feature')] = $extra['selectedFeature'];
        }
        if (isset($extra['selectedStatus']) && !empty($extra['selectedStatus'])) {
            $where[$this->aliasField('status_id')] = $extra['selectedStatus'];
        }

        $query->where($where);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
    	$query->contain(['LinkedRecords']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
    	$this->setupFields($entity, $extra);
        $this->addToolbarButtons($entity, $extra);
        $this->addWorkflowTransitionModal($entity, $extra);
        $this->addWorkflowTransitionElement($entity, $extra);
    }

    private function setupFields(Entity $entity, ArrayObject $extra)
    {
		$this->field('feature', ['entity' => $entity]);
		$this->field('status_id');
		$this->field('assignee_id');
		$this->field('linked_records', ['type' => 'chosenSelect', 'entity' => $entity]);

		$this->setFieldOrder(['feature', 'status_id', 'assignee_id', 'title', 'linked_records']);
    }

    public function getFeatureOptions()
    {
        $featureOptions = [];
        foreach ($this->features as $key => $obj) {
            $featureOptions[$key] = __(Inflector::humanize(Inflector::underscore($key)));
        }

        return $featureOptions;
    }

    private function addToolbarButtons(Entity $entity, ArrayObject $extra)
    {
        $workflowStepEntity = $this->getWorkflowStep($entity);

        if (!empty($workflowStepEntity)) {
            $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
            $attr = [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false
            ];

            $isSchoolBased = $workflowStepEntity->_matchingData['WorkflowModels']->is_school_based;

            $actionButtons = [];
            foreach ($workflowStepEntity->workflow_actions as $actionKey => $actionObj) {
                $actionType = $actionObj->action;
                $nextStep = $actionObj->next_workflow_step;
                $assigneeRequired = 1;
                $eventDescription = '';
                $visibleField = [];

                $button = [
                    'id' => $actionObj->id,
                    'name' => $actionObj->name,
                    'description' => $actionObj->description,
                    'next_step_id' => $nextStep->id,
                    'next_step_name' => $nextStep->name,
                    'assignee_required' => $assigneeRequired,
                    'comment_required' => $actionObj->comment_required,
                    'event_description' => $eventDescription,
                    'is_school_based' => $isSchoolBased,
                    'modal_visible_field' => $visibleField
                ];

                $json = json_encode($button, JSON_NUMERIC_CHECK);

                $buttonAttr = [
                    'escapeTitle' => false,
                    'escape' => true,
                    'onclick' => 'Workflow.init();Workflow.copy('.$json.');return false;',
                    'data-toggle' => 'modal',
                    'data-target' => '#workflow-transition-modal'
                ];
                $buttonAttr = array_merge($attr, $buttonAttr);

                switch($actionType) {
                    case self::APPROVE:
                        $approveButton = [];
                        $approveButton['type'] = 'button';
                        $approveButton['label'] = '<i class="fa kd-approve"></i>';
                        $approveButton['url'] = '#';
                        $approveButton['attr'] = $buttonAttr;
                        $approveButton['attr']['title'] = __($actionObj->name);

                        $toolbarButtonsArray['approve'] = $approveButton;
                        break;

                    case self::REJECT:
                        $rejectButton = [];
                        $rejectButton['type'] = 'button';
                        $rejectButton['label'] = '<i class="fa kd-reject"></i>';
                        $rejectButton['url'] = '#';
                        $rejectButton['attr'] = $buttonAttr;
                        $rejectButton['attr']['title'] = __($actionObj->name);

                        $toolbarButtonsArray['reject'] = $rejectButton;
                        break;

                    default:
                        if (array_key_exists('class', $buttonAttr)) {
                            unset($buttonAttr['class']);
                        }

                        $actionButton = [];
                        $actionButton['label'] = __($actionObj->name);
                        $actionButton['url'] = '#';
                        $actionButton['attr'] = $buttonAttr;
                        $actionButton['attr']['title'] = __($actionObj->name);
                        $actionButton['attr']['role'] = 'menuitem';

                        $actionButtons[] = $actionButton;
                        break;
                }
            }

            // More Actions
            $moreButtonLink = [];
            if (!empty($actionButtons)) {
                $moreButtonLink = [
                    'title' => __('More Actions') . '<span class="caret-down"></span>',
                    'url' => '#',
                    'options' => [
                        'escapeTitle' => false, // Disabled coversion of HTML special characters in $title to HTML entities
                        'id' => 'action-menu',
                        'class' => 'btn btn-default action-toggle outline-btn',
                        'data-toggle' => 'dropdown',
                        'aria-expanded' => true
                    ]
                ];

                $moreButton = [];
                $moreButton['type'] = 'element';
                $moreButton['element'] = 'Workflow.buttons';
                $moreButton['data'] = [
                    'buttons' => $actionButtons
                ];
                $moreButton['options'] = [];

                $toolbarButtonsArray['more'] = $moreButton;
            }
            $this->controller->set(compact('moreButtonLink', 'actionButtons'));
            // End

            $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        }
    }

    private function getWorkflow($entity)
    {
        $workflowEntity = null;

        if ($entity->has('status_id') && !empty($entity->status_id)) {
            $WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
            $workflowStepEntity = $WorkflowSteps
                ->find()
                ->matching('Workflows')
                ->where([$WorkflowSteps->aliasField('id') => $entity->status_id])
                ->first();

            $workflowEntity = $workflowStepEntity->_matchingData['Workflows'];
        }

        return $workflowEntity;
    }

    private function getWorkflowStep($entity)
    {
        $workflowStepEntity = null;

        if ($entity->has('status_id') && !empty($entity->status_id)) {
            $WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
            $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');

            $userId = $this->Auth->user('id');
            $isAdmin = $this->AccessControl->isAdmin();
            $registryAlias = $this->features[$entity->feature]['className'];
            $model = TableRegistry::get($registryAlias);

            $workflowStepId = $entity->status_id;
            $assigneeId = $entity->assignee_id;

            // user roles
            $roleIds = [];
            $event = $model->dispatchEvent('Workflow.onUpdateRoles', null, $this);
            if ($event->result) {
                $roleIds = $event->result;
            } else {
                $roles = $this->AccessControl->getRolesByUser()->toArray();
                foreach ($roles as $key => $role) {
                    $roleIds[$role->security_role_id] = $role->security_role_id;
                }
            }
            // End

            if ($isAdmin) {
                // super admin allow to see the full list of action buttons
                $query = $WorkflowSteps
                    ->find()
                    ->matching('Workflows.WorkflowModels')
                    ->contain(['WorkflowActions' => function ($q) {
                            return $q
                                ->find('visible')
                                ->where(['next_workflow_step_id !=' => 0]);
                        }
                    ])
                    ->contain('WorkflowActions.NextWorkflowSteps')
                    ->where([
                        $WorkflowSteps->aliasField('id') => $workflowStepId // Latest Workflow Step
                    ]);

                $workflowStepEntity = $query->first();
            } else {
                // if is not super admin
                if (!empty($roleIds)) {
                    $stepsRolesResult = $WorkflowStepsRoles
                        ->find()
                        ->where([
                            $WorkflowStepsRoles->aliasField('workflow_step_id') => $workflowStepId,
                            $WorkflowStepsRoles->aliasField('security_role_id IN') => $roleIds
                        ])
                        ->all();

                    if ($stepsRolesResult->isEmpty()) {
                        // if login user roles is not allow to access current step
                        if ($userId == $assigneeId) {
                            $query = $WorkflowSteps
                                ->find()
                                ->matching('Workflows.WorkflowModels')
                                ->contain(['WorkflowActions' => function ($q) {
                                        return $q
                                            ->find('visible')
                                            ->where([
                                                'next_workflow_step_id !=' => 0,
                                                'allow_by_assignee' => 1
                                            ]);
                                    }
                                ])
                                ->contain('WorkflowActions.NextWorkflowSteps')
                                ->where([
                                    $WorkflowSteps->aliasField('id') => $workflowStepId // Latest Workflow Step
                                ]);

                            $workflowStepEntity = $query->first();
                        }
                    } else {
                        // if login user roles is allow to access current step
                        $query = $WorkflowSteps
                            ->find()
                            ->matching('Workflows.WorkflowModels')
                            ->contain(['WorkflowActions' => function ($q) {
                                    return $q
                                        ->find('visible')
                                        ->where(['next_workflow_step_id !=' => 0]);
                                }
                            ])
                            ->contain('WorkflowActions.NextWorkflowSteps')
                            ->where([
                                $WorkflowSteps->aliasField('id') => $workflowStepId // Latest Workflow Step
                            ])
                            ->innerJoin(
                                [$WorkflowStepsRoles->alias() => $WorkflowStepsRoles->table()],
                                [
                                    $WorkflowStepsRoles->aliasField('workflow_step_id = ') . $WorkflowSteps->aliasField('id'),
                                    $WorkflowStepsRoles->aliasField('security_role_id IN') => $roleIds
                                ]
                            );

                        $workflowStepEntity = $query->first();
                    }
                }
            }
        }

        return $workflowStepEntity;
    }

    private function addWorkflowTransitionModal(Entity $entity, ArrayObject $extra)
    {
        $modal = [];
        $workflowEntity = $this->getWorkflow($entity);
        $workflowStepEntity = $this->getWorkflowStep($entity);
        $assigneeUrl = Router::url(['plugin' => 'Workflow', 'controller' => 'Workflows', 'action' => 'ajaxGetAssignees']);

        if (!empty($workflowEntity) && !empty($workflowStepEntity)) {
            $registryAlias = $this->features[$entity->feature]['className'];
            $model = TableRegistry::get($registryAlias);

            $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');
            $alias = $WorkflowTransitions->alias();
            // workflow_step_id is needed for afterSave logic in WorkflowTransitions
            $fields = [
                $alias.'.prev_workflow_step_id' => [
                    'type' => 'hidden',
                    'value' => $workflowStepEntity->id
                ],
                $alias.'.prev_workflow_step_name' => [
                    'type' => 'hidden',
                    'value' => $workflowStepEntity->name
                ],
                $alias.'.workflow_step_id' => [
                    'type' => 'hidden',
                    'value' => 0,
                    'class' => 'workflowtransition-step-id',
                    'unlockField' => true
                ],
                $alias.'.workflow_step_name' => [
                    'type' => 'hidden',
                    'value' => '',
                    'class' => 'workflowtransition-step-name',
                    'unlockField' => true
                ],
                $alias.'.workflow_action_id' => [
                    'type' => 'hidden',
                    'value' => 0,
                    'class' => 'workflowtransition-action-id',
                    'unlockField' => true
                ],
                $alias.'.workflow_action_name' => [
                    'type' => 'hidden',
                    'value' => '',
                    'class' => 'workflowtransition-action-name',
                    'unlockField' => true
                ],
                $alias.'.workflow_action_description' => [
                    'type' => 'hidden',
                    'value' => '',
                    'class' => 'workflowtransition-action-description',
                    'unlockField' => true
                ],
                $alias.'.workflow_model_id' => [
                    'type' => 'hidden',
                    'value' => $workflowEntity->workflow_model_id
                ],
                $alias.'.model_reference' => [
                    'type' => 'hidden',
                    'value' => $entity->id
                ],
                $alias.'.assignee_required' => [
                    'type' => 'hidden',
                    'value' => 1,
                    'class' => 'workflowtransition-assignee-required',
                    'unlockField' => true
                ],
                $alias.'.comment_required' => [
                    'type' => 'hidden',
                    'value' => 0,
                    'class' => 'workflowtransition-comment-required',
                    'unlockField' => true
                ]
            ];

            $contentFields = new ArrayObject([
                $alias.'.action_name' => [
                    'label' => __('Action'),
                    'type' => 'string',
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                    'class'=> 'workflowtransition-action-name'
                ],
                $alias.'.action_description' => [
                    'label' => __('Description'),
                    'type' => 'textarea',
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                    'class'=> 'workflowtransition-action-description'
                ],
                $alias.'.step_name' => [
                    'label' => __('Next Step'),
                    'type' => 'string',
                    'readonly' => 'readonly',
                    'disabled' => 'disabled',
                    'class'=> 'workflowtransition-step-name'
                ],
                $alias.'.assignee_id' => [
                    'label' => __('Assignee'),
                    'type' => 'select',
                    'class'=> 'workflowtransition-assignee-id',
                    'assignee-url' => $assigneeUrl
                ],
                $alias.'.comment' => [
                    'label' => __('Comment'),
                    'type' => 'textarea',
                    'class'=> 'workflowtransition-comment'
                ]
            ]);

            $model->dispatchEvent('Workflow.addCustomModalFields', [$entity, $contentFields, $alias], $this);

            $content = '';
            $content = '<style type="text/css">.modal-footer { clear: both; } .modal-body textarea { width: 60%; }</style>';
            $content .= '<div class="input string"><span class="button-label"></span>';
                $content .= '<div class="workflowtransition-assignee-loading">' . __('Loading') . '</div>';
                $content .= '<div class="workflowtransition-assignee-no_options">' . __('No options') . '</div>';
                $content .= '<div class="workflowtransition-assignee-error">' . __('This field cannot be left empty') . '</div>';
            $content .= '</div>';
            $content .= '<div class="input string"><span class="button-label"></span><div class="workflowtransition-comment-error error-message">' . __('This field cannot be left empty') . '</div></div>';
            $content .= '<div class="input string"><span class="button-label"></span><div class="workflowtransition-assignee-sql-error error-message">' .$this->getMessage('general.error'). '</div></div>';
            $content .= '<div class="input string"><span class="button-label"></span><div class="workflowtransition-event-description error-message"></div></div>';
            $buttons = [
                '<button id="workflow-submit" type="submit" class="btn btn-default" onclick="return Workflow.onSubmit();">' . __('Save') . '</button>'
            ];

            $modal = [
                'id' => 'workflow-transition-modal',
                'title' => __('Add Comment'),
                'content' => $content,
                'contentFields' => $contentFields,
                'form' => [
                    'model' => $this,
                    'formOptions' => [
                        'class' => 'form-horizontal',
                        'url' => $this->url('processWorkflow'),
                        'onSubmit' => 'document.getElementById("workflow-submit").disabled=true;'
                    ],
                    'fields' => $fields
                ],
                'buttons' => $buttons,
                'cancelButton' => true
            ];
        }

        if (!empty($modal)) {
            if (!isset($this->controller->viewVars['modals'])) {
                $this->controller->set('modals', ['workflow-transition-modal' => $modal]);
            } else {
                $modals = array_merge($this->controller->viewVars['modals'], ['workflow-transition-modal' => $modal]);
                $this->controller->set('modals', $modals);
            }
        }
    }

    private function addWorkflowTransitionElement(Entity $entity, ArrayObject $extra)
    {
        $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');

        $tableHeaders = [];
        $tableHeaders[] = __('Transition') . '<i class="fa fa-history fa-lg"></i>';
        $tableHeaders[] = __('Action') . '<i class="fa fa-ellipsis-h fa-2x"></i>';
        $tableHeaders[] = __('Comment') . '<i class="fa fa-comments fa-lg"></i>';
        $tableHeaders[] = __('Last Executer') . '<i class="fa fa-user fa-lg"></i>';
        $tableHeaders[] = __('Last Execution Date') . '<i class="fa fa-calendar fa-lg"></i>';

        $tableCells = [];
        $workflowEntity = $this->getWorkflow($entity);
        $transitionResults = $WorkflowTransitions
            ->find()
            ->contain(['ModifiedUser', 'CreatedUser'])
            ->where([
                $WorkflowTransitions->aliasField('workflow_model_id') => $workflowEntity->workflow_model_id,
                $WorkflowTransitions->aliasField('model_reference') => $entity->id
            ])
            ->order([
                $WorkflowTransitions->aliasField('created ASC')
            ])
            ->all();

        if (!$transitionResults->isEmpty()) {
            $transitions = $transitionResults->toArray();
            foreach ($transitions as $key => $transition) {
                $transitionDisplay = '<span class="status past">' . $transition->prev_workflow_step_name . '</span>';
                $transitionDisplay .= '<span class="transition-arrow"></span>';
                if (count($transitions) - 1 == $key) {
                    $transitionDisplay .= '<span class="status highlight">' . $transition->workflow_step_name . '</span>';
                } else {
                    $transitionDisplay .= '<span class="status past">' . $transition->workflow_step_name . '</span>';
                }

                $rowData = [];
                $rowData[] = $transitionDisplay;
                $rowData[] = $transition->workflow_action_name;
                $rowData[] = nl2br(htmlspecialchars($transition->comment));
                $rowData[] = $transition->created_user->name;
                $rowData[] = $transition->created->format('Y-m-d H:i:s');

                $tableCells[$key] = $rowData;
            }
        }

        $this->field('workflow_transitions', [
            'type' => 'element',
            'element' => 'Institution.Workflows/transitions',
            'after' => 'created',
            'override' => true,
            'rowClass' => 'transition-container',
            'tableHeaders' => $tableHeaders,
            'tableCells' => $tableCells
        ]);
    }
}
