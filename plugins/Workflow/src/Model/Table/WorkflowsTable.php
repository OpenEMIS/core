<?php
namespace Workflow\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\Datasource\ConnectionManager;
use App\Model\Traits\OptionsTrait;

class WorkflowsTable extends AppTable {
    use OptionsTrait;

    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    // Workflow Actions - action
    const APPROVE = 0;
    const REJECT = 1;

    // Apply To All
    const YES = 1;
    const NO = 0;

    private $WorkflowsFilters = null;
    private $filterClass = [
        'className' => null,
        'joinTable' => 'workflows_filters',
        'foreignKey' => 'workflow_id',
        'targetForeignKey' => 'filter_id',
        'through' => 'Workflow.WorkflowsFilters',
        'dependent' => true
    ];
    private $excludedModels = ['Cases.InstitutionCases'];

    public function initialize(array $config): void {
        parent::initialize($config);
        $this->belongsTo('WorkflowModels', ['className' => 'Workflow.WorkflowModels']);
        $this->hasMany('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('WorkflowRules', ['className' => 'Workflow.WorkflowRules', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->WorkflowsFilters = TableRegistry::getTableLocator()->get('Workflow.WorkflowsFilters');

    }

    public function validationDefault(Validator $validator): Validator {
        $validator = parent::validationDefault($validator);

        $validator->add('code', [
            'ruleUnique' => [
                'rule' => ['validateUnique', ['scope' => 'workflow_model_id']],
                'provider' => 'table'
            ]
        ])
        ->requirePresence('workflow_model_id')
        ->requirePresence('name');

        return $validator;
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options) {
        // Auto insert default workflow_steps when add
        if ($entity->isNew()) {
            $data = [
                'workflow_steps' => [
                    ['name' => __('Open'), 'category' => self::TO_DO, 'is_editable' => 1, 'is_removable' => 1, 'is_system_defined' => 1],
                    ['name' => __('Pending For Approval'), 'category' => self::IN_PROGRESS, 'is_editable' => 0, 'is_removable' => 0, 'is_system_defined' => 1],
                    ['name' => __('Closed'), 'category' => self::DONE, 'is_editable' => 0, 'is_removable' => 0, 'is_system_defined' => 1]
                ]
            ];

            $entity = $this->patchEntity($entity, $data);
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options) {
        if ($entity->isNew()) {
            // When add: preinsert default workflow actions
            $this->setWorkflowActions($entity);
        }

        // Only allow one workflow to set as Apply To All
        $this->setApplyToAll($entity);

        // Remap Workflow Steps
        $this->resetWorkflowStepId($entity);
    }

    public function indexBeforeAction(EventInterface $event) {
        //Add controls filter to index page
        $toolbarElements = [
            ['name' => 'Workflow.Workflows/controls', 'data' => [], 'options' => []]
        ];
        $this->controller->set('toolbarElements', $toolbarElements);
        // End

        $this->ControllerAction->field('message',['visible' => false]);
        $this->ControllerAction->field('apply_to_all');
        $this->ControllerAction->field('filters');
        $this->ControllerAction->setFieldOrder(['workflow_model_id', 'apply_to_all', 'filters', 'code', 'name']);
    }

    public function indexBeforePaginate(EventInterface $event,  $request, Query $query, ArrayObject $options) {
        $modelOptions = $this->getWorkflowModel();
        $modelOptions = ['-1' => __('All Workflows')] + $modelOptions;
        $selectedModel = $this->queryString('model', $modelOptions);
        $this->controller->set(compact('modelOptions', 'selectedModel'));

        $query->matching('WorkflowModels');
        $options['order'] = [
            $this->aliasField('workflow_model_id') => 'asc',
            $this->aliasField('code') => 'asc',
            $this->aliasField('name') => 'asc'
        ];

        if ($selectedModel != -1) {
            $query->where([$this->aliasField('workflow_model_id') => $selectedModel]);
        }
    }

    public function indexAfterAction(EventInterface $event, $data) {
        $session = $this->request->getSession();

        $sessionKey = $this->getRegistryAlias() . '.warning';
        if ($session->check($sessionKey)) {
            $warningKey = $session->read($sessionKey);
            $this->Alert->warning($warningKey);
            $session->delete($sessionKey);
        }
    }

    public function onGetApplyToAll(EventInterface $event, Entity $entity) {
        if ($this->action == 'index') {
            $entity->filters = [];

            if (!is_null($entity->_matchingData['WorkflowModels']->filter)) {
                $filter = $entity->_matchingData['WorkflowModels']->filter;

                $filterIds = $this->WorkflowsFilters
                    ->find('list', ['keyField' => 'filter_id', 'valueField' => 'filter_id'])
                    ->where([
                        $this->WorkflowsFilters->aliasField('workflow_id') => $entity->id
                    ])
                    ->toArray();

                if (array_key_exists(0, $filterIds)) {
                    $value = __('Yes');
                } else {
                    $value = __('No');

                    $filters = [];
                    $filterModel = TableRegistry::getTableLocator()->get($filter);
                    if (!empty($filterIds)) {
                        $filters = $filterModel
                            ->getList()
                            ->where([$filterModel->aliasField('id IN ') => $filterIds])
                            ->toArray();
                    }

                    $entity->filters = $filters;
                }

                return $value;
            }

            return '<i class="fa fa-minus"></i>';
        }
    }

    public function onGetFilters(EventInterface $event, Entity $entity) {
        if ($this->action == 'index') {
            if (!is_null($entity->_matchingData['WorkflowModels']->filter)) {
                if (sizeof($entity->filters) > 0) {
                    $chosenSelectList = [];
                    foreach ($entity->filters as $key => $value) {
                        $chosenSelectList[] = $value;
                    }
                    return implode(', ', $chosenSelectList);
                }
            }

            return '<i class="fa fa-minus"></i>';
        }
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query) {
        $paramsPass = $this->ControllerAction->paramsPass();
        if (!empty($paramsPass)) {
            $workflowId = $this->paramsDecode($paramsPass[0]);
            $selectedModel = $this->get($workflowId)->workflow_model_id;
            $this->addAssociation($selectedModel);



            $query->matching('WorkflowModels');

            if (!is_null($selectedModel)) {
                $filter = $this->WorkflowModels->get($selectedModel)->filter;
                if (!is_null($filter)) {
                    $query->contain(['Filters']);
                }
            }
        }
    }

    public function viewAfterAction(EventInterface $event, Entity $entity) {
        $this->setupFields($entity);
    }

    public function addOnInitialize(EventInterface $event, Entity $entity) {
        // always reset
        $request = $this->request;
        $queryParams = $request->getQuery();
        unset($queryParams['model']);
        $request = $request->withQueryParams($queryParams);

    }

    public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
        if (isset($data[$this->getAlias()])) {
            if (array_key_exists('workflow_model_id', $data[$this->getAlias()])) {
                $selectedModel = $data[$this->getAlias()]['workflow_model_id'];
                $this->addAssociation($selectedModel);
            }
        }
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity) {
        $this->setupFields($entity);
    }

    public function deleteOnInitialize(EventInterface $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        list($isEditable, $isDeletable) = array_values($this->checkIfCanEditOrDelete($entity));

        if (!$isDeletable) {
            $session = $this->request->getSession();
            $sessionKey = $this->getRegistryAlias() . '.warning';
            $session->write($sessionKey, $this->aliasField('restrictDelete'));

            $event->stopPropagation();
            return $this->controller->redirect($this->ControllerAction->url('index', false));
        }

        $query->where([
            $this->aliasField('workflow_model_id') => $entity->workflow_model_id
        ]);

        if ($query->count() == 1) {
            $this->Alert->warning('general.notTransferrable');
            $event->stopPropagation();
            return $this->controller->redirect($this->ControllerAction->url('index'));
        }

        $options['keyField'] = 'id';
        $options['valueField'] = 'code_name';

        // Convert Step Options
        $convertOptions = $this
            ->find('list')
            ->where([
                $this->aliasField('workflow_model_id') => $entity->workflow_model_id,
                $this->aliasField('id <>') => $entity->id
            ])
            ->toArray();

        $encodedTransferTo = !is_null($this->request->getQuery('workflow')) ? $this->request->getQuery('workflow') : $this->ControllerAction->paramsEncode(['id' => key($convertOptions)]);
        $entity->transfer_to = $encodedTransferTo;
        $transferTo = $this->ControllerAction->paramsDecode($encodedTransferTo);

        $convertStepOptions = $this->WorkflowSteps
            ->find('list')
            ->where([
                $this->WorkflowSteps->aliasField('workflow_id') => $transferTo['id']
            ])
            ->toArray();
        // End

        // Steps
        $where = [
            $this->WorkflowSteps->aliasField('workflow_id') => $entity->id
        ];

        $steps = $this->WorkflowSteps
            ->find()
            ->where($where)
            ->all();

        $stepIds = $this->WorkflowSteps
            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->where($where)
            ->toArray();
        // End

        // Apply To
        $tableHeaders = [__('Feature'), __('No of records')];
        $tableCells = [];

        // WorkflowsFilters
        $rowData = [];
        $rowData[] = $this->WorkflowsFilters->getAlias();
        $rowData[] = $this->WorkflowsFilters->find()->where([$this->WorkflowsFilters->aliasField('workflow_id') => $entity->id])->count();
        $tableCells[] = $rowData;

        // Staff Leaves / Institution Surveys & Institution Student Surveys
        $registryAlias = $this->WorkflowModels->get($entity->workflow_model_id)->model;

        $featureList = [];
        $featureList[] = $registryAlias;
        if ($registryAlias == 'Institution.InstitutionSurveys') {
            $featureList[] = 'Student.StudentSurveys';
            $featureList[] = 'Staff.StaffSurveys';
        }

        foreach ($featureList as $key => $feature) {
            $rowData = [];
            $targetModel = TableRegistry::getTableLocator()->get($feature);
            $rowData[] = $targetModel->getAlias();
            $rowData[] = $targetModel
                ->find()
                ->where([
                    $targetModel->aliasField('status_id IN') => $stepIds
                ])
                ->count();
            $tableCells[] = $rowData;
        }
        // End

        $this->controller->set(compact('steps', 'convertStepOptions', 'tableHeaders', 'tableCells'));
    }

    public function onBeforeDelete(EventInterface $event, ArrayObject $options, $ids) {
        $requestData = $this->request->getData();
        $submit = isset($requestData['submit']) ? $requestData['submit'] : 'save';

        if ($submit == 'save') {
            $process = function($model, $ids, $options) {
                $entity = $model->get($ids);
                // Overwrite $process and skip delete, delete is done in onDeleteTransfer
                return true;
            };

            return $process;
        } else {
            $url = $this->ControllerAction->url('remove');
            $url['workflow'] = $requestData['transfer_to'];
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }

    public function onDeleteTransfer(EventInterface $event, ArrayObject $options, $id) {
        $transferProcess = function($associations, $transferFrom, $transferTo, $model) {
            $conn = ConnectionManager::get('default');
            $conn->begin();

            $requestData = $this->request->getData();
            $entity = $model->get($transferFrom['id']);

            // Update workflow_id in workflows_filters
            $filterResults = [];
            if (!empty($transferTo['id'])) {
                $filterResults = $this->WorkflowsFilters
                    ->find()
                    ->where([
                        $this->WorkflowsFilters->aliasField('workflow_id') => $transferTo['id'],
                        $this->WorkflowsFilters->aliasField('filter_id') => 0
                    ])
                    ->all();
            }


            if (empty($filterResults)) {
                $this->WorkflowsFilters->updateAll(
                    ['workflow_id' => $transferTo['id']],
                    ['workflow_id' => $transferFrom['id']]
                );
            } else {
                $this->WorkflowsFilters->deleteAll([
                    'workflow_id' => $transferFrom['id']
                ]);
            }

            // End

            // Update workflow_step_id in workflow_records and model table
            $WorkflowTransitions = TableRegistry::getTableLocator()->get('Workflow.WorkflowTransitions');
            $registryAlias = $this->WorkflowModels->get($entity->workflow_model_id)->model;
            $targetModel = TableRegistry::getTableLocator()->get($registryAlias);
            foreach ($requestData[$this->getAlias()]['steps'] as $key => $stepObj) {
                $stepFrom = $stepObj['workflow_step_id'];
                $stepTo = $stepObj['convert_workflow_step_id'];
                $step = $this->WorkflowSteps->get($stepTo);

                $targetModel->updateAll(
                    ['status_id' => $stepTo],
                    ['status_id' => $stepFrom]
                );
            }
            // End

            // delete workflow
            if ($model->delete($entity)) {
                $conn->commit();
            } else {
                $conn->rollback();
            }
            // End
        };

        return $transferProcess;
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['remove'])) {
            // Check by model if filter applied, disabled delete button if the workflow is apply to all.
            $filter = $entity->_matchingData['WorkflowModels']->filter;
            if (!is_null($filter)) {
                $results = $this->WorkflowsFilters
                    ->find()
                    ->where([
                        $this->WorkflowsFilters->aliasField('workflow_id') => $entity->id,
                        $this->WorkflowsFilters->aliasField('filter_id') => 0
                    ])
                    ->all();

                if (!$results->isEmpty()) {
                    // unset($buttons['remove']);
                }
            }
        }

        return $buttons;
    }

