<?php 
namespace App\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;

class RestrictAssoicatedDeleteBehavior extends Behavior {

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.onBeforeDelete'	=> ['callable' => 'onBeforeDelete', 'priority' => 20],
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onBeforeDelete(Event $event, ArrayObject $deleteOptions, $id) {
		if (isset($deleteOptions['url'])) {
			$urlParams = $deleteOptions['url'];
		} else {
			$urlParams = $this->_table->ControllerAction->url('index');
		}
		$totalCount = 0;
		foreach ($this->_table->associations() as $assoc) {
			if ($assoc->type() == 'oneToMany' || $assoc->type() == 'manyToMany') {
				$count = 0;
				if($assoc->type() == 'oneToMany') {
					$count = $assoc->find()
					->where([$assoc->aliasField($assoc->foreignKey()) => $id])
					->count();
					$totalCount = $totalCount + $count;
				} else {
					$modelAssociationTable = $assoc->junction();
					$count += $modelAssociationTable->find()
						->where([$modelAssociationTable->aliasField($assoc->foreignKey()) => $id])
						->count();
					$totalCount = $totalCount + $count;
				}
			}
		}
		if ($totalCount > 0) {
			$event->stopPropagation();
			$this->_table->Alert->error('general.deleteTransfer.restrictDelete');
			return $this->_table->controller->redirect($urlParams);
		}
	}
}
