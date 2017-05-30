<?php
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use Cake\ORM\TableRegistry;

class ExaminationGradingTypesControllerTest extends AppTestCase
{
	public $fixtures = [
        'app.examination_grading_types',
        'app.examination_grading_options',
        'app.config_items',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.config_items',
        'app.config_product_lists',
        'app.security_users',
        'app.labels',
    ];

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Examinations/GradingTypes/');
    }

    public function testIndex()
    {
        $testUrl = $this->url('index', ['parent' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }
}
