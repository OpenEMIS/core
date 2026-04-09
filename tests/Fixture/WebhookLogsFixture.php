<?php
// POCOR-9257: Fixture for WebhookLogs functional tests
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class WebhookLogsFixture extends TestFixture
{
    public $import = ['table' => 'webhook_logs'];
    public $records = [];
}
