<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionSubjectsControllerTest extends AppTestCase
{
	public $fixtures = [
        'app.academic_periods',
        'app.academic_period_levels',
        'app.assessment_item_results',
        'app.config_items',
        'app.custom_field_types',
        'app.custom_field_values',
        'app.custom_modules',
        'app.education_cycles',
        'app.education_subjects',
        'app.education_grades',
        'app.education_grades_subjects',
        'app.education_programmes',
        'app.genders',
        'app.institutions',
        'app.institution_class_grades',
        'app.institution_class_subjects',
        'app.institution_class_students',
        'app.institution_classes',
        'app.institution_custom_fields',
        'app.institution_custom_field_values',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.institution_grades',
        'app.institution_positions',
        'app.institution_staff',
        'app.institution_subjects',
        'app.institution_subject_staff',
        'app.institution_subject_students',
        'app.labels',
        'app.security_users',
        'app.staff_position_titles',
        'app.staff_position_grades',
        'app.staff_statuses',
        'app.staff_types',
        'app.student_statuses',
        'app.survey_forms',
        'app.survey_rules',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps'
    ];

    private $id = 7;

    public function setup()
    {
        parent::setUp();
        $this->urlPrefix('/Institutions/Subjects/');
    }

    public function testIndex()
    {
        $this->setInstitutionSession(1);
        $testUrl = $this->url("index");

        $this->get($testUrl);
        $this->assertResponseCode(200);

        $this->assertEquals(true, (count($this->viewVariable('data')) >= 1));
    }

    public function testSearchFound()
    {
        $this->setInstitutionSession(1);
        $querystring = 'academic_period_id=25&class_id=1';
        $testUrl = $this->url("index?$querystring");
        
        $testUrl = $this->url("index");
        $data = [
            'Search' => [
                'searchField' => 'Comm'
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
                'searchField' => 'Art'
            ]
        ];
        $this->postData($testUrl, $data);

        $this->assertEquals(true, (count($this->viewVariable('data')) == 0));
    }

    public function testCreate()
    {
        $this->setInstitutionSession(1);
        
        $testUrl = $this->url('add');

        // $this->assertResponseCode(200);

        $table = TableRegistry::get('Institutions.InstitutionSubjects');
        $hasManyTable = TableRegistry::get('Institutions.InstitutionClassSubjects');
        
        $data = [
            'InstitutionSubjects' => [
                'academic_period_id' => 25,
                'class_name' => 1,
                'id' => '',
                'institution_id' => 1
            ],
            'MultiSubjects' => [
                0 => [
                    'education_subject_id' => 91,
                    'name' => 'Mathematics',
                    'subject_staff' => [
                        0 => [
                            'status' => 1,
                            'staff_id' => 0
                        ]
                    ]
                ]
            ],
            'submit' => 'save'
        ];
        // pr($data);die;
        $this->postData($testUrl, $data);
        
        $lastInsertedRecord = $table->find()
            ->where([
                $table->aliasField('institution_id') => $data['InstitutionSubjects']['institution_id'],
                $table->aliasField('academic_period_id') => $data['InstitutionSubjects']['academic_period_id'],
                $table->aliasField('education_subject_id') => $data['MultiSubjects'][0]['education_subject_id']
            ])
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));

        //test hasMany data
        $lastInsertedRecord = $hasManyTable->find()
            ->innerJoin(
                [$table->alias() => $table->table()],
                [
                    $hasManyTable->aliasField('institution_subject_id = ') . $table->aliasField('id'),
                    $hasManyTable->aliasField('institution_class_id = ') . $data['InstitutionSubjects']['class_name']
                ]
            )
            ->first();
        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testRead()
    {
        $this->setInstitutionSession(1);
        $testUrl = $this->url('view/'.$this->id);

        $this->get($testUrl);

        $this->assertResponseCode(200);
        $this->assertEquals(true, ($this->viewVariable('data')->id == $this->id));
    }

    public function testUpdate()
    {
        $this->setInstitutionSession(1);
        $testUrl = $this->url('edit/'.$this->id);

        $table = TableRegistry::get('Institutions.InstitutionSubjects');
        $hasManyTable = TableRegistry::get('Institutions.InstitutionSubjectStudents');

        $this->get($testUrl);
        $this->assertResponseCode(200);

        //remove all beside two student record
        $data = [
            'InstitutionSubjects' => [
                'name' => 'Language Arts Edit',
                'academic_period_id' => 25,
                'education_subject_id' => 94,
                'teachers' => [
                    '_ids' => ''
                ],
                'subject_students' => [
                    6124 => [
                        'id' => '05a4caf3-b172-4a51-89e2-8876e2f643d3',
                        'student_id' => 6124,
                        'status' => 1,
                        'institution_subject_id' => 7,
                        'institution_class_id' => 2,
                        'institution_id' => 1,
                        'academic_period_id' => 25,
                        'education_subject_id' => 94,
                        'user' => [
                            'id' => 6124,
                            'openemis_no' => 'STU1463587860',
                            'name' => 'Marilyn Bell',
                            'gender' => [
                                'name' => 'Female'
                            ]
                        ]
                    ],
                    7942 => [
                        'id' => 'b250ee16-da3b-4a73-81ad-5ed0142906fa',
                        'student_id' => 7942,
                        'status' => 1,
                        'institution_subject_id' => 7,
                        'institution_class_id' => 2,
                        'institution_id' => 1,
                        'academic_period_id' => 25,
                        'education_subject_id' => 94,
                        'user' => [
                            'id' => 7942,
                            'openemis_no' => 'STU1463593314',
                            'name' => 'Sharon Cooper',
                            'gender' => [
                                'name' => 'Female'
                            ]
                        ]
                    ]
                ],
                'id' => 7,
                'institution_id' => 1
            ],
            'student_id' => -1,
            'submit' => 'save'
        ];

        // pr($data);die;
        $this->postData($testUrl, $data);
        
        $entity = $table->get($this->id);
        $this->assertEquals($data['InstitutionSubjects']['name'], $entity->name);

        //test hasMany data
        $entity = $hasManyTable->find()
            ->innerJoin(
                [$table->alias() => $table->table()],
                [
                    $hasManyTable->aliasField('institution_subject_id = ') . $table->aliasField('id'),
                    $table->aliasField('id = ') . $this->id
                ]
            )
            ->where([
                $hasManyTable->aliasField('id') => $data['InstitutionSubjects']['subject_students'][7942]['id']
            ])
            ->first();
        // pr($entity);
        //check whether the status has been updated.
        $this->assertEquals($data['InstitutionSubjects']['subject_students'][7942]['status'], $entity->status);

    }

    public function testDelete()
    {
        $this->setInstitutionSession(1);
        $testUrl = $this->url('remove');

        $table = TableRegistry::get('Institutions.InstitutionSubjects');
        $hasManyTable = TableRegistry::get('Institutions.InstitutionSubjectStudents');

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
        $exists = $hasManyTable->exists([$hasManyTable->aliasField('institution_subject_id = ') . $this->id]);
        $this->assertFalse($exists);
    }
}