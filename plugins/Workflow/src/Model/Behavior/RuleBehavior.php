<?php
namespace Workflow\Model\Behavior;

use Cake\ORM\Behavior;

class RuleBehavior extends Behavior
{
    protected $rule;
    protected $_defaultConfig = [
        'feature' => '',
        'rule' => []
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $class = basename(str_replace('\\', '/', get_class($this)));
        $class = str_replace('Rule', '', $class);
        $class = str_replace('Behavior', '', $class);

        $this->_table->addRuleType($class, $this->config());
        $this->rule = $class;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $eventMap = [
            'WorkflowRule.SetupFields' => 'onWorkflowRuleSetupFields',
            'WorkflowRule.onGet'.$this->rule.'Rule' => 'onGet'.$this->rule.'Rule'
        ];

        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method)) {
                continue;
            }
            $events[$event] = $method;
        }
        return $events;
    }
}
