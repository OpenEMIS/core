<?php
// POCOR-9257: Feature test for WebhookQueue controller
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use App\Test\SystemFixturesTrait;

class WebhookControllerTest extends AppTestCase
{
    use SystemFixturesTrait;

    private $primaryKey = ['id' => 1];
    private $modelPlugin = false;
    private $modelAlias = 'WebhookQueue';

    public function __construct()
    {
        $this->fixtures[] = 'app.webhook_queue';
        parent::__construct();
    }

    public function testIndex()
    {
        $this->get('/Webhook/WebhookQueue');
        $this->assertResponseOk();
    }
}
