<?php
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use Cake\ORM\TableRegistry;

class CompetenciesControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.config_product_lists',
        'app.labels',
        'app.security_users',
        'app.translations',
        'app.workflows',
        'app.workflows_filters',
        'app.workflow_actions',
        'app.workflow_comments',
        'app.workflow_transitions',
        'app.workflow_steps_roles',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.competencies'
    ];

    private $table;
    private $id = 4;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/FieldOptions/Competencies/');

        $this->table = TableRegistry::get('Staff.competencies');
    }

    public function testIndexCompetencies()
    {
        $testUrl = $this->url('index');
     $this->get($testUrl);
     $this->assertResponseCode(200);
    }

    public function testViewCompetencies()
    {
        $testUrl = $this->url('view/' . $this->id);
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->id));
    }

    // public function testUpdateCompetencies()
    // {
    //    $alias = $this->table->alias();
    //     $testUrl = $this->url('edit/' . $this->id);

    //     $this->get($testUrl);
    //     $this->assertResponseCode(200);

    //     $data = [
    //         $alias => [
    //             'id' => $this->id,
    //             'name' => 'Training Strictness',
    //             'visible' => '1'
    //         ],
    //         'submit' => 'save'
    //     ];
    //     $this->postData($testUrl, $data);

    //     $entity = $this->table->get($this->id);
    //     $this->assertEquals($data[$alias]['name'], $entity->name);
    // }

    // public function testCreateCompetencies()
    // {
    //     $alias = $this->table->alias();
    //     $testUrl = $this->url('add');

    //     $this->get($testUrl);
    //     $this->assertResponseCode(200);

    //     $data = [
    //         $alias => [
    //             'name' => 'Test Provider',
    //         ],
    //         'submit' => 'save'
    //     ];
    //     $this->postData($testUrl, $data);

    //     $lastInsertedRecord = $this->table->find()
    //         ->where([$this->table->aliasField('name') => $data[$alias]['name']])
    //         ->first();
    //     $this->assertEquals(true, (!empty($lastInsertedRecord)));
    // }

}
