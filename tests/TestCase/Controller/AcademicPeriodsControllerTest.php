<?php
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

// attempt to create extending classes and traits fail maybe because of link below
// https://getcomposer.org/doc/04-schema.md#autoload-dev
// use App\tests\TestCase\Controller\CoreTestCases;
// CoreTestCases

// extends IntegrationTestCase: "A test case class intended to make integration tests of your controllers easier... provides a number of helper methods and features that make dispatching requests and checking their responses simpler."
class AcademicPeriodsControllerTest extends IntegrationTestCase
{
    public $fixtures = ['app.academic_period_levels', 'app.academic_periods'];

    private $urlPrefix = '/AcademicPeriods/Periods/';
    private $testingId = 2;

    public function setup() 
    {
        parent::setUp();
        $this->setAuthSession();
    }

	public function setAuthSession() 
    {
        $this->session([
            'Auth' => [
                'User' => [
                    'id' => 2,
                    'username' => 'admin',
                    'super_admin' => '1'
                ]
            ]
        ]);
	}

	public function testIndex() 
    {
        $this->get($this->urlPrefix.'index?parent=1');
        $this->assertResponseCode(200);
    }

    public function testAdd() 
    {
        $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $data = [
            'AcademicPeriods' => [
                'academic_period_level_id' => 1,
                'code' => 'AcademicPeriodsControllerTest_testAdd',
                'name' => 'AcademicPeriodsControllerTest_testAdd',
                'start_date' => '08-06-2016',
                'end_date' => '09-06-2016',
                'current' => 1,
                'editable' => 1,
                'parent_id' => 1,
                'visible' => 1
            ],
            'submit' => 'save'
        ];

        $this->post($this->urlPrefix.'add?parent=1', $data);

        $lastInsertId = null;
        $lastInsertedRecord = $table->find()
            // ->select([$table->primaryKey()])
            ->where([$table->aliasField('name') => $data['AcademicPeriods']['name']])
            ->first();
        if (!empty($lastInsertedRecord)) {
            $lastInsertId = $lastInsertedRecord->id;
        }
        $this->assertNotEmpty($lastInsertId);
    }

    public function testView() 
    {
        $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $url = $this->urlPrefix.'view/'.$this->testingId.'?parent='.'1';
        $this->get($url);
        $this->assertResponseCode(200);
    }

    public function testEditEducationSystem() {
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

        $this->post($this->urlPrefix.'edit/'.$this->testingId, $data);

        $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $entity = $table->get($this->testingId);
        $this->assertEquals($data['AcademicPeriods']['name'], $entity->name);
    }

    public function testDeleteYear() {
        $data = [
            'id' => $this->testingId,
            '_method' => 'DELETE'
        ];

        $this->post($this->urlPrefix.'remove', $data);
        $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $exists = $table->exists([$table->primaryKey() => $this->testingId]);
        $this->assertTrue($exists);
    }

}