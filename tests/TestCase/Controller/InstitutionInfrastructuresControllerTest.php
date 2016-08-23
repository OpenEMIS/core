<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionInfrastructuresControllerTest extends AppTestCase
{
    public $fixtures = [
        // 'app.area_levels',
        // 'app.areas',
        // 'app.area_administrative_levels',
        // 'app.area_administratives',
        // 'app.institution_localities',
        // 'app.institution_types',
        // 'app.institution_ownerships',
        // 'app.institution_statuses',
        // 'app.institution_sectors',
        // 'app.institution_providers',
        // 'app.institution_genders',
        // 'app.institution_network_connectivities',
        // 'app.security_groups',
        // 'app.academic_period_levels',
        'app.academic_periods',
        'app.institutions',
        // 'app.shift_options',
        'app.institution_shifts',
        // 'app.room_statuses',
        // 'app.room_types',
        'app.infrastructure_conditions',
        // 'app.institution_rooms',
        'app.infrastructure_levels',
        'app.infrastructure_types',
        'app.infrastructure_ownerships',
        'app.institution_infrastructures'
    ];

    // private $testingId = 1;
    private $table;

    public function setup()
    {
        parent::setUp();
        $this->setInstitutionSession(2);
        $this->urlPrefix('/Institutions/Infrastructures/');
        $this->table = TableRegistry::get('Institution.InstitutionInfrastructures');
    }


    public function condition()
    {
        // from institution id in the session it will get the owner institution id
        // and get the conditions.
        $institutionId = $this->_session['Institution']['Institutions']['id'];
        $institutionShifts = TableRegistry::get('Institution.InstitutionShifts');

        $ownerInstitutionId = $institutionShifts->find()
            ->where([$institutionShifts->aliasField('location_institution_id') => $institutionId])
            ->toArray();

        foreach ($ownerInstitutionId as $key => $value) {
            $conditions ['OR'][$key] = [
                $this->table->aliasField('institution_id') => $value['institution_id']
            ];
        }

        return $conditions;
    }

    // public function testIndex()
    // {
    //     // if separate the index (owner and occupier), one of the test index will be failed.
    //     $testUrl = $this->url('index');

    //     $this->get($testUrl);
    //     $this->assertResponseCode(200);

    //     $query = $this->table->find()
    //         ->where($this->condition())
    //         ->toArray();

    //     $this->assertEquals(true, (count($query) >= 1));
    // }

    public function testSearchFound()
    {
        $testUrl = $this->url('index');

        $data = [
            'Search' => [
                'searchField' => 'land'
            ]
        ];
        $this->postData($testUrl, $data);
        $query = $this->table->find()
            ->where($this->condition())
            ->toArray();
            pr($query);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    // public function testSearchNotFound()
    // {
    //     // ndex?level=1&type=1
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
    //     $testUrl = $this->url('add');

    //     $this->get($testUrl);
    //     $this->assertResponseCode(200);

    //     $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');
    //     $data = [
    //         'AcademicPeriods' => [
    //             'academic_period_level_id' => 1,
    //             'code' => 'AcademicPeriodsControllerTest_testCreate',
    //             'name' => 'AcademicPeriodsControllerTest_testCreate',
    //             'start_date' => '08-06-2016',
    //             'end_date' => '09-06-2016',
    //             'current' => 1,
    //             'editable' => 1,
    //             'parent_id' => 1,
    //             'visible' => 1
    //         ],
    //         'submit' => 'save'
    //     ];
    //     $this->postData($testUrl, $data);

    //     $lastInsertedRecord = $table->find()
    //         ->where([$table->aliasField('name') => $data['AcademicPeriods']['name']])
    //         ->first();
    //     $this->assertEquals(true, (!empty($lastInsertedRecord)));
    // }

    // public function testRead()
    // {
    //     $testUrl = $this->url('view/'.$this->testingId, ['parent' => 1]);

    //     $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');
    //     $this->get($testUrl);

    //     $this->assertResponseCode(200);
    //     $this->assertEquals(true, ($this->viewVariable('data')->id == $this->testingId));
    // }

    // public function testUpdate() {
    //     $testUrl = $this->url('edit/'.$this->testingId);

    //     // TODO: DO A GET FIRST
    //     $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');
    //     $this->get($testUrl);

    //     $this->assertResponseCode(200);

    //     $data = [
    //         'AcademicPeriods' => [
    //             'parent' => '',
    //             'academic_period_level_id' => 1,
    //             'code' => 'TestEditCode',
    //             'name' => 'TestEditName',
    //             'id' => $this->testingId,
    //             'start_date' => '01-09-2015',
    //             'end_date' => '30-06-2016',
    //             'current' => 1,
    //             'editable' => 1,
    //             'parent_id' => 1,
    //             'visible' => 1
    //         ],
    //         'submit' => 'save'
    //     ];
    //     $this->postData($testUrl, $data);

    //     $entity = $table->get($this->testingId);
    //     $this->assertEquals($data['AcademicPeriods']['name'], $entity->name);
    // }

    // public function testDelete() {
    //     $testUrl = $this->url('remove');

    //     $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');

    //     $exists = $table->exists([$table->primaryKey() => $this->testingId]);
    //     $this->assertTrue($exists);

    //     $data = [
    //         'id' => $this->testingId,
    //         '_method' => 'DELETE'
    //     ];
    //     $this->postData($testUrl, $data);

    //     $exists = $table->exists([$table->primaryKey() => $this->testingId]);
    //     $this->assertFalse($exists);
    // }
}
