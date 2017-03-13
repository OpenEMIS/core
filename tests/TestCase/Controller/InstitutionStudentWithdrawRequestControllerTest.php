<?php
namespace App\Test\TestCases;

use Cake\ORM\TableRegistry;
use App\Test\AppTestCase;

class InstitutionStudentWithdrawRequestControllerTest extends AppTestCase
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
        'app.institution_student_withdraw',
        'app.institution_students',
        'app.institutions',
        'app.academic_periods',
        'app.education_grades',
        'app.student_withdraw_reasons',
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
                'WithdrawRequests' => [
                    'id' => $this->studentId
                ]
            ],
            'Student' => [
                'Students' => [
                    'id' => $this->securityUserId
                ]
            ]
        ]);

        $this->urlPrefix('/Institutions/WithdrawRequests/');
    }

    public function testCreate()
    {
        $testUrl = $this->url('add');
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            'WithdrawRequests' => [
                'student_id' => $this->securityUserId,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 77,
                'effective_date' => '2016-06-01', // correct date (after enrollment date '2016-01-01')
                'student_withdraw_reason_id' => 661,
                'comment' => NULL,
                'status' => 0
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $table = TableRegistry::get('Institutions.institution_student_withdraw');
        $lastInsertedRecord = $table->find()
            ->where([$table->aliasField('student_id') => $data['WithdrawRequests']['student_id'],
                $table->aliasField('institution_id') => $data['WithdrawRequests']['institution_id'],
                $table->aliasField('academic_period_id') => $data['WithdrawRequests']['academic_period_id'],
                $table->aliasField('education_grade_id') => $data['WithdrawRequests']['education_grade_id']])
            ->first();

        $this->assertEquals(true, (!empty($lastInsertedRecord)));
    }

    public function testCreateWrongDate()
    {
        $testUrl = $this->url('add');

        $data = [
            'WithdrawRequests' => [
                'student_id' => $this->securityUserId,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 77,
                'effective_date' => '2015-01-01', // wrong date (before enrollment date '2016-01-01')
                'student_withdraw_reason_id' => 661,
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
        $testUrl = $this->url('edit/'.$this->paramsEncode(['id' => $this->editId]));
        $this->get($testUrl);
        $this->assertResponseCode(200);

        $data = [
            'WithdrawRequests' => [
                'id' => $this->editId,
                'student_id' => 7,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 76,
                'effective_date' => '2016-10-01', // correct date (after enrollment date '2016-06-01')
                'student_withdraw_reason_id' => 649,
                'comment' => 'Test comment',
            ],
            'submit' => 'save'
        ];
        $this->postData($testUrl, $data);

        $table = TableRegistry::get('Institutions.institution_student_withdraw');
        $editedRecord = $table->find()
            ->where([$table->aliasField('id') => $data['WithdrawRequests']['id'],
                $table->aliasField('effective_date') => $data['WithdrawRequests']['effective_date'],
                $table->aliasField('student_withdraw_reason_id') => $data['WithdrawRequests']['student_withdraw_reason_id'],
                $table->aliasField('comment') => $data['WithdrawRequests']['comment']])
            ->first();

        $this->assertEquals(true, (!empty($editedRecord)));
    }

    public function testUpdateWrongDate() {
        $testUrl = $this->url('edit/'.$this->paramsEncode(['id' => $this->editId]));

        $data = [
            'WithdrawRequests' => [
                'id' => $this->editId,
                'student_id' => 7,
                'institution_id' => 1,
                'academic_period_id' => 3,
                'education_grade_id' => 76,
                'effective_date' => '2016-01-01', // wrong date (before enrollment date '2016-06-01')
                'student_withdraw_reason_id' => 649,
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
