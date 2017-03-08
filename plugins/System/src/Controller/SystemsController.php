<?php
namespace System\Controller;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use System\Controller\AppController;

class SystemsController extends AppController
{
	public function initialize() {
		parent::initialize();
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$name = $this->name;
		$action = $this->request->action;
		$actionName = __(Inflector::humanize($action));
		$header = $name .' - '.$actionName;
		$this->Navigation->addCrumb(__($name), ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $action]);
		$this->Navigation->addCrumb($actionName);
		$this->set('contentHeader', $header);
        $this->set('selectedAction', $this->request->action);
	}

    public function Updates() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.SystemUpdates']); }
}
