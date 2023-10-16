<?php
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use Cake\ORM\TableRegistry;

class InstitutionProvidersControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.institution_genders',
        'app.institution_providers',
        'app.institution_sectors',
        'app.config_product_lists',
        'app.institutions',
        'app.custom_modules',
        'app.custom_field_types',
        'app.institution_custom_field_values',
        'app.institution_custom_fields',
        'app.survey_forms',
        'app.survey_rules',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters'
    ];

    private $id = 1;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/FieldOptions/Providers/');

        $this->table = TableRegistry::get('Institution.Providers');
    }

    public function testIndex()
    {
        $testUrl = $this->url('index');

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound()
    {
        $testUrl = $this->url('index');

        $data = [
            'Search' => [
                'searchField' => 'Private'
            ]
        ];
        $this->postData($testUrl, $data);

        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFound()
    {
        $testUrl = $this->url('index');

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
        $alias = $this->table->alias();
        $testUrl = $this->url('add');

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias => [
                'name' => 'Test Provider',
                'institution_sector_id' => 1,
                'default' => 0,
                'international_code' => '',
                'national_code' => ''
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $this->table->find()
            ->where([$this->table->aliasField('name') => $data[$alias]['name']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));

        $this->assertResponseCode(302);
    }

    public function testCreateMissingFields()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('add');

        $data = [
            $alias => [
                'name' => '',
                'institution_sector_id' => NULL,
                'default' => 0,
                'international_code' => '',
                'national_code' => ''
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $postData = $this->viewVariable('data');
        $errors = $postData->errors();
        $this->assertEquals(true, (array_key_exists('name', $errors)));
        $this->assertEquals(true, (array_key_exists('institution_sector_id', $errors)));

    }

    public function testRead()
    {
        $testUrl = $this->url('view/'.$this->id);

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->id));
    }

    public function testUpdate()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('edit/'.$this->id);

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias => [
                'name' => 'Updated Provider',
                'institution_sector_id' => 2,
                'default' => 0,
                'international_code' => 'intcode',
                'national_code' => 'natcode'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $this->table->find()
            ->where([$this->table->aliasField('name') => $data[$alias]['name'],
                $this->table->aliasField('institution_sector_id') => $data[$alias]['institution_sector_id'],
                $this->table->aliasField('international_code') => $data[$alias]['international_code'],
                $this->table->aliasField('national_code') => $data[$alias]['national_code']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));

        $this->assertResponseCode(302);
    }

    public function testUpdateMissingFields()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('edit/'.$this->id);

        $data = [
            $alias => [
                'name' => '',
                'institution_sector_id' => NULL,
                'default' => 0,
                'international_code' => 'intcode',
                'national_code' => 'natcode'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $postData = $this->viewVariable('data');
        $errors = $postData->errors();
        $this->assertEquals(true, (array_key_exists('name', $errors)));
        $this->assertEquals(true, (array_key_exists('institution_sector_id', $errors)));
    }

    public function testDelete() {
        $deleteId = 3;
        $testUrl = $this->url('remove/'.$deleteId);

        $table = TableRegistry::get('Institution.Providers');

        $exists = $table->exists([$table->primaryKey() => $deleteId]);
        $this->assertTrue($exists);

        $data = [
            'id' => $deleteId,
            '_method' => 'DELETE'
        ];
        $this->postData($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $deleteId]);
        $this->assertFalse($exists);
    }
}