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

	public function initialize(array $config)
	{
		parent::initialize($config);

		$class = basename(str_replace('\\', '/', get_class($this)));
		$class = str_replace('AlertRule', '', $class);
		$class = str_replace('Behavior', '', $class);

		$this->_table->addAlertRuleType($class, $this->config());
		$this->alertRule = $class;
	}

	public function implementedEvents()
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

	public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
    	if (isset($data['submit']) && $data['submit'] == 'save') {
    		if (isset($data['feature']) && !empty($data['feature'])) {
	    		$alertRuleTypes = $this->_table->getAlertRuleTypes();
	    		$thresholdConfig = $alertRuleTypes[$data['feature']]['threshold'];
	    		if (!empty($thresholdConfig)) {
	    			$thresholdArray = [];
		    		foreach ($thresholdConfig as $key => $attr) {
		    			$thresholdArray[$key] = $data[$key];
		    		}
		    		$data['threshold'] = !empty($thresholdArray) ? json_encode($thresholdArray, JSON_UNESCAPED_UNICODE) : '';
		    	}
	    	}
    	}
    }

	protected function onAlertRuleSetupFields(Event $event, Entity $entity)
	{
		$model = $this->_table;
		$thresholdConfig = $this->config('threshold');
		// logic to auto render fields based on setting in config threshold
		if (!empty($thresholdConfig)) {
			if ($model->action == 'view' || $model->action == 'edit') {
				$thresholdArray = json_decode($entity->threshold, true);
				foreach ($thresholdArray as $key => $value) {
					$entity->{$key} = $value;
				}
			}

			foreach ($thresholdConfig as $key => $attr) {
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

				$model->field($key, $attr);
			}
		}
	}
}
