<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class WorkflowsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflows',
        'app.workflows_filters',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.survey_forms'
    ];

    public function testWorkflowIndex()
    {
        $this->get('/Workflows/Workflows');
        $this->assertResponseCode(200);
    }
}
