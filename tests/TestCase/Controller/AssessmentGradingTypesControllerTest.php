<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class AssessmentGradingTypesControllerTest extends AppTestCase
{
	public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.assessments',
        'app.assessment_items',
        'app.assessment_periods',
        'app.assessment_grading_types',
        'app.assessment_grading_options',
        'app.assessment_items_grading_types',
        'app.assessment_item_results',
        'app.education_subjects'
    ];

    private $id = 7;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Assessments/GradingTypes/');
    }

    public function testIndex()
    {
        $testUrl = $this->url("index");

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound()
    {
        $testUrl = $this->url("index");
        $data = [
            'Search' => [
                'searchField' => 'Type'
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
                'searchField' => 'Four'
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

        $table = TableRegistry::get('Assessment.AssessmentGradingTypes');
        $hasManyTable = TableRegistry::get('Assessment.AssessmentGradingOptions');
        $data = [
            'AssessmentGradingTypes' => [
                'code' => 'GradingType04',
                'name' => 'Grading Type Four',
                'pass_mark' => 65.00,
                'max' => 80.00,
                'result_type' => 'MARKS',
                'grading_options' => [
                    0 => [
                        'code' => 'GradingOption0401',
                        'name' => 'Grading Options Four One',
                        'min' => 40,
                        'max' => 41
                    ],
                    1 => [
                        'code' => 'GradingOption0402',
                        'name' => 'Grading Options Four Two',
                        'min' => 40,
                        'max' => 42
                    ]
                ]
            ],
            'submit' => 'save'
        ];

        $this->postData($testUrl, $data);

        $lastInsertedRecord = $table->find()
            ->where([$table->aliasField('code') => $data['AssessmentGradingTypes']['code']])
            ->first();

        $this->assertEquals(true, (!empty($lastInsertedRecord)));

        //test hasMany data
        $lastInsertedRecord = $hasManyTable->find()
            ->innerJoin(
                [$table->alias() => $table->table()],
                [
                    $hasManyTable->aliasField('assessment_grading_type_id = ') . $table->aliasField('id')
                ]
            )
            ->where([
                $hasManyTable->aliasField('code') => $data['AssessmentGradingTypes']['grading_options'][1]['code']
            ])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testRead()
    {
        $testUrl = $this->url('view/'.$this->id);

        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->id));
    }

    public function testUpdate()
    {
        $testUrl = $this->url('edit/'.$this->id);

        // TODO: DO A GET FIRST
        $table = TableRegistry::get('Assessment.AssessmentGradingTypes');
        $hasManyTable = TableRegistry::get('Assessment.AssessmentGradingOptions');

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            'AssessmentGradingTypes' => [
                'id' => $this->id,
                'code' => 'GradingType01e',
                'name' => 'Grading Type One Edit',
                'pass_mark' => 65.00,
                'max' => 80.00,
                'result_type' => 'MARKS',
                'grading_options' => [
                    0 => [
                        'id' => 10,
                        'assessment_grading_type_id' => $this->id,
                        'code' => 'GradingOption0401e',
                        'name' => 'Grading Options Four One Edit',
                        'min' => 40.4,
                        'max' => 41.4

                    ],
                    1 => [
                        'id' => 11,
                        'assessment_grading_type_id' => $this->id,
                        'code' => 'GradingOption0402e',
                        'name' => 'Grading Options Four Two Edit',
                        'min' => 40.4,
                        'max' => 42.4
                    ]
                ]
            ],
            'submit' => 'save'
        ];

        $this->postData($testUrl, $data);

        $entity = $table->get($this->id);
        $this->assertEquals($data['AssessmentGradingTypes']['code'], $entity->code);

        //test hasMany data
        $entity = $hasManyTable->find()
            ->innerJoin(
                [$table->alias() => $table->table()],
                [
                    $hasManyTable->aliasField('assessment_grading_type_id = ') . $table->aliasField('id'),
                    $table->aliasField('id = ') . $this->id
                ]
            )
            ->where([
                $hasManyTable->aliasField('id') => $data['AssessmentGradingTypes']['grading_options'][0]['id']
            ])
            ->first();
        // pr($entity);
        $this->assertEquals($data['AssessmentGradingTypes']['grading_options'][0]['code'], $entity->code);

    }

    public function testDelete()
    {
        $testUrl = $this->url('remove');

        $table = TableRegistry::get('Assessment.AssessmentGradingTypes');
        $hasManyTable = TableRegistry::get('Assessment.AssessmentGradingOptions');

        $exists = $table->exists([$table->primaryKey() => $this->id]);
        $this->assertTrue($exists);

        $data = [
            'id' => $this->id,
            '_method' => 'DELETE',
        ];

        $this->postData($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $this->id]);
        $this->assertFalse($exists);

        //test hasMany data
        $exists = $hasManyTable->exists([$hasManyTable->aliasField('assessment_grading_type_id = ') . $this->id]);
        $this->assertFalse($exists);
    }
}
