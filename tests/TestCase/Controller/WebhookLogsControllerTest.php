<?php
// POCOR-9257: Feature test for WebhookLogs controller
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use App\Test\SystemFixturesTrait;

class WebhookLogsControllerTest extends AppTestCase
{
    use SystemFixturesTrait;

    private $primaryKey = ['id' => 1];
    private $modelPlugin = false;
    private $modelAlias = 'WebhookLogs';

    public function __construct()
    {
        $this->fixtures[] = 'app.webhook_logs';
        parent::__construct();
    }

    public function testIndex()
    {
        $this->get('/Webhook/WebhookLogs');
        $this->assertResponseOk();
    }
}
