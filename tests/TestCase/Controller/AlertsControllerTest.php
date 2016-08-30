<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class AlertsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.alert_logs',
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.sms_messages',
        'app.sms_responses',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps'
    ];

    public function testAlertQuestionIndex()
    {
        $this->get('/Alerts/Questions');
        $this->assertResponseCode(200);
    }

    public function testAlertResponseIndex()
    {
        $this->get('/Alerts/Responses');
        $this->assertResponseCode(200);
    }

    public function testAlertLogIndex()
    {
        $this->get('/Alerts/Logs');
        $this->assertResponseCode(200);
    }
}