    public function onUpdateFieldWorkflowModelId(EventInterface $event, array $attr, $action, $request) {
        if ($action == 'add') {
            $modelOptions = $this->getWorkflowModel();

            // Loop through modelOptions and unset it if the model do not have filter and already created workflow.
            foreach ($modelOptions as $key => $value) {
                $workflowModelEntity = $this->WorkflowModels->get($key);
                $filter = $workflowModelEntity->filter;
                $registryAlias = $workflowModelEntity->model;
                if (empty($filter)) {
                    if (!in_array($registryAlias, $this->excludedModels)) {
                        $workflowResults = $this
                            ->find()
                            ->where([
                                $this->aliasField('workflow_model_id') => $key
                            ])
                            ->all();

                        if (!$workflowResults->isEmpty()) {
                            unset($modelOptions[$key]);
                        }
                    }
                }
            }
            // End

            $modelOptions = ['' => __('-- Select Workflow --')] + $modelOptions;
            $selectedModel = !is_null($request->getQuery('model')) ? $request->getQuery('model') : key($modelOptions);
            $this->advancedSelectOptions($modelOptions, $selectedModel);

            $attr['options'] = $modelOptions;
            $attr['onChangeReload'] = 'changeModel';
        } else if ($action == 'edit') {
            $workflowModel = $attr['attr']['workflowModel'];

            $attr['type'] = 'readonly';
            $attr['value'] = $workflowModel->id;
            $attr['attr']['value'] = $workflowModel->name;
        }

        return $attr;
    }

