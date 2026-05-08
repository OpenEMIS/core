<?php
namespace System\Controller;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use System\Controller\AppController;
use Cake\Http\ServerRequest;

class SystemsController extends AppController
{
	public function initialize(): void {
		parent::initialize();
    }

    public function beforeFilter(EventInterface $event) {
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

    public function SystemNotices() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.Notices']); }

    //POCOR-9396
    public function SystemProcesses() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.SystemProcesses']); }

    //POCOR-9694: Administration → Async Services group. Each action delegates
    //to the matching System.* Table class, which extends AsyncServicesAdminTable.
    public function AsyncServicesOverview() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.AsyncServicesOverview']); }
    public function FailedJobs()             { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.FailedJobs']); }
    public function StuckProcesses()         { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.StuckProcesses']); }
    public function WebhookFailures()        { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.WebhookFailures']); }
    public function QueueBacklog()           { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'System.QueueBacklog']); }

}
