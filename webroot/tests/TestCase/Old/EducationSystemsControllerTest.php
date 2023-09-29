<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class EducationSystemsControllerTest extends AppTestCase
{
	public $fixtures = [
        'app.config_items',
        'app.config_product_lists',
        'app.labels',
        'app.security_users',
        'app.user_identities',
        'app.identity_types',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.education_systems'
    ];

    private $id = 1;

    public function setUp()
    {
        parent::setUp();
        $this->urlPrefix('/Educations/Systems/');
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
                'searchField' => 'National'
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
        $testUrl = $this->url('add');

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $table = TableRegistry::get('Education.EducationSystems');
        $data = [
            'EducationSystems' => [
                'name' => 'New National Education System',
                'visible' => 1
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $table->find()
            ->where([$table->aliasField('name') => $data['EducationSystems']['name']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testRead()
    {
        $testUrl = $this->url('view/'.$this->id);

        $table = TableRegistry::get('Education.EducationSystems');
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->id));
    }

    public function testUpdate() {
        $testUrl = $this->url('edit/'.$this->id);

        // TODO: DO A GET FIRST
        $table = TableRegistry::get('Education.EducationSystems');
        $this->get($testUrl);

        $this->assertResponseCode(200);

        $data = [
            'EducationSystems' => [
                'id' => $this->id,
                'name' => 'National Education System',
                'visible' => 0
            ],
            'submit' => 'save'
        ];

        $this->postData($testUrl, $data);

        $entity = $table->get($this->id);
        $this->assertEquals($data['EducationSystems']['visible'], $entity->visible);
    }

    // Need to implement for delete transfer
    // public function testDelete() {
    //     $testUrl = $this->url('remove');

    //     $table = TableRegistry::get('AcademicPeriod.AcademicPeriods');

    //     $exists = $table->exists([$table->primaryKey() => $this->id]);
    //     $this->assertTrue($exists);

    //     $data = [
    //         'id' => $this->id,
    //         '_method' => 'DELETE'
    //     ];

    //     $this->post($testUrl, $data);

    //     $exists = $table->exists([$table->primaryKey() => $this->id]);
    //     $this->assertFalse($exists);
    // }
}
