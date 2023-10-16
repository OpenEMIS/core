<?php
namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Network\Request;
use ControllerAction\Model\Traits\SecurityTrait;

class SecurityBehavior extends Behavior {
	use SecurityTrait;
}