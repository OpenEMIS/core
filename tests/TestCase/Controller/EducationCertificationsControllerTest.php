<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class EducationCertificationsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.education_certifications'
    ];

    private $id = 1;
    private $table;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Educations/Certifications/');
        $this->table = TableRegistry::get('Education.EducationCertifications');
    }

    public function testIndex()
    {
        $testUrl = $this->url('index', ['setup' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound()
    {
        $testUrl = $this->url('index', ['setup' => 1]);
        $data = [
            'Search' => [
                'searchField' => 'cert'
            ]
        ];
        $this->postData($testUrl, $data);

        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFound()
    {
        $testUrl = $this->url('index', ['setup' => 1]);
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
        $testUrl = $this->url('add', ['setup' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias => [
                'id' => 4,
                'name' => 'EducationCertificationsControllerTest_testCreate',
                'order' => 4,
                'visible' => 1
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $this->table->find()
            ->where([$this->table->aliasField('name') => $data[$alias]['name']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testRead()
    {
        $testUrl = $this->url('view/'.$this->id, ['setup' => 1]);

        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->id));
    }

    public function testUpdate()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('edit/'.$this->id, ['setup' => 1]);

        // TODO: DO A GET FIRST
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias => [
                'name' => 'EducationCertificationsControllerTest_testUpdate',
                'visible' => 1
            ],
            'submit' => 'save'
        ];

        $this->postData($testUrl, $data);

        $entity = $this->table->get($this->id);
        $this->assertEquals($data[$alias]['name'], $entity->name);
    }
}
