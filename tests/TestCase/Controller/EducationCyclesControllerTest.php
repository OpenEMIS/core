<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class EducationCyclesControllerTest extends AppTestCase
{
        public $fixtures = ['app.education_systems', 'app.education_levels', 'app.education_cycles'];

    private $testingId = 2;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Educations/Cycles/');
    }

    public function testIndex()
    {
        // http://localhost:8888/core/Educations/Cycles/index?level=5
        $testUrl = $this->url('index', ['level' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound()
    {
        $testUrl = $this->url('index', ['level' => 1]);
        $data = [
            'Search' => [
                'searchField' => 'education'
            ]
        ];
        $this->post($testUrl, $data);

        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFound()
    {
        $testUrl = $this->url('index', ['level' => 1]);
        $data = [
            'Search' => [
                'searchField' => '@#!@!cantFindThis!@#!'
            ]
        ];
        $this->post($testUrl, $data);

        $this->assertEquals(true, (count($this->viewVariable('data')) == 0));
    }

    public function testCreate()
    {
        $testUrl = $this->url('add', ['level' => 11]);

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $table = TableRegistry::get('EducationCycle.EducationCycles');
        $data = [
            'EducationCycles' => [
                'name' => 'EducationCyclesControllerTest_testCreate',
                'admission_age' => '6',
                'education_level_id' => '11',
            ],
            'submit' => 'save'
        ];
        $this->post($testUrl, $data);

        $lastInsertedRecord = $table->find()
            ->where([$table->aliasField('name') => $data['EducationCycles']['name']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testRead()
    {
        $testUrl = $this->url('view/'.$this->testingId, ['level' => 11]);

        $table = TableRegistry::get('EducationCycle.EducationCycles');
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->testingId));
    }

    public function testUpdate() {
        $testUrl = $this->url('edit/'.$this->testingId, ['level' => 11]);

        // TODO: DO A GET FIRST
        $table = TableRegistry::get('EducationCycle.EducationCycles');
        $this->get($testUrl);

        $this->assertResponseCode(200);

        $data = [
            'EducationCycles' => [
                'name' => 'EducationCyclesControllerTest_testUpdate',
                'admission_age' => '7',
                'education_level_id' => '12',
            ],
            'submit' => 'save'
        ];

        $this->post($testUrl, $data);

        $entity = $table->get($this->testingId);
        $this->assertEquals($data['EducationCycles']['name'], $entity->name);
    }

    public function testDelete() {
        $testUrl = $this->url('remove');

        $table = TableRegistry::get('EducationCycle.EducationCycles');

        $exists = $table->exists([$table->primaryKey() => $this->testingId]);
        $this->assertTrue($exists);

        $data = [
            'id' => $this->testingId,
            '_method' => 'DELETE'
        ];

        $this->post($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $this->testingId]);
        $this->assertFalse($exists);
    }
}
