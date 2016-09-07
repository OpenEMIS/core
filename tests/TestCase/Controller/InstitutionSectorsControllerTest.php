<?php
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use Cake\ORM\TableRegistry;

class InstitutionSectorsControllerTest extends AppTestCase
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
        'app.institution_sectors'
    ];

    private $id = 1;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/FieldOptions/Sectors/');

        $this->table = TableRegistry::get('Institution.Sectors');
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
                'searchField' => 'Government'
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
                'name' => 'Test Sector',
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

    public function testCreateMissingName()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('add');

        $data = [
            $alias => [
                'name' => '',
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
                'name' => 'Updated Sector',
                'default' => 0,
                'international_code' => 'intcode',
                'national_code' => 'natcode'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $this->table->find()
            ->where([$this->table->aliasField('name') => $data[$alias]['name'],
                $this->table->aliasField('international_code') => $data[$alias]['international_code'],
                $this->table->aliasField('national_code') => $data[$alias]['national_code']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));

        $this->assertResponseCode(302);
    }

    public function testUpdateMissingName()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('edit/'.$this->id);

        $data = [
            $alias => [
                'name' => '',
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
    }

    public function testDelete() {
        $deleteId = 4;
        $testUrl = $this->url('remove/'.$deleteId);

        $table = TableRegistry::get('Institution.Sectors');

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