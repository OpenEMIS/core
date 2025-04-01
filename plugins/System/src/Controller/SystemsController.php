<?php
namespace System\Controller;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use System\Controller\AppController;
use Cake\Http\ServerRequest;

class SystemsController extends AppController
{
	public function initialize(): void {
		parent::initialize();
    }

    public function beforeFilter(Event|\Cake\Event\EventInterface $event) {
		$request = $this->request;
    	parent::beforeFilter($event);

		$name = $this->name;
		$action  = $this->request->getParam('action');
		$actionName = __(Inflector::humanize($action));
		$header = $name .' - '.$actionName;
		$this->Navigation->addCrumb(__($name), ['plugin' => $this->getPlugin(), 'controller' => $this->getName(), 'action' => $action]);
		$this->Navigation->addCrumb($actionName);
		$this->set('contentHeader', $header);
        $this->set('selectedAction', $this->request->getParam('action'));
        if ($this->getPlugin() == 'System') {
        	//POCOR-7485 add this for removing blackhole error
            $this->Security->setConfig('validatePost', false);
        }
	}

    public function Updates() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.SystemUpdates']); }
    public function StaffPolicies() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.LeavePolicies']); }    // POCOR-8128 end
    public function StaffEntitlements() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.LeaveEntitlements']); }    // POCOR-8128 end
}
