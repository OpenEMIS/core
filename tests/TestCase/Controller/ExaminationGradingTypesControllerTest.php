<?php
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use Cake\ORM\TableRegistry;

class ExaminationGradingTypesControllerTest extends AppTestCase
{
	public $fixtures = [
        'app.examination_grading_types'
    ];

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Examinations/GradingTypes/');
    }

    // public function testIndex()
    // {
    //     $testUrl = $this->url('index', ['parent' => 1]);

    //     $this->get($testUrl);
    //     $this->assertResponseCode(200);
    //     $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    // }
}
