<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class IndexesControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.indexes',
        'app.indexes_criterias',
        'app.institution_student_indexes',
        'app.student_indexes_criterias',
        'app.academic_periods',
        'app.academic_period_levels',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.config_items',
        'app.config_product_lists',
        'app.security_users',
        'app.labels',
        'app.behaviour_classifications',
        'app.student_behaviours',
        'app.student_behaviour_categories',
    ];

    public function testIndexIndexes()
    {
        $this->get('/Indexes/Indexes/index');
        $this->assertResponseCode(200);
    }

    public function testAddIndexes()
    {
        $data = [
            'id' => '99',
            'name' => 'Dropout Risk 2015-A',
            'generated_by' => '2',
            'generated_on' => '2017-01-03 15:01:27',
            'academic_period_id' => '10',
            'modified_user_id' => '2',
            'modified' => '2016-12-30 15:08:11',
            'created_user_id' => '2',
            'created' => '2016-12-30 14:36:23'
        ];

        $this->postData('/Indexes/Indexes/add', $data);

        $table = TableRegistry::get('Indexes.Indexes');
        $this->assertNotEmpty($table->get(99));
    }

    public function testViewIndexes()
    {
        $table = TableRegistry::get('Indexes.Indexes');
        $urlParams = $table->paramsEncode(['id' => 19]);

        $this->get('/Indexes/Indexes/view/' . $urlParams);
        $this->assertResponseCode(200);
    }

    public function testEditIndexes()
    {
        $data = [
            'id' => 19,
            'academic_period_id' => 10,
            'name' => 'Dropout Risk 2015 - update name'
        ];

        $table = TableRegistry::get('Indexes.Indexes');
        $urlParams = $table->paramsEncode(['id' => 19]);

        $this->postData('/Indexes/Indexes/edit/' . $urlParams, $data);

        $entity = $table->get(19);
        $this->assertEquals($data['name'], $entity->name);
    }
}
