<?php
namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;

class YearBehavior extends Behavior {
	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		$config = $this->config();
		foreach ($config as $date => $year) {
			if ($entity->has($date) && !empty($entity->$date)) {
				$entity->$year = date('Y', strtotime($entity->$date));
			}
		}
	}
}
