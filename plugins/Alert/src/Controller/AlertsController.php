<?php
namespace Alert\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class AlertsController extends AppController
{
	public function initialize(): void {
		parent::initialize();

    }

    public function Alerts() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.Alerts']); }
    public function AlertRules() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.AlertRules']); }
    //POCOR-9509: Add Queue action to view alert_queue
    public function Queue() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.AlertQueue']); }
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

        $logsTable = TableRegistry::getTableLocator()->get('Alert.AlertLogs');
//        dd($ids);
        $count = $logsTable->deleteAll(['id IN' => $ids]);

        if ($count) {
            $this->Alert->success(__('{0} record(s) deleted.', $count),  ['type' => 'string', 'reset' => true]);
        } else {
            $this->Alert->warning(__('Unable to delete selected records.'), ['type' => 'string', 'reset' => true]);
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
            $this->Alert->warning(__('No records selected.'), ['type' => 'string', 'reset' => true]);
            return $this->redirect($this->referer());
        }

        $queueTable = TableRegistry::getTableLocator()->get('Alert.AlertQueue');;
        $count = $queueTable->deleteAll(['id IN' => $ids]);

        if ($count) {
            $this->Alert->success(__('{0} record(s) deleted.', $count), ['type' => 'string', 'reset' => true]);
        } else {
            $this->Alert->warning(__('Unable to delete selected records.'), ['type' => 'string', 'reset' => true]);
        }

        return $this->redirect($this->referer());
    }

    public function processQueue()
    {
        //POCOR-9509: Execute Laravel artisan command to process webhook queue
        $apiPath = ROOT . DS . 'api';
        $command = 'cd ' . escapeshellarg($apiPath) . ' && php artisan alerts:process 2>&1';

        exec($command, $output, $returnVar);

        $outputText = implode("\n", $output);

        if ($returnVar === 0) {
            $this->Alert->success(__('Alert queue processed successfully.'), ['type' => 'string', 'reset' => true]);

        } else {
            $this->Alert->error(__('Failed to process alert queue. Command output: ') . $outputText, ['type' => 'string', 'reset' => true]);
            Log::error('[Alerts] Queue process failed with exit code ' . $returnVar . '. Output: ' . $outputText);
        }

        return $this->redirect(['action' => 'Logs']);
    }

    public function processLogs()
    {
        //POCOR-9509: Execute Laravel artisan command to process webhook queue
        $apiPath = ROOT . DS . 'api';
        $command = 'cd ' . escapeshellarg($apiPath) . ' && php artisan alerts:check-and-queue 2>&1';

        exec($command, $output, $returnVar);

        $outputText = implode("\n", $output);

        if ($returnVar === 0) {
            $this->Alert->success(__('Alert queue filled successfully.'), ['type' => 'string', 'reset' => true]);

        } else {
            $this->Alert->error(__('Failed to process alert queue. Command output: ') . $outputText, ['type' => 'string', 'reset' => true]);
            Log::error('[Alerts] Queue filling failed with exit code ' . $returnVar . '. Output: ' . $outputText);
        }

        return $this->redirect(['action' => 'Queue']);
    }
}
