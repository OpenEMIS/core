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

    /**
     * POCOR-9694 — manual retry for a single Laravel failed_jobs row.
     *
     * Replicates {{php artisan queue:retry <id>}} via DB transaction:
     * INSERTs the payload into {{jobs}}, DELETEs the {{failed_jobs}} row.
     * Cron-driven {{openemis-core:run}} picks the re-queued job up on the
     * next tick. Read-side ACL is the same as the FailedJobs page.
     */
    public function FailedJobsRetry($id = null)
    {
        $failedJobId = (int) $id;
        $table       = \Cake\ORM\TableRegistry::getTableLocator()->get('System.FailedJobs');
        $redirectUrl = ['plugin' => 'System', 'controller' => 'Systems', 'action' => 'FailedJobs'];

        if ($failedJobId <= 0) {
            $this->Alert->warning(__('Invalid failed-job id.'), ['type' => 'string', 'reset' => true]);
            return $this->redirect($redirectUrl);
        }

        try {
            $requeued = $table->requeue($failedJobId);
        } catch (\Throwable $e) {
            \Cake\Log\Log::warning('[POCOR-9694][FailedJobsRetry] ' . $e->getMessage());
            $this->Alert->error(__('Retry failed: ') . $e->getMessage(), ['type' => 'string', 'reset' => true]);
            return $this->redirect($redirectUrl);
        }

        if (!$requeued) {
            $this->Alert->warning(__('Failed job no longer exists — already retried?'), ['type' => 'string', 'reset' => true]);
            return $this->redirect($redirectUrl);
        }

        $this->Alert->ok(
            __('Task re-queued — next openemis-core:run will pick it up.'),
            ['type' => 'string', 'reset' => true]
        );
        return $this->redirect($redirectUrl);
    }

}
