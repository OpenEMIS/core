<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class SurveysControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.custom_modules',
        'app.custom_field_types',
        'app.survey_questions',
        'app.survey_forms'
    ];

    public function testSurveyQuestionIndex()
    {
        $this->get('/Surveys/Questions');
        $this->assertResponseCode(200);
    }
}
