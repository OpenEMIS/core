<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class EducationLevelsControllerTest extends AppTestCase
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
        'app.education_level_isced',
        'app.education_levels'
    ];

    private $id = 1;
    private $table;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Educations/Levels/');
        $this->table = TableRegistry::get('Education.EducationLevels');
    }

    public function testIndex()
    {
        $testUrl = $this->url('index');

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) == 2));
    }

    public function testSearchFound()
    {
        $testUrl = $this->url('index');
        $data = [
            'Search' => [
                'searchField' => 'Primary'
            ]
        ];
        $this->postData($testUrl, $data);

        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    }

    public function testSearchNotFound()
    {
        $testUrl = $this->url('index');
        $data = [
            'Search' => [
                'searchField' => '@#!@!cantFindThis!@#!'
            ]
        ];
        $this->postData($testUrl, $data);

        $this->assertEquals(true, (count($this->viewVariable('data')) == 0));
    }

    public function testCreate()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('add');

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias => [
                'name' => 'Kindergarten',
                'education_system_id' => 1,
                'education_level_isced_id' => 1,
                'visible' => 1
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $this->table->find()
            ->where([$this->table->aliasField('name') => $data[$alias]['name']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testRead()
    {
        $testUrl = $this->url('view/'.$this->id);

        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->id));
    }

    public function testUpdate()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('edit/'.$this->id);

        // TODO: DO A GET FIRST
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias => [
                'id' => $this->id,
                'name' => 'Kindergarten (changed)',
                'education_system_id' => 1,
                'education_level_isced_id' => 1,
                'visible' => 0
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $entity = $this->table->get($this->id);
        $this->assertEquals($data[$alias]['visible'], $entity->visible);
    }

    // Need to implement for delete transfer
    // public function testDelete() {
    //     $testUrl = $this->url('remove');

    //     $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');

    //     $exists = $table->exists([$table->primaryKey() => $this->id]);
    //     $this->assertTrue($exists);

    //     $data = [
    //         'id' => $this->id,
    //         '_method' => 'DELETE'
    //     ];

    //     $this->post($testUrl, $data);

    //     $exists = $table->exists([$table->primaryKey() => $this->id]);
    //     $this->assertFalse($exists);
    // }
}
