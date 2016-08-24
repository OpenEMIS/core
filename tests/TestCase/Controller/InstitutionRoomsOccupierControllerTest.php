<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionRoomsOccupierControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.academic_period_levels',
        'app.academic_periods',
        'app.institutions',
        'app.institution_shifts',
        'app.infrastructure_conditions',
        'app.institution_rooms',
        'app.infrastructure_levels',
        'app.room_types',
        'app.room_statuses',
        'app.institution_infrastructures'
        ];


    private $testingId = 5;
    private $table;

    public function setup()
    {
        parent::setUp();
        $this->setInstitutionSession(2);
        $this->urlPrefix('/Institutions/Rooms/');
        $table = TableRegistry::get('Institution.InstitutionRooms');
    }

    public function testIndex()
    {
        $testUrl = $this->url('index', ['parent' => 13, 'parent_level' => 3]);

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound()
    {
        $testUrl = $this->url('index', ['parent' => 13, 'parent_level' => 3]);

        $data = [
            'Search' => [
                'searchField' => 'C'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFound()
    {
        $testUrl = $this->url('index', ['parent' => 13, 'parent_level' => 3]);

        $data = [
            'Search' => [
                'searchField' => '@#!@!cantFindThis!@#!'
            ]
        ];
        $this->postData($testUrl, $data);

        $this->assertEquals(true, (count($this->viewVariable('data')) == 0));
    }

    public function testCreateOccupier()
    {
        $testUrl = $this->url('add', ['parent' => 13, 'parent_level' => 3]);

        $this->get($testUrl);
        $this->assertResponseCode(302);
    }

    public function testRead()
    {
        $testUrl = $this->url('view/'.$this->testingId, ['parent' => 13, 'parent_level' => 3]);

        $table = TableRegistry::get('Institution.InstitutionRooms');
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->testingId));
    }

    // public function testUpdate() {
    //     $testUrl = $this->url('edit/'.$this->testingId, ['parent' => 13, 'parent_level' => 3]);

    //     // TODO: DO A GET FIRST
    //     $table = TableRegistry::get('Institution.InstitutionRooms');
    //     $this->get($testUrl);

    //     $this->assertResponseCode(200);

    //     $data = [
    //         'InstitutionRooms' => [
    //             'id' => '5',
    //             'code' => 'ABS6653802010101',
    //             'name' => 'Room 1-AAA',
    //             'start_date' => '2016-08-21',
    //             'start_year' => '2016',
    //             'end_date' => '2016-12-31',
    //             'end_year' => '2016',
    //             'room_status_id' => '1',
    //             'institution_infrastructure_id' => '13',
    //             'institution_id' => '1',
    //             'academic_period_id' => '25',
    //             'room_type_id' => '1',
    //             'infrastructure_condition_id' => '1',
    //             'previous_room_id' => '0',
    //             'modified_user_id' => null,
    //             'modified' => null,
    //             'created_user_id' => '2',
    //             'created' => '2016-08-21 07:06:58'
    //         ],
    //         'submit' => 'save'
    //     ];
    //     $this->postData($testUrl, $data);

    //     $entity = $table->get($this->testingId);
    //     $this->assertEquals($data['InstitutionRooms']['name'], $entity->name);
    // }

    // public function testDelete() {
    //     $testUrl = $this->url('remove/'.$this->testingId, ['parent' => 13, 'parent_level' => 3]);
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
