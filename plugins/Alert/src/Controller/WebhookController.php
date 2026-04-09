<?php
// POCOR-9257: Merged Webhook controller - handles WebhookQueue and WebhookLogs actions
declare(strict_types=1);

namespace Alert\Controller;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Log\Log;

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
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.WebhookQueue']); //POCOR-9257: moved to Alert plugin
    }

    //POCOR-9257: Display webhook audit logs
    public function WebhookLogs()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Alert.WebhookLogs']); //POCOR-9257: moved to Alert plugin
    }


    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $action = $this->request->getParam('action');

        if ($action === 'WebhookLogs') {
            $header = __('Webhook') . ' - ' . __('Logs');
            $this->Navigation->addCrumb(__('Webhook'), ['plugin' => 'Alert', 'controller' => $this->getName(), 'action' => $action]); //POCOR-9257: plugin=Alert
            $this->Navigation->addCrumb(__('Logs'));
        } else {
            $header = __('Webhook') . ' - ' . __('Queue');
            $this->Navigation->addCrumb(__('Webhook'), ['plugin' => 'Alert', 'controller' => $this->getName(), 'action' => $action]); //POCOR-9257: plugin=Alert
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
