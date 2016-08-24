<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionInfrastructuresOwnerControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.academic_periods',
        'app.institutions',
        'app.institution_shifts',
        'app.infrastructure_conditions',
        'app.infrastructure_levels',
        'app.infrastructure_types',
        'app.infrastructure_ownerships',
        'app.institution_infrastructures'
    ];

    private $testingId = 1;
    private $table;

    public function setup()
    {
        $this->setInstitutionSession(1);
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
        $this->setInstitutionSession(1);
        $testUrl = $this->url('add', ['level' => 1, 'type' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $table = TableRegistry::get('Institution.InstitutionInfrastructures');
        $data = [
            'InstitutionInfrastructures' => [
                'code' => 'ABS6653804',
                'name' => 'Parcel AA',
                'year_acquired' => '2000',
                'year_disposed' => null,
                'comment' => '',
                'size' => '10000',
                'parent_id' => null,
                'institution_id' => '1',
                'infrastructure_level_id' => '1',
                'infrastructure_type_id' => '1',
                'infrastructure_ownership_id' => '1',
                'infrastructure_condition_id' => '1'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $table->find()
            ->where([$table->aliasField('name') => $data['InstitutionInfrastructures']['name']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testRead()
    {
        $this->setInstitutionSession(1);
        $testUrl = $this->url('view/'.$this->testingId, ['level' => 1, 'type' => 1]);

        $table = TableRegistry::get('Institution.InstitutionInfrastructures');
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == 1));
    }

    public function testUpdate()
    {
        $testUrl = $this->url('edit/'. $this->testingId, ['level' => 1, 'type' => 1]);

        // TODO: DO A GET FIRST
        $table = TableRegistry::get('Institution.InstitutionInfrastructures');
        $this->get($testUrl);

        $this->assertResponseCode(200);

        $data = [
            'InstitutionInfrastructures' => [
                'id' => '1',
                'code' => 'ABS6653801',
                'name' => 'Parcel A1',
                'year_acquired' => '2000',
                'year_disposed' => null,
                'comment' => '',
                'size' => '10000',
                'parent_id' => null,
                'institution_id' => '1',
                'infrastructure_level_id' => '1',
                'infrastructure_type_id' => '1',
                'infrastructure_ownership_id' => '4',
                'infrastructure_condition_id' => '1'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $entity = $table->get($this->testingId);
        $this->assertEquals($data['InstitutionInfrastructures']['name'], $entity->name);
    }

    public function testDelete()
    {
        $this->setInstitutionSession(1);

        $testUrl = $this->url('remove/15', ['level' => 1, 'type' => 1]);

        $table = TableRegistry::get('Institution.InstitutionInfrastructures');

        // will check if the data exists, exists will be true
        $exists = $table->exists([$table->primaryKey() => 15]);
        $this->assertTrue($exists);

        $data = [
            'id' => 15,
            '_method' => 'DELETE'
        ];
        $this->postData($testUrl, $data);

        // will check if the data exists, $exists will be false.
        $exists = $table->exists([$table->primaryKey() => 15]);
        $this->assertFalse($exists);
    }
}
