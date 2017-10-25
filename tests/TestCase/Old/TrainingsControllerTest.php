<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class TrainingsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.training_session_results',
        'app.workflows',
        'app.workflows_filters',
        'app.workflow_steps_roles',
        'app.workflow_steps',
        'app.workflow_actions',
        'app.workflow_comments',
        'app.workflow_transitions',
        'app.training_sessions',
        'app.training_courses',
        'app.training_providers',
        'app.training_field_of_studies',
        'app.training_course_types',
        'app.training_mode_deliveries',
        'app.training_requirements',
        'app.training_levels'
    ];

    public function testTrainingCourseIndex()
    {
        $this->get('/Trainings/Courses');
        $this->assertResponseCode(200);
    }

    public function testTrainingSessionIndex()
    {
        $this->get('/Trainings/Sessions');
        $this->assertResponseCode(200);
    }

    public function testTrainingResultIndex()
    {
        $this->get('/Trainings/Results');
        $this->assertResponseCode(200);
    }
}
