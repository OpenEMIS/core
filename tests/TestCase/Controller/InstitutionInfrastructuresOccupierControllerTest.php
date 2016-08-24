<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionInfrastructuresOccupierControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.area_levels',
        'app.areas',
        'app.area_administrative_levels',
        'app.area_administratives',
        'app.institution_localities',
        'app.institution_types',
        'app.institution_ownerships',
        'app.institution_statuses',
        'app.institution_sectors',
        'app.institution_providers',
        'app.institution_genders',
        'app.institution_network_connectivities',
        'app.security_groups',
        'app.academic_period_levels',
        'app.academic_periods',
        'app.institutions',
        'app.shift_options',
        'app.institution_shifts',
        'app.room_statuses',
        'app.room_types',
        'app.infrastructure_conditions',
        'app.institution_rooms',
        'app.infrastructure_levels',
        'app.infrastructure_types',
        'app.infrastructure_ownerships',
        'app.institution_infrastructures'
    ];

    private $testingId = 1;
    private $table;

    public function setup()
    {
        $this->setInstitutionSession(2);
        parent::setUp();
        $this->urlPrefix('/Institutions/Infrastructures/');
        $table = TableRegistry::get('Institution.InstitutionInfrastructures');
    }

    public function testIndex()
    {
        $testUrl = $this->url('index', ['level' => 1, 'type' => 1]);
        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound()
    {
        $testUrl = $this->url('index', ['level' => 1, 'type' => 1]);

        $data = [
            'Search' => [
                'searchField' => 'land'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFound()
    {
        $testUrl = $this->url('index', ['level' => 1, 'type' => 1]);
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
        // occupier cant create infrastructure, it will redirect to index page and show warning message.
        // 302 is redirect response code.
        $testUrl = $this->url('add', ['level' => 1, 'type' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(302);
    }

    // public function testRead()
    // {
    //     // view/5?parent=4&parent_level=1&level=2&type=2
    //     // view/10?level=1&type=1
    //     $testUrl = $this->url('view/'.$this->testingId, ['level' => 1, 'type' => 1]);
    //     $this->get($testUrl);
    //     $this->assertResponseCode(200);
    //     $this->assertEquals(true, ($this->viewVariable('data')->id == $this->testingId));
    // }

    // public function testUpdate() {
    //     // http://localhost:8888/core/Institutions/Infrastructures/edit/1?level=1&type=1
    //     $testUrl = $this->url('edit/'. $this->testingId, ['level' => 1, 'type' => 1]);

    //     // TODO: DO A GET FIRST
    //     $this->get($testUrl);

    //     $this->assertResponseCode(200);

    //     $data = [
    //         'InstitutionInfrastructures' => [
    //             'id' => '1',
    //             'code' => 'ABS6653801',
    //             'name' => 'Parcel A1',
    //             'year_acquired' => '2000',
    //             'year_disposed' => null,
    //             'comment' => '',
    //             'size' => '10000',
    //             'parent_id' => null,
    //             'institution_id' => '1',
    //             'infrastructure_level_id' => '1',
    //             'infrastructure_type_id' => '1',
    //             'infrastructure_ownership_id' => '4',
    //             'infrastructure_condition_id' => '1'
    //         ],
    //         'submit' => 'save'
    //     ];
    //     $this->postData($testUrl, $data);

    //     $entity = $this->table->get($this->testingId);
    //     $this->assertEquals($data['InstitutionInfrastructures']['name'], $entity->name);
    // }

    // public function testDelete()
    // {
    //     $testUrl = $this->url('remove/'. $this->testingId, ['level' => 1, 'type' => 1]);

    //     // will check if the data exists, exists will be true
    //     $exists = $this->table->exists([$this->table->primaryKey() => $this->testingId]);
    //     $this->assertTrue($exists);

    //     $data = [
    //         'id' => $this->testingId,
            // '_method' => 'DELETE'
    //     ];
    //     $this->postData($testUrl, $data);

    //     // will check if the data exists, $exists will be false.
    //     $exists = $this->table->exists([$this->table->primaryKey() => $this->testingId]);
    //     $this->assertFalse($exists);
    // }
}
