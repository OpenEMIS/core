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
        'app.workflow_statuses_steps',
        'app.config_product_lists',
        'app.staff_training_applications',
        'app.institutions',
        'app.custom_modules',
        'app.custom_field_types',
        'app.training_sessions',
        'app.security_users',
        'app.workflows',
        'app.workflows_filters',
        'app.workflow_actions',
        'app.workflow_comments',
        'app.workflow_transitions',
        'app.workflow_steps_roles',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.survey_forms',
        'app.survey_rules',
        'app.institution_custom_fields',
        'app.institution_custom_field_values',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
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
