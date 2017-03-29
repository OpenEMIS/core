<?php
namespace Alert\Model\Behavior;

use Cake\ORM\Behavior;

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
}
