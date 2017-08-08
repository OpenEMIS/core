<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;

use App\Test\PageTestCase;

class LabelsControllerTest extends PageTestCase
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
        'app.workflow_statuses_steps'
    ];

    private $primaryKey = ['id' => '017c68d8-e914-11e6-a68b-525400b263eb'];

    public function testIndex()
    {
        $this->get('/Labels');
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testRead()
    {
        $this->get('/Labels/view/' . $this->encode($this->primaryKey));
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));
    }

    public function testUpdate()
    {
        $url = '/Labels/edit/' . $this->encode($this->primaryKey);
        $this->get($url);
        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) == 1));

        $data = [
            'Labels' => [
                'id' => $this->primaryKey['id'],
                'code' => 'LABEL001',
                'name' => 'New Label Test'
            ]
        ];

        // $this->enableCsrfToken();
        // $this->enableSecurityToken();
        $this->postData($url, $data);

        $table = TableRegistry::get('Labels');
        $entity = $table->get($this->primaryKey['id']);
// pr($entity);
        $this->assertEquals($data['Labels']['code'], $entity->code);
    }
}
