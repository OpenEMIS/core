<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class TrainingApplicationsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.academic_periods',
        'app.academic_period_levels',
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps'
    ];

    private $testingId = 2;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Trainings/Applications/');
    }

    public function testIndex()
    {
        $testUrl = $this->url('index');

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }
}
