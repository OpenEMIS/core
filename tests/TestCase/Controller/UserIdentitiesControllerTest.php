<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class UserIdentitiesControllerTest extends AppTestCase
{
	public $fixtures = [
        'app.identity_types',
        'app.user_identities',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.config_items',
        'app.config_product_lists',
        'app.config_item_options',
        'app.custom_fields',
        'app.custom_field_options',
        'app.custom_field_values',
        'app.custom_field_types',
        'app.custom_table_cells',
        'app.custom_table_rows',
        'app.custom_table_columns',
        'app.custom_modules',
        'app.custom_forms',
        'app.custom_forms_fields',
        // TestDelete needed
        'app.security_users',
        'app.student_custom_field_values',
        'app.student_custom_fields',
        'app.student_custom_forms_fields',
        'app.survey_rules',
        'app.survey_forms',
        'app.institution_students',
        'app.institution_custom_field_values',
        'app.institution_custom_fields',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.institutions',
        'app.labels'
    ];

    private $studentId = 1005;
    private $id = 3;
    private $table;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Students/Identities/');

        $this->setStudentSession($this->studentId);
        $this->table = TableRegistry::get('User.Identities');
    }

    public function testIndexIdentities()
    {
        $testUrl = $this->url('index');
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFoundIdentities()
    {
        $testUrl = $this->url('index');
        $data = [
            'Search' => [
                'searchField' => '31'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchNotFoundIdentities()
    {
        $testUrl = $this->url('index');
        $data = [
            'Search' => [
                'searchField' => '22'
            ]
        ];
        $this->postData($testUrl, $data);
        $this->assertEquals(true, (count($this->viewVariable('data')) == 0));
    }

    public function testViewIdentities()
    {
        $testUrl = $this->url('view/' . $this->id);
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->id));
    }

    public function testUpdateIdentities()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('edit/' . $this->id);

        // TODO: DO A GET FIRST
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias => [
                'id' => '3',
                'identity_type_id' => '452',
                'number' => '312',
                'issue_date' => '2016-09-21',
                'expiry_date' => '2016-09-22',
                'issue_location' => '',
                'comments' => 'aa',
                'security_user_id' => '1005'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $entity = $this->table->get($this->id);
        $this->assertEquals($data[$alias]['comments'], $entity->comments);
    }

    public function testCreateIdentities()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('add');

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias => [
                'identity_type_id' => '452',
                'number' => '312312',
                'issue_date' => '2016-09-21',
                'expiry_date' => '2016-09-22',
                'issue_location' => 'LTA',
                'comments' => 'TestCreate',
                'security_user_id' => '1005'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $this->table->find()
            ->where([$this->table->aliasField('number') => $data[$alias]['number']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testDeleteIdentities() {
        $testUrl = $this->url('remove'); // Delete records with confirmation modal (delete modal)

        $table = TableRegistry::get('User.Identities');

        $exists = $table->exists([$table->primaryKey() => $this->id]);
        $this->assertTrue($exists);

        $data = [
            'id' => $this->id,
            '_method' => 'DELETE'
        ];
        $this->postData($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $this->id]);
        $this->assertFalse($exists);
    }


}
