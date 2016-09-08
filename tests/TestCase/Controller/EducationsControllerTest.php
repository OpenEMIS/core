<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class EducationsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.education_systems',
        'app.education_levels'
    ];

    public function testEducationSystemIndex()
    {
        $this->setAuthSession();
        $this->get('/Educations/Systems');
        $this->assertResponseCode(200);
    }

    public function testAddEducationSystem()
    {
        $this->setAuthSession();

        $data = [
            'id' => 1,
            'name' => 'National Education System'
        ];
        $this->postData('/Educations/Systems/add', $data);

        $table = TableRegistry::get('Education.EducationSystems');
        $this->assertNotEmpty($table->get(1));
    }

    public function testViewEducationSystem()
    {
        $this->setAuthSession();

        $this->setAuthSession();
        $this->get('/Educations/Systems/view/1');
        $this->assertResponseCode(200);
    }

    public function testEditEducationSystem()
    {
        $this->setAuthSession();

        $data = [
            'name' => 'PHPUnit Education System'
        ];
        $this->postData('/Educations/Systems/edit/1', $data);
        $table = TableRegistry::get('Education.EducationSystems');
        $entity = $table->get(1);
        $this->assertEquals($data['name'], $entity->name);
    }

    public function testDeleteEducationSystem()
    {
        $this->setAuthSession();
        $this->get('/Educations/Systems/remove/1');
        $this->assertResponseCode(200);

        $data = [
            'id' => 1,
            'transfer_to' => 2,
            '_method' => 'DELETE'
        ];
        $this->postData('/Educations/Systems/remove/1', $data);
        $table = TableRegistry::get('Education.EducationSystems');
        $exists = $table->exists([$table->primaryKey() => 1]);
        $this->assertFalse($exists);
    }
}
