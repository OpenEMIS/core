<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class WorkflowBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	// priority has to be set at 100 so that onUpdateToolbarButtons in model will be triggered first
    	$events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 100];
    	$events['ControllerAction.Model.view.afterAction'] = ['callable' => 'viewAfterAction', 'priority' => 101];
    	return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
    }

    public function viewAfterAction(Event $event, Entity $entity) {
    }
}
