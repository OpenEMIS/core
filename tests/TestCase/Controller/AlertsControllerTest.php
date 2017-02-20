<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class AlertsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.alert_logs',
        'app.config_items',
        'app.config_product_lists',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps'
    ];

    public function testAlertLogIndex()
    {
        $this->get('/Alerts/Logs');
        $this->assertResponseCode(200);
    }
}
