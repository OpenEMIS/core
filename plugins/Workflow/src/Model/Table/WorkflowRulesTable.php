<?php
namespace Workflow\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Log\Log;
use Cake\Utility\Hash;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;

class WorkflowRulesTable extends ControllerActionTable
{
    use OptionsTrait;

    private $excludedModels = ['Cases.InstitutionCases'];
    private $ruleTypes = [];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Workflows', ['className' => 'Workflow.Workflows']);
        $this->hasMany('WorkflowRuleEvents', ['className' => 'Workflow.WorkflowRuleEvents', 'saveStrategy' => 'replace', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Workflow.RuleStaffBehaviours');
        $this->addBehavior('Workflow.RuleStudentAttendances');
        $this->addBehavior('Workflow.RuleStudentUnmarkedAttendances');
        // $this->addBehavior('Workflow.RuleStudentBehaviours');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $eventMap = [
            'WorkflowRule.SetupFields' => 'onWorkflowRuleSetupFields',
            'ControllerAction.Model.getSearchableFields' => 'getSearchableFields'
        ];

        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method)) {
                continue;
            }
            $events[$event] = $method;
        }
        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'workflow_id';
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        // enables all events to be deleted
        if (!$data->offsetExists('workflow_rule_events')) {
            $data->offsetSet('workflow_rule_events', []);
        }

        if (isset($data['submit']) && $data['submit'] == 'save') {
            if (isset($data['feature']) && !empty($data['feature'])) {
                $ruleConfig = $this->getRuleConfigByFeature($data['feature']);
                if (!empty($ruleConfig)) {
                    $where = [];
                    foreach ($ruleConfig as $key => $attr) {
                        $where[$key] = $data[$key];
                    }

                    $ruleArray = [];
                    $ruleArray['where'] = $where;
                    $data['rule'] = !empty($ruleArray) ? json_encode($ruleArray, JSON_UNESCAPED_UNICODE) : '';
                }
            }
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('rule_events');

        $featureOptions = $this->getFeatureOptions();
        $selectedFeature = !is_null($this->request->query('feature')) ? $this->request->query('feature') : key($featureOptions);
        $workflowOptions = $this->getWorkflowOptions($selectedFeature);
        if (empty($workflowOptions)) {
            $defaultWorkflow = '';
            $workflowOptions = [$defaultWorkflow => __('No Workflows')];
        } else {
            $defaultWorkflow = '-1';
            $workflowOptions = [$defaultWorkflow => __('All Workflows')] + $workflowOptions;
        }
        $selectedWorkflow = !is_null($this->request->query('workflow')) ? $this->request->query('workflow') : $defaultWorkflow;

        $extra['selectedFeature'] = $selectedFeature;
        $extra['selectedWorkflow'] = $selectedWorkflow;

        $extra['elements']['controls'] = [
            'name' => 'Workflow.WorkflowRules/controls',
            'data' => [
                'featureOptions' => $featureOptions,
                'selectedFeature' => $selectedFeature,
                'workflowOptions' => $workflowOptions,
                'selectedWorkflow' => $selectedWorkflow
            ],
            'options' => [],
            'order' => 1
        ];

        $this->setFieldOrder(['feature', 'workflow_id', 'rule', 'rule_events']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->matching('Workflows')
            ->contain('WorkflowRuleEvents');

        $searchKey = $this->getSearchKey();

        if (strlen($searchKey)) {
            $extra['OR'] = [
                $this->Workflows->aliasField('code').' LIKE' => '%' . $searchKey . '%',
                $this->Workflows->aliasField('name').' LIKE' => '%' . $searchKey . '%',
            ];
        }

        if ($extra->offsetExists('selectedFeature') && !empty($extra['selectedFeature'])) {
            $query->where([$this->aliasField('feature') => $extra['selectedFeature']]);
        }

        if ($extra->offsetExists('selectedWorkflow') && $extra['selectedWorkflow'] != '-1') {
            $query->where([$this->aliasField('workflow_id') => $extra['selectedWorkflow']]);
        }
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->extractRuleFromEntity($entity);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->matching('Workflows')
            ->contain('WorkflowRuleEvents');
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity, $extra);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity, $extra);
    }

    private function setupFields(Entity $entity, ArrayObject $extra)
    {
        $fieldOrder = ['feature', 'workflow_id', 'rule'];

        $this->field('feature', ['type' => 'select', 'entity' => $entity]);
        $this->field('workflow_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('rule', ['type' => 'hidden']);

        $event = $this->dispatchEvent('WorkflowRule.SetupFields', [$entity, $extra], $this);
        if ($event->isStopped()) {
            return $event->result;
        }

        $this->field('workflow_rule_events', [
            'type' => 'element',
            'element' => 'Workflow.WorkflowRules/events',
            'valueClass' => 'table-full-width',
            'attr' => [
                'entity' => $entity,
                'label' => __('Rule Events')
            ]
        ]);

        $this->setFieldOrder(['feature', 'workflow_id', 'rule']);
    }

    public function onWorkflowRuleSetupFields(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->has('feature') && !empty($entity->feature)) {
            $ruleConfig = $this->getRuleConfigByFeature($entity->feature);

            if ($this->action == 'view' && !empty($ruleConfig)) {
                $this->extractRuleFromEntity($entity);
            }

            foreach ($ruleConfig as $key => $attr) {
                if (array_key_exists('type', $attr) && $attr['type'] == 'select') {
                    $options = $this->getOptionsByConfig($attr);
                    $attr['options'] = $options;
                }

                $this->field($key, $attr);
            }
        }
    }

    public function getOptionsByConfig($attr)
    {
        $options = [];
        if (array_key_exists('options', $attr) && !empty($attr['options'])) {
            $options = $this->getSelectOptions($this->alias().".".$attr['options']);
        } else if (array_key_exists('lookupModel', $attr) && !empty($attr['lookupModel'])) {
            $modelTable = TableRegistry::get($attr['lookupModel']);
            $options = $modelTable->getList()->toArray();
        }

        return $options;
    }

    public function onGetFeature(Event $event, Entity $entity)
    {
        return Inflector::humanize(Inflector::underscore($entity->feature));
    }

    public function onGetWorkflowId(Event $event, Entity $entity)
    {
        if (isset($entity->_matchingData['Workflows'])) {
            return $entity->_matchingData['Workflows']->code_name;
        }
    }

    public function onGetRule(Event $event, Entity $entity)
    {
        // temporary solution
        $origEntity = $this->get($entity->id);
        if ($origEntity->has('feature') && !empty($origEntity->feature)) {
            $event = $this->dispatchEvent('WorkflowRule.onGet'.$origEntity->feature.'Rule', [$origEntity], $this);
            if ($event->isStopped()) {
                return $event->result;
            }
            if (!empty($event->result)) {
                return $event->result;
            }
        }
    }

    public function onGetRuleEvents(Event $event, Entity $entity)
    {
        $feature = $entity->getOriginal('feature');
        $eventOptions = $this->getEvents($feature);

        $events = $this->convertEventKeysToEvents($entity);
        $eventArray = [];
        foreach ($events as $key => $event) {
            $eventArray[] = $eventOptions[$event];
        }

        return implode(', ', $eventArray);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $featureOptions = $this->getFeatureOptions();

            $attr['options'] = $featureOptions;
            $attr['onChangeReload'] = 'changeFeature';
        } else if ($action == 'edit') {
            $entity = $attr['entity'];
            $featureOptions = $this->getFeatureOptions();

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->feature;
            $attr['attr']['value'] = $featureOptions[$entity->feature];
        }

        return $attr;
    }

    public function onUpdateFieldWorkflowId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $selectedFeature = $request->query('feature');
            $workflowOptions = $this->getWorkflowOptions($selectedFeature);

            $attr['options'] = $workflowOptions;
            $attr['onChangeReload'] = true;
        } else if ($action == 'edit') {
            $entity = $attr['entity'];
            $selectedFeature = $entity->feature;

            $workflowOptions = $this->getWorkflowOptions($selectedFeature);

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->workflow_id;
            $attr['attr']['value'] = $workflowOptions[$entity->workflow_id];
        }

        return $attr;
    }

    public function addOnChangeFeature(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['feature']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('feature', $request->data[$this->alias()])) {
                    $request->query['feature'] = $request->data[$this->alias()]['feature'];
                }
            }
        }
    }

    public function onUpdateFieldWorkflowRuleEvents(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $entity = $attr['attr']['entity'];
            $feature = $entity->feature;
            $eventOptions = $this->getEvents($feature, false);

            $tableHeaders = [];
            $tableHeaders[] = $this->getMessage('general.name');
            $tableHeaders[] = __('Description');

            $tableCells = [];
            $events = $this->convertEventKeysToEvents($entity);
            foreach ($events as $key => $event) {
                $tableCells[$key] = [];
                $tableCells[$key][] = $eventOptions[$event]['text'];
                $tableCells[$key][] = $eventOptions[$event]['description'];
            }

            $attr['attr']['tableHeaders'] = $tableHeaders;
            $attr['attr']['tableCells'] = $tableCells;
        } else if ($action == 'add' || $action == 'edit') {
            $entity = $attr['attr']['entity'];
            if ($action == 'add') {
                $feature = $request->query('feature');
            } else if ($action == 'edit') {
                $feature = $entity->feature;
            }

            $eventOptions = $this->getEvents($feature, false);
            $attr['attr']['eventOptions'] = $eventOptions;
            $eventSelectOptions = $this->getEvents($feature);

            $selectedEventKeys = [];
            if ($request->is(['get'])) {
                if ($action == 'edit') {
                    $selectedEventKeys = $this->convertEventKeysToEvents($entity);
                }
            } else if ($request->is(['post', 'put'])) {
                $requestData = $request->data;

                if (array_key_exists($this->alias(), $requestData)) {
                    if (array_key_exists('workflow_rule_events', $requestData[$this->alias()])) {
                        foreach ($requestData[$this->alias()]['workflow_rule_events'] as $event) {
                            $selectedEventKeys[] = $event['event_key'];
                        }
                    }
                }
            }

            foreach ($selectedEventKeys as $key => $value) {
                unset($eventSelectOptions[$value]);
            }

            $workflowId = $entity->workflow_id;
            $eventOptionsBySecurityRoles = $this->getAvailableEventOptionsBySecurityRoles($eventOptions, $workflowId);
            $eventSelectOptions = array_intersect_key($eventSelectOptions, $eventOptionsBySecurityRoles);

            $attr['attr']['eventSelectOptions'] = $eventSelectOptions;
        }

        return $attr;
    }

    public function addEditOnAddEvent(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('event_method_key', $data[$this->alias()])) {
                $methodKey = $data[$this->alias()]['event_method_key'];
                if (!empty($methodKey)) {
                    $data[$this->alias()]['workflow_rule_events'][] = [
                        'event_key' => $methodKey
                    ];
                }
                $data[$this->alias()]['event_method_key'] = '';
            }
        }
    }

    public function getRuleTypes()
    {
        return $this->ruleTypes;
    }

    public function addRuleType($newRuleType, $config = [])
    {
        if (empty($config)) {
            $this->ruleTypes[$newRuleType] = $newRuleType;
        } else {
            $this->ruleTypes[$newRuleType] = $config;
        }
    }

    public function getRuleConfigByFeature($selectedFeature)
    {
        $ruleTypes = $this->getRuleTypes();
        $ruleConfig = $ruleTypes[$selectedFeature]['rule'];

        return $ruleConfig;
    }

    public function getFeatureOptions()
    {
        $featureOptions = [];
        $ruleTypes = $this->getRuleTypes();
        foreach ($ruleTypes as $key => $config) {
            $feature = $config['feature'];
            $featureOptions[$feature] = __(Inflector::humanize(Inflector::underscore($feature)));
        }

        return $featureOptions;
    }

    public function getFeatureOptionsWithClassName()
    {
        $features = $this->getSelectOptions($this->aliasField('features'));
        $classNames = $this->array_column($features, 'className');
        
        return $classNames;
    }

    public function getWorkflowOptions($selectedFeature)
    {
        $workflowOptions = [];

        if (!empty($selectedFeature) && $selectedFeature != '-1') {
            $excludedModels = $this->excludedModels;
            $workflowResults = $this->Workflows
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->matching('WorkflowModels', function ($q) use ($excludedModels) {
                    return $q
                        ->where(['model IN ' => $excludedModels]);
                })
                ->all();

            if (!$workflowResults->isEmpty()) {
                $workflowOptions = $workflowResults->toArray();
            }
        }

        return $workflowOptions;
    }

    public function getFeatureByEntity(Entity $entity)
    {
        $model = TableRegistry::get($entity->source());
        $registryAlias = $model->registryAlias();
        $feature = $this->getFeatureByRegistryAlias($registryAlias);

        return $feature;
    }

    public function getFeatureByRegistryAlias($registryAlias)
    {
        $features = $this->getSelectOptions($this->aliasField('features'));
        $classNames = $this->array_column($features, 'className');
        $feature = array_search($registryAlias, $classNames);

        return $feature;
    }

    public function getRegistryAliasByFeature($featureName)
    {
        $registryAlias = false;
        $features = $this->getSelectOptions($this->aliasField('features'));
        if (array_key_exists($featureName, $features)) {
            if (array_key_exists('className', $features[$featureName])) {
                $registryAlias = $features[$featureName]['className'];
            }
        }
        return $registryAlias;
    }

    private function extractRuleFromEntity(Entity $entity)
    {
        $ruleArray = json_decode($entity->rule, true);

        if (array_key_exists('where', $ruleArray)) {
            $where = $ruleArray['where'];
            foreach ($where as $field => $value) {
                $entity->{$field} = $value;
            }
        }
    }

    private function convertEventKeysToEvents($entity)
    {
        $events = [];
        if ($entity->has('workflow_rule_events') && !empty($entity->workflow_rule_events)) {
            foreach ($entity->workflow_rule_events as $workflowRuleEvent) {
                $events[] = $workflowRuleEvent->event_key;
            }
        }

        return $events;
    }

    public function getEvents($feature = null, $listOnly = true)
    {
        $emptyOptions = [
            0 => [
                'value' => '',
                'text' => $this->getMessage('general.select.noOptions')
            ]
        ];

        // trigger Workflow.getEvents to retrieve the list of available events for the model
        if (empty($feature)) {
            return $emptyOptions;
        } else {
            $registryAlias = $this->getRegistryAliasByFeature($feature);
            $subject = TableRegistry::get($registryAlias);
            $eventsObject = new ArrayObject();
            $subjectEvent = $subject->dispatchEvent('Workflow.getRuleEvents', [$eventsObject], $subject);
            if ($subjectEvent->isStopped()) {
                return $subjectEvent->result;
            }

            $events = $eventsObject;
            if (!sizeof($events)) {
                return $emptyOptions;
            } else {
                $eventOptions = [];

                if ($listOnly) {
                    $eventOptions = [
                        0 => __('-- Select Event --')
                    ];
                    foreach ($events as $event) {
                        $eventOptions[$event['value']] = $event['text'];
                    }
                } else {
                    $eventOptions = [
                        0 => [
                            'value' => '',
                            'text' => __('-- Select Event --')
                        ]
                    ];
                    foreach ($events as $event) {
                        $eventOptions[$event['value']] = $event;
                    }
                }

                return $eventOptions;
            }
        }
    }

    private function getAvailableEventOptionsBySecurityRoles($eventOptions, $workflowId = null)
    {
        $availableOptions = [];
        if (array_key_exists(0, $eventOptions)) {
            $availableOptions[0] = $eventOptions[0];
        }

        $securityRoleList = $this->getFirstStepSecurityRoleCode($workflowId);

        if (!empty($securityRoleList)) {
            foreach ($eventOptions as $key => $eventObj) {
                if ($key === 0) {
                    continue;
                } 
                
                if (array_key_exists('roleCode', $eventObj)) {
                    $roleCode = $eventObj['roleCode'];

                    if (in_array($roleCode, $securityRoleList)) {
                        $availableOptions[$eventObj['value']] = $eventObj['text'];
                    }
                }
            }
        }
        
        return $availableOptions;
    }

    private function getFirstStepSecurityRoleCode($workflowId = null)
    {
        $securityRoleCode = [];

        if (!is_null($workflowId)) {
            $workflowStepObj = $this->getWorkflowFirstStep($workflowId);

            if (!is_null($workflowStepObj)) {
                $securityRoleList = $workflowStepObj['security_roles'];
                $securityRoleCode = Hash::extract($securityRoleList, '{n}.code');
            }
        }

        return $securityRoleCode;
    }

    public function getWorkflowFirstStep($workflowId, $hydrate = false)
    {
        $WorkflowStepsTable = $this->Workflows->WorkflowSteps;
        $workflowFirstStep = $WorkflowStepsTable
            ->find()
            ->matching('Workflows', function ($q) use ($workflowId) {
                return $q->where(['Workflows.id' => $workflowId]);
            })
            ->contain(['SecurityRoles'])
            ->where([
                $WorkflowStepsTable->aliasField('category') => WorkflowSteps::TO_DO
            ])
            ->hydrate($hydrate)
            ->first();

        return $workflowFirstStep;
    }
}
