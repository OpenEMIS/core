<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class Institution1RoomsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.academic_period_levels',
        'app.academic_periods',
        'app.custom_modules',
        'app.custom_field_types',
        'app.custom_field_values',
        'app.institutions',
        'app.institution_rooms',
        'app.institution_shifts',
        'app.institution_infrastructures',
        'app.institution_custom_fields',
        'app.institution_custom_field_values',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.infrastructure_conditions',
        'app.infrastructure_levels',
        'app.infrastructure_custom_fields',
        'app.infrastructure_custom_forms',
        'app.infrastructure_custom_forms_fields',
        'app.infrastructure_custom_forms_filters',
        'app.room_types',
        'app.room_statuses',
        'app.room_custom_field_values',
        'app.survey_forms',
        'app.survey_rules'
    ];

    private $testingId = 5;
    private $table;

    public function setup()
    {
        parent::setUp();
        $this->setInstitutionSession(1);

        $this->urlPrefix('/Institutions/Rooms/');
        $table = TableRegistry::get('Institution.InstitutionRooms');
    }

// Test as an Owner
    public function testIndex()
    {
        $this->setInstitutionSession(1);
        $testUrl = $this->url('index', ['parent' => 13, 'parent_level' => 3]);

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound()
    {
        $this->setInstitutionSession(1);
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

    public function testCreate()
    {
        $this->setInstitutionSession(1);
        $testUrl = $this->url('add', ['parent' => 13, 'parent_level' => 3]);

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $table = TableRegistry::get('Institution.InstitutionRooms');
        $data = [
            'InstitutionRooms' => [
                'code' => 'ABS6653802010104',
                'name' => 'Room 1-D',
                'start_date' => '2016-08-21',
                'start_year' => '2016',
                'end_date' => '2016-12-31',
                'end_year' => '2016',
                'room_status_id' => '1',
                'institution_infrastructure_id' => '13',
                'institution_id' => '1',
                'academic_period_id' => '25',
                'room_type_id' => '1',
                'infrastructure_condition_id' => '1',
                'previous_room_id' => '0'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $table->find()
            ->where([$table->aliasField('name') => $data['InstitutionRooms']['name']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testRead()
    {
        $this->setInstitutionSession(1);
        $testUrl = $this->url('view/'.$this->testingId, ['parent' => 13, 'parent_level' => 3]);

        $table = TableRegistry::get('Institution.InstitutionRooms');
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->testingId));
    }

    public function testUpdate()
    {
        $this->setInstitutionSession(1);
        $testUrl = $this->url('edit/'.$this->testingId, ['parent' => 13, 'parent_level' => 3]);

        // TODO: DO A GET FIRST
        $table = TableRegistry::get('Institution.InstitutionRooms');
        $this->get($testUrl);

        $this->assertResponseCode(200);

        $data = [
            'InstitutionRooms' => [
                'id' => '5',
                'code' => 'ABS6653802010101',
                'name' => 'Room 1-AAA',
                'start_date' => '2016-08-21',
                'start_year' => '2016',
                'end_date' => '2016-12-31',
                'end_year' => '2016',
                'room_status_id' => '1',
                'institution_infrastructure_id' => '13',
                'institution_id' => '1',
                'academic_period_id' => '25',
                'room_type_id' => '1',
                'infrastructure_condition_id' => '1',
                'previous_room_id' => '0',
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => '2',
                'created' => '2016-08-21 07:06:58'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $entity = $table->get($this->testingId);
        $this->assertEquals($data['InstitutionRooms']['name'], $entity->name);
    }

    public function testDelete() {
        $testUrl = $this->url('remove/'.$this->testingId, ['parent' => 13, 'parent_level' => 3]);

        $table = TableRegistry::get('Institution.InstitutionRooms');

        $exists = $table->exists([$table->primaryKey() => $this->testingId]);
        $this->assertTrue($exists);

        $data = [
            'id' => $this->testingId,
            '_method' => 'DELETE'
        ];
        $this->postData($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $this->testingId]);
        $this->assertFalse($exists);
    }


// // Test as an Occupier
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
                'searchField' => 'room'
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

    public function testDeleteOccupier() {
        $testUrl = $this->url('remove/'.$this->testingId, ['parent' => 13, 'parent_level' => 3]);
        $this->get($testUrl);
        $this->assertResponseCode(302);
    }
}
