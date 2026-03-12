<?php
namespace Alert\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\ORM\Table;

class AlertsController extends AppController
{
	public function initialize(): void {
		parent::initialize();

    }

    public function Alerts() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.Alerts']); }
    public function AlertRules() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.AlertRules']); }
    //POCOR-9509: Add Queue action to view alerts_queue
    public function Queue() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.AlertsQueue']); }
    public function Logs() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.AlertLogs']); }
    public function Notices() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.Notices']); }

    public function beforeFilter(Event|\Cake\Event\EventInterface $event) {
        if ($this->getPlugin() == $this->getPlugin()) {
            $this->Security->setConfig('validatePost', false);
        }
        parent::beforeFilter($event);
    }

	public function onInitialize(EventInterface $event, Table $model, ArrayObject $extra) {
		$header = __('Communications');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Communications', ['plugin' => 'Alert', 'controller' => 'Alerts', 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }

    /**
     * POCOR-9509: Bulk delete selected alert logs
     */
    public function logsDeleteSelected()
    {
        $this->request->allowMethod(['post']);
        $ids = $this->request->getData('selected_ids', []);

        if (empty($ids)) {
            $this->Flash->error(__('No records selected.'));
            return $this->redirect($this->referer());
        }

        $logsTable = $this->AlertLogs;
        $count = $logsTable->deleteAll(['id IN' => $ids]);

        if ($count) {
            $this->Flash->success(__('{0} record(s) deleted.', $count));
        } else {
            $this->Flash->error(__('Unable to delete selected records.'));
        }

        return $this->redirect($this->referer());
    }

    /**
     * POCOR-9509: Bulk delete selected queue entries
     */
    public function queueDeleteSelected()
    {
        $this->request->allowMethod(['post']);
        $ids = $this->request->getData('selected_ids', []);

        if (empty($ids)) {
            $this->Flash->error(__('No records selected.'));
            return $this->redirect($this->referer());
        }

        $queueTable = $this->AlertsQueue;
        $count = $queueTable->deleteAll(['id IN' => $ids]);

        if ($count) {
            $this->Flash->success(__('{0} record(s) deleted.', $count));
        } else {
            $this->Flash->error(__('Unable to delete selected records.'));
        }

        return $this->redirect($this->referer());
    }
}
