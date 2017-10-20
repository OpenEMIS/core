<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;

use App\Test\AppTestCase;
use App\Test\SystemFixturesTrait;
use Page\Traits\EncodingTrait;

class LabelsControllerTest extends AppTestCase
{
    use SystemFixturesTrait;
    use EncodingTrait;

    private $primaryKey = ['id' => '017c68d8-e914-11e6-a68b-525400b263eb'];
    private $modelPlugin = '';
    private $modelAlias = 'Labels';

    public function testIndex()
    {
        $this->get('/Labels');
        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearch()
    {
        $search = $this->encode(['search' => 'option']);
        $this->get('/Labels?querystring=' . $search);

        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testRead()
    {
        $this->get('/Labels/view/' . $this->encode($this->primaryKey));
        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    }

    public function testUpdate()
    {
        $url = '/Labels/edit/' . $this->encode($this->primaryKey);
        $this->get($url);
        $this->assertResponseOk();
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));

        $data = [
            $this->modelAlias => [
                'id' => $this->primaryKey['id'],
                'code' => 'NEW LABEL CODE',
                'name' => 'NEW LABEL NAME'
            ]
        ];

        $this->postData($url, $data);

        $table = TableRegistry::get($this->modelAlias);
        $entity = $table->get($this->primaryKey);
        $this->assertEquals($data[$this->modelAlias]['name'], $entity->name);
    }

    // no add function for Labels
    // public function testCreate()
    // {
    // }

    // no delete function for Labels
    // public function testDelete()
    // {
    // }
}
