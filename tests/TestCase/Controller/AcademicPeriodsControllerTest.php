<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class AcademicPeriodsControllerTest extends AppTestCase
{
    public $fixtures = ['app.academic_period_levels', 'app.academic_periods'];

    private $testingId = 2;

    public function setup() 
    {
        parent::setUp();
        $this->urlPrefix('/AcademicPeriods/Periods/');
    }

    public function testIndex() 
    {
        $testUrl = $this->url('index', ['parent' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound() 
    {
        $testUrl = $this->url('index', ['parent' => 1]);
        $data = [
            'Search' => [
                'searchField' => '2015'
            ]
        ];
        $this->post($testUrl, $data);

        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFound() 
    {
        $testUrl = $this->url('index', ['parent' => 1]);
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
        $testUrl = $this->url('add', ['parent' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $data = [
            'AcademicPeriods' => [
                'academic_period_level_id' => 1,
                'code' => 'AcademicPeriodsControllerTest_testCreate',
                'name' => 'AcademicPeriodsControllerTest_testCreate',
                'start_date' => '08-06-2016',
                'end_date' => '09-06-2016',
                'current' => 1,
                'editable' => 1,
                'parent_id' => 1,
                'visible' => 1
            ],
            'submit' => 'save'
        ];
        $this->post($testUrl, $data);

        $lastInsertedRecord = $table->find()
            ->where([$table->aliasField('name') => $data['AcademicPeriods']['name']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testRead() 
    {
        $testUrl = $this->url('view/'.$this->testingId, ['parent' => 1]);

        $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->testingId));
    }

    public function testUpdate() {
        $testUrl = $this->url('edit/'.$this->testingId, ['parent' => 1]);

        // TODO: DO A GET FIRST
        $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->get($testUrl);

        $this->assertResponseCode(200);

        $data = [
            'AcademicPeriods' => [
                'parent' => '',
                'academic_period_level_id' => 1,
                'code' => 'TestEditCode',
                'name' => 'TestEditName',
                'id' => $this->testingId,
                'start_date' => '01-09-2015',
                'end_date' => '30-06-2016',
                'current' => 1,
                'editable' => 1,
                'parent_id' => 1,
                'visible' => 1
            ],
            'submit' => 'save'
        ];

        $this->post($testUrl, $data);

        $entity = $table->get($this->testingId);
        $this->assertEquals($data['AcademicPeriods']['name'], $entity->name);
    }

    public function testDelete() {
        $testUrl = $this->url('remove');

        $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');

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
