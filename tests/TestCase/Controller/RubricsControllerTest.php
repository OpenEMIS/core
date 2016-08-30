<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class RubricsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.rubric_templates'
    ];

    public function testRubricIndex()
    {
        $this->get('/Rubrics/Templates');
        $this->assertResponseCode(200);
    }
}
