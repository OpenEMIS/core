<?php
// POCOR-9257: Fixture for WebhookQueue functional tests
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class WebhookQueueFixture extends TestFixture
{
    // Import schema directly from existing table; no records needed for index test
    public $import = ['table' => 'webhook_queue'];
    public $records = [];
}
