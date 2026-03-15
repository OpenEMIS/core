<?php
// POCOR-9257: Merged Webhook controller - handles WebhookQueue and WebhookLogs actions
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\Utility\Inflector;

class WebhookController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
    }

    //POCOR-9257: Execute Laravel artisan command to process webhook queue
    public function processQueue()
    {
        $apiPath = ROOT . DS . 'api';
        $command = 'cd ' . escapeshellarg($apiPath) . ' && php artisan webhooks:process 2>&1';

        exec($command, $output, $returnVar);

        $outputText = implode("\n", $output);

        if ($returnVar === 0) {
            $this->Alert->success(__('Webhook queue processed successfully.'), ['type' => 'string', 'reset' => true]);
            Log::info('[Webhook] Queue processed via manual button. Output: ' . $outputText);
        } else {
            $this->Alert->error(__('Failed to process webhook queue. Command output: ') . $outputText, ['type' => 'string', 'reset' => true]);
            Log::error('[Webhook] Queue processing failed with exit code ' . $returnVar . '. Output: ' . $outputText);
        }

        return $this->redirect(['action' => 'WebhookQueue']);
    }

    //POCOR-9257: Display webhook delivery queue
    public function WebhookQueue()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'WebhookQueue']);
    }

    //POCOR-9257: Display webhook audit logs
    public function WebhookLogs()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'WebhookLogs']);
    }

    //POCOR-9257: Bulk delete selected webhook queue entries
    public function queueDeleteSelected()
    {
        $this->request->allowMethod(['post']);
        $ids = array_filter(array_map('intval', (array)$this->request->getData('selected_ids', [])));

        if (empty($ids)) {
            $this->Alert->warning(__('No records selected.'), ['type' => 'string', 'reset' => true]);
            return $this->redirect(['action' => 'WebhookQueue']);
        }

        $WebhookQueue = \Cake\ORM\TableRegistry::getTableLocator()->get('WebhookQueue');
        $count = $WebhookQueue->deleteAll(['WebhookQueue.id IN' => $ids]);

        if ($count > 0) {
            $this->Alert->success(__('{0} record(s) deleted.', $count), ['type' => 'string', 'reset' => true]);
        } else {
            $this->Alert->error(__('No records were deleted.'), ['type' => 'string', 'reset' => true]);
        }

        return $this->redirect(['action' => 'WebhookQueue']);
    }

    //POCOR-9257: Bulk delete selected webhook log entries
    public function logsDeleteSelected()
    {
        $this->request->allowMethod(['post']);
        $ids = array_filter(array_map('intval', (array)$this->request->getData('selected_ids', [])));

        if (empty($ids)) {
            $this->Alert->warning(__('No records selected.'), ['type' => 'string', 'reset' => true]);
            return $this->redirect(['action' => 'WebhookLogs']);
        }

        $WebhookLogs = \Cake\ORM\TableRegistry::getTableLocator()->get('WebhookLogs');
        $count = $WebhookLogs->deleteAll(['WebhookLogs.id IN' => $ids]);

        if ($count > 0) {
            $this->Alert->success(__('{0} record(s) deleted.', $count), ['type' => 'string', 'reset' => true]);
        } else {
            $this->Alert->error(__('No records were deleted.'), ['type' => 'string', 'reset' => true]);
        }

        return $this->redirect(['action' => 'WebhookLogs']);
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $action = $this->request->getParam('action');

        if ($action === 'WebhookLogs') {
            $header = __('Webhook') . ' - ' . __('Logs');
            $this->Navigation->addCrumb(__('Webhook'), ['plugin' => false, 'controller' => $this->getName(), 'action' => $action]);
            $this->Navigation->addCrumb(__('Logs'));
        } else {
            $header = __('Webhook') . ' - ' . __('Queue');
            $this->Navigation->addCrumb(__('Webhook'), ['plugin' => false, 'controller' => $this->getName(), 'action' => $action]);
            $this->Navigation->addCrumb(__('Queue'));
        }

        //POCOR-9257: Disable Security component POST validation for bulk delete actions
        if (in_array($action, ['queueDeleteSelected', 'logsDeleteSelected'])) {
            $this->Security->setConfig('validatePost', false);
        }

        $this->set('contentHeader', $header);
        $this->set('selectedAction', $action);
    }
}
