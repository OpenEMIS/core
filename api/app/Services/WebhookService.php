<?php

namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\WebhookRepository;
use JWTAuth;
use Illuminate\Support\Facades\Log;

class WebhookService extends Controller
{

    protected $webhookRepository;

    public function __construct(
        WebhookRepository $webhookRepository) {
        $this->webhookRepository = $webhookRepository;
    }

    
    public function handleWebhookRequest($request)
    {
        try {
            $data = $this->webhookRepository->handleWebhookRequest($request);
        } catch (\Exception $e) {
            Log::error(
                'Failed to fetch list from DB',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Institution Students List Not Found');
        }
    }


}
