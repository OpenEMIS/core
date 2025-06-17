<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;
use App\Models\Webhooks;
use App\Models\WebhookEvents;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class WebhookRepository extends Controller
{
    protected $eventKey;

    public function __construct(array $eventKey = ['attendance_update']) {
        $this->eventKey = $eventKey;
    }

    public function getEventKeys() {
        return $this->eventKey;
    }

    const ACTIVE = 1;
    public function handleWebhookRequest($request)
    {
        try {
            $eventKey = $this->eventKey[0];
            $webhooks = Webhooks::query()
            ->join('webhook_events', 'webhooks.id', '=', 'webhook_events.webhook_id') // Replace 'webhook_events.webhook_id' with the correct foreign key
            ->where('webhook_events.event_key', trim($eventKey))
            ->where('webhooks.status', self::ACTIVE) // Assuming Webhook::ACTIVE is defined as a constant in your model
            ->get();


            foreach ($webhooks as $webhook) {
                // Build the command
                $cmd = base_path('artisan') . ' webhook:process ' . escapeshellarg($webhook->url) . ' ' . escapeshellarg($webhook->method) . ' ' . escapeshellarg($request);
                $logs = storage_path('logs/webhook.log') . ' & echo $!';
                $shellCmd = $cmd . ' >> ' . $logs;

                try {
                    // Execute the shell command
                    $pid = exec($shellCmd);
                    Log::info("Webhook triggered: {$webhook->url} with PID: $pid");
                } catch (\Exception $ex) {
                    Log::error('Exception when triggering webhook: ' . $ex->getMessage());
                }
            }
            return $webhooks;
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Students List Not Found');
        }
    }
}