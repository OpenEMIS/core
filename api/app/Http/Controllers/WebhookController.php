<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WebhookService;

class WebhookController extends Controller
{
    // The WebhookService instance is injected into the controller
    protected $webhookService;

    // Constructor to initialize the WebhookService dependency
    public function __construct(WebhookService $webhookService)
    {
        // Assign the injected WebhookService instance to the class property
        $this->webhookService = $webhookService;
    }

    /**
     * Handles incoming webhook requests.
     *
     * @param Request $request The HTTP request containing the webhook data.
     * @return Request The original request (can be customized as needed).
     */
    public function handleWebhookRequest(Request $request)
    {
        // Delegate the request handling to the WebhookService
        $data = $this->webhookService->handleWebhookRequest($request);

        // Return the request object (modify or return a response if needed)
        return $request;
    }
}
