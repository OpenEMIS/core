<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;

use App\Test\AppTestCase;
use App\Test\SystemFixturesTrait;

class BusTypesControllerTest extends AppTestCase
{
    use SystemFixturesTrait;

    private $primaryKey = ['id' => 1];
    private $modelPlugin = 'Transport';
    private $modelAlias = 'BusTypes';

    public function __construct()
    {
        $this->fixtures[] = 'app.bus_types';
        $this->fixtures[] = 'app.institution_buses';
        parent::__construct();
    }

    public function testIndex()
    {
        $this->get('/FieldOptions/BusTypes');
        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testRead()
    {
        $this->get('/FieldOptions/BusTypes/view/' . $this->paramsEncode($this->primaryKey));
        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    }

    public function testUpdate()
    {
        $url = '/FieldOptions/BusTypes/edit/' . $this->paramsEncode($this->primaryKey);
        $this->get($url);
        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));

        $data = [
            $this->modelAlias => [
                'id' => $this->primaryKey['id'],
                'name' => 'NEW LABEL',
                'visible' => 1,
                'default' => 1
            ]
        ];

        $this->postData($url, $data);

        $table = TableRegistry::get($this->modelPlugin.'.'.$this->modelAlias);
        $entity = $table->get($this->primaryKey);
        $this->assertEquals($data[$this->modelAlias]['name'], $entity->name);
    }

    public function testCreate()
    {
        $url = '/FieldOptions/BusTypes/add';
        $this->get($url);
        $this->assertResponseOk();

        $data = [
            $this->modelAlias => [
                'id' => '2',
                'name' => 'School Field Trip',
                'order' => '2',
                'visible' => '1',
                'editable' => '1',
                'default' => '1',
                'created_user_id' => '1',
                'created' => '2017-10-19 05:29:26'
            ]
        ];

        $this->postData($url, $data);

        $table = TableRegistry::get($this->modelPlugin.'.'.$this->modelAlias);
        $entity = $table->get($data[$this->modelAlias]['id']);
        $this->assertEquals($data[$this->modelAlias]['name'], $entity->name);
    }

    public function testDelete()
    {
        $url = '/FieldOptions/BusTypes/remove/' . $this->paramsEncode($this->primaryKey);
        $this->get($url);
        $this->assertResponseOk();

        $this->deleteData($url);

        $table = TableRegistry::get($this->modelPlugin.'.'.$this->modelAlias);
        $entity = $table->find()->where($this->primaryKey)->first();

        $this->assertEquals($entity, null);
    }
}
