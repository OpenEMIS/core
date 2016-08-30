<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class WorkflowsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testWorkflowIndex()
    {
        $this->setAuthSession();
        $this->get('/Workflows/Workflows');
        $this->assertResponseCode(200);
    }
}
