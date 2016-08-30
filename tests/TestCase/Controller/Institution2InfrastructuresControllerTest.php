<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class Institution2InfrastructuresControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.academic_periods',
        'app.academic_period_levels',
        'app.custom_modules',
        'app.custom_field_types',
        'app.custom_field_values',
        'app.institutions',
        'app.institution_shifts',
        'app.institution_infrastructures',
        'app.institution_custom_fields',
        'app.institution_custom_field_values',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.infrastructure_conditions',
        'app.infrastructure_levels',
        'app.infrastructure_types',
        'app.infrastructure_ownerships',
        'app.infrastructure_custom_forms',
        'app.infrastructure_custom_forms_fields',
        'app.infrastructure_custom_forms_filters',
        'app.infrastructure_custom_fields',
        'app.infrastructure_custom_field_values',
        'app.survey_forms',
        'app.survey_rules'
    ];

    private $testingId = 1;
    private $table;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Institutions/Infrastructures/');
        $table = TableRegistry::get('Institution.InstitutionInfrastructures');
    }

// Test as an owner
    public function testIndexOwner()
    {
        $this->setInstitutionSession(1);
        $testUrl = $this->url('index', ['level' => 1, 'type' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFoundOwner()
    {
        $this->setInstitutionSession(1);
        $testUrl = $this->url('index', ['level' => 1, 'type' => 1]);

        $data = [
            'Search' => [
                'searchField' => 'land'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFoundOwner()
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

    public function testCreateOwner()
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

// the commented test is failed when run the whole testCase folder.
// if this test case run individually, everything works fine.
// error message is caused by customFieldValue.institution_infrastucture_id

    // public function testRead()
    // {
    //     $this->setInstitutionSession(1);
    //     $testUrl = $this->url('view/5?parent=4&parent_level=1&level=2&type=2');

    //     $this->get($testUrl);

    //     $this->assertResponseCode(200);
    //     $this->assertEquals(true, ($this->viewVariable('data')->id == 5));
    // }

    // public function testUpdate()
    // {
    //     $this->setInstitutionSession(1);
    //     $testUrl = $this->url('edit/'. $this->testingId, ['level' => 1, 'type' => 1]);

    //     // TODO: DO A GET FIRST
    //     $table = TableRegistry::get('Institution.InstitutionInfrastructures');
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

    //     $entity = $table->get($this->testingId);
    //     $this->assertEquals($data['InstitutionInfrastructures']['name'], $entity->name);
    // }

    // public function testDelete()
    // {
    //     $this->setInstitutionSession(1);

    //     $testUrl = $this->url('remove/15', ['level' => 1, 'type' => 1]);

    //     $table = TableRegistry::get('Institution.InstitutionInfrastructures');

    //     // will check if the data exists, exists will be true
    //     $exists = $table->exists([$table->primaryKey() => 15]);
    //     $this->assertTrue($exists);

    //     $data = [
    //         'id' => 15,
    //         '_method' => 'DELETE'
    //     ];
    //     $this->postData($testUrl, $data);

    //     // will check if the data exists, $exists will be false.
    //     $exists = $table->exists([$table->primaryKey() => 15]);
    //     $this->assertFalse($exists);
    // }

// Test case as an occupier
    public function testIndexOccupier()
    {
        $this->setInstitutionSession(2);
        $testUrl = $this->url('index', ['level' => 1, 'type' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFoundOccupier()
    {
        $this->setInstitutionSession(2);
        $testUrl = $this->url('index', ['level' => 1, 'type' => 1]);

        $data = [
            'Search' => [
                'searchField' => 'land'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFoundOccupier()
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

    public function testCreateOccupier()
    {
        $this->setInstitutionSession(2);
        $testUrl = $this->url('add', ['level' => 1, 'type' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(302);
    }

    // public function testReadOccupier()
    // {
    //     $this->setInstitutionSession(2);
    //     $testUrl = $this->url('view/1?parent=1&level=1&type=1');

    //     $this->get($testUrl);

    //     $this->assertResponseCode(200);
    //     $this->assertEquals(true, ($this->viewVariable('data')->id == $this->testingId));
    // }

    public function testUpdateOccupier() {
        $testUrl = $this->url('edit/'. $this->testingId, ['level' => 1, 'type' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(302);

    }

    public function testDeleteOccupier()
    {
        $testUrl = $this->url('remove/'. $this->testingId, ['level' => 1, 'type' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(302);
    }
}
