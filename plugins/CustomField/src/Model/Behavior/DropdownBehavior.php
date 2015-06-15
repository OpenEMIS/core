<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;

class DropdownBehavior extends Behavior {
	public function initialize(array $config) {
        if (isset($config['events'])) {
            $this->config('events', $config['events'], false);
        }

        $this->_table->ControllerAction->addField('options', [
            'type' => 'element',
            'order' => 5,
            'element' => 'CustomField.field_options',
            'visible' => true
        ]);
    }

    public function handleEvent(Event $event, Entity $entity) {
        $eventName = $event->name();
        $events = $this->_config['events'];
        //pr($events);die;
    }

    public function implementedEvents() {
        return array_fill_keys(array_keys($this->_config['events']), 'handleEvent');
    }

    public function addEditOnAddOption() {
        pr(88);die;
    }
}