    public function onUpdateFieldApplyToAll(EventInterface $event, array $attr, $action, $request) {
        if ($action == 'view') {
            $applyToAllOptions = $attr['options'];
            $attr['value'] = $applyToAllOptions[$attr['value']];
        }

        return $attr;
    }

    public function onUpdateFieldFilters(EventInterface $event, array $attr, $action, $request) {
        if ($action == 'view') {
            $workflowModel = $attr['attr']['workflowModel'];
            $filter = $workflowModel->filter;
            list($plugin, $modelAlias) = explode('.', $filter, 2);
            $labelText = Inflector::underscore(Inflector::singularize($modelAlias));

            $attr['attr']['label'] = __(Inflector::humanize($labelText));
        } else if ($action == 'add' || $action == 'edit') {
            $workflowModel = $attr['attr']['workflowModel'];
            $selectedModel = $workflowModel->id;
            $filter = $workflowModel->filter;
            $model = $workflowModel->model;

            list($plugin, $modelAlias) = explode('.', $filter, 2);
            $labelText = Inflector::underscore(Inflector::singularize($modelAlias));
            /*POCOR-5833 starts*/
            $LicenseTypes = TableRegistry::getTableLocator()->get('FieldOption.LicenseTypes');
            $paramsPass = $this->ControllerAction->paramsPass();
            if (!empty($paramsPass)) {
                $workflowId = $this->paramsDecode(current($paramsPass))['id'];
                //POCOR-7686:: Create if condition for Institution.StaffLeave
                if($attr['attr']['workflowModel']['model'] == "Institution.StaffLeave"){
                    $filterOptions = TableRegistry::getTableLocator()->get($filter)->getList()->toArray();
                }else{
                    $filterOptions = $LicenseTypes->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                                ->leftJoin([$this->WorkflowsFilters->getAlias() => $this->WorkflowsFilters->getTable()], [
                                    $this->WorkflowsFilters->aliasField('filter_id = ') . $LicenseTypes->aliasField('id'),
                                ])
                                ->where([$this->WorkflowsFilters->aliasField('workflow_id = ') => $workflowId])
                                ->toArray();
                }
                //END
            } else {
                $filterOptions = TableRegistry::getTableLocator()->get($filter)->getList()->toArray();
            }

            /*POCOR-5833 ends*/
            // Trigger event to get the correct wofkflow filter options
            $subject = TableRegistry::getTableLocator()->get($model);
            $newEvent = $subject->dispatchEvent('Workflow.getFilterOptions', null, $subject);
            if ($newEvent->isStopped()) { return $newEvent->getResult(); }
            if (!empty($newEvent->getResult())) {
                $filterOptions = $newEvent->getResult();
            }
            // End

            // Logic to remove filter from the list if already in used
            $Workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');

            $filterQuery = $this->WorkflowsFilters
                ->find('list', ['keyField' => 'filter_id', 'valueField' => 'filter_id'])
                ->matching('Workflows', function ($q) use ($Workflows, $selectedModel) {
                    return $q->where([
                            $Workflows->aliasField('workflow_model_id') => $selectedModel
                        ]);
                })
                ->where([
                    $this->WorkflowsFilters->aliasField('filter_id <> ') => 0
                ]);

            if ($action == 'edit') {
                $paramsPass = $this->request->getAttribute('params')['pass'][1];
                $workflowId = $this->paramsDecode($paramsPass)['id'];
                $filterQuery->where([
                    $this->WorkflowsFilters->aliasField('workflow_id <> ') => $workflowId
                ]);
            }
            $filterIds = $filterQuery->toArray();

            foreach ($filterOptions as $key => $value) {
                if (array_key_exists($key, $filterIds)) {
                    unset($filterOptions[$key]);
                }
            }
            // End

            $attr['placeholder'] = __('Select ') . __(Inflector::humanize($labelText));
            $attr['options'] = $filterOptions;
            $attr['attr']['label'] = __(Inflector::humanize($labelText));
        }

        return $attr;
    }

