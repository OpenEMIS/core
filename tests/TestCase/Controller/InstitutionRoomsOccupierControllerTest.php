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
        $this->urlPrefix('/Institutions/Rooms/');
        $table = TableRegistry::get('Institution.InstitutionRooms');
    }

    public function testIndexOccupier()
    {
        $this->setInstitutionSession(2);
        $testUrl = $this->url('index', ['parent' => 13, 'parent_level' => 3]);

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFoundOccupier()
    {
        $this->setInstitutionSession(2);
        $testUrl = $this->url('index', ['parent' => 13, 'parent_level' => 3]);

        $data = [
            'Search' => [
                'searchField' => 'C'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFoundOccupier()
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
        $this->setInstitutionSession(2);
        $testUrl = $this->url('add', ['parent' => 13, 'parent_level' => 3]);

        $this->get($testUrl);
        $this->assertResponseCode(302);
    }

    public function testReadOccupier()
    {
        $this->setInstitutionSession(2);
        $testUrl = $this->url('view/'.$this->testingId, ['parent' => 13, 'parent_level' => 3]);

        $table = TableRegistry::get('Institution.InstitutionRooms');
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->testingId));
    }

    public function testUpdateOccupier() {
        $testUrl = $this->url('edit/'.$this->testingId, ['parent' => 13, 'parent_level' => 3]);

        $this->get($testUrl);
        $this->assertResponseCode(302);
    }

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
