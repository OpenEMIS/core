<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
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
            'Render.'.'format'.$this->fieldType.'Entity' => 'format'.$this->fieldType.'Entity',
            'Render.'.'process'.$this->fieldType.'Values' => 'process'.$this->fieldType.'Values',
            'Render.'.'onSave' => 'onSave',
            'ControllerAction.Model.onUpdateIncludes' => 'onUpdateIncludes'
        ];

        foreach ($eventMap as $event => $method) {
            if (!method_exists($this, $method)) {
                continue;
            }
            $events[$event] = $method;
        }
		return $events;
	}

    protected function processValues(Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $fieldKey = $settings['fieldKey'];
        $valueKey = $settings['valueKey'];

        $customValue = $settings['customValue'];
        $fieldValues = $settings['fieldValues'];

        if (strlen($customValue[$valueKey]) == 0) {
            if (isset($entity->id)) {
                $settings['deleteFieldIds'][] = $customValue[$fieldKey];
            }
        } else {
            $fieldValues[] = $customValue;
        }
        $settings['fieldValues'] = $fieldValues;
    }
}
