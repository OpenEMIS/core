<?php
// POCOR-9257: Fixture for WebhooksQueue functional tests
namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class WebhooksQueueFixture extends TestFixture
{
    // Import schema directly from existing table; no records needed for index test
    public $import = ['table' => 'webhooks_queue'];
    public $records = [];
}