    private function getWorkflowModel() {
        return $this->WorkflowModels->find('list')
            ->order([ //POCOR-8033 readable
                $this->WorkflowModels->aliasField('name')
            ])->toArray();
    }

    private function setupFields(Entity $entity)
    {
        $this->ControllerAction->field('message', ['visible' => false]);

        $selectedModel = $entity->workflow_model_id;

        // for workflow that has filter:
        // If no workflow is added before, show apply_to_all = Yes
        // else show apply_to_all = No and Filters

        if (empty($selectedModel)) {
            $this->ControllerAction->field('workflow_model_id');
            $fieldOrder = ['workflow_model_id'];
        } else {
            $workflowModel = $this->WorkflowModels->get($selectedModel);
            $this->ControllerAction->field('workflow_model_id', [
                'attr' => ['workflowModel' => $workflowModel]
            ]);
            $fieldOrder = ['workflow_model_id'];

            $filter = $workflowModel->filter;
            if (!empty($filter)) {
                $showFilters = true;

                if (isset($entity->id)) {
                    // edit
                    $filterResults = $this->WorkflowsFilters
                        ->find()
                        ->where([
                            $this->WorkflowsFilters->aliasField('workflow_id') => $entity->id,
                            $this->WorkflowsFilters->aliasField('filter_id') => 0
                        ])
                        ->all();

                    if (!$filterResults->isEmpty()) {
                        $showFilters = false;
                    }
                } else {
                    // when add, check whether any workflow added before, if is not then hide Filters
                    $workflowResults = $this->find()
                        ->matching('WorkflowModels', function ($q) use ($selectedModel) {
                            return $q->where(['workflow_model_id' => $selectedModel]);
                        })
                        ->all();

                    if ($workflowResults->isEmpty()) {
                        $showFilters = false;
                    }
                }

                $applyToAllOptions = $this->getSelectOptions('general.yesno');
                $inputOptions = [
                    'type' => 'readonly',
                    'options' => $applyToAllOptions
                ];

                if ($showFilters) {
                    $inputOptions['value'] = self::NO;
                    $inputOptions['attr']['value'] = $applyToAllOptions[self::NO];

                    $this->ControllerAction->field('apply_to_all', $inputOptions);
                    $this->ControllerAction->field('filters', [
                        'type' => 'chosenSelect',
                        'attr' => ['workflowModel' => $workflowModel]
                    ]);
                    $fieldOrder[] = 'apply_to_all';
                    $fieldOrder[] = 'filters';
                } else {
                    $inputOptions['value'] = self::YES;
                    $inputOptions['attr']['value'] = $applyToAllOptions[self::YES];

                    $this->ControllerAction->field('apply_to_all', $inputOptions);
                    $fieldOrder[] = 'apply_to_all';
                }
            }
        }

        $fieldOrder[] = 'code';
        $fieldOrder[] = 'name';
        $this->ControllerAction->setFieldOrder($fieldOrder);
    }

