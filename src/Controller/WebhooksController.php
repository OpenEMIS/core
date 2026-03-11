<?php
// POCOR-9257: WebhooksQueue controller for Administration/Communications
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\Utility\Inflector;

class WebhooksController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function processQueue()
    {
        //POCOR-9257: Execute Laravel artisan command to process webhook queue
        $apiPath = ROOT . DS . 'api';
        $command = 'cd ' . escapeshellarg($apiPath) . ' && php artisan webhooks:process 2>&1';

        exec($command, $output, $returnVar);

        $outputText = implode("\n", $output);

        if ($returnVar === 0) {
            $this->Alert->success(__('Webhook queue processed successfully.'), ['type' => 'string', 'reset' => true]);
            Log::info('[Webhooks] Queue processed via manual button. Output: ' . $outputText);
        } else {
            $this->Alert->error(__('Failed to process webhook queue. Command output: ') . $outputText, ['type' => 'string', 'reset' => true]);
            Log::error('[Webhooks] Queue processing failed with exit code ' . $returnVar . '. Output: ' . $outputText);
        }

        return $this->redirect(['action' => 'Webhooks']);
    }

    public function Webhooks()
    {
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'WebhooksQueue']);
    }

    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $name = $this->name; // WebhooksQueue
        $action = $this->request->getParam('action'); // WebhooksQueue
        $actionName = __(Inflector::humanize($action));
        $header = $name . ' - ' . $actionName;
        $this->Navigation->addCrumb(__($name), ['plugin' => false, 'controller' => $this->getName(), 'action' => $action]);
        $this->Navigation->addCrumb($actionName);
        $this->set('contentHeader', $header);
        $this->set('selectedAction', $action);
    }
}
