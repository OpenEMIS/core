<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class AssessmentPeriodsControllerTest extends AppTestCase
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
        'app.assessment_item_results',
        'app.assessment_periods',
        'app.assessment_grading_types',
        'app.assessment_items_grading_types',
        'app.academic_periods',
        'app.academic_period_levels',
        'app.education_subjects'
    ];

    private $id = 1;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Assessments/AssessmentPeriods/');
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
        $querystring = 'period=26&template=2';
        $testUrl = $this->url("index?$querystring");
        $data = [
            'Search' => [
                'searchField' => 'Three'
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
                'searchField' => 'Three'
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

        $table = TableRegistry::get('Assessment.AssessmentPeriods');
        $belongsToManyTable = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');

        $data = [
            'AssessmentPeriods' => [
                'code' => 'AssessmentPeriod04',
                'name' => 'Assessment Four',
                'start_date' => '2016-01-01',
                'end_date' => '2016-12-31',
                'date_enabled' => '2015-12-31',
                'date_disabled' => '2017-01-01',
                'weight' => 0.4,
                'assessment_id' => 2,
                'education_subjects' => [
                    0 => [
                        '_joinData' => [
                            'assessment_id' => 2,
                            'education_subject_id' => 2,
                            'assessment_grading_type_id' => 6
                        ],
                        'id' => 2
                    ],
                    1 => [
                        '_joinData' => [
                            'assessment_id' => 2,
                            'education_subject_id' => 1,
                            'assessment_grading_type_id' => 7
                        ],
                        'id' => 1
                    ]
                ]
            ],
            'submit' => 'save'
        ];

        $this->postData($testUrl, $data);

        $lastInsertedRecord = $table->find()
            ->where([$table->aliasField('code') => $data['AssessmentPeriods']['code']]);
            //->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));

        //test belongsToManyTable data
        $lastInsertedRecord = $belongsToManyTable->find('list')
            ->innerJoin(
                [$table->alias() => $table->table()],
                [
                    $belongsToManyTable->aliasField('assessment_period_id = ') . $table->aliasField('id')
                ]
            )
            ->where([
                $belongsToManyTable->aliasField('assessment_grading_type_id') => $data['AssessmentPeriods']['education_subjects'][0]['_joinData']['assessment_grading_type_id'],
                $belongsToManyTable->aliasField('education_subject_id') => $data['AssessmentPeriods']['education_subjects'][0]['_joinData']['education_subject_id'],
                $belongsToManyTable->aliasField('assessment_id') => $data['AssessmentPeriods']['education_subjects'][0]['_joinData']['assessment_id']
            ]);

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
        $table = TableRegistry::get('Assessment.AssessmentPeriods');
        $belongsToManyTable = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            'AssessmentPeriods' => [
                'id' => $this->id,
                'code' => 'AssessmentPeriod01edit',
                'name' => 'Assessment Period One Edit',
                'academic_period_id' => 25,
                'start_date' => '2016-01-01',
                'end_date' => '2016-12-31',
                'date_enabled' => '2015-12-31',
                'date_disabled' => '2017-01-01',
                'weight' => 0.11,
                'assessment_id' => 1,
                'education_subjects' => [
                    0 => [
                        '_joinData' => [
                            'id' => 123,
                            'assessment_id' => 1,
                            'education_subject_id' => 3,
                            'assessment_grading_type_id' => 6,
                            'assessment_period_id' => $this->id
                        ],
                        'id' => 3
                    ]
                ]
            ],
            'submit' => 'save'
        ];

        $this->postData($testUrl, $data);

        $entity = $table->get($this->id);

        $this->assertEquals($data['AssessmentPeriods']['code'], $entity->code);

        // test belongsToManyTable data
        $entity = $belongsToManyTable->find()
            ->where([
                $belongsToManyTable->aliasField('assessment_period_id') => $data['AssessmentPeriods']['education_subjects'][0]['_joinData']['assessment_period_id'],
                $belongsToManyTable->aliasField('education_subject_id') => $data['AssessmentPeriods']['education_subjects'][0]['_joinData']['education_subject_id'],
                $belongsToManyTable->aliasField('assessment_id') => $data['AssessmentPeriods']['education_subjects'][0]['_joinData']['assessment_id'],
                // $belongsToManyTable->aliasField('id') => $data['AssessmentPeriods']['education_subjects'][0]['_joinData']['id'], //exclude ID because it is UUID, will keep changing.
            ])
            ->toArray();

        $this->assertEquals($data['AssessmentPeriods']['education_subjects'][0]['_joinData']['assessment_grading_type_id'], $entity[0]->assessment_grading_type_id);

    }

    public function testDelete()
    {
        $testUrl = $this->url('remove');

        $table = TableRegistry::get('Assessment.AssessmentPeriods');
        $belongsToManyTable = TableRegistry::get('Assessment.AssessmentItemsGradingTypes');

        $exists = $table->exists([$table->primaryKey() => $this->id]);
        $this->assertTrue($exists);

        $data = [
            'id' => $this->id,
            '_method' => 'DELETE',
        ];

        $this->postData($testUrl, $data);

        $exists = $table->exists([$table->primaryKey() => $this->id]);
        $this->assertFalse($exists);

        //test belongsToManyTable data
        $exists = $belongsToManyTable->exists([$belongsToManyTable->aliasField('assessment_period_id = ') . $this->id]);
        $this->assertFalse($exists);
    }
}
