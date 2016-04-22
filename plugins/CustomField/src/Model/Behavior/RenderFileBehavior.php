<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use CustomField\Model\Behavior\RenderBehavior;

class RenderFileBehavior extends RenderBehavior {
	public function initialize(array $config) {
        parent::initialize($config);
    }

	public function onGetCustomFileElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        if ($action == 'view') {
        } else if ($action == 'edit') {
        }

        $event->stopPropagation();
        return $value;
    }

    public function processDateValues(Event $event, Entity $entity, ArrayObject $data, ArrayObject $settings) {
        $settings['valueKey'] = 'file';
        $this->processValues($entity, $data, $settings);
    }
}