    private function addAssociation($selectedModel=null) {
        if (!is_null($selectedModel) && !empty($selectedModel)) {
            $filter = $this->WorkflowModels->get($selectedModel)->filter;
            if (!is_null($filter)) {
                $this->filterClass['className'] = $filter;
                $this->belongsToMany('Filters', $this->filterClass);
            }
        }
    }

    private function setWorkflowActions($entity) {
        //echo "<pre>";print_r($entity);die;
        $stepOpen = null;
        $stepPending = null;
        $stepClosed = null;

        foreach ($entity->workflow_steps as $key => $step) {
            switch ($step->category) {
                case self::TO_DO:
                    $stepOpen = $step;
                    break;
                case self::IN_PROGRESS:
                    $stepPending = $step;
                    break;
                case self::DONE:
                    $stepClosed = $step;
                    break;
                default:
                    break;
            }
        }

        // Step - Open
        $dataOpen = [
            'id' => $stepOpen->id,
            'category' => $stepOpen->category,
            'is_editable' => $stepOpen->is_editable,
            'is_removable' => $stepOpen->is_removable,
            'is_system_defined' => $stepOpen->is_system_defined,
            'workflow_actions' => [
                [
                    'name' => __('Submit For Approval'),
                    'action' => self::APPROVE,
                    'visible' => 1,
                    'next_workflow_step_id' => $stepPending->id,
                    'comment_required' => 0,
                    'allow_by_assignee' => 1
                ],
                [
                    'name' => __('Cancel'),
                    'action' => self::REJECT,
                    'visible' => 1,
                    'next_workflow_step_id' => $stepClosed->id,
                    'comment_required' => 0,
                    'allow_by_assignee' => 1
                ]
            ]
        ];
        $entityOpen = $this->WorkflowSteps->newEntity($dataOpen);
        if ($this->WorkflowSteps->save($entityOpen)) {
        } else {
            $this->WorkflowSteps->log(print_r($entityOpen->getErrors(), true), 'debug');
        }
        // End

        // Step - Pending
        $dataPending = [
            'id' => $stepPending->id,
            'category' => $stepPending->category,
            'is_editable' => $stepPending->is_editable,
            'is_removable' => $stepPending->is_removable,
            'is_system_defined' => $stepPending->is_system_defined,
            'workflow_actions' => [
                [
                    'name' => __('Approve'),
                    'action' => self::APPROVE,
                    'visible' => 1,
                    'next_workflow_step_id' => $stepClosed->id,
                    'comment_required' => 0,
                    'allow_by_assignee' => 0
                ],
                [
                    'name' => __('Reject'),
                    'action' => self::REJECT,
                    'visible' => 1,
                    'next_workflow_step_id' => $stepOpen->id,
                    'comment_required' => 0,
                    'allow_by_assignee' => 0
                ]
            ]
        ];
        $entityPending = $this->WorkflowSteps->newEntity($dataPending);
        if ($this->WorkflowSteps->save($entityPending)) {
        } else {
            $this->WorkflowSteps->log(print_r($entityPending->getErrors(), true), 'debug');
        }
        // End

        // Step - Closed
        $dataClosed = [
            'id' => $stepClosed->id,
            'category' => $stepClosed->category,
            'is_editable' => $stepClosed->is_editable,
            'is_removable' => $stepClosed->is_removable,
            'is_system_defined' => $stepClosed->is_system_defined,
            'workflow_actions' => [
                [
                    'name' => __('Approve'),
                    'action' => self::APPROVE,
                    'visible' => 0,
                    'next_workflow_step_id' => 0,
                    'comment_required' => 0,
                    'allow_by_assignee' => 0
                ],
                [
                    'name' => __('Reject'),
                    'action' => self::REJECT,
                    'visible' => 0,
                    'next_workflow_step_id' => 0,
                    'comment_required' => 0,
                    'allow_by_assignee' => 0
                ],
                [
                    'name' => __('Reopen'),
                    'action' => null,
                    'visible' => 1,
                    'next_workflow_step_id' => $stepOpen->id,
                    'comment_required' => 0,
                    'allow_by_assignee' => 0
                ]
            ]
        ];
        $entityClosed = $this->WorkflowSteps->newEntity($dataClosed);
        if ($this->WorkflowSteps->save($entityClosed)) {
        } else {
            $this->WorkflowSteps->log(print_r($entityClosed->getErrors(), true), 'debug');
        }
        // End
    }

