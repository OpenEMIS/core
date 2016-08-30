<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class SurveysControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
    ];

    public function testSurveyQuestionIndex()
    {
        $this->setAuthSession();
        $this->get('/Surveys/Questions');
        $this->assertResponseCode(200);
    }
}
