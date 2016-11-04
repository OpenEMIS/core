<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class StaffTrainingApplicationsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.academic_periods',
        'app.academic_period_levels',
        'app.config_items',
        'app.config_product_lists',
        'app.config_item_options',
        'app.labels',
        'app.security_users',
        'app.workflows',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.workflows_filters',
        'app.workflow_steps_roles',
        'app.workflow_actions',
        'app.workflow_comments',
        'app.workflow_transitions',
        'app.custom_modules',
        'app.custom_field_types',
        'app.institution_custom_field_values',
        'app.institution_custom_fields',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.survey_forms',
        'app.survey_rules',
        'app.institutions',
        'app.training_courses',
        'app.training_field_of_studies',
        'app.training_levels',
        'app.training_field_of_studies',
        'app.training_requirements',
        'app.training_mode_deliveries',
        'app.training_course_types',
        'app.training_sessions',
        'app.training_courses_target_populations',
        'app.staff_training_applications',
        'app.staff_statuses',
        'app.staff_position_titles',
        'app.institution_staff',
        'app.institution_positions',
    ];

    private $existingApplicationId = 1;

    public function setup()
    {
        parent::setUp();
        $this->setAuthSession();
        $this->session([
            'Institution' => [
                'Institutions' => [
                    'id' => 1
                ]
            ],
            'Staff' => [
                'Staff' => [
                    'id' => 3
                ]
            ]
        ]);

        $this->urlPrefix('/Institutions/StaffTrainingApplications/');
    }

    // public function testIndex()
    // {
    //     $testUrl = $this->url('index');

    //     $this->get($testUrl);
    //     $this->assertResponseCode(200);
    //     $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    // }

    // public function testSearchFound()
    // {
    //     $testUrl = $this->url('index');
    //     $data = [
    //         'Search' => [
    //             'searchField' => 'Basic'
    //         ]
    //     ];
    //     $this->postData($testUrl, $data);

    //     $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    // }

    // public function testSearchNotFound()
    // {
    //     $testUrl = $this->url('index');
    //     $data = [
    //         'Search' => [
    //             'searchField' => '@#!@!cantFindThis!@#!'
    //         ]
    //     ];
    //     $this->postData($testUrl, $data);

    //     $this->assertEquals(true, (count($this->viewVariable('data')) == 0));
    // }

    // public function testCreate()
    // {
    //     $courseCatalogUrl = '/Institutions/CourseCatalogue/index';
    //     $this->get($courseCatalogUrl);
    //     $this->assertResponseCode(200);

    //     $addUrl = $this->url('add', ['queryString' => 'eyJ0cmFpbmluZ19zZXNzaW9uX2lkIjoyLCI1YzNhMDliZjIyZTEyNDExYjZhZjQ4ZGZlMGI4NWMyZDlkMTE4MWNkMzkxZTA4OTU3NGM4Y2YzY2ExZTVlNGFkIjoiMjNjbDZmcG5paXZmaGZuZG5udHMxNWprYjQifQ.NzllODEzNjc4ZTIxNTlmYWI2YjYwNDBlMjk0MDAxNzM3ZGM1NDlhZjg5YWNkNWUxYTBlNDFhNjg0OTZiYjdkNA']);
    //     $this->get($addUrl);
    //     $this->assertResponseCode(302);

        // $lastInsertedRecord = $this->table->find()
        //     ->where([$this->table->aliasField('name') => $data[$alias]['name']])
        //     ->first();
        // $this->assertEquals(true, (!empty($lastInsertedRecord)));
    // }

    public function testDelete() {
        $testUrl = $this->url('remove/' . $this->existingApplicationId);

        $table = TableRegistry::get('Institution.StaffTrainingApplications');

        $exists = $table->exists([$table->primaryKey() => $this->existingApplicationId]);
        $this->assertTrue($exists);

        $data = [
            'id' => $this->existingApplicationId,
            '_method' => 'DELETE'
        ];
        $this->post($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $this->existingApplicationId]);
        $this->assertFalse($exists);
    }
}