    private function setApplyToAll($entity) {
        if (isset($entity->apply_to_all) && $entity->apply_to_all == self::YES) {
            $workflowIds = $this
                ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                ->where([
                    $this->aliasField('workflow_model_id') => $entity->workflow_model_id
                ])
                ->toArray();

            $this->WorkflowsFilters->deleteAll([
                'OR' => [
                    [
                        $this->WorkflowsFilters->aliasField('workflow_id IN') => $workflowIds,
                        $this->WorkflowsFilters->aliasField('filter_id') => 0
                    ],
                    $this->WorkflowsFilters->aliasField('workflow_id') => $entity->id
                ]
            ]);

            $filterData = [
                'workflow_id' => $entity->id,
                'filter_id' => 0
            ];
            $filterEntity = $this->WorkflowsFilters->newEntity($filterData);

            if ($this->WorkflowsFilters->save($filterEntity)) {
            } else {
                $this->WorkflowsFilters->log($filterEntity->errors(), 'debug');
            }
        }
    }

    private function resetWorkflowStepId($entity) {
        $selectedModel = $entity->workflow_model_id;
        $workflowModel = $this->WorkflowModels->get($selectedModel);

        $model = $workflowModel->model;
        $filter = $workflowModel->filter;

        if (!is_null($filter)) {
            $statusKey = 'status_id';
            list($filterPlugin, $filterAlias) = explode(".", $filter, 2);
            // e.g. staff_leave_type_id, survey_form_id
            $filterKey = Inflector::underscore(Inflector::singularize($filterAlias)) . '_id';

            // List of affected filter Ids (e.g. Staff Leave Type IDs, Survey Form IDs)
            $filterIds = [];
            // List of all Workflow Steps of the Workflows
            $stepIds = [];
            // First step of the Workflows or the Default Workflows (Apply To All)
            $openStepId = null;
            // IDs of affected records to patch
            $recordIds = [];

            $steps = $this->WorkflowSteps
                ->find()
                ->where([
                    $this->WorkflowSteps->aliasField('workflow_id') => $entity->id
                ])
                ->toArray();

            foreach ($steps as $key => $step) {
                $stepIds[$step->id] = $step->id;
                if ($step->category == self::TO_DO) {
                    $openStepId = $step->id;
                }
            }

            $subject = TableRegistry::getTableLocator()->get($model);

            if ($entity->has($statusKey) && $entity->has('filters')) {
                // When edit: If filterIds is clear, fall back to the first step of Default Workflows (Apply To All)
                if (empty($entity->filters) && !$entity->isNew()) {
                    $originalFilters = $entity->extractOriginal(['filters']);
                    foreach ($originalFilters['filters'] as $key => $obj) {
                        $filterIds[$obj->id] = $obj->id;
                    }

                    if (!empty($filterIds) && !empty($stepIds)) {
                        $recordIds = $subject
                        ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                        ->where([
                            $subject->aliasField($filterKey . ' IN ') => $filterIds,
                            $subject->aliasField($statusKey . ' IN ') => $stepIds
                        ])
                        ->toArray();
                    }

                    $Workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
                    $defaultWorkflowId = $this->WorkflowsFilters
                        ->find('list', ['keyField' => 'workflow_id', 'valueField' => 'workflow_id'])
                        ->matching('Workflows', function ($q) use ($Workflows, $selectedModel) {
                            return $q->where([
                                    $Workflows->aliasField('workflow_model_id') => $selectedModel
                                ]);
                        })
                        ->where([
                            $this->WorkflowsFilters->aliasField('filter_id') => 0
                        ])
                        ->toArray();

                    if (!empty($defaultWorkflowId)) {
                        $openStepId = $this->WorkflowSteps
                            ->find()
                            ->where([
                                $this->WorkflowSteps->aliasField('workflow_id IN ') => $defaultWorkflowId,
                                $this->WorkflowSteps->aliasField('category') => self::TO_DO
                            ])
                            ->first()
                            ->id;
                    }
                } else {
                    foreach ($entity->filters as $key => $obj) {
                        $filterIds[$obj->id] = $obj->id;
                    }

                    if (!empty($filterIds) && !empty($stepIds)) {
                        $recordIds = $subject
                            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                            ->where([
                                $subject->aliasField($filterKey . ' IN ') => $filterIds,
                                $subject->aliasField($statusKey . ' NOT IN ') => $stepIds
                            ])
                            ->toArray();
                    }
                }
            }

            if (!is_null($openStepId) && !empty($recordIds)) {
                $subject->updateAll(
                    [$statusKey => $openStepId],
                    ['id IN ' => $recordIds]
                );
            }
        }
    }

    private function checkIfCanEditOrDelete($entity) {
        $isEditable = true;
        $isDeletable = true;

        // Check by model if filter applied, not allow to delete if the workflow is apply to all.
        if ($entity->has('workflow_model_id')) {
            $filter = $this->WorkflowModels->get($entity->workflow_model_id)->filter;
            if (!is_null($filter)) {
                $results = $this->WorkflowsFilters
                    ->find()
                    ->where([
                        $this->WorkflowsFilters->aliasField('workflow_id') => $entity->id,
                        $this->WorkflowsFilters->aliasField('filter_id') => 0
                    ])
                    ->all();

                if (!$results->isEmpty()) {
                    $isDeletable = false;
                }
            }

        }

        return compact('isEditable', 'isDeletable');
    }

    public function getExcludedModels() {
        return $this->excludedModels;
    }
}
