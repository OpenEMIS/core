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
        //POCOR-9509: Send enqueued alerts from alert_queue
        $apiPath = ROOT . DS . 'api';

        //POCOR-9509: Drain Laravel queue first so any RunAlertJob rows produced by
        //recent attendance markings/etc. land into alert_queue before alerts:send runs.
        $drainOutput = $this->drainAlertJobsQueue($apiPath);

        $command = 'cd ' . escapeshellarg($apiPath) . ' && php artisan alerts:send 2>&1'; //POCOR-9509: renamed from alerts:process
        exec($command, $output, $returnVar);

        $outputText = implode("\n", array_merge($drainOutput, $output));

        if ($returnVar === 0) {
            $this->Alert->success(__('Alert queue processed successfully.'), ['type' => 'string', 'reset' => true]);

        } else {
            $this->Alert->error(__('Failed to process alert queue. Command output: ') . $outputText, ['type' => 'string', 'reset' => true]);
            Log::error('[Alerts] Queue process failed with exit code ' . $returnVar . '. Output: ' . $outputText);
        }

        return $this->redirect($this->referer()); //POCOR-9509: stay on whichever page the user clicked from
    }

    public function triggerAlerts()
    {
        //POCOR-9509: Combined one-click trigger — check alert rules then immediately send pending queue
        $apiPath = ROOT . DS . 'api';

        // Step 1: Check frequency and fill alert_queue for scheduled alert types
        $checkCommand = 'cd ' . escapeshellarg($apiPath) . ' && php artisan alerts:check --sync 2>&1';
        exec($checkCommand, $checkOutput, $checkReturn);

        //POCOR-9509: Drain Laravel queue between check (which enqueues RunAlertJob into jobs)
        //and send (which drains alert_queue) so the click flows end-to-end in one request.
        $drainOutput = $this->drainAlertJobsQueue($apiPath);

        // Step 2: Send everything pending in alert_queue (including event-based alerts already queued)
        $sendCommand = 'cd ' . escapeshellarg($apiPath) . ' && php artisan alerts:send 2>&1';
        exec($sendCommand, $sendOutput, $sendReturn);

        if ($checkReturn === 0 && $sendReturn === 0) {
            $this->Alert->success(__('Alerts triggered and sent successfully.'), ['type' => 'string', 'reset' => true]);
        } else {
            $errorText = implode("\n", array_merge($checkOutput, $drainOutput, $sendOutput));
            $this->Alert->error(__('Alert trigger failed. Output: ') . $errorText, ['type' => 'string', 'reset' => true]);
            Log::error('[Alerts] triggerAlerts failed. Output: ' . $errorText);
        }

        return $this->redirect($this->referer()); //POCOR-9509: stay on whichever page the user clicked from
    }

    public function processLogs()
    {
        //POCOR-9509: Check alert rules and fill alert_queue
        $apiPath = ROOT . DS . 'api';
        $command = 'cd ' . escapeshellarg($apiPath) . ' && php artisan alerts:check 2>&1'; //POCOR-9509: renamed from alerts:check-and-queue

        exec($command, $output, $returnVar);

        //POCOR-9509: Drain Laravel queue after alerts:check so RunAlertJob rows it produced
        //flow through into alert_queue in this same request — otherwise nothing visibly happens
        //until the systemd queue:work daemon picks them up.
        $drainOutput = $this->drainAlertJobsQueue($apiPath);

        $outputText = implode("\n", array_merge($output, $drainOutput));

        if ($returnVar === 0) {
            $this->Alert->success(__('Alert queue filled successfully.'), ['type' => 'string', 'reset' => true]);

        } else {
            $this->Alert->error(__('Failed to process alert queue. Command output: ') . $outputText, ['type' => 'string', 'reset' => true]);
            Log::error('[Alerts] Queue filling failed with exit code ' . $returnVar . '. Output: ' . $outputText);
        }

        return $this->redirect($this->referer()); //POCOR-9509: stay on whichever page the user clicked from
    }

    /**
     * POCOR-9509: Drain Laravel `alerts` queue once. Used by the three button handlers above
     * so user-initiated trigger/process/send buttons flow end-to-end without waiting for the
     * background `queue:work` daemon. Safe to call even when the daemon is also running —
     * Laravel claims jobs atomically via `reserved_at`, so two workers can't grab the same row.
     *
     * @param string $apiPath Absolute path to the Laravel api/ directory
     * @return array Captured stdout/stderr lines, returned for inclusion in user-visible output
     */
    private function drainAlertJobsQueue(string $apiPath): array
    {
        $cmd = 'cd ' . escapeshellarg($apiPath) . ' && php artisan queue:work --queue=alerts --once --stop-when-empty 2>&1';
        exec($cmd, $output, $returnVar);
        if ($returnVar !== 0) {
            Log::warning('[Alerts] queue:work drain returned non-zero exit code ' . $returnVar . '. Output: ' . implode("\n", $output));
        }
        return $output;
    }
}
