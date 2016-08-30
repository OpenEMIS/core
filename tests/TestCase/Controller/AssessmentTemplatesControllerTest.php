<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class AssessmentTemplatesControllerTest extends AppTestCase
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
        'app.assessment_items_grading_types',
        'app.academic_periods',
        'app.academic_period_levels',
        'app.education_cycles',
        'app.education_subjects',
        'app.education_grades',
        'app.education_programmes'
    ];

    private $id = 2;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Assessments/Assessments/');
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
        $querystring = 'period=26';
        $testUrl = $this->url("index?$querystring");
        $data = [
            'Search' => [
                'searchField' => 'Two'
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
                'searchField' => 'Two'
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

        $table = TableRegistry::get('Assessment.Assessments');
        $hasManyTable = TableRegistry::get('Assessment.AssessmentItems');
        $data = [
            'Assessments' => [
                'code' => 'Assessment03',
                'name' => 'Assessment Three',
                'description' => 'Assessment Three Desc',
                'type' => 2,
                'academic_period_id' => 10,
                'education_grade_id' => 61,
                'assessment_items' => [
                    0 => [
                        'weight' => 0.4,
                        'education_subject_id' => 1
                    ],
                    1 => [
                        'weight' => 0.5,
                        'education_subject_id' => 2
                    ]
                ]
            ],
            'submit' => 'save'
        ];
        // pr($data);
        $this->postData($testUrl, $data);

        $lastInsertedRecord = $table->find()
            ->where([$table->aliasField('code') => $data['Assessments']['code']])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));

        //test hasMany data
        $lastInsertedRecord = $hasManyTable->find()
            ->innerJoin(
                [$table->alias() => $table->table()],
                [
                    $hasManyTable->aliasField('assessment_id = ') . $table->aliasField('id')
                ]
            )
            ->where([
                $hasManyTable->aliasField('weight') => $data['Assessments']['assessment_items'][1]['weight']
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
        $table = TableRegistry::get('Assessment.Assessments');
        $hasManyTable = TableRegistry::get('Assessment.AssessmentItems');

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            'Assessments' => [
                'id' => $this->id,
                'code' => 'Assessment02e',
                'name' => 'Assessment Two Edit',
                'description' => 'Assessment Two Edit Desc',
                'type' => 2,
                'academic_period_id' => 10,
                'education_grade_id' => 62,
                'assessment_items' => [
                    0 => [
                        'id' => 1,
                        'weight' => 0.11,
                        'education_subject_id' => 1
                    ],
                    1 => [
                        'id' => 2,
                        'weight' => 0.22,
                        'education_subject_id' => 2
                    ],
                    2 => [
                        'id' => 3,
                        'weight' => 0.33,
                        'education_subject_id' => 2
                    ]
                ]
            ],
            'submit' => 'save'
        ];

        $this->postData($testUrl, $data);

        $entity = $table->get($this->id);
        $this->assertEquals($data['Assessments']['code'], $entity->code);

        //test hasMany data
        $entity = $hasManyTable->find()
            ->innerJoin(
                [$table->alias() => $table->table()],
                [
                    $hasManyTable->aliasField('assessment_id = ') . $table->aliasField('id'),
                    $table->aliasField('id = ') . $this->id
                ]
            )
            ->where([
                $hasManyTable->aliasField('id') => $data['Assessments']['assessment_items'][2]['id']
            ])
            ->first();
        // pr($entity);
        $this->assertEquals($data['Assessments']['assessment_items'][2]['weight'], $entity->weight);

    }

    public function testDelete()
    {
        $testUrl = $this->url('remove');

        $table = TableRegistry::get('Assessment.Assessments');
        $hasManyTable = TableRegistry::get('Assessment.AssessmentItems');

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
        $exists = $hasManyTable->exists([$hasManyTable->aliasField('assessment_id = ') . $this->id]);
        $this->assertFalse($exists);
    }
}
