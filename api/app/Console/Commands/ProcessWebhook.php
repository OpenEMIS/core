<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:process {url} {method} {body}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process a webhook';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $url = $this->argument('url');
        $method = $this->argument('method');
        $body = $this->argument('body');

        // Perform the webhook request
        $client = new \GuzzleHttp\Client();
        $options = ['body' => $body];

        try {
            $response = $client->request($method, $url, $options);
            $this->info("Webhook sent to $url with status: " . $response->getStatusCode());
        } catch (\Exception $e) {
            $this->error("Failed to send webhook: " . $e->getMessage());
        }
    }
}
