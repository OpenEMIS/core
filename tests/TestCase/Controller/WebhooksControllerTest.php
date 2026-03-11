<?php
// POCOR-9257: Feature test for WebhooksQueue controller
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use App\Test\SystemFixturesTrait;

class WebhooksControllerTest extends AppTestCase
{
    use SystemFixturesTrait;

    private $primaryKey = ['id' => 1];
    private $modelPlugin = false;
    private $modelAlias = 'WebhooksQueue';

    public function __construct()
    {
        $this->fixtures[] = 'app.webhooks_queue';
        parent::__construct();
    }

    public function testIndex()
    {
        $this->get('/Webhooks/Webhooks');
        $this->assertResponseOk();
    }
}
