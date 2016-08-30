<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class TrainingsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.workflow_models'
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
