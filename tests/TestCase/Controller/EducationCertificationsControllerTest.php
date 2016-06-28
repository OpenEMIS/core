<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

// http://localhost:8888/core/Educations/Certifications/add?setup=1

class EducationCertificationsControllerTest extends AppTestCase
{
    public $fixtures = ['app.education_certifications'];

    private $testingId = 2;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Educations/Certifications/');
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
                'searchField' => 'primary'
            ]
        ];
        $this->post($testUrl, $data);

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
        $this->post($testUrl, $data);

        $this->assertEquals(true, (count($this->viewVariable('data')) == 0));
    }

    public function testCreate()
    {
        $testUrl = $this->url('add', ['setup' => 1]);

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $table = TableRegistry::get('EducationCertification.EducationCertifications');
        $data = [
            'EducationCertifications' => [
                'id' => 4,
                'name' => 'EducationCertificationsControllerTest_testCreate',
                'order' => 4,
                'visible' => 1
            ],
            'submit' => 'save'
        ];
        $this->post($testUrl, $data);

        $lastInsertedRecord = $table->find()
            ->where([$table->aliasField('name') => $data['EducationCertifications']['name']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testRead()
    {
        $testUrl = $this->url('view/'.$this->testingId, ['setup' => 1]);

        $table = TableRegistry::get('EducationCertification.EducationCertifications');
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->testingId));
    }

    public function testUpdate() {
        $testUrl = $this->url('edit/'.$this->testingId, ['setup' => 1]);

        // TODO: DO A GET FIRST
        $table = TableRegistry::get('EducationCertification.EducationCertifications');
        $this->get($testUrl);

        $this->assertResponseCode(200);

        $data = [
            'EducationCertifications' => [
                'name' => 'EducationCertificationsControllerTest_testUpdate',
                'visible' => 1
            ],
            'submit' => 'save'
        ];

        $this->post($testUrl, $data);

        $entity = $table->get($this->testingId);
        $this->assertEquals($data['EducationCertifications']['name'], $entity->name);
    }

    public function testDelete() {
        $testUrl = $this->url('remove');

        $table = TableRegistry::get('EducationCertification.EducationCertifications');

        $exists = $table->exists([$table->primaryKey() => $this->testingId]);
        $this->assertTrue($exists);

        $data = [
            'id' => $this->testingId,
            '_method' => 'DELETE'
        ];

        $this->post($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $this->testingId]);
        $this->assertFalse($exists);
    }
}
