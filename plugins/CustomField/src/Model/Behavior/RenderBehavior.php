<?php
namespace CustomField\Model\Behavior;

use Cake\Utility\Inflector;
use Cake\ORM\Behavior;

class RenderBehavior extends Behavior {
	protected $fieldTypeCode;
	protected $fieldType;

	public function initialize(array $config) {
        parent::initialize($config);

        $class = basename(str_replace('\\', '/', get_class($this)));
		$class = str_replace('Render', '', $class);
		$class = str_replace('Behavior', '', $class);

		$code = strtoupper(Inflector::underscore($class));
		$this->fieldTypeCode = $code;
		$this->fieldType = $class;
    }

    public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$eventMap = [
            'Render.'.'onSet'.$this->fieldType.'Values' => 'onSet'.$this->fieldType.'Values',
            'Render.'.'onSave' => 'onSave'
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
