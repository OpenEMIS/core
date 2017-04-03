<?php
namespace Workflow\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class WorkflowRulesTable extends ControllerActionTable
{
    use OptionsTrait;

	private $ruleTypes = [];

	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->belongsTo('Workflows', ['className' => 'Workflow.Workflows']);

		$this->addBehavior('Workflow.RuleStaffBehaviours');
        $this->addBehavior('Workflow.RuleStudentBehaviours');
	}

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $eventMap = [
            'WorkflowRule.SetupFields' => 'onWorkflowRuleSetupFields'
        ];

        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method)) {
                continue;
            }
            $events[$event] = $method;
        }
        return $events;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {
            if (isset($data['feature']) && !empty($data['feature'])) {
                $ruleTypes = $this->getRuleTypes();
                $ruleConfig = $ruleTypes[$data['feature']]['rule'];
                if (!empty($ruleConfig)) {
                    $ruleArray = [];
                    foreach ($ruleConfig as $key => $attr) {
                        $ruleArray[$key] = $data[$key];
                    }
                    $data['rule'] = !empty($ruleArray) ? json_encode($ruleArray, JSON_UNESCAPED_UNICODE) : '';
                }
            }
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
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

        $this->setFieldOrder(['feature', 'workflow_id', 'rule']);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
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

        $this->field('feature', ['type' => 'select']);
        $this->field('workflow_id', ['type' => 'select']);
        $this->field('rule', ['type' => 'hidden']);

        $event = $this->dispatchEvent('WorkflowRule.SetupFields', [$entity, $extra], $this);
        if ($event->isStopped()) { return $event->result; }

        $this->setFieldOrder(['feature', 'workflow_id', 'rule']);
    }

    public function onWorkflowRuleSetupFields(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->has('feature') && !empty($entity->feature)) {
            $ruleTypes = $this->getRuleTypes();
            $ruleConfig = $ruleTypes[$entity->feature]['rule'];

            if (!empty($ruleConfig)) {
                if ($this->action == 'view' || $this->action == 'edit') {
                    $this->extractRuleFromEntity($entity);
                }
            }

            foreach ($ruleConfig as $key => $attr) {
                if (array_key_exists('type', $attr) && $attr['type'] == 'select') {
                    $options = [];
                    if (array_key_exists('options', $attr) && !empty($attr['options'])) {
                        $options = $model->getSelectOptions($model->aliasField($attr['options']));
                    } else if (array_key_exists('lookupModel', $attr) && !empty($attr['lookupModel'])) {
                        $modelTable = TableRegistry::get($attr['lookupModel']);
                        $options = $modelTable->getList()->toArray();
                    }
                    $attr['options'] = $options;
                }

                $this->field($key, $attr);
            }
        }
    }

    public function onGetFeature(Event $event, Entity $entity)
    {
        return Inflector::humanize(Inflector::underscore($entity->feature));
    }

    public function onGetRule(Event $event, Entity $entity)
    {
        // temporary solution
        $origEntity = $this->get($entity->id);
        if ($origEntity->has('feature') && !empty($origEntity->feature)) {
            $event = $this->dispatchEvent('WorkflowRule.onGet.'.$origEntity->feature.'.Rule', [$origEntity], $this);
            if ($event->isStopped()) { return $event->result; }
            if (!empty($event->result)) {
                return $event->result;
            }
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $featureOptions = $this->getFeatureOptions();

            $attr['options'] = $featureOptions;
            $attr['onChangeReload'] = 'changeFeature';
        }

        return $attr;
    }

    public function onUpdateFieldWorkflowId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $selectedFeature = $request->query('feature');
            $workflowOptions = $this->getWorkflowOptions($selectedFeature);

            $attr['options'] = $workflowOptions;
        }

        return $attr;
    }

    public function addEditOnChangeFeature(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
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

    public function getRuleTypes()
    {
        return $this->ruleTypes;
    }

    public function addRuleType($newRuleType, $config=[])
    {
    	if (empty($config)) {
			$this->ruleTypes[$newRuleType] = $newRuleType;
    	} else {
    		$this->ruleTypes[$newRuleType] = $config;
    	}
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

    public function getWorkflowOptions($selectedFeature)
    {
        $workflowOptions = [];

        if (!empty($selectedFeature) && $selectedFeature != '-1') {
            $features = $this->getSelectOptions($this->aliasField('features'));
            $registryAlias = $features[$selectedFeature]['className'];

            $workflowResults = $this->Workflows
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->matching('WorkflowModels', function ($q) use ($registryAlias) {
                    return $q->where(['model' => $registryAlias]);
                })
                ->all();

            if (!$workflowResults->isEmpty()) {
                $workflowOptions = $workflowResults->toArray();
            }
        }

        return $workflowOptions;
    }

    private function extractRuleFromEntity(Entity $entity)
    {
        $ruleArray = json_decode($entity->rule, true);
        foreach ($ruleArray as $key => $value) {
            $entity->{$key} = $value;
        }
    }
}
