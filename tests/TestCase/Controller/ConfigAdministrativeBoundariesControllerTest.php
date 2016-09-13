<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class ConfigAdministrativeBoundariesControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.config_items',
        'app.config_product_lists',
        'app.security_users',
        'app.labels',
        'app.areas'
    ];

    private $id = 100;
    private $table;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Configurations/AdministrativeBoundaries/');
        $this->table = TableRegistry::get('Configuration.ConfigItems');
    }

    public function testIndex()
    {
        $testUrl = $this->url('index', ['type' => 2, 'type_value' => 'Administrative Boundaries']);
        $this->get($testUrl);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound()
    {
        $testUrl = $this->url('index', ['type' => 2, 'type_value' => 'Administrative Boundaries']);
        $data = [
            'Search' => [
                'searchField' => 'api'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    }

    public function testSearchNotFound()
    {
        $testUrl = $this->url('index', ['type' => 2, 'type_value' => 'Administrative Boundaries']);
        $data = [
            'Search' => [
                'searchField' => 'areass'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) == 0));
    }

    public function testView()
    {
        $testUrl = $this->url('view/' . $this->id, ['type' => 2, 'type_value' => 'Administrative Boundaries']);

        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->id));
    }

    public function testUpdate()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('edit/' . $this->id, ['type' => 2, 'type_value' => 'Administrative Boundaries']);

        // TODO: DO A GET FIRST
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            'value' => 'https://demo.openemis.org/datamanager/api/area',
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $entity = $this->table->get($this->id);
        $this->assertEquals($data['value'], $entity->value);
    }
}
