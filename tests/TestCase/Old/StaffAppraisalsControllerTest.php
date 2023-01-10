<?php
namespace App\Test\TestCases;

use App\Test\AppTestCase;
use Cake\ORM\TableRegistry;

class StaffAppraisalsControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.academic_periods',
        'app.config_items',
        'app.config_item_options',
        'app.config_product_lists',
        'app.competency_sets_competencies',
        'app.competency_sets',
        'app.competencies',
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
        'app.institutions',
        'app.institution_custom_fields',
        'app.institution_custom_field_values',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.institution_infrastructures',
        'app.institution_staff',
        'app.staff_statuses',
        'app.staff_custom_fields',
        'app.staff_custom_field_values',
        'app.staff_custom_forms_fields',
        'app.custom_modules',
        'app.custom_field_types',
        'app.survey_forms',
        'app.survey_rules',
        'app.staff_appraisals',
        'app.staff_appraisal_types',
        'app.staff_appraisals_competencies',
        'app.academic_period_levels',
        'app.user_identities'
    ];

    private $table;
    private $id = 11;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Institutions/StaffAppraisals/');
        $this->table = TableRegistry::get('Institution.StaffAppraisals');
        $this->setInstitutionSession(2);
    }

    public function testIndexAppraisals()
    {
        $testUrl = $this->url('index?user_id=5');
        $this->get($testUrl);
        $this->assertResponseCode(200);
    }

    public function testViewAppraisals()
    {
        $testUrl = $this->url('view/' . $this->id . '?user_id=5');
        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->id));
    }

    public function testUpdateIdentities()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('edit/' . $this->id . '?user_id=5');

        // TODO: DO A GET FIRST
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias => [
                'academic_period_id' => '10',
                'comment' => 'Testing',
                'competency_set_id' => '2',
                'created' => '2016-10-25 15:15:39',
                'created_user_id' => '5',
                'final_rating' => '13.50',
                'from' => '2015-01-01',
                'id' => '11',
                'modified' => '2016-10-27 15:49:43',
                'modified_user_id' => '5',
                'staff_appraisal_type_id' => '2',
                'staff_id' => '5',
                'title' => 'Appraisal self test 1',
                'to' => '2015-12-31'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $entity = $this->table->get($this->id);
        $this->assertEquals($data[$alias]['comment'], $entity->comment);
    }

    public function testCreateAppraisals()
    {
        $alias = $this->table->alias();
        $testUrl = $this->url('add?user_id=5');
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            $alias=> [
                'academic_period_id' => '10',
                'comment' => '',
                'competency_set_id' => '2',
                'created' => '2016-10-25 15:15:39',
                'created_user_id' => '5',
                'final_rating' => '15.50',
                'from' => '2015-01-01',
                'id' => '80',
                'modified' => '2016-10-27 15:49:43',
                'modified_user_id' => '5',
                'staff_appraisal_type_id' => '2',
                'staff_id' => '5',
                'title' => 'Appraisal unit test 1',
                'to' => '2015-12-31'
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $this->table->find()
            ->where([$this->table->aliasField('id') => $data[$alias]['id']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testDeleteAppraisals()
    {
        $testUrl = $this->url('remove'); // Delete records with confirmation modal (delete modal)

        $exists = $this->table->exists([$this->table->primaryKey() => $this->id]);
        $this->assertTrue($exists);

        $data = [
            'id' => $this->id,
            '_method' => 'DELETE'
        ];
        $this->postData($testUrl, $data);

        $exists = $this->table->exists([$this->table->primaryKey() => $this->id]);
        $this->assertFalse($exists);
    }
}

