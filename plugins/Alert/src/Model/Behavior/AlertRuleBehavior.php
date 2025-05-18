<?php
namespace Alert\Model\Behavior;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class AlertRuleBehavior extends Behavior
{
    protected $alertRule;
    protected $_defaultConfig = [
        'feature' => '',
        'name' => '',
        'method' => '',
        'threshold' => [],
        'placeholder' => []
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $class = basename(str_replace('\\', '/', get_class($this)));
        $class = str_replace('AlertRule', '', $class);
        $class = str_replace('Behavior', '', $class);

        $this->_table->addAlertRuleType($class, $this->getConfig());
        $this->alertRule = $class;
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $eventMap = [
            'AlertRule.'.$this->alertRule.'.SetupFields' => 'on'.$this->alertRule.'SetupFields',
            'AlertRule.UpdateField.'.$this->alertRule.'.Threshold' => 'onUpdateField'.$this->alertRule.'Threshold',
            'AlertRule.onGet.'.$this->alertRule.'.Threshold' => 'onGet'.$this->alertRule.'Threshold'
        ];

        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method)) {
                continue;
            }
            $events[$event] = $method;
        }
        return $events;
    }

    protected function onAlertRuleSetupFields(Event $event, Entity $entity)
    {
        $model = $this->_table;
        $thresholdConfig = $this->getConfig('threshold');
        // logic to auto render fields based on setting in config threshold
        if (!empty($thresholdConfig)) {
            if ($model->action == 'view') {
                $model->extractThresholdValuesFromEntity($entity);
            }

            foreach ($thresholdConfig as $field => $attr) {
                if (isset($attr['type'])) {
                    $fieldType = $attr['type'];

                    if (in_array($fieldType, ['select', 'chosenSelect'])) {
                        $options = [];
                        if (isset($attr['options']) && !empty($attr['options'])) {
                            $options = $model->getSelectOptions($model->getAlias().".".$attr['options']);

                        } else if (isset($attr['lookupModel']) && !empty($attr['lookupModel'])) {
                            $modelTable = TableRegistry::get($attr['lookupModel']);
                            $options = $modelTable->getList()->toArray();
                        }
                        $attr['options'] = $options;
                    }
                }

                if (isset($attr['tooltip'])) {
                    $sprintf = $attr['tooltip']['sprintf'];
                    $message = $model->getMessage($model->getAlias().".".$entity->feature.'.'.$field, ['sprintf' => $sprintf]);

                    $label = $attr['tooltip']['label'];
                    $attr['attr']['label']['escape'] = false;
                    $attr['attr']['label']['class'] = 'tooltip-desc';
                    $attr['attr']['label']['text'] = $label . $this->tooltipMessage($message);
                }

                $model->field($field, $attr);
            }
        }
    }

    // for info tooltip
    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';

        return $tooltipMessage;
    }
}
