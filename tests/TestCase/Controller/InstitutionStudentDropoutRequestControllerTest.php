<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionStudentDropoutRequestControllerTest extends AppTestCase
{
    public $fixtures = [
        'app.config_items',
        'app.labels',
        'app.security_users',
        'app.workflow_models',
        'app.workflow_steps',
        'app.workflow_statuses',
        'app.workflow_statuses_steps',
        'app.custom_modules',
        'app.custom_field_types',
        'app.institution_custom_field_values',
        'app.institution_custom_fields',
        'app.survey_forms',
        'app.institution_custom_forms_fields',
        'app.institution_custom_forms_filters',
        'app.survey_rules',
        'app.education_programmes',
        'app.config_item_options',
        'app.config_product_lists',
        'app.student_custom_field_values',
        'app.student_custom_fields',
        'app.student_custom_forms_fields',
        'app.student_statuses',
        'app.institution_student_admission',
        'app.institution_student_dropout',
        'app.institution_students',
        'app.institutions',
        'app.academic_periods',
        'app.education_grades',
        'app.student_dropout_reasons',
    ];

    private $studentId = 2;
    private $securityUserId = 6;
    private $editId = 1;

    public function setup()
    {
        parent::setUp();

        $this->setAuthSession();
        $this->session([
            'Institution' => [
                'Institutions' => [
                    'id' => 1
                ],
                'DropoutRequests' => [
                    'id' => $this->studentId
                ]
            ],
            'Student' => [
                'Students' => [
                    'id' => $this->securityUserId
                ]
            ]
        ]);

        $this->urlPrefix('/Institutions/DropoutRequests/');
    }

    public function testCreate()
    {
        $testUrl = $this->url('add');
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            'DropoutRequests' => [
                'student_id' => $this->securityUserId,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 77,
                'effective_date' => '2016-06-01', // correct date (after enrollment date '2016-01-01')
                'student_dropout_reason_id' => 661,
                'comment' => NULL,
                'status' => 0
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $table = TableRegistry::get('Institutions.institution_student_dropout');
        $lastInsertedRecord = $table->find()
            ->where([$table->aliasField('student_id') => $data['DropoutRequests']['student_id'],
                $table->aliasField('institution_id') => $data['DropoutRequests']['institution_id'],
                $table->aliasField('academic_period_id') => $data['DropoutRequests']['academic_period_id'],
                $table->aliasField('education_grade_id') => $data['DropoutRequests']['education_grade_id']])
            ->first();

        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testCreateWrongDate()
    {
        $testUrl = $this->url('add');

        $data = [
            'DropoutRequests' => [
                'student_id' => $this->securityUserId,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 77,
                'effective_date' => '2015-01-01', // wrong date (before enrollment date '2016-01-01')
                'student_dropout_reason_id' => 661,
                'comment' => NULL,
                'status' => 0
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $postData = $this->viewVariable('data');
        $errors = $postData->errors();
        $this->assertEquals(true, (array_key_exists('effective_date', $errors)));
    }

    public function testUpdate() {
        $testUrl = $this->url('edit/' . $this->editId);
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            'DropoutRequests' => [
                'id' => $this->editId,
                'student_id' => 7,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 76,
                'effective_date' => '2016-10-01', // correct date (after enrollment date '2016-06-01')
                'student_dropout_reason_id' => 649,
                'comment' => 'Test comment',
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $table = TableRegistry::get('Institutions.institution_student_dropout');
        $editedRecord = $table->find()
            ->where([$table->aliasField('id') => $data['DropoutRequests']['id'],
                $table->aliasField('effective_date') => $data['DropoutRequests']['effective_date'],
                $table->aliasField('student_dropout_reason_id') => $data['DropoutRequests']['student_dropout_reason_id'],
                $table->aliasField('comment') => $data['DropoutRequests']['comment']])
            ->first();

        $this->assertEquals(true, (!empty($editedRecord)));
    }

    public function testUpdateWrongDate() {
        $testUrl = $this->url('edit/' . $this->editId);

        $data = [
            'DropoutRequests' => [
                'id' => $this->editId,
                'student_id' => 7,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 76,
                'effective_date' => '2016-01-01', // wrong date (before enrollment date '2016-06-01')
                'student_dropout_reason_id' => 649,
                'comment' => 'Test comment',
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $postData = $this->viewVariable('data');
        $errors = $postData->errors();
        $this->assertEquals(true, (array_key_exists('effective_date', $errors)));
    }
}
